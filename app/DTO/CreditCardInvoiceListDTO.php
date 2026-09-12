<?php

namespace App\DTO;

final readonly class CreditCardInvoiceListDTO
{
    public function __construct(public int $walletId, public ?int $creditCardId = null, public ?string $status = null) {}
}
