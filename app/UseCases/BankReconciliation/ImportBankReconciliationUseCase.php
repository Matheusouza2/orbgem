<?php

namespace App\UseCases\BankReconciliation;

use App\Models\BankReconciliation;
use App\Models\User;
use App\Services\BankReconciliation\BankReconciliationService;

class ImportBankReconciliationUseCase
{
    public function __construct(private BankReconciliationService $service) {}

    /** @param array<int, int> $entryIds
     * @return array{reconciliation: BankReconciliation, imported: int, already_imported: int, skipped: int}
     */
    public function execute(int $reconciliationId, array $entryIds, bool $acknowledgeWarnings, User $user): array
    {
        return $this->service->import($reconciliationId, $entryIds, $acknowledgeWarnings, $user);
    }
}
