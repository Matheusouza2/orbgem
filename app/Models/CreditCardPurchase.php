<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCardPurchase extends Model
{
    protected $fillable = ['wallet_id', 'credit_card_id', 'category_id', 'merchant_id', 'description', 'purchase_date', 'total_amount', 'installment_count'];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'total_amount' => 'integer', 'installment_count' => 'integer'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
