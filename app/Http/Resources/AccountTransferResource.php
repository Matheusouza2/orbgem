<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountTransferResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'transfer_group_id' => $this->resource['transfer_group_id'],
            'transactions' => collect($this->resource['transactions'])
                ->map(fn ($transaction): array => (new TransactionResource($transaction))->toArray($request))
                ->all(),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(201);
    }
}
