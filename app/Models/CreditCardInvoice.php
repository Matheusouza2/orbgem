<?php

namespace App\Models;

use App\Enums\CreditCardInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCardInvoice extends Model
{
    protected $fillable = ['wallet_id', 'credit_card_id', 'reference_month', 'closing_date', 'due_date', 'status', 'paid_at'];

    protected function casts(): array
    {
        return ['status' => CreditCardInvoiceStatus::class, 'closing_date' => 'date', 'due_date' => 'date', 'paid_at' => 'datetime'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function totalAmount(): int
    {
        return (int) $this->installments()->sum('amount')
            + (int) $this->transactions()
                ->whereNull('installment_id')
                ->whereDoesntHave('invoicePayments')
                ->sum('amount');
    }
}
