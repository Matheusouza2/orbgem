<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'name' => $this->name, 'color' => $this->color];
    }
}
