<?php

namespace App\Enums;

enum InvestmentType: string
{
    case STOCK = 'STOCK';
    case FUND = 'FUND';
    case FII = 'FII';
    case FIXED_INCOME = 'FIXED_INCOME';
    case CRYPTO = 'CRYPTO';
    case OTHER = 'OTHER';
}
