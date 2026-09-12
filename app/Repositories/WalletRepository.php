<?php

namespace App\Repositories;

use App\DTO\WalletDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Support\Collection;

class WalletRepository implements WalletRepositoryInterface
{
    public function find(int $walletId): ?Wallet
    {
        return Wallet::query()->find($walletId);
    }

    public function create(WalletDTO $walletDTO): Wallet
    {
        return Wallet::query()->create($walletDTO->toArray());
    }

    public function update(Wallet $wallet, WalletDTO $walletDTO): Wallet
    {
        $wallet->update($walletDTO->toArray());

        return $wallet->refresh();
    }

    public function addMember(Wallet $wallet, User $user, WalletMemberRole $role): WalletMember
    {
        return $wallet->members()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function forMember(User $user): Collection
    {
        return $user->walletMemberships()
            ->with('wallet')
            ->latest()
            ->get()
            ->map(fn (WalletMember $membership): Wallet => $membership->wallet);
    }

    public function findMembership(User $user, Wallet $wallet): ?WalletMember
    {
        return $user->walletMemberships()
            ->where('wallet_id', $wallet->id)
            ->first();
    }

    public function membersForWallet(Wallet $wallet): Collection
    {
        return $wallet->members()->with('user')->orderBy('id')->get();
    }

    public function findMemberInWallet(int $walletId, int $memberId): ?WalletMember
    {
        return WalletMember::query()
            ->where('wallet_id', $walletId)
            ->whereKey($memberId)
            ->with('user')
            ->first();
    }

    public function findMemberByUser(Wallet $wallet, int $userId): ?WalletMember
    {
        return $wallet->members()->where('user_id', $userId)->first();
    }

    public function memberBelongsToWallet(Wallet $wallet, int $memberId): bool
    {
        return $wallet->members()->whereKey($memberId)->exists();
    }

    public function ownerCount(Wallet $wallet): int
    {
        return $wallet->ownerMemberships()->count();
    }

    public function lockWallet(int $walletId): Wallet
    {
        return Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();
    }

    public function lockMember(int $memberId): WalletMember
    {
        return WalletMember::query()->whereKey($memberId)->lockForUpdate()->firstOrFail();
    }

    public function lockMemberInWallet(int $walletId, int $memberId): WalletMember
    {
        return WalletMember::query()
            ->where('wallet_id', $walletId)
            ->whereKey($memberId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function deleteMember(WalletMember $member): void
    {
        $member->delete();
    }

    public function updateMemberRole(WalletMember $member, WalletMemberRole $role): WalletMember
    {
        $member->update(['role' => $role]);

        return $member->refresh();
    }
}
