<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['investment_id', 'position_date', 'value', 'quantity', 'unit_price'])]
class InvestmentPosition extends Model
{
    protected function casts(): array
    {
        return [
            'position_date' => 'date',
            'value' => 'integer',
            'quantity' => 'decimal:8',
            'unit_price' => 'integer',
        ];
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
