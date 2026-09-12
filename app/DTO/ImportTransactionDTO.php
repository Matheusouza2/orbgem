<?php

namespace App\DTO;

use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

final readonly class ImportTransactionDTO
{
    /** @param array<string, mixed> $rawData @param array<string, mixed> $creditCardMetadata */
    public function __construct(
        public string $externalId,
        public int $walletId,
        public ?int $accountId,
        public string $description,
        public int $amount,
        public string $date,
        public TransactionType $type,
        public TransactionEffect $effect,
        public TransactionStatus $status,
        public FinancialInstrumentType $financialInstrumentType,
        public ?string $category,
        public array $creditCardMetadata,
        public array $rawData,
    ) {}
}
