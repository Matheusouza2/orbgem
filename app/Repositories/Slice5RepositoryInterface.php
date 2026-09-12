<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface Slice5RepositoryInterface
{
    public function findConsolidation(int $id): ?Model;

    public function listConsolidations(int $userId): Collection;

    public function createConsolidation(int $userId, string $name, array $walletIds): Model;

    public function updateConsolidation(Model $consolidation, string $name, array $walletIds): Model;

    public function deleteConsolidation(Model $consolidation): void;

    public function findGoal(int $id): ?Model;

    public function listGoals(int $walletId): Collection;

    public function createGoal(array $attributes): Model;

    public function updateGoal(Model $goal, array $attributes): Model;

    public function deleteGoal(Model $goal): void;

    public function createGoalContribution(Model $goal, array $attributes): void;

    public function incrementGoal(Model $goal, int $amount): void;

    public function createTag(array $attributes): Model;

    public function listTags(int $walletId): Collection;

    public function findTransaction(int $id): ?Model;

    public function attachTags(Model $transaction, array $tagIds): Model;

    public function findAttachable(string $type, int $id): ?Model;

    public function listAttachments(Model $attachable): Collection;

    public function createAttachment(array $attributes): Model;

    public function findAttachment(int $id): ?Model;

    public function deleteAttachment(Model $attachment): void;

    public function listNotifications(int $userId): Collection;

    public function findNotification(int $id): ?Model;

    public function markNotificationRead(Model $notification): Model;

    public function findImportBatch(int $id): ?Model;

    public function createImportBatch(array $attributes): Model;

    public function updateImportBatch(Model $batch, array $attributes): Model;

    public function findWalletMember(int $walletId, int $userId): ?Model;

    public function importRowExists(int $walletId, string $hash): bool;

    public function transactionsForWallets(array $walletIds, string $month): Collection;

    public function accountsForWallets(array $walletIds): Collection;
}
