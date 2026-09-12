<?php

namespace App\Enums;

enum TransactionEffect: string
{
    case DEBIT = 'DEBIT';
    case CREDIT = 'CREDIT';
    case NONE = 'NONE';
}
