<?php

namespace App\Jobs;

use App\Services\Ai\FinancialSummaryService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshFinancialSummary implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 900;

    public function __construct(public int $walletId, public bool $force = false) {}

    public function uniqueId(): string
    {
        return (string) $this->walletId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(FinancialSummaryService $summaries): void
    {
        $summaries->refresh($this->walletId, $this->force);
    }
}
