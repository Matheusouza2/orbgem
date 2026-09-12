<?php

namespace App\DTO;

use App\Enums\WalletMemberRole;

final readonly class AddWalletMemberDTO
{
    public function __construct(public int $walletId, public int $userId, public WalletMemberRole $role) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id'], $attributes['user_id'], WalletMemberRole::from($attributes['role']));
    }

    /** @return array{wallet_id: int, user_id: int, role: WalletMemberRole} */
    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'user_id' => $this->userId, 'role' => $this->role];
    }
}
