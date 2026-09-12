<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsolidationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'wallet_ids' => $this->whenLoaded('wallets', fn () => $this->wallets->pluck('id')->values()), 'created_at' => $this->created_at];
    }
}
