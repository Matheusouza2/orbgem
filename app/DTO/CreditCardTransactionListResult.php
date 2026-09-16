<?php

namespace App\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CreditCardTransactionListResult
{
    public function __construct(
        public LengthAwarePaginator $transactions,
        public int $amount,
        public int $totalAmount,
    ) {}
}
