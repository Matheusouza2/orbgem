<?php

namespace App\Repositories;

use App\DTO\WalletDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Support\Collection;

interface WalletRepositoryInterface
{
    public function find(int $walletId): ?Wallet;

    public function create(WalletDTO $walletDTO): Wallet;

    public function update(Wallet $wallet, WalletDTO $walletDTO): Wallet;

    public function addMember(Wallet $wallet, User $user, WalletMemberRole $role): WalletMember;

    public function forMember(User $user): Collection;

    public function findMembership(User $user, Wallet $wallet): ?WalletMember;

    public function membersForWallet(Wallet $wallet): Collection;

    public function findMemberInWallet(int $walletId, int $memberId): ?WalletMember;

    public function findMemberByUser(Wallet $wallet, int $userId): ?WalletMember;

    public function memberBelongsToWallet(Wallet $wallet, int $memberId): bool;

    public function ownerCount(Wallet $wallet): int;

    public function ownerMember(int $walletId): ?WalletMember;

    public function lockWallet(int $walletId): Wallet;

    public function lockMember(int $memberId): WalletMember;

    public function lockMemberInWallet(int $walletId, int $memberId): WalletMember;

    public function deleteMember(WalletMember $member): void;

    public function updateMemberRole(WalletMember $member, WalletMemberRole $role): WalletMember;
}
