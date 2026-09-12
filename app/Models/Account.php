<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wallet_id', 'pluggy_item_id', 'pluggy_account_id', 'pluggy_balance', 'owner_wallet_member_id', 'name', 'institution', 'bank_code', 'account_number', 'type', 'initial_balance', 'is_default', 'show_in_dashboard', 'ignore_in_totals', 'active'])]
class Account extends Model
{
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ownerWalletMember(): BelongsTo
    {
        return $this->belongsTo(WalletMember::class, 'owner_wallet_member_id');
    }

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => 'integer',
            'pluggy_balance' => 'integer',
            'is_default' => 'boolean',
            'show_in_dashboard' => 'boolean',
            'ignore_in_totals' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
