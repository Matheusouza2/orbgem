<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $invoices = $this->invoices?->sortByDesc('reference_month') ?? collect();
        $currentInvoice = $invoices->first(fn ($invoice): bool => $invoice->status?->value !== 'PAID');
        $previousInvoice = $currentInvoice === null ? null : $invoices->first(function ($invoice) use ($currentInvoice): bool {
            return $invoice->reference_month < $currentInvoice->reference_month;
        });
        $invoiceAmount = $this->invoiceAmount($currentInvoice);
        $previousInvoiceAmount = $this->invoiceAmount($previousInvoice);
        $limit = (int) $this->credit_limit;

        return ['id' => $this->id, 'wallet_id' => $this->wallet_id, 'account_id' => $this->account_id, 'name' => $this->name, 'institution' => $this->institution, 'limit' => $limit, 'current_invoice_id' => $currentInvoice?->id, 'current_invoice_amount' => $invoiceAmount, 'dashboard_balance' => $this->when(array_key_exists('dashboard_balance', $this->resource->getAttributes()), fn (): int => (int) $this->dashboard_balance), 'current_invoice_reference_month' => $currentInvoice?->reference_month, 'current_invoice_status' => $currentInvoice?->status?->value, 'current_invoice_due_date' => $currentInvoice?->due_date?->toDateString(), 'limit_usage_percentage' => $limit > 0 ? round(($invoiceAmount / $limit) * 100, 2) : 0, 'previous_invoice_amount' => $previousInvoiceAmount, 'previous_invoice_reference_month' => $previousInvoice?->reference_month, 'previous_invoice_usage_percentage' => $limit > 0 ? round(($previousInvoiceAmount / $limit) * 100, 2) : 0, 'closing_day' => $this->closing_day, 'due_day' => $this->due_day, 'active' => $this->active, 'created_at' => $this->created_at];
    }

    private function invoiceAmount($invoice): int
    {
        if ($invoice === null) {
            return 0;
        }

        return (int) ($invoice->installments?->sum('amount') ?? 0) + (int) Transaction::query()
            ->where('credit_card_invoice_id', $invoice->id)
            ->whereNull('installment_id')
            ->sum('amount');
    }
}
