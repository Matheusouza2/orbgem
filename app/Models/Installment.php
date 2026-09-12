<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $fillable = ['credit_card_purchase_id', 'credit_card_invoice_id', 'number', 'amount', 'competence_date', 'due_date', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'number' => 'integer', 'competence_date' => 'date', 'due_date' => 'date', 'status' => InstallmentStatus::class];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(CreditCardPurchase::class, 'credit_card_purchase_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CreditCardInvoice::class, 'credit_card_invoice_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
