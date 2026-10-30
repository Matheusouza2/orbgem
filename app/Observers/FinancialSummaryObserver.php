<?php

namespace App\Observers;

use App\Jobs\RefreshFinancialSummary;
use App\Models\Investment;
use App\Models\Transaction;

/** Quando lançamentos ou investimentos mudam, agenda uma única atualização do resumo (agrupa rajadas, como importações e sincronizações). */
class FinancialSummaryObserver
{
    public function saved(Transaction|Investment $model): void
    {
        $this->schedule($model);
    }

    public function deleted(Transaction|Investment $model): void
    {
        $this->schedule($model);
    }

    private function schedule(Transaction|Investment $model): void
    {
        if (! is_string(config('services.gemini.api_key')) || config('services.gemini.api_key') === '') {
            return;
        }

        RefreshFinancialSummary::dispatch($model->wallet_id)->delay(now()->addMinutes(5));
    }
}
