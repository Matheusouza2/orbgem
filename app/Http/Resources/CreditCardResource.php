<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'name' => $this->name, 'institution' => $this->institution, 'limit' => $this->credit_limit, 'closing_day' => $this->closing_day, 'due_day' => $this->due_day, 'active' => $this->active, 'created_at' => $this->created_at];
    }
}
