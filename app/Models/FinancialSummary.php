<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wallet_id', 'facts', 'analysis', 'data_hash', 'generated_at'])]
class FinancialSummary extends Model
{
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    protected function casts(): array
    {
        return ['facts' => 'array', 'analysis' => 'array', 'generated_at' => 'datetime'];
    }
}
