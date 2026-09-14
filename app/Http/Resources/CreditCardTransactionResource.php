<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $externalTransaction = $this->externalTransactions->first();

        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => $this->amount,
            'type' => $this->type,
            'effect' => $this->effect,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'competence_date' => $this->competence_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'is_third_party' => (bool) $this->is_third_party,
            'category_id' => $this->category_id,
            'merchant_id' => $this->merchant_id,
            'purchase_id' => $this->installment?->purchase_id,
            'purchase' => $this->installment?->purchase === null ? null : [
                'id' => $this->installment->purchase->id,
                'description' => $this->installment->purchase->description,
                'purchase_date' => $this->installment->purchase->purchase_date?->toDateString(),
                'total_amount' => $this->installment->purchase->total_amount,
                'installment_count' => $this->installment->purchase->installment_count,
                'category_id' => $this->installment->purchase->category_id,
                'merchant_id' => $this->installment->purchase->merchant_id,
            ],
            'can_edit' => ($this->installment !== null || ($this->creditCardInvoice !== null && $this->financial_instrument_type?->value === 'CREDIT_CARD')) && $this->creditCardInvoice?->status?->value !== 'PAID',
            'can_delete' => ($this->installment !== null || ($this->creditCardInvoice !== null && $this->financial_instrument_type?->value === 'CREDIT_CARD')) && $this->creditCardInvoice?->status?->value !== 'PAID',
            'source' => $externalTransaction?->source ?? 'internal',
            'installment' => $this->installment === null ? null : [
                'number' => $this->installment->number,
                'total' => $this->installment->purchase?->installment_count,
                'status' => $this->installment->status,
            ],
            'invoice' => $this->creditCardInvoice === null ? null : [
                'reference_month' => $this->creditCardInvoice->reference_month,
                'due_date' => $this->creditCardInvoice->due_date?->toDateString(),
                'status' => $this->creditCardInvoice->status,
            ],
        ];
    }
}
