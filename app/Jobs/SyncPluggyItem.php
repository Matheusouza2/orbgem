<?php

namespace App\Jobs;

use App\Services\FinancialConnectionService;
use App\Services\PluggyItemService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPluggyItem implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId) {}

    public function handle(PluggyItemService $items, FinancialConnectionService $connections): void
    {
        $item = $items->find($this->itemId);

        if ($item !== null) {
            $connection = $connections->findByProviderExternalId('pluggy', $item->pluggy_item_id);
            if ($connection !== null) {
                SyncPluggyConnectionJob::dispatch($connection->id);
            }
        }
    }
}
