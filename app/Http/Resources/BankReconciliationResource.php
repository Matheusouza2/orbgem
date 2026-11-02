<?php

namespace App\Http\Resources;

use App\Models\BankReconciliation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankReconciliationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var BankReconciliation $reconciliation */
        $reconciliation = $this->resource;
        $entries = $reconciliation->relationLoaded('entries') ? $reconciliation->entries : collect();
        return [
            'id' => $reconciliation->id,
            'month' => $reconciliation->month,
            'original_name' => $reconciliation->original_name,
            'file_type' => $reconciliation->file_type,
            'status' => $reconciliation->status,
            'account' => $reconciliation->account === null ? null : [
                'id' => $reconciliation->account->id,
                'name' => $reconciliation->account->name,
            ],
            'document_account_number_last4' => $reconciliation->document_account_number_last4,
            'document_period_start' => $reconciliation->document_period_start?->toDateString(),
            'document_period_end' => $reconciliation->document_period_end?->toDateString(),
            'opening_balance' => $reconciliation->opening_balance,
            'closing_balance' => $reconciliation->closing_balance,
            'total_credits' => $reconciliation->total_credits,
            'total_debits' => $reconciliation->total_debits,
            'statement_row_count' => $reconciliation->statement_row_count,
            'imported_count' => $reconciliation->imported_count,
            'warnings' => $reconciliation->warnings ?? [],
            'counts' => $entries->groupBy('match_status')->map->count(),
            'entries' => BankReconciliationEntryResource::collection($this->whenLoaded('entries')),
        ];
    }
}
