<?php

namespace App\Enums;

enum FinancialInstrumentType: string
{
    case ACCOUNT = 'ACCOUNT';
    case CREDIT_CARD = 'CREDIT_CARD';
    case NONE = 'NONE';
}
