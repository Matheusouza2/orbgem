<?php

namespace App\Enums;

enum AccountType: string
{
    case CHECKING = 'CHECKING';
    case SAVINGS = 'SAVINGS';
    case CASH = 'CASH';
    case INVESTMENT = 'INVESTMENT';
    case DIGITAL_WALLET = 'DIGITAL_WALLET';
}
