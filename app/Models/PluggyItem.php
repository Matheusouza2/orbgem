<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'wallet_id', 'pluggy_item_id', 'connector_id', 'connector_name', 'connector_logo', 'status', 'last_updated_at', 'error_code', 'error_message'])]
class PluggyItem extends Model
{
    protected function casts(): array
    {
        return ['last_updated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'pluggy_item_id', 'id');
    }
}
