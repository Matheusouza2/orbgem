<?php

namespace App\UseCases\BankReconciliation;

use App\Models\BankReconciliation;
use App\Models\User;
use App\Services\BankReconciliation\BankReconciliationService;

class ShowBankReconciliationUseCase
{
    public function __construct(private BankReconciliationService $service) {}

    public function execute(int $reconciliationId, User $user): BankReconciliation
    {
        return $this->service->findAuthorized($reconciliationId, $user);
    }
}
