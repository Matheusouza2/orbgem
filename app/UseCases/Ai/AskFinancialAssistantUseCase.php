<?php

namespace App\UseCases\Ai;

use App\DTO\AiChatDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\Ai\FinancialChatService;
use App\Services\Ai\FinancialSummaryService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AskFinancialAssistantUseCase
{
    public function __construct(private FinancialChatService $chat, private FinancialSummaryService $summaries, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    /** @return array{reply: string, summary_generated_at: string} */
    public function execute(AiChatDTO $dto, User $user): array
    {
        $wallet = $this->wallets->find($dto->walletId) ?? throw new ModelNotFoundException;
        $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR, WalletMemberRole::VIEWER);
        $summary = $this->summaries->forWallet($wallet->id);

        return ['reply' => $this->chat->reply($summary, $dto->message, $dto->history), 'summary_generated_at' => $summary->generated_at->toIso8601String()];
    }
}
