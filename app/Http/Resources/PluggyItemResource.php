<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluggyItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'item_id' => $this->pluggy_item_id, 'wallet_id' => $this->wallet_id, 'connector_id' => $this->connector_id, 'connector_name' => $this->connector_name, 'connector_logo' => $this->connector_logo, 'status' => $this->status, 'last_updated_at' => $this->last_updated_at, 'error_code' => $this->error_code, 'error_message' => $this->error_message, 'accounts' => AccountResource::collection($this->whenLoaded('accounts'))];
    }
}
