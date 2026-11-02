<?php

namespace App\UseCases\BankReconciliation;

use App\Models\BankReconciliation;
use App\Models\User;
use App\Services\BankReconciliation\BankReconciliationService;
use Illuminate\Http\UploadedFile;

class PreviewBankReconciliationUseCase
{
    public function __construct(private BankReconciliationService $service) {}

    public function execute(int $accountId, string $month, UploadedFile $file, User $user): BankReconciliation
    {
        return $this->service->preview($accountId, $month, $file, $user);
    }
}
