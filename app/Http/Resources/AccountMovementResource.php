<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        /** @var Transaction $transaction */
        $transaction = $this->resource;

        return [...(new TransactionResource($transaction))->toArray($request), 'is_invoice_summary' => false];
    }
}
