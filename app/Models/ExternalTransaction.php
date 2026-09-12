<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['external_account_id', 'transaction_id', 'source', 'external_id', 'imported_at', 'raw_data'])]
class ExternalTransaction extends Model
{
    protected function casts(): array
    {
        return ['imported_at' => 'datetime', 'raw_data' => 'array'];
    }

    public function externalAccount(): BelongsTo
    {
        return $this->belongsTo(ExternalAccount::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
