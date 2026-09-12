<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

final readonly class Slice5DTO
{
    public function __construct(
        public int $userId,
        public ?int $walletId = null,
        public ?int $recordId = null,
        public ?string $name = null,
        public array $walletIds = [],
        public array $attributes = [],
        public array $tagIds = [],
        public ?string $attachableType = null,
        public ?int $attachableId = null,
        public ?UploadedFile $file = null,
        public ?int $memberId = null,
        public ?string $month = null,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes, int $userId): self
    {
        return new self(
            userId: $userId,
            walletId: isset($attributes['wallet_id']) ? (int) $attributes['wallet_id'] : null,
            recordId: isset($attributes['record_id']) ? (int) $attributes['record_id'] : null,
            name: isset($attributes['name']) ? (string) $attributes['name'] : null,
            walletIds: array_map('intval', $attributes['wallet_ids'] ?? []),
            attributes: $attributes,
            tagIds: array_map('intval', $attributes['tag_ids'] ?? []),
            attachableType: isset($attributes['attachable_type']) ? (string) $attributes['attachable_type'] : null,
            attachableId: isset($attributes['attachable_id']) ? (int) $attributes['attachable_id'] : null,
            file: $attributes['file'] ?? null,
            memberId: isset($attributes['member_id']) ? (int) $attributes['member_id'] : null,
            month: isset($attributes['month']) ? (string) $attributes['month'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function withWallet(int $walletId): self
    {
        return new self($this->userId, $walletId, $this->recordId, $this->name, $this->walletIds, $this->attributes, $this->tagIds, $this->attachableType, $this->attachableId, $this->file, $this->memberId, $this->month);
    }

    public function withMember(int $memberId): self
    {
        return new self($this->userId, $this->walletId, $this->recordId, $this->name, $this->walletIds, $this->attributes, $this->tagIds, $this->attachableType, $this->attachableId, $this->file, $memberId, $this->month);
    }
}
