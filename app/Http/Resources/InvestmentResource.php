<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'name' => $this->name,
            'ticker' => $this->ticker,
            'type' => $this->type,
            'institution' => $this->institution,
            'quantity' => $this->quantity,
            'average_price' => $this->average_price,
            'invested_amount' => $this->invested_amount,
            'current_value' => $this->current_value,
            'profit_amount' => $this->current_value - $this->invested_amount,
            'profit_percentage' => $this->invested_amount > 0 ? round((($this->current_value - $this->invested_amount) / $this->invested_amount) * 100, 2) : 0,
            'acquired_at' => $this->acquired_at?->toDateString(),
            'active' => $this->active,
            'cdi_linked' => (bool) $this->cdi_linked,
            'cdi_percentage' => $this->cdi_percentage,
            'last_yield_date' => $this->last_yield_date?->toDateString(),
        ];
    }
}
