<?php

namespace App\Models;

use App\Enums\FinancialInstrumentType;
use App\Enums\InstallmentPeriodicity;
use App\Enums\PaymentChannel;
use App\Enums\TransactionEffect;
use App\Enums\TransactionRecurrence;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['wallet_id', 'account_id', 'category_id', 'merchant_id', 'description', 'type', 'effect', 'amount', 'financial_instrument_type', 'transaction_date', 'competence_date', 'due_date', 'recurrence_type', 'installment_initial', 'installment_count', 'installment_periodicity', 'auto_post_on_due_date', 'paid_at', 'status', 'payment_channel', 'notes', 'recurring_transaction_id', 'financial_commitment_id', 'import_batch_id', 'import_row_hash', 'credit_card_invoice_id', 'installment_id', 'transfer_group_id', 'reversal_of_transaction_id', 'created_by_member_id', 'updated_by_member_id'])]
class Transaction extends Model
{
    public function scopeIncludedInTotals(Builder $query): Builder
    {
        return $query->whereDoesntHave('account', fn (Builder $accountQuery): Builder => $accountQuery->where('ignore_in_totals', true));
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(WalletMember::class, 'created_by_member_id');
    }

    public function updatedByMember(): BelongsTo
    {
        return $this->belongsTo(WalletMember::class, 'updated_by_member_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_transaction_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_transaction_id');
    }

    public function creditCardInvoice(): BelongsTo
    {
        return $this->belongsTo(CreditCardInvoice::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    public function financialCommitment(): BelongsTo
    {
        return $this->belongsTo(FinancialCommitment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function externalTransactions(): HasMany
    {
        return $this->hasMany(ExternalTransaction::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'effect' => TransactionEffect::class,
            'financial_instrument_type' => FinancialInstrumentType::class,
            'status' => TransactionStatus::class,
            'payment_channel' => PaymentChannel::class,
            'amount' => 'integer',
            'transaction_date' => 'date',
            'competence_date' => 'date',
            'due_date' => 'date',
            'recurrence_type' => TransactionRecurrence::class,
            'installment_periodicity' => InstallmentPeriodicity::class,
            'installment_initial' => 'integer',
            'installment_count' => 'integer',
            'auto_post_on_due_date' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }
}
