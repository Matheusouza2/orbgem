<?php

namespace App\UseCases\OpenFinance;

use App\DTO\SyncFinancialConnectionDTO;
use App\Enums\WalletMemberRole;
use App\Jobs\SyncPluggyHistoricalConnectionJob;
use App\Models\User;
use App\Services\FinancialConnectionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SyncFinancialConnectionUseCase
{
    public function __construct(private FinancialConnectionService $connections, private WalletService $wallets, private WalletMembershipAuthorization $authorization) {}

    public function execute(SyncFinancialConnectionDTO $dto, User $user): void
    {
        $connection = $this->connections->find($dto->connectionId);
        if ($connection === null || ($wallet = $this->wallets->find($connection->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }

        $this->authorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        SyncPluggyHistoricalConnectionJob::dispatch($connection->id, $dto->from ?? now()->subYear()->toDateString(), $dto->to ?? now()->toDateString());
    }
}
