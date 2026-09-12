<?php

namespace App\Enums;

enum InstallmentPeriodicity: string
{
    case MONTHLY = 'MONTHLY';
    case BIMONTHLY = 'BIMONTHLY';
    case QUARTERLY = 'QUARTERLY';
    case YEARLY = 'YEARLY';
}
