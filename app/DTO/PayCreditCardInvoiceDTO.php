<?php

namespace App\DTO;

final readonly class PayCreditCardInvoiceDTO
{
    public function __construct(public int $invoiceId, public int $accountId, public int $amount, public int $memberId) {}
}
