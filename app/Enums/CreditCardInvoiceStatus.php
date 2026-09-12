<?php

namespace App\Enums;

enum CreditCardInvoiceStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
}
