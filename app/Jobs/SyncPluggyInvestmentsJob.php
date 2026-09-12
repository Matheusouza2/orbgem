<?php

namespace App\Jobs;

use App\Infrastructure\Pluggy\PluggyClient;
use App\Repositories\FinancialConnectionRepositoryInterface;
use App\Services\ExternalInvestmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyInvestmentsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $connectionId) {}

    public function handle(FinancialConnectionRepositoryInterface $connections, PluggyClient $client, ExternalInvestmentService $investments): void
    {
        $connection = $connections->find($this->connectionId);
        if ($connection === null) {
            return;
        }

        foreach ($client->getInvestments($connection->external_id) as $remoteInvestment) {
            $investments->sync($connection, $remoteInvestment);
        }
    }
}
