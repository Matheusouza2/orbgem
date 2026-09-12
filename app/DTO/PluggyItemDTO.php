<?php

namespace App\DTO;

final readonly class PluggyItemDTO
{
    public function __construct(public int $userId, public int $walletId, public string $pluggyItemId) {}
}
