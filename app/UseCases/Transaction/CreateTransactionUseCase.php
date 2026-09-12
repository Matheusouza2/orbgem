<?php

namespace App\UseCases\Transaction;

use App\DTO\TransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Services\MerchantService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTransactionUseCase
{
    public function __construct(private TransactionService $transactionService, private WalletService $walletService, private AccountService $accountService, private CategoryService $categoryService, private MerchantService $merchantService, private WalletMembershipAuthorization $membershipAuthorization) {}

    public function execute(TransactionDTO $dto, User $user): Transaction
    {
        return DB::transaction(function () use ($dto, $user): Transaction {
            $wallet = $this->walletService->find($dto->walletId);
            if ($wallet === null) {
                throw new ModelNotFoundException;
            }
            $member = $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
            if ($dto->financialInstrumentType !== FinancialInstrumentType::ACCOUNT || $dto->type === TransactionType::TRANSFER || $dto->accountId === null) {
                throw ValidationException::withMessages(['financial_instrument_type' => 'Slice 1 accepts only account transactions.']);
            }
            if (($dto->type === TransactionType::INCOME && $dto->effect !== TransactionEffect::CREDIT) || ($dto->type === TransactionType::EXPENSE && $dto->effect !== TransactionEffect::DEBIT)) {
                throw ValidationException::withMessages(['effect' => 'The effect is incompatible with the transaction type.']);
            }
            $account = $this->accountService->find($dto->accountId);
            if ($account === null || $account->wallet_id !== $dto->walletId) {
                throw ValidationException::withMessages(['account_id' => 'The account must belong to the transaction wallet.']);
            }
            if ($dto->categoryId !== null) {
                $category = $this->categoryService->find($dto->categoryId);
                if ($category === null) {
                    throw ValidationException::withMessages(['category_id' => 'The category must exist.']);
                }
                $this->categoryService->validateForTransaction($category, $dto->walletId, $dto->type);
            }
            if ($dto->merchantId !== null) {
                $merchant = $this->merchantService->find($dto->merchantId);
                if ($merchant === null || ! $this->merchantService->belongsToWallet($merchant, $dto->walletId)) {
                    throw ValidationException::withMessages(['merchant_id' => 'The merchant must belong to the transaction wallet.']);
                }
            }

            return $this->transactionService->create($dto->withMemberId($member->id));
        });
    }
}
