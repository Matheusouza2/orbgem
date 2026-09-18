<?php

namespace App\UseCases\Transaction;

use App\DTO\MonthlySummaryDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\AccountBalanceCalculator;
use App\Services\AccountService;
use App\Services\PlanningReportService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MonthlySummaryUseCase
{
    public function __construct(private TransactionService $transactionService, private WalletService $walletService, private AccountService $accountService, private AccountBalanceCalculator $balanceCalculator, private WalletMembershipAuthorization $membershipAuthorization, private PlanningReportService $planningReportService) {}

    /** @return array<string, int|string> */
    public function execute(MonthlySummaryDTO $summaryDTO, User $user): array
    {
        $wallet = $this->walletService->find($summaryDTO->walletId);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $summary = $this->transactionService->monthlyTotals($summaryDTO);
        $balance = $this->accountService->listForWallet($summaryDTO->walletId)->where('show_in_dashboard', true)->where('ignore_in_totals', false)->sum(fn ($account): int => $this->balanceCalculator->calculate($account->initial_balance, $this->transactionService->postedAmountsForAccountThroughMonth($account->id, $summaryDTO->month, $summaryDTO->includeThirdParty)));

        return ['wallet_id' => $summaryDTO->walletId, 'month' => $summaryDTO->month, 'balance' => $balance, ...$summary, ...$this->planningReportService->summary($summaryDTO->walletId, $summaryDTO->month, $summaryDTO->includeThirdParty)];
    }
}
