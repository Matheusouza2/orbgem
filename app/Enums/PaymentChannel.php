<?php

namespace App\Enums;

enum PaymentChannel: string
{
    case PIX = 'PIX';
    case DEBIT_CARD = 'DEBIT_CARD';
    case CASH = 'CASH';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case BOLETO = 'BOLETO';
    case OTHER = 'OTHER';
}
