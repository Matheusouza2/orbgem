<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['financial_connection_id', 'investment_id', 'source', 'external_id', 'raw_data'])]
class ExternalInvestment extends Model
{
    protected function casts(): array
    {
        return ['raw_data' => 'array'];
    }

    public function financialConnection(): BelongsTo
    {
        return $this->belongsTo(FinancialConnection::class);
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ExternalInvestmentTransaction::class);
    }
}
