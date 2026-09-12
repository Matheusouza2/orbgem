<?php

namespace App\UseCases\OpenFinance;

use App\Models\User;
use App\Services\PluggyItemService;
use Illuminate\Support\Collection;

class ListItemsUseCase
{
    public function __construct(private PluggyItemService $items) {}

    public function execute(User $user): Collection
    {
        return $this->items->forUser($user->id);
    }
}
