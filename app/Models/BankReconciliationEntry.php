<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'signed_amount' => 'integer',
            'balance_after' => 'integer',
            'flags' => 'array',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function matchedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'matched_transaction_id');
    }

    public function suggestedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'suggested_transaction_id');
    }

    public function importedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'imported_transaction_id');
    }
}
