<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentPositionHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'position_date' => $this->resource['position_date'],
            'total_value' => $this->resource['total_value'],
        ];
    }
}
