<?php

namespace App\Http\Requests\Transaction;

use App\Enums\FinancialInstrumentType;
use App\Enums\InstallmentPeriodicity;
use App\Enums\PaymentChannel;
use App\Enums\TransactionEffect;
use App\Enums\TransactionRecurrence;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['wallet_id' => ['required', 'integer', 'exists:wallets,id'], 'account_id' => ['required', 'integer', 'exists:accounts,id'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'], 'description' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::enum(TransactionType::class)], 'effect' => ['required', Rule::enum(TransactionEffect::class)], 'amount' => ['required', 'integer', 'min:1'], 'financial_instrument_type' => ['required', Rule::enum(FinancialInstrumentType::class)], 'transaction_date' => ['required', 'date'], 'competence_date' => ['required', 'date'], 'due_date' => ['nullable', 'date'], 'recurrence_type' => ['sometimes', Rule::enum(TransactionRecurrence::class)], 'installment_initial' => ['nullable', 'integer', 'min:1', 'required_if:recurrence_type,INSTALLMENT'], 'installment_count' => ['nullable', 'integer', 'min:1', 'required_if:recurrence_type,INSTALLMENT'], 'installment_periodicity' => ['nullable', Rule::enum(InstallmentPeriodicity::class), 'required_if:recurrence_type,INSTALLMENT'], 'auto_post_on_due_date' => ['sometimes', 'boolean'], 'paid_at' => ['nullable', 'date'], 'status' => ['required', Rule::enum(TransactionStatus::class)], 'payment_channel' => ['nullable', Rule::enum(PaymentChannel::class)], 'notes' => ['nullable', 'string'], 'is_third_party' => ['sometimes', 'boolean'], 'recurring_transaction_id' => ['prohibited'], 'credit_card_invoice_id' => ['prohibited'], 'installment_id' => ['prohibited'], 'transfer_group_id' => ['prohibited'], 'reversal_of_transaction_id' => ['prohibited']];
    }
}
