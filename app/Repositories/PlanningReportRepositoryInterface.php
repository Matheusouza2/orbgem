<?php

namespace App\Repositories;

interface PlanningReportRepositoryInterface
{
    /** @return array<string, mixed> */
    public function summary(int $walletId, string $month, bool $includeThirdParty = true): array;
}
