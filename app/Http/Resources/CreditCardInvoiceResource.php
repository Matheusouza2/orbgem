<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'credit_card_id' => $this->credit_card_id, 'reference_month' => $this->reference_month, 'closing_date' => $this->closing_date, 'due_date' => $this->due_date, 'status' => $this->status, 'total_amount' => $this->whenLoaded('installments', fn () => $this->installments->sum('amount')), 'paid_at' => $this->paid_at, 'installments' => InstallmentResource::collection($this->whenLoaded('installments'))];
    }
}
