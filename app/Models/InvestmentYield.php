<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentYield extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['reference_date' => 'date', 'cdi_daily_rate' => 'decimal:10', 'cdi_percentage' => 'decimal:4', 'opening_value' => 'integer', 'yield_amount' => 'integer', 'closing_value' => 'integer'];
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
