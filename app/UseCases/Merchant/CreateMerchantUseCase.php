<?php

namespace App\UseCases\Merchant;

use App\DTO\MerchantDTO;
use App\Enums\WalletMemberRole;
use App\Models\Merchant;
use App\Models\User;
use App\Services\MerchantService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CreateMerchantUseCase
{
    public function __construct(
        private MerchantService $merchantService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(MerchantDTO $merchantDTO, User $user): Merchant
    {
        return DB::transaction(function () use ($merchantDTO, $user): Merchant {
            $wallet = $this->walletService->find($merchantDTO->walletId);
            if ($wallet === null) {
                throw new ModelNotFoundException;
            }

            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

            return $this->merchantService->create($merchantDTO);
        });
    }
}
