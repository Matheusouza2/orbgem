<?php

namespace App\Enums;

enum TransactionRecurrence: string
{
    case NONE = 'NONE';
    case FIXED_MONTHLY = 'FIXED_MONTHLY';
    case INSTALLMENT = 'INSTALLMENT';
}
