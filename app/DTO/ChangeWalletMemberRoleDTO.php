<?php

namespace App\DTO;

use App\Enums\WalletMemberRole;

final readonly class ChangeWalletMemberRoleDTO
{
    public function __construct(public int $walletId, public int $memberId, public WalletMemberRole $role) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id'], $attributes['member_id'], WalletMemberRole::from($attributes['role']));
    }

    /** @return array{wallet_id: int, member_id: int, role: WalletMemberRole} */
    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'member_id' => $this->memberId, 'role' => $this->role];
    }
}
