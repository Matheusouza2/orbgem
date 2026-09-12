<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PROJECTED = 'PROJECTED';
    case POSTED = 'POSTED';
    case CANCELLED = 'CANCELLED';
}
