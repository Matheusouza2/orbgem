<?php

namespace App\DTO;

use App\Enums\TransactionType;

final readonly class CategoryDTO
{
    public function __construct(
        public ?int $walletId,
        public ?int $parentId,
        public string $name,
        public TransactionType $type,
        public ?string $icon,
        public ?string $iconColor,
        public bool $active,
    ) {}

    /** @param array{wallet_id?: int|null, parent_id?: int|null, name: string, type: string, icon?: string|null, icon_color?: string|null, active: bool} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes['wallet_id'] ?? null,
            $attributes['parent_id'] ?? null,
            $attributes['name'],
            TransactionType::from($attributes['type']),
            $attributes['icon'] ?? null,
            $attributes['icon_color'] ?? null,
            $attributes['active'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'type' => $this->type,
            'icon' => $this->icon,
            'icon_color' => $this->iconColor,
            'active' => $this->active,
        ];
    }
}
