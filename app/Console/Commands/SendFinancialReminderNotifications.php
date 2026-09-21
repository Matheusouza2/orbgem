<?php

namespace App\Console\Commands;

use App\Services\FinancialReminderNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('notifications:send-financial-reminders {--date= : Data de referência no formato YYYY-MM-DD}')]
#[Description('Envia lembretes de transações vencendo hoje e transações em atraso')]
class SendFinancialReminderNotifications extends Command
{
    public function handle(FinancialReminderNotificationService $notifications): int
    {
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        $sent = $notifications->notifyDueTransactions($date);
        $this->info("{$sent} notificação(ões) de vencimento enviada(s).");

        return self::SUCCESS;
    }
}
