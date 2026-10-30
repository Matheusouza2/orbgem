<?php

namespace App\Http\Resources;

use App\Models\FinancialSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialSummary */
class FinancialSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['wallet_id' => $this->wallet_id, 'generated_at' => $this->generated_at->toIso8601String(), 'facts' => $this->facts, 'analysis' => $this->analysis];
    }
}
