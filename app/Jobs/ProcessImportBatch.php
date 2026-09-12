<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Slice5Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessImportBatch implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public int $batchId)
    {
        $this->onConnection('redis');
        $this->onQueue('imports');
    }

    public function uniqueId(): string
    {
        return (string) $this->batchId;
    }

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [new WithoutOverlapping('import-batch-'.$this->batchId)];
    }

    public function handle(Slice5Service $service): void
    {
        $batch = ImportBatch::query()->find($this->batchId);

        if ($batch === null || in_array($batch->status, ['COMPLETED', 'COMPLETED_WITH_ERRORS'], true)) {
            return;
        }

        $service->processImport($batch);
    }

    public function failed(?\Throwable $exception): void
    {
        ImportBatch::query()->whereKey($this->batchId)->update([
            'status' => 'FAILED',
            'errors' => [['row' => 0, 'message' => $exception?->getMessage() ?? 'Import job failed.']],
        ]);
    }
}
