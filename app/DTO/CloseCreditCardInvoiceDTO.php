<?php

namespace App\DTO;

final readonly class CloseCreditCardInvoiceDTO
{
    public function __construct(public int $invoiceId, public int $memberId) {}
}
