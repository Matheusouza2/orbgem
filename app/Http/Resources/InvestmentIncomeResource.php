<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentIncomeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investment_id' => $this->investment_id,
            'investment_name' => $this->investment?->name,
            'ticker' => $this->investment?->ticker,
            'description' => $this->description,
            'event_type' => $this->event_type,
            'amount' => $this->amount,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'source' => $this->source,
        ];
    }
}
