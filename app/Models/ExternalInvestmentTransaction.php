<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['external_investment_id', 'investment_id', 'source', 'external_id', 'event_type', 'description', 'amount', 'transaction_date', 'raw_data', 'imported_at'])]
class ExternalInvestmentTransaction extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'transaction_date' => 'date', 'raw_data' => 'array', 'imported_at' => 'datetime'];
    }

    public function externalInvestment(): BelongsTo
    {
        return $this->belongsTo(ExternalInvestment::class);
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
