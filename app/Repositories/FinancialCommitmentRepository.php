<?php

namespace App\Repositories;

use App\DTO\FinancialCommitmentDTO;
use App\Models\FinancialCommitment;
use Illuminate\Support\Collection;

class FinancialCommitmentRepository implements FinancialCommitmentRepositoryInterface
{
    public function create(FinancialCommitmentDTO $dto): FinancialCommitment
    {
        return FinancialCommitment::query()->create($dto->toArray());
    }

    public function forWallet(int $walletId): Collection
    {
        return FinancialCommitment::query()->where('wallet_id', $walletId)->latest()->get();
    }

    public function find(int $id): ?FinancialCommitment
    {
        return FinancialCommitment::query()->find($id);
    }

    public function findForUpdate(int $id): ?FinancialCommitment
    {
        return FinancialCommitment::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function updateProgress(FinancialCommitment $commitment, int $currentInstallment, bool $active): FinancialCommitment
    {
        $commitment->forceFill([
            'current_installment' => $currentInstallment,
            'active' => $active,
        ])->save();

        return $commitment->refresh();
    }

    public function update(FinancialCommitment $commitment, FinancialCommitmentDTO $dto): FinancialCommitment
    {
        $commitment->update($dto->toArray());

        return $commitment->refresh();
    }

    public function delete(FinancialCommitment $commitment): void
    {
        $commitment->delete();
    }
}
