<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletActivityOccurred
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $walletId, public string $type, public string $title, public ?string $body, public array $data = []) {}
}
