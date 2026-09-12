<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'name' => $this->name,
            'normalized_name' => $this->normalized_name,
            'active' => $this->active,
            'created_at' => $this->created_at,
        ];
    }
}
