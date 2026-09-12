<?php

namespace App\DTO;

use App\Enums\AccountType;

final readonly class AccountDTO
{
    public function __construct(
        public int $walletId,
        public ?int $ownerWalletMemberId,
        public string $name,
        public ?string $institution,
        public ?string $bankCode,
        public ?string $accountNumber,
        public AccountType $type,
        public int $initialBalance,
        public bool $isDefault,
        public bool $showInDashboard,
        public bool $ignoreInTotals,
        public bool $active,
    ) {}

    /** @param array{wallet_id: int, owner_wallet_member_id?: int|null, name: string, institution?: string|null, bank_code?: string|null, account_number?: string|null, type: string, initial_balance: int, is_default: bool, show_in_dashboard: bool, ignore_in_totals: bool, active: bool} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes['wallet_id'],
            $attributes['owner_wallet_member_id'] ?? null,
            $attributes['name'],
            $attributes['institution'] ?? null,
            $attributes['bank_code'] ?? null,
            $attributes['account_number'] ?? null,
            AccountType::from($attributes['type']),
            $attributes['initial_balance'],
            $attributes['is_default'] ?? false,
            $attributes['show_in_dashboard'] ?? true,
            $attributes['ignore_in_totals'] ?? false,
            $attributes['active'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'owner_wallet_member_id' => $this->ownerWalletMemberId,
            'name' => $this->name,
            'institution' => $this->institution,
            'bank_code' => $this->bankCode,
            'account_number' => $this->accountNumber,
            'type' => $this->type,
            'initial_balance' => $this->initialBalance,
            'is_default' => $this->isDefault,
            'show_in_dashboard' => $this->showInDashboard,
            'ignore_in_totals' => $this->ignoreInTotals,
            'active' => $this->active,
        ];
    }
}
