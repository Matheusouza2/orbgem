<?php

namespace App\Console\Commands;

use App\Jobs\RefreshFinancialSummary;
use App\Repositories\FinancialSummaryRepositoryInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai:refresh-summaries {--wallet=} {--force}')]
#[Description('Enfileira a atualização dos resumos financeiros analisados pela IA')]
class RefreshFinancialSummaries extends Command
{
    public function handle(FinancialSummaryRepositoryInterface $repository): int
    {
        if (! config('services.gemini.api_key')) {
            $this->error('STUDIO_AI_API_KEY não configurada.');

            return self::FAILURE;
        }

        $walletIds = $repository->walletIdsWithFinancialData($this->option('wallet') ? (int) $this->option('wallet') : null);
        foreach ($walletIds as $walletId) {
            RefreshFinancialSummary::dispatch($walletId, (bool) $this->option('force'));
        }
        $this->info("{$walletIds->count()} resumo(s) enfileirado(s).");

        return self::SUCCESS;
    }
}
