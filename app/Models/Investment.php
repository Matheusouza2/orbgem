<?php

namespace App\Models;

use App\Enums\InvestmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['wallet_id', 'name', 'ticker', 'type', 'institution', 'quantity', 'average_price', 'invested_amount', 'current_value', 'acquired_at', 'active', 'cdi_linked', 'cdi_percentage', 'last_yield_date'])]
class Investment extends Model
{
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function yields(): HasMany
    {
        return $this->hasMany(InvestmentYield::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(InvestmentPosition::class);
    }

    protected function casts(): array
    {
        return [
            'type' => InvestmentType::class,
            'quantity' => 'decimal:8',
            'average_price' => 'integer',
            'invested_amount' => 'integer',
            'current_value' => 'integer',
            'acquired_at' => 'date',
            'active' => 'boolean',
            'cdi_linked' => 'boolean',
            'cdi_percentage' => 'decimal:4',
            'last_yield_date' => 'date',
        ];
    }
}
