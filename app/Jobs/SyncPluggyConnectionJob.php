<?php

namespace App\Jobs;

use App\Infrastructure\Pluggy\PluggyClient;
use App\Services\FinancialConnectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyConnectionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $connectionId) {}

    public function handle(FinancialConnectionService $connections, PluggyClient $client): void
    {
        $connection = $connections->find($this->connectionId);
        if ($connection === null) {
            return;
        }

        $item = $client->getItem($connection->external_id);
        $connections->upsertPluggy($connection->wallet_id, $connection->external_id, $item);

        foreach ($client->getAccounts($connection->external_id) as $remoteAccount) {
            SyncPluggyAccountJob::dispatch($connection->id, $remoteAccount);
        }

        $connections->markSynced($connection);
    }
}
