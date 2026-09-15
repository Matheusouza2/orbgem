<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['investment_id', 'description', 'event_type', 'amount', 'transaction_date', 'source'])]
class InvestmentIncome extends Model
{
    protected $table = 'investment_income';

    protected function casts(): array
    {
        return ['amount' => 'integer', 'transaction_date' => 'date'];
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
