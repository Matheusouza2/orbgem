<?php

namespace App\Http\Resources;

use App\Models\BankReconciliationEntry;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankReconciliationEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var BankReconciliationEntry $entry */
        $entry = $this->resource;

        return [
            'id' => $entry->id,
            'entry_kind' => $entry->entry_kind,
            'external_id' => $entry->external_id,
            'transaction_date' => $entry->transaction_date?->toDateString(),
            'description' => $entry->description,
            'signed_amount' => $entry->signed_amount,
            'balance_after' => $entry->balance_after,
            'match_status' => $entry->match_status,
            'matched_transaction' => $this->transaction($entry->matchedTransaction),
            'suggested_transaction' => $this->transaction($entry->suggestedTransaction),
            'imported_transaction' => $this->transaction($entry->importedTransaction),
            'flags' => $entry->flags ?? [],
            'can_import' => $entry->entry_kind === 'STATEMENT'
                && $entry->match_status === 'ONLY_IN_STATEMENT'
                && $entry->imported_transaction_id === null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function transaction(?Transaction $transaction): ?array
    {
        if ($transaction === null) {
            return null;
        }

        return [
            'id' => $transaction->id,
            'description' => $transaction->description,
            'transaction_date' => $transaction->transaction_date?->toDateString(),
            'signed_amount' => $transaction->effect->value === 'CREDIT' ? $transaction->amount : -$transaction->amount,
            'status' => $transaction->status->value,
        ];
    }
}
