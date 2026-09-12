<?php

namespace App\Repositories;

use App\DTO\FinancialCommitmentDTO;
use App\Models\FinancialCommitment;
use Illuminate\Support\Collection;

interface FinancialCommitmentRepositoryInterface
{
    public function create(FinancialCommitmentDTO $dto): FinancialCommitment;

    public function forWallet(int $walletId): Collection;

    public function find(int $id): ?FinancialCommitment;

    public function findForUpdate(int $id): ?FinancialCommitment;

    public function updateProgress(FinancialCommitment $commitment, int $currentInstallment, bool $active): FinancialCommitment;

    public function update(FinancialCommitment $commitment, FinancialCommitmentDTO $dto): FinancialCommitment;

    public function delete(FinancialCommitment $commitment): void;
}
