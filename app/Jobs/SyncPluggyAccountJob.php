<?php

namespace App\Jobs;

use App\Services\ExternalAccountService;
use App\Services\FinancialConnectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyAccountJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string, mixed> $remoteAccount */
    public function __construct(public int $connectionId, public array $remoteAccount, public ?string $from = null, public ?string $to = null) {}

    public function handle(FinancialConnectionService $connections, ExternalAccountService $accounts): void
    {
        $connection = $connections->find($this->connectionId);
        if ($connection === null) {
            return;
        }

        $externalAccount = $accounts->sync($connection, $this->remoteAccount);
        SyncPluggyTransactionsJob::dispatch($externalAccount->id, $this->from, $this->to);
    }
}
