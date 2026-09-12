<?php

namespace App\Services;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Repositories\WalletRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class WalletMembershipAuthorization
{
    public function __construct(private WalletRepositoryInterface $walletRepository) {}

    public function resolve(User $user, Wallet $wallet): ?WalletMember
    {
        return $this->walletRepository->findMembership($user, $wallet);
    }

    public function authorize(User $user, Wallet $wallet, WalletMemberRole ...$roles): WalletMember
    {
        $membership = $this->resolve($user, $wallet);

        if ($membership === null || ! in_array($membership->role, $roles, true)) {
            throw new AuthorizationException('The user is not authorized for this wallet action.');
        }

        return $membership;
    }

    public function walletsFor(User $user): Collection
    {
        return $this->walletRepository->forMember($user);
    }

    public function memberBelongsToWallet(Wallet $wallet, int $memberId): bool
    {
        return $this->walletRepository->memberBelongsToWallet($wallet, $memberId);
    }
}
