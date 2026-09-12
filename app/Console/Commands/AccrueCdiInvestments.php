<?php

namespace App\Console\Commands;

use App\UseCases\Investment\AccrueCdiInvestmentsUseCase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('investments:accrue-cdi {--date=}')]
#[Description('Contabiliza o rendimento diário de investimentos vinculados ao CDI')]
class AccrueCdiInvestments extends Command
{
    public function handle(AccrueCdiInvestmentsUseCase $useCase): int
    {
        $processed = $useCase->execute($this->option('date') ?: Carbon::today()->toDateString());
        $this->info("{$processed} investimento(s) processado(s).");

        return self::SUCCESS;
    }
}
