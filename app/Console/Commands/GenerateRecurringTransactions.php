<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Services\RecurringTransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('planning:generate-recurring')]
#[Description('Command description')]
class GenerateRecurringTransactions extends Command
{
    public function handle(RecurringTransactionService $service): int
    {
        $until = Carbon::today()->addMonth()->toDateString();
        RecurringTransaction::query()->with('wallet.ownerMemberships')->where('active', true)->each(function (RecurringTransaction $rule) use ($service, $until): void {
            $member = $rule->wallet->ownerMemberships->first();
            if ($member !== null) {
                $service->generate($rule, $until, $member->id);
            }
        });

        return self::SUCCESS;
    }
}
