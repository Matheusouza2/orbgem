<?php

namespace App\Jobs;

use App\Infrastructure\Pluggy\PluggyClient;
use App\Infrastructure\Pluggy\PluggyTransactionMapper;
use App\Repositories\ExternalAccountRepositoryInterface;
use App\Services\ExternalTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyTransactionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $externalAccountId, public ?string $from = null, public ?string $to = null) {}

    public function handle(ExternalAccountRepositoryInterface $accounts, PluggyClient $client, PluggyTransactionMapper $mapper, ExternalTransactionService $transactions): void
    {
        $externalAccount = $accounts->find($this->externalAccountId);
        if ($externalAccount === null || $externalAccount->accountable_id === null) {
            return;
        }

        $type = $externalAccount->accountable_type === 'App\\Models\\CreditCard' ? 'credit_card' : 'account';
        foreach ($client->getTransactions($externalAccount->external_id, $this->from, $this->to) as $remoteTransaction) {
            $dto = $mapper->map($remoteTransaction, $externalAccount->financialConnection->wallet_id, (int) $externalAccount->accountable_id, $type);
            $transactions->sync($externalAccount, $dto);
        }

        $externalAccount->update(['last_synced_at' => now()]);
    }
}
