<?php

namespace App\Enums;

enum WalletMemberRole: string
{
    case OWNER = 'OWNER';
    case EDITOR = 'EDITOR';
    case VIEWER = 'VIEWER';
}
