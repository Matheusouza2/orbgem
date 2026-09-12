<?php

namespace App\Models;

use Database\Factories\FinancialGoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialGoal extends Model
{
    /** @use HasFactory<FinancialGoalFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['target_amount' => 'integer', 'current_amount' => 'integer', 'deadline' => 'date', 'active' => 'boolean'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(FinancialGoalContribution::class);
    }
}
