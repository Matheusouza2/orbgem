<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialCommitmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'description' => $this->description, 'type' => $this->type, 'original_amount' => $this->original_amount, 'installment_amount' => $this->installment_amount, 'installment_count' => $this->installment_count, 'current_installment' => $this->current_installment, 'start_date' => $this->start_date?->toDateString(), 'end_date' => $this->end_date?->toDateString(), 'creditor' => $this->creditor, 'active' => $this->active];
    }
}
