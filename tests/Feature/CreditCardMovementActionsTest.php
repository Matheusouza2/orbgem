<?php

namespace Tests\Feature;

use App\Enums\CreditCardInvoiceStatus;
use App\Enums\WalletMemberRole;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardPurchase;
use App\Models\Installment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreditCardMovementActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_edit_only_one_installment(): void
    {
        [$user, $wallet, $purchase] = $this->purchaseWithInstallments(3);
        $transaction = Transaction::query()->where('installment_id', $purchase->installments[1]->id)->firstOrFail();

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/credit-card-transactions/'.$transaction->id, [
            'description' => 'Parcela corrigida',
            'amount' => 777,
            'transaction_date' => '2026-09-11',
            'category_id' => null,
            'merchant_id' => null,
            'is_third_party' => true,
        ])->assertOk()->assertJsonPath('data.amount', 777);

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'amount' => 777, 'is_third_party' => true]);
        $this->assertSame([333, 777, 334], Installment::query()->where('credit_card_purchase_id', $purchase->id)->orderBy('number')->pluck('amount')->all());
    }

    public function test_editor_can_edit_the_whole_purchase_and_preserve_exact_installment_sum(): void
    {
        [$user, $wallet, $purchase] = $this->purchaseWithInstallments(3);

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/credit-card-purchases/'.$purchase->id, [
            'description' => 'Compra atualizada',
            'total_amount' => 1001,
            'purchase_date' => '2026-09-10',
            'category_id' => null,
            'merchant_id' => null,
            'is_third_party' => false,
        ])->assertOk()->assertJsonPath('data.total_amount', 1001);

        $this->assertSame([333, 333, 335], Installment::query()->where('credit_card_purchase_id', $purchase->id)->orderBy('number')->pluck('amount')->all());
        $this->assertSame(['Compra atualizada (1/3)', 'Compra atualizada (2/3)', 'Compra atualizada (3/3)'], Transaction::query()->whereIn('installment_id', $purchase->installments->pluck('id'))->orderBy('id')->pluck('description')->all());
    }

    public function test_editor_can_delete_one_installment_and_last_parcel_cleans_up_purchase(): void
    {
        [$user, $wallet, $purchase] = $this->purchaseWithInstallments(2);
        $first = $purchase->installments->first();
        $second = $purchase->installments->last();

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/credit-card-transactions/'.Transaction::query()->where('installment_id', $first->id)->value('id'))->assertNoContent();
        $this->assertDatabaseMissing('installments', ['id' => $first->id]);
        $this->assertDatabaseHas('credit_card_purchases', ['id' => $purchase->id, 'installment_count' => 1, 'total_amount' => 500]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/credit-card-transactions/'.Transaction::query()->where('installment_id', $second->id)->value('id'))->assertNoContent();
        $this->assertDatabaseMissing('credit_card_purchases', ['id' => $purchase->id]);
    }

    public function test_editor_can_delete_the_whole_purchase(): void
    {
        [$user, $wallet, $purchase] = $this->purchaseWithInstallments(3);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/credit-card-purchases/'.$purchase->id)->assertNoContent();

        $this->assertDatabaseMissing('credit_card_purchases', ['id' => $purchase->id]);
        $this->assertDatabaseCount('installments', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_viewer_cannot_mutate_a_purchase_from_another_wallet(): void
    {
        [$owner, $wallet, $purchase] = $this->purchaseWithInstallments(1);
        $viewer = User::factory()->create();
        $otherWallet = Wallet::create(['name' => 'Outra carteira']);
        WalletMember::create(['wallet_id' => $otherWallet->id, 'user_id' => $viewer->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => Carbon::now()]);

        $this->actingAs($viewer, 'sanctum')->deleteJson('/api/v1/credit-card-purchases/'.$purchase->id)->assertForbidden();
        $this->assertDatabaseHas('credit_card_purchases', ['id' => $purchase->id]);
    }

    public function test_closed_invoice_allows_edit_and_delete(): void
    {
        [$user, $wallet, $purchase] = $this->purchaseWithInstallments(1);
        $invoice = CreditCardInvoice::query()->firstOrFail();
        $invoice->update(['status' => CreditCardInvoiceStatus::CLOSED]);
        $transaction = Transaction::query()->firstOrFail();

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/credit-card-transactions/'.$transaction->id, ['description' => 'Corrigida após fechamento', 'amount' => 999, 'transaction_date' => '2026-09-10'])->assertOk();
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/credit-card-purchases/'.$purchase->id)->assertNoContent();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_paid_invoice_rejects_edit_and_delete(): void
    {
        [$user, $wallet, $purchase] = $this->purchaseWithInstallments(1);
        $invoice = CreditCardInvoice::query()->firstOrFail();
        $invoice->update(['status' => CreditCardInvoiceStatus::PAID]);
        $transaction = Transaction::query()->firstOrFail();

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/credit-card-transactions/'.$transaction->id, ['description' => 'Bloqueada', 'amount' => 999, 'transaction_date' => '2026-09-10'])->assertUnprocessable();
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/credit-card-purchases/'.$purchase->id)->assertUnprocessable();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'amount' => 1000]);
    }

    /** @return array{User, Wallet, CreditCardPurchase} */
    private function purchaseWithInstallments(int $count): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => Carbon::now()]);
        $card = CreditCard::create(['wallet_id' => $wallet->id, 'name' => 'Cartão', 'credit_limit' => 100000, 'closing_day' => 15, 'due_day' => 5, 'active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-purchases', [
            'wallet_id' => $wallet->id,
            'credit_card_id' => $card->id,
            'description' => 'Compra',
            'purchase_date' => '2026-09-10',
            'total_amount' => 1000,
            'installment_count' => $count,
        ])->assertCreated();

        $purchase = CreditCardPurchase::query()->with('installments')->firstOrFail();

        return [$user, $wallet, $purchase];
    }
}
