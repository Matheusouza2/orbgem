<?php

namespace App\DTO;

final readonly class CreditCardDTO
{
    public function __construct(public int $walletId, public ?int $ownerWalletMemberId, public ?int $accountId, public string $name, public ?string $institution, public int $creditLimit, public int $closingDay, public int $dueDay, public bool $active) {}

    public static function fromArray(array $a): self
    {
        return new self($a['wallet_id'], $a['owner_wallet_member_id'] ?? null, $a['account_id'] ?? null, $a['name'], $a['institution'] ?? null, $a['limit'], $a['closing_day'], $a['due_day'], $a['active'] ?? true);
    }

    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'owner_wallet_member_id' => $this->ownerWalletMemberId, 'account_id' => $this->accountId, 'name' => $this->name, 'institution' => $this->institution, 'credit_limit' => $this->creditLimit, 'closing_day' => $this->closingDay, 'due_day' => $this->dueDay, 'active' => $this->active];
    }
}
