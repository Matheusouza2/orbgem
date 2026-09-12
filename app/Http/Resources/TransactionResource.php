<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'account_id' => $this->account_id, 'category_id' => $this->category_id, 'merchant_id' => $this->merchant_id, 'description' => $this->description, 'type' => $this->type, 'effect' => $this->effect, 'amount' => $this->amount, 'financial_instrument_type' => $this->financial_instrument_type, 'transaction_date' => $this->transaction_date?->toDateString(), 'competence_date' => $this->competence_date?->toDateString(), 'due_date' => $this->due_date?->toDateString(), 'recurrence_type' => $this->recurrence_type, 'installment_initial' => $this->installment_initial, 'installment_count' => $this->installment_count, 'installment_periodicity' => $this->installment_periodicity, 'auto_post_on_due_date' => (bool) $this->auto_post_on_due_date, 'paid_at' => $this->paid_at, 'status' => $this->status, 'payment_channel' => $this->payment_channel, 'notes' => $this->notes, 'recurring_transaction_id' => $this->recurring_transaction_id, 'credit_card_invoice_id' => $this->credit_card_invoice_id, 'installment_id' => $this->installment_id, 'transfer_group_id' => $this->transfer_group_id, 'reversal_of_transaction_id' => $this->reversal_of_transaction_id, 'has_reversal' => (bool) ($this->reversals_exists ?? false), 'created_by_member_id' => $this->created_by_member_id, 'updated_by_member_id' => $this->updated_by_member_id, 'created_at' => $this->created_at];
    }
}
