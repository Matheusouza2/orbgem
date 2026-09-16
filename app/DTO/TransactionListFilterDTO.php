<?php

namespace App\DTO;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

final readonly class TransactionListFilterDTO
{
    public function __construct(
        public int $walletId,
        public ?int $accountId,
        public ?int $merchantId,
        public ?int $categoryId,
        public ?TransactionType $type,
        public ?TransactionStatus $status,
        public ?string $month,
        public int $page,
        public int $perPage,
        public string $sortBy,
        public string $sortDirection,
        public ?string $transactionDateFrom,
        public ?string $transactionDateTo,
        public ?string $competenceDateFrom,
        public ?string $competenceDateTo,
        public bool $includeThirdParty = true,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            walletId: $attributes['wallet_id'],
            accountId: $attributes['account_id'] ?? null,
            merchantId: $attributes['merchant_id'] ?? null,
            categoryId: isset($attributes['category_id']) ? (int) $attributes['category_id'] : null,
            type: isset($attributes['type']) ? TransactionType::from($attributes['type']) : null,
            status: isset($attributes['status']) ? TransactionStatus::from($attributes['status']) : null,
            month: $attributes['month'] ?? null,
            page: $attributes['page'] ?? 1,
            perPage: $attributes['per_page'] ?? 25,
            sortBy: $attributes['sort_by'] ?? 'transaction_date',
            sortDirection: $attributes['sort_direction'] ?? 'desc',
            transactionDateFrom: $attributes['transaction_date_from'] ?? null,
            transactionDateTo: $attributes['transaction_date_to'] ?? null,
            competenceDateFrom: $attributes['competence_date_from'] ?? null,
            competenceDateTo: $attributes['competence_date_to'] ?? null,
            includeThirdParty: (bool) ($attributes['include_third_party'] ?? true),
        );
    }
}
