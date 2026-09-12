<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyHistoricalConnectionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $connectionId, public string $from, public string $to) {}

    public function handle(): void
    {
        SyncPluggyConnectionJob::dispatch($this->connectionId, $this->from, $this->to);
    }
}
