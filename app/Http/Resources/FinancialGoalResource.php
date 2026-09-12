<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'name' => $this->name, 'target_amount' => $this->target_amount, 'current_amount' => $this->current_amount, 'remaining_amount' => max(0, $this->target_amount - $this->current_amount), 'deadline' => $this->deadline?->toDateString(), 'status' => $this->status, 'active' => $this->active, 'contributions' => $this->whenLoaded('contributions', fn () => $this->contributions->map(fn ($contribution): array => ['id' => $contribution->id, 'amount' => $contribution->amount, 'contributed_at' => $contribution->contributed_at?->toDateString(), 'note' => $contribution->note]))];
    }
}
