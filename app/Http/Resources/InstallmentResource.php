<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'number' => $this->number, 'amount' => $this->amount, 'competence_date' => $this->competence_date, 'due_date' => $this->due_date, 'status' => $this->status];
    }
}
