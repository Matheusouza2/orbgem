<?php

namespace App\DTO;

final readonly class BankStatementData
{
    /** @param array<int, array{external_id: ?string, date: string, description: string, signed_amount: int, balance_after: ?int}> $rows */
    public function __construct(
        public ?string $accountNumber,
        public ?string $periodStart,
        public ?string $periodEnd,
        public ?int $openingBalance,
        public ?int $closingBalance,
        public ?int $totalCredits,
        public ?int $totalDebits,
        public array $rows,
    ) {}
}
