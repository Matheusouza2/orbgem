<?php

namespace App\Enums;

enum FinancialCommitmentType: string
{
    case LOAN = 'LOAN';
    case FINANCING = 'FINANCING';
    case CONSORTIUM = 'CONSORTIUM';
    case EDUCATION = 'EDUCATION';
    case OTHER = 'OTHER';
}
