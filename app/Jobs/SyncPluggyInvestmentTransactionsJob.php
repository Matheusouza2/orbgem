<?php

namespace App\Jobs;

use App\Infrastructure\Pluggy\PluggyClient;
use App\Infrastructure\Pluggy\PluggyInvestmentTransactionMapper;
use App\Models\ExternalInvestment;
use App\Services\ExternalInvestmentTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyInvestmentTransactionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $externalInvestmentId) {}

    public function handle(PluggyClient $client, PluggyInvestmentTransactionMapper $mapper, ExternalInvestmentTransactionService $transactions): void
    {
        $externalInvestment = ExternalInvestment::query()->find($this->externalInvestmentId);
        if ($externalInvestment === null) {
            return;
        }

        foreach ($client->getInvestmentTransactions($externalInvestment->external_id) as $remoteTransaction) {
            $transaction = $mapper->map($remoteTransaction);
            if ($transaction !== null) {
                $transactions->sync($externalInvestment, $transaction);
            }
        }
    }
}
