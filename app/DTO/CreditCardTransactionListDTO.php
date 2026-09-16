<?php

namespace App\DTO;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

final readonly class CreditCardTransactionListDTO
{
    public function __construct(
        public int $walletId,
        public int $creditCardId,
        public ?string $month,
        public ?TransactionStatus $status,
        public int $page,
        public int $perPage,
        public bool $includeThirdParty = true,
        public ?TransactionType $type = null,
        public ?int $categoryId = null,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes, int $creditCardId): self
    {
        return new self(
            walletId: (int) $attributes['wallet_id'],
            creditCardId: $creditCardId,
            month: $attributes['month'] ?? null,
            status: isset($attributes['status']) ? TransactionStatus::from($attributes['status']) : null,
            type: isset($attributes['type']) ? TransactionType::from($attributes['type']) : null,
            categoryId: isset($attributes['category_id']) ? (int) $attributes['category_id'] : null,
            page: (int) ($attributes['page'] ?? 1),
            perPage: (int) ($attributes['per_page'] ?? 25),
            includeThirdParty: (bool) ($attributes['include_third_party'] ?? true),
        );
    }
}
