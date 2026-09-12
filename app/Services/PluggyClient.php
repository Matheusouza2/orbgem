<?php

namespace App\Services;

use App\Infrastructure\Pluggy\PluggyClient as InfrastructurePluggyClient;

class PluggyClient extends InfrastructurePluggyClient
{
    /** @return array<string, mixed> */
    public function item(string $itemId): array
    {
        return $this->getItem($itemId);
    }

    /** @return array<int, array<string, mixed>> */
    public function accounts(string $itemId): array
    {
        return $this->getAccounts($itemId);
    }
}
