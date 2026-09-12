<?php

namespace App\UseCases\OpenFinance;

use App\DTO\RegisterPluggyConnectionDTO;
use App\Infrastructure\Pluggy\PluggyClient;
use App\Jobs\SyncPluggyConnectionJob;
use App\Models\FinancialConnection;
use App\Services\FinancialConnectionService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RegisterPluggyConnectionUseCase
{
    public function __construct(
        private WalletService $wallets,
        private PluggyClient $client,
        private FinancialConnectionService $connections,
    ) {}

    public function execute(RegisterPluggyConnectionDTO $dto): FinancialConnection
    {
        $wallet = $this->wallets->find($dto->walletId);
        if ($wallet === null) {
            throw (new ModelNotFoundException)->setModel('wallets', [$dto->walletId]);
        }

        $item = $this->client->getItem($dto->itemId);
        $connection = $this->connections->upsertPluggy($wallet->id, $dto->itemId, $item);
        SyncPluggyConnectionJob::dispatch($connection->id);

        return $connection;
    }
}
