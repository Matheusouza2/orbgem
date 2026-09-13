<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentInvoice = $this->invoices?->sortByDesc('reference_month')->first(fn ($invoice): bool => $invoice->status?->value !== 'PAID');
        $invoiceAmount = $currentInvoice?->installments?->sum('amount') ?? 0;
        $limit = (int) $this->credit_limit;

        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'name' => $this->name, 'institution' => $this->institution, 'limit' => $limit, 'current_invoice_amount' => $invoiceAmount, 'current_invoice_reference_month' => $currentInvoice?->reference_month, 'limit_usage_percentage' => $limit > 0 ? round(($invoiceAmount / $limit) * 100, 2) : 0, 'closing_day' => $this->closing_day, 'due_day' => $this->due_day, 'active' => $this->active, 'created_at' => $this->created_at];
    }
}
