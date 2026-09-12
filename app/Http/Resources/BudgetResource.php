<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'category_id' => $this->category_id, 'reference_month' => $this->reference_month, 'amount' => $this->amount, 'active' => $this->active];
    }
}
