<?php

namespace App\Jobs;

use App\Services\PluggyItemService;
use App\Services\PluggySyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyItem implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId) {}

    public function handle(PluggyItemService $items, PluggySyncService $sync): void
    {
        $item = $items->find($this->itemId);

        if ($item !== null) {
            $sync->sync($item);
        }
    }
}
