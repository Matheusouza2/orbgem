<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'original_name' => $this->original_name, 'status' => $this->status, 'total_rows' => $this->total_rows, 'imported_rows' => $this->imported_rows, 'failed_rows' => $this->failed_rows, 'errors' => $this->errors];
    }
}
