<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentPositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investment_id' => $this->investment_id,
            'position_date' => $this->position_date?->toDateString(),
            'value' => $this->value,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
