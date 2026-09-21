<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditCardInvoice;
use App\Models\InAppNotification;
use App\Models\Transaction;
use App\Models\WalletMember;
use Illuminate\Support\Carbon;

class FinancialReminderNotificationService
{
    public function notifyTransactionPaid(Transaction $transaction): void
    {
        if ($transaction->type !== TransactionType::EXPENSE || $transaction->is_third_party || $transaction->paid_at === null) {
            return;
        }

        $this->notifyWalletMembers($transaction->wallet_id, 'TRANSACTION_PAID', 'Transação paga', 'A transação "'.$transaction->description.'" foi marcada como paga.', [
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
            'paid_at' => $transaction->paid_at->toISOString(),
        ], 'transaction:'.$transaction->id.':TRANSACTION_PAID');
    }

    public function notifyInvoicePaid(CreditCardInvoice $invoice): void
    {
        $this->notifyWalletMembers($invoice->wallet_id, 'CREDIT_CARD_INVOICE_PAID', 'Fatura paga', 'A fatura do cartão referente a '.$invoice->reference_month.' foi paga.', [
            'invoice_id' => $invoice->id,
            'reference_month' => $invoice->reference_month,
            'amount' => $invoice->totalAmount(),
            'paid_at' => $invoice->paid_at?->toISOString(),
        ], 'invoice:'.$invoice->id.':CREDIT_CARD_INVOICE_PAID');
    }

    public function notifyDueTransactions(string $date): int
    {
        $day = Carbon::parse($date)->toDateString();
        $sent = 0;

        Transaction::query()
            ->where('type', TransactionType::EXPENSE)
            ->where('status', TransactionStatus::PROJECTED)
            ->whereNull('paid_at')
            ->where('is_third_party', false)
            ->whereDate('due_date', '<=', $day)
            ->get()
            ->each(function (Transaction $transaction) use ($day, &$sent): void {
                $overdue = $transaction->due_date->toDateString() < $day;
                $type = $overdue ? 'TRANSACTION_OVERDUE' : 'TRANSACTION_DUE_TODAY';
                $title = $overdue ? 'Transação vencida' : 'Transação vencendo hoje';
                $body = $overdue
                    ? 'A transação "'.$transaction->description.'" está vencida e ainda não foi paga.'
                    : 'A transação "'.$transaction->description.'" vence hoje.';
                $sent += $this->notifyWalletMembers($transaction->wallet_id, $type, $title, $body, [
                    'transaction_id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                    'due_date' => $transaction->due_date->toDateString(),
                ], 'transaction:'.$transaction->id.':'.$type.':'.$day);
            });

        return $sent;
    }

    /** @param array<string, mixed> $data */
    private function notifyWalletMembers(int $walletId, string $type, string $title, ?string $body, array $data, string $deduplicationKey): int
    {
        $sent = 0;

        WalletMember::query()->with('user')->where('wallet_id', $walletId)->get()->each(function (WalletMember $member) use ($type, $title, $body, $data, $deduplicationKey, &$sent): void {
            if (InAppNotification::query()->where('user_id', $member->user_id)->where('type', $type)->where('data->deduplication_key', $deduplicationKey)->exists()) {
                return;
            }

            $member->user->notify(new \App\Notifications\WalletActivityNotification($member->wallet_id, $type, $title, $body, $data, $deduplicationKey));
            $sent++;
        });

        return $sent;
    }
}
