<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentYieldResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investment_id' => $this->investment_id,
            'reference_date' => $this->reference_date?->toDateString(),
            'cdi_daily_rate' => $this->cdi_daily_rate,
            'cdi_percentage' => $this->cdi_percentage,
            'opening_value' => $this->opening_value,
            'yield_amount' => $this->yield_amount,
            'closing_value' => $this->closing_value,
        ];
    }
}
