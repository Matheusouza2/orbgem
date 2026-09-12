<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['wallet_id', 'provider', 'external_id', 'institution_name', 'status', 'last_synced_at', 'metadata'])]
class FinancialConnection extends Model
{
    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime', 'metadata' => 'array'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function externalAccounts(): HasMany
    {
        return $this->hasMany(ExternalAccount::class);
    }
}
