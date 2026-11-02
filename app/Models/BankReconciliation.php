<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'document_period_start' => 'date',
            'document_period_end' => 'date',
            'opening_balance' => 'integer',
            'closing_balance' => 'integer',
            'total_credits' => 'integer',
            'total_debits' => 'integer',
            'warnings' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BankReconciliationEntry::class);
    }
}
