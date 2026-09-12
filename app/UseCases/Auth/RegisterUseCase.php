<?php

namespace App\UseCases\Auth;

use App\DTO\AccountDTO;
use App\DTO\RegisterDTO;
use App\DTO\WalletDTO;
use App\Enums\AccountType;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

class RegisterUseCase
{
    public function __construct(
        private AuthService $authService,
        private WalletService $walletService,
        private AccountService $accountService,
        private CategoryService $categoryService,
    ) {}

    public function execute(RegisterDTO $dto): array
    {
        return DB::transaction(function () use ($dto): array {
            $result = $this->authService->register($dto);
            $wallet = $this->walletService->createWallet(new WalletDTO('Carteira principal'), $result['user']);
            $ownerMember = $wallet->members->firstOrFail();

            $this->accountService->create(AccountDTO::fromArray([
                'wallet_id' => $wallet->id,
                'owner_wallet_member_id' => $ownerMember->id,
                'name' => 'Conta principal',
                'institution' => null,
                'bank_code' => null,
                'account_number' => null,
                'type' => AccountType::CHECKING->value,
                'initial_balance' => 0,
                'is_default' => true,
                'show_in_dashboard' => true,
                'ignore_in_totals' => false,
                'active' => true,
            ]));
            $this->categoryService->ensureDefaultCategories($wallet->id);

            return $result;
        });
    }
}
