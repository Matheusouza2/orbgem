<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CreditCard extends Model
{
    use HasFactory;

    protected $fillable = ['wallet_id', 'owner_wallet_member_id', 'account_id', 'name', 'institution', 'credit_limit', 'closing_day', 'due_day', 'active'];

    protected function casts(): array
    {
        return ['credit_limit' => 'integer', 'closing_day' => 'integer', 'due_day' => 'integer', 'active' => 'boolean'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ownerWalletMember(): BelongsTo
    {
        return $this->belongsTo(WalletMember::class, 'owner_wallet_member_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(CreditCardPurchase::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CreditCardInvoice::class);
    }

    public function externalAccounts(): MorphMany
    {
        return $this->morphMany(ExternalAccount::class, 'accountable');
    }
}
