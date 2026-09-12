<?php

namespace App\Services;

use App\DTO\WalletDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\WalletRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class WalletService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function createWallet(WalletDTO $walletDTO, User $owner): Wallet
    {
        $wallet = $this->walletRepository->create($walletDTO);
        $this->walletRepository->addMember($wallet, $owner, WalletMemberRole::OWNER);

        return $wallet->load('members');
    }

    public function updateWallet(Wallet $wallet, WalletDTO $walletDTO): Wallet
    {
        return $this->walletRepository->update($wallet, $walletDTO);
    }

    public function listForMember(User $user): Collection
    {
        return $this->walletRepository->forMember($user);
    }

    public function find(int $walletId): ?Wallet
    {
        return $this->walletRepository->find($walletId);
    }

    public function findUser(int $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }

    public function listMembers(Wallet $wallet): Collection
    {
        return $this->walletRepository->membersForWallet($wallet);
    }

    public function findMemberInWallet(int $walletId, int $memberId): ?WalletMember
    {
        return $this->walletRepository->findMemberInWallet($walletId, $memberId);
    }

    public function addMemberToWallet(Wallet $wallet, User $user, WalletMemberRole $role): WalletMember
    {
        if ($this->walletRepository->findMemberByUser($wallet, $user->id) !== null) {
            throw ValidationException::withMessages(['user_id' => 'The user is already a member of this wallet.']);
        }

        return $this->walletRepository->addMember($wallet, $user, $role)->load('user');
    }

    public function changeMemberRoleInWallet(int $walletId, int $memberId, WalletMemberRole $role): WalletMember
    {
        return DB::transaction(function () use ($walletId, $memberId, $role): WalletMember {
            $wallet = $this->walletRepository->lockWallet($walletId);
            $member = $this->walletRepository->lockMemberInWallet($wallet->id, $memberId);

            if ($member->role === WalletMemberRole::OWNER && $role !== WalletMemberRole::OWNER) {
                $this->ensureNotRemovingLastOwner($member, $wallet);
            }

            return $this->walletRepository->updateMemberRole($member, $role)->load('user');
        });
    }

    public function removeMemberFromWallet(int $walletId, int $memberId): void
    {
        DB::transaction(function () use ($walletId, $memberId): void {
            $wallet = $this->walletRepository->lockWallet($walletId);
            $member = $this->walletRepository->lockMemberInWallet($wallet->id, $memberId);

            $this->ensureNotRemovingLastOwner($member, $wallet);
            $this->walletRepository->deleteMember($member);
        });
    }

    public function removeMember(WalletMember $member): void
    {
        DB::transaction(function () use ($member): void {
            $wallet = $this->walletRepository->lockWallet($member->wallet_id);
            $lockedMember = $this->walletRepository->lockMember($member->id);

            $this->ensureNotRemovingLastOwner($lockedMember, $wallet);
            $this->walletRepository->deleteMember($lockedMember);
        });
    }

    public function changeMemberRole(WalletMember $member, WalletMemberRole $role): WalletMember
    {
        return DB::transaction(function () use ($member, $role): WalletMember {
            $wallet = $this->walletRepository->lockWallet($member->wallet_id);
            $lockedMember = $this->walletRepository->lockMember($member->id);

            if ($lockedMember->role === WalletMemberRole::OWNER && $role !== WalletMemberRole::OWNER) {
                $this->ensureNotRemovingLastOwner($lockedMember, $wallet);
            }

            return $this->walletRepository->updateMemberRole($lockedMember, $role);
        });
    }

    private function ensureNotRemovingLastOwner(WalletMember $member, Wallet $wallet): void
    {
        if ($member->role === WalletMemberRole::OWNER && $this->walletRepository->ownerCount($wallet) === 1) {
            throw new LogicException('A wallet must retain at least one OWNER.');
        }
    }
}
