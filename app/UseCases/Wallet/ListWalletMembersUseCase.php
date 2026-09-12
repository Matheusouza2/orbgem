<?php

namespace App\UseCases\Wallet;

use App\DTO\ListWalletMembersDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListWalletMembersUseCase
{
    public function __construct(
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    /** @return Collection<int, WalletMember> */
    public function execute(ListWalletMembersDTO $dto, User $user): Collection
    {
        $wallet = $this->walletService->find($dto->walletId);
        if ($wallet === null) {
            throw (new ModelNotFoundException)->setModel(Wallet::class, [$dto->walletId]);
        }

        $this->membershipAuthorization->authorize($user, $wallet, ...WalletMemberRole::cases());

        return $this->walletService->listMembers($wallet);
    }
}
