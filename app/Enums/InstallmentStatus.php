<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case PENDING = 'PENDING';
    case INVOICED = 'INVOICED';
    case CANCELLED = 'CANCELLED';
}
