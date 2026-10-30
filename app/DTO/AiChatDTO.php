<?php

namespace App\DTO;

final readonly class AiChatDTO
{
    /** @param list<array{role: string, content: string}> $history */
    public function __construct(public int $walletId, public string $message, public array $history = []) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id'], $attributes['message'], $attributes['history'] ?? []);
    }
}
