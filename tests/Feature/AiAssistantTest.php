<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\FinancialInstrumentType;
use App\Enums\InvestmentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Jobs\RefreshFinancialSummary;
use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialSummary;
use App\Models\Investment;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\Ai\FinancialSummaryService;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\PersonalDataScrubber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.api_key' => 'gemini-test', 'services.groq.api_key' => 'groq-test']);
    }

    public function test_scrubber_removes_personal_data(): void
    {
        $clean = (new PersonalDataScrubber)->scrub('Pix para maria@example.com CPF 123.456.789-09 tel (11) 98765-4321 conta 123456789012 https://x.com/a');

        foreach (['maria@example.com', '123.456.789-09', '98765-4321', '123456789012', 'https://x.com'] as $leak) {
            $this->assertStringNotContainsString($leak, $clean);
        }
    }

    public function test_summary_is_generated_by_gemini_from_anonymized_facts_and_cached_by_data_hash(): void
    {
        Queue::fake();
        [$user, $wallet] = $this->walletWithMember();
        $this->seedData($wallet, $user);
        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiResponse()]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/ai/summary?wallet_id='.$wallet->id)
            ->assertOk()
            ->assertJsonPath('data.analysis.overview', 'Resumo do período.')
            ->assertJsonPath('data.facts.portfolio.current_value_cents', 120000)
            ->assertJsonPath('data.facts.top_expense_categories.0.name', 'Mercado');

        Http::assertSent(function (Request $request): bool {
            $body = $request->body();

            return str_contains($request->url(), 'gemini-3.8-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'gemini-test')
                && $request['generationConfig']['temperature'] === 0.2
                && str_contains($body, 'expense_brl')
                && str_contains($body, '[email]')
                && ! str_contains($body, 'joao@example.com')
                && ! str_contains($body, 'Lançamento sensível');
        });

        $summary = FinancialSummary::query()->where('wallet_id', $wallet->id)->firstOrFail();
        $this->app->make(FinancialSummaryService::class)->refresh($wallet->id);
        Http::assertSentCount(1);
        $this->assertSame($summary->data_hash, FinancialSummary::query()->where('wallet_id', $wallet->id)->value('data_hash'));
    }

    public function test_gemini_falls_back_to_the_secondary_model_when_the_primary_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*gemini-3.8-flash:*' => Http::response(['error' => ['status' => 'UNAVAILABLE']], 503),
            'generativelanguage.googleapis.com/*gemini-3.1-flash-lite:*' => $this->geminiResponse(),
        ]);

        $analysis = $this->app->make(GeminiClient::class)->generateJson('sistema', 'prompt');

        $this->assertSame('Resumo do período.', $analysis['overview']);
        Http::assertSentCount(2);
    }

    public function test_chat_sends_saved_summary_and_scrubbed_message_to_groq_with_low_temperature(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        FinancialSummary::query()->create(['wallet_id' => $wallet->id, 'facts' => ['portfolio' => ['current_value_cents' => 120000]], 'analysis' => ['overview' => 'Carteira concentrada.'], 'data_hash' => 'x', 'generated_at' => now()]);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Sua carteira está concentrada.']]]])]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/ai/chat', [
            'wallet_id' => $wallet->id, 'message' => 'Meu CPF 123.456.789-09, devo comprar mais?',
            'history' => [['role' => 'user', 'content' => 'oi'], ['role' => 'assistant', 'content' => 'olá']],
        ])->assertOk()->assertJsonPath('data.reply', 'Sua carteira está concentrada.');

        Http::assertSent(function (Request $request): bool {
            $messages = $request['messages'];

            return $request->hasHeader('Authorization', 'Bearer groq-test')
                && $request['model'] === 'openai/gpt-oss-120b'
                && $request['temperature'] === 0.2
                && $request['reasoning_effort'] === 'low'
                && $messages[0]['role'] === 'system' && str_contains($messages[0]['content'], 'current_value_brl') && str_contains($messages[0]['content'], '1200')
                && count($messages) === 4
                && str_contains($messages[3]['content'], '[documento]') && ! str_contains($messages[3]['content'], '123.456.789-09');
        });
        Http::assertSentCount(1);
    }

    public function test_chat_is_forbidden_for_non_members(): void
    {
        [, $wallet] = $this->walletWithMember();
        Http::fake();

        $this->actingAs(User::factory()->create(), 'sanctum')->postJson('/api/v1/ai/chat', ['wallet_id' => $wallet->id, 'message' => 'oi'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_provider_failure_returns_503_without_leaking_details(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        FinancialSummary::query()->create(['wallet_id' => $wallet->id, 'facts' => [], 'analysis' => null, 'data_hash' => 'x', 'generated_at' => now()]);
        Http::fake(['api.groq.com/*' => Http::response('boom', 500)]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/ai/chat', ['wallet_id' => $wallet->id, 'message' => 'oi'])
            ->assertStatus(503)->assertJsonMissing(['HTTP 500']);
    }

    public function test_only_owner_or_editor_can_request_a_refresh(): void
    {
        Queue::fake();
        [$viewer, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $this->actingAs($viewer, 'sanctum')->postJson('/api/v1/ai/summary/refresh', ['wallet_id' => $wallet->id])->assertForbidden();
        Queue::assertNothingPushed();

        [$owner, $ownerWallet] = $this->walletWithMember();
        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/ai/summary/refresh', ['wallet_id' => $ownerWallet->id])->assertStatus(202);
        Queue::assertPushed(RefreshFinancialSummary::class, fn ($job): bool => $job->walletId === $ownerWallet->id && $job->force);
    }

    public function test_new_transactions_schedule_a_single_delayed_refresh(): void
    {
        Queue::fake();
        [$user, $wallet] = $this->walletWithMember();
        $this->seedData($wallet, $user);

        Queue::assertPushed(RefreshFinancialSummary::class);
    }

    public function test_refresh_command_enqueues_wallets_with_data(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        $this->walletWithMember();
        Queue::fake();
        $this->seedData($wallet, $user);
        Queue::fake();

        $this->artisan('ai:refresh-summaries')->assertSuccessful();

        Queue::assertPushed(RefreshFinancialSummary::class, 1);
    }

    public function test_assistant_page_requires_authentication_and_renders_for_members(): void
    {
        $this->get('/assistente')->assertRedirect('/login');
        $this->withoutMiddleware()->get('/assistente')->assertOk();
    }

    private function geminiResponse()
    {
        $analysis = ['overview' => 'Resumo do período.', 'spending_insights' => ['Mercado lidera os gastos.'], 'savings_opportunities' => [], 'portfolio_observations' => ['Renda variável concentrada.'], 'risks' => [], 'alerts' => []];

        return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($analysis)]]]]]]);
    }

    private function seedData(Wallet $wallet, User $user): void
    {
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->value('id');
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => AccountType::CHECKING, 'initial_balance' => 0, 'active' => true]);
        $category = Category::query()->create(['wallet_id' => $wallet->id, 'name' => 'Mercado', 'type' => 'EXPENSE', 'active' => true]);
        $merchant = Merchant::query()->create(['wallet_id' => $wallet->id, 'name' => 'joao@example.com', 'normalized_name' => 'joao@example.com', 'active' => true]);

        foreach ([[TransactionType::INCOME, TransactionEffect::CREDIT, 500000, null, null], [TransactionType::EXPENSE, TransactionEffect::DEBIT, 18990, $category->id, $merchant->id]] as [$type, $effect, $amount, $categoryId, $merchantId]) {
            Transaction::query()->create(['wallet_id' => $wallet->id, 'account_id' => $account->id, 'category_id' => $categoryId, 'merchant_id' => $merchantId, 'description' => 'Lançamento sensível', 'type' => $type->value, 'effect' => $effect->value, 'amount' => $amount, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value, 'transaction_date' => today()->toDateString(), 'competence_date' => today()->toDateString(), 'status' => TransactionStatus::POSTED->value, 'created_by_member_id' => $memberId, 'updated_by_member_id' => $memberId]);
        }

        Investment::query()->create(['wallet_id' => $wallet->id, 'name' => 'PETR4', 'ticker' => 'PETR4', 'type' => InvestmentType::STOCK, 'quantity' => '10', 'average_price' => 10000, 'invested_amount' => 100000, 'current_value' => 120000, 'active' => true]);
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(WalletMemberRole $role = WalletMemberRole::OWNER): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return [$user, $wallet];
    }
}
