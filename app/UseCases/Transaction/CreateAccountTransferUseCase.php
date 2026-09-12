<?php

namespace App\UseCases\Transaction;

use App\DTO\AccountTransferDTO;
use App\DTO\TransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAccountTransferUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private WalletService $walletService,
        private AccountService $accountService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    /** @return array{transfer_group_id: string, transactions: array<int, Transaction>} */
    public function execute(AccountTransferDTO $dto, User $user): array
    {
        return DB::transaction(function () use ($dto, $user): array {
            $wallet = $this->walletService->find($dto->walletId);
            if ($wallet === null) {
                throw new ModelNotFoundException;
            }
            $member = $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
            $accounts = $this->accountService->lockForTransfer([$dto->fromAccountId, $dto->toAccountId]);
            $this->validateAccounts($accounts, $dto);
            $transferGroupId = (string) Str::uuid();

            $common = [
                'wallet_id' => $dto->walletId,
                'category_id' => null,
                'merchant_id' => null,
                'amount' => $dto->amount,
                'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
                'transaction_date' => $dto->transactionDate,
                'competence_date' => $dto->competenceDate,
                'status' => TransactionStatus::POSTED->value,
                'payment_channel' => $dto->paymentChannel?->value,
                'notes' => $dto->notes,
                'transfer_group_id' => $transferGroupId,
                'created_by_member_id' => $member->id,
                'updated_by_member_id' => $member->id,
            ];

            $source = $this->transactionService->create(TransactionDTO::fromArray([
                ...$common,
                'account_id' => $dto->fromAccountId,
                'description' => 'Transferência para '.$accounts->get($dto->toAccountId)->name,
                'type' => TransactionType::TRANSFER->value,
                'effect' => TransactionEffect::DEBIT->value,
            ], $member->id));
            $destination = $this->transactionService->create(TransactionDTO::fromArray([
                ...$common,
                'account_id' => $dto->toAccountId,
                'description' => 'Transferência de '.$accounts->get($dto->fromAccountId)->name,
                'type' => TransactionType::TRANSFER->value,
                'effect' => TransactionEffect::CREDIT->value,
            ], $member->id));

            return ['transfer_group_id' => $transferGroupId, 'transactions' => [$source, $destination]];
        });
    }

    /** @param Collection<int, Account> $accounts */
    private function validateAccounts(Collection $accounts, AccountTransferDTO $dto): void
    {
        if ($accounts->count() !== 2) {
            throw ValidationException::withMessages(['from_account_id' => 'Both accounts must exist.']);
        }
        if ($accounts->get($dto->fromAccountId)->wallet_id !== $dto->walletId) {
            throw ValidationException::withMessages(['from_account_id' => 'The source account must belong to the wallet.']);
        }
        if ($accounts->get($dto->toAccountId)->wallet_id !== $dto->walletId) {
            throw ValidationException::withMessages(['to_account_id' => 'The destination account must belong to the wallet.']);
        }
    }
}
