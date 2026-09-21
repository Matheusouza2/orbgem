<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\FinancialReminderNotificationService;
use App\Services\TransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('transactions:post-due {--date=}')]
#[Description('Efetiva previsões configuradas para serem pagas no vencimento')]
class PostDueTransactions extends Command
{
    public function handle(TransactionService $transactions, FinancialReminderNotificationService $notifications): int
    {
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        $transactionIds = Transaction::query()
            ->where('status', TransactionStatus::PROJECTED)
            ->where('auto_post_on_due_date', true)
            ->whereDate('due_date', '<=', $date)
            ->pluck('id');
        $posted = $transactions->postDueAutomatically($date);
        Transaction::query()->whereIn('id', $transactionIds)->get()->each($notifications->notifyTransactionPaid(...));
        $this->info("{$posted} transação(ões) efetivada(s).");

        return self::SUCCESS;
    }
}
