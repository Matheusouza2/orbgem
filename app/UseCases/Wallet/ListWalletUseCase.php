<?php

namespace App\UseCases\Wallet;

use App\Models\User;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Support\Collection;

class ListWalletUseCase
{
    public function __construct(
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(User $user): Collection
    {
        return $this->membershipAuthorization->walletsFor($user);
    }
}
