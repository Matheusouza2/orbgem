<?php

namespace App\Console\Commands;

use App\Models\FinancialCommitment;
use App\Services\FinancialCommitmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('planning:generate-commitments')]
#[Description('Command description')]
class GenerateFinancialCommitments extends Command
{
    public function handle(FinancialCommitmentService $service): int
    {
        $until = Carbon::today()->addMonths(12)->toDateString();
        FinancialCommitment::query()->with('wallet.ownerMemberships')->where('active', true)->each(function (FinancialCommitment $commitment) use ($service, $until): void {
            $member = $commitment->wallet->ownerMemberships->first();
            if ($member !== null) {
                $service->generate($commitment, $until, $member->id);
            }
        });

        return self::SUCCESS;
    }
}
