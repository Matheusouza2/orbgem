<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['financial_connection_id', 'external_id', 'type', 'subtype', 'accountable_type', 'accountable_id', 'last_synced_at', 'metadata'])]
class ExternalAccount extends Model
{
    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime', 'metadata' => 'array'];
    }

    public function financialConnection(): BelongsTo
    {
        return $this->belongsTo(FinancialConnection::class);
    }

    public function accountable(): MorphTo
    {
        return $this->morphTo();
    }

    public function externalTransactions(): HasMany
    {
        return $this->hasMany(ExternalTransaction::class);
    }
}
