<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialConnectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->external_id,
            'wallet_id' => $this->wallet_id,
            'connector_name' => $this->institution_name,
            'connector_logo' => data_get($this->metadata, 'connector_image_url'),
            'status' => $this->status,
            'last_synced_at' => $this->last_synced_at,
            'accounts' => $this->externalAccounts->map(fn ($externalAccount): array => [
                'id' => $externalAccount->id,
                'name' => $externalAccount->accountable?->name ?? 'Conta Open Finance',
                'pluggy_balance' => (int) round((float) data_get($externalAccount->metadata, 'balance', data_get($externalAccount->metadata, 'currentBalance', 0)) * 100),
                'accountable_type' => $externalAccount->accountable_type,
            ])->values(),
        ];
    }
}
