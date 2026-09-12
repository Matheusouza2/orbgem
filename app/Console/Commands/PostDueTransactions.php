<?php

namespace App\Console\Commands;

use App\Services\TransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('transactions:post-due {--date=}')]
#[Description('Efetiva previsões configuradas para serem pagas no vencimento')]
class PostDueTransactions extends Command
{
    public function handle(TransactionService $transactions): int
    {
        $posted = $transactions->postDueAutomatically($this->option('date') ?: Carbon::today()->toDateString());
        $this->info("{$posted} transação(ões) efetivada(s).");

        return self::SUCCESS;
    }
}
