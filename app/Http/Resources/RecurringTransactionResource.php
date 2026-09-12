<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'account_id' => $this->account_id, 'category_id' => $this->category_id, 'description' => $this->description, 'type' => $this->type, 'amount' => $this->amount, 'frequency' => $this->frequency, 'start_date' => $this->start_date?->toDateString(), 'end_date' => $this->end_date?->toDateString(), 'due_day' => $this->due_day, 'auto_create' => $this->auto_create, 'active' => $this->active];
    }
}
