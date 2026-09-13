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
