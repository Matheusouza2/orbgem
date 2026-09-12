<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'credit_card_id' => $this->credit_card_id, 'description' => $this->description, 'purchase_date' => $this->purchase_date, 'total_amount' => $this->total_amount, 'installment_count' => $this->installment_count, 'installments' => InstallmentResource::collection($this->whenLoaded('installments'))];
    }
}
