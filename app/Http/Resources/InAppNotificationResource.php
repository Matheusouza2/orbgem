<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InAppNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'type' => $this->type, 'title' => $this->title, 'body' => $this->body, 'data' => $this->data, 'read_at' => $this->read_at];
    }
}
