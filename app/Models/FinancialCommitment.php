<?php

namespace App\Models;

use App\Enums\FinancialCommitmentType;
use Database\Factories\FinancialCommitmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialCommitment extends Model
{
    /** @use HasFactory<FinancialCommitmentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => FinancialCommitmentType::class, 'original_amount' => 'integer', 'installment_amount' => 'integer', 'installment_count' => 'integer', 'current_installment' => 'integer', 'start_date' => 'date', 'end_date' => 'date', 'active' => 'boolean'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
