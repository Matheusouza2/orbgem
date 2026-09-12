<?php

namespace App\Models;

use Database\Factories\ConsolidationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Consolidation extends Model
{
    /** @use HasFactory<ConsolidationFactory> */
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallets(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class);
    }
}
