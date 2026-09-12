<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\Budget;
use App\Models\Consolidation;
use App\Models\CreditCard;
use App\Models\FinancialCommitment;
use App\Models\FinancialGoal;
use App\Models\FinancialGoalContribution;
use App\Models\ImportBatch;
use App\Models\InAppNotification;
use App\Models\RecurringTransaction;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\WalletMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Slice5Repository implements Slice5RepositoryInterface
{
    public function findConsolidation(int $id): ?Model
    {
        return Consolidation::query()->with('wallets')->find($id);
    }

    public function listConsolidations(int $userId): Collection
    {
        return Consolidation::query()->with('wallets')->where('user_id', $userId)->latest()->get();
    }

    public function createConsolidation(int $userId, string $name, array $walletIds): Model
    {
        $consolidation = Consolidation::query()->create(['user_id' => $userId, 'name' => $name]);
        $consolidation->wallets()->sync($walletIds);

        return $consolidation->load('wallets');
    }

    public function updateConsolidation(Model $consolidation, string $name, array $walletIds): Model
    {
        $consolidation->update(['name' => $name]);
        $consolidation->wallets()->sync($walletIds);

        return $consolidation->refresh()->load('wallets');
    }

    public function deleteConsolidation(Model $consolidation): void
    {
        $consolidation->delete();
    }

    public function findGoal(int $id): ?Model
    {
        return FinancialGoal::query()->with('contributions')->find($id);
    }

    public function listGoals(int $walletId): Collection
    {
        return FinancialGoal::query()->with('contributions')->where('wallet_id', $walletId)->latest()->get();
    }

    public function createGoal(array $attributes): Model
    {
        return FinancialGoal::query()->create($attributes)->load('contributions');
    }

    public function updateGoal(Model $goal, array $attributes): Model
    {
        $goal->update($attributes);

        return $goal->refresh()->load('contributions');
    }

    public function deleteGoal(Model $goal): void
    {
        $goal->delete();
    }

    public function createGoalContribution(Model $goal, array $attributes): void
    {
        FinancialGoalContribution::query()->create($attributes);
    }

    public function incrementGoal(Model $goal, int $amount): void
    {
        $goal->increment('current_amount', $amount);
    }

    public function createTag(array $attributes): Model
    {
        return Tag::query()->create($attributes);
    }

    public function listTags(int $walletId): Collection
    {
        return Tag::query()->where('wallet_id', $walletId)->orderBy('name')->get();
    }

    public function findTransaction(int $id): ?Model
    {
        return Transaction::query()->find($id);
    }

    public function attachTags(Model $transaction, array $tagIds): Model
    {
        $transaction->tags()->syncWithoutDetaching($tagIds);

        return $transaction->load('tags');
    }

    public function findAttachable(string $type, int $id): ?Model
    {
        $models = [
            'transaction' => Transaction::class,
            'financial_goal' => FinancialGoal::class,
            'financial_commitment' => FinancialCommitment::class,
            'recurring_transaction' => RecurringTransaction::class,
            'budget' => Budget::class,
            'import_batch' => ImportBatch::class,
            'credit_card' => CreditCard::class,
        ];

        return isset($models[$type]) ? $models[$type]::query()->find($id) : null;
    }

    public function listAttachments(Model $attachable): Collection
    {
        return Attachment::query()
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())
            ->latest()
            ->get();
    }

    public function createAttachment(array $attributes): Model
    {
        return Attachment::query()->create($attributes);
    }

    public function findAttachment(int $id): ?Model
    {
        return Attachment::query()->find($id);
    }

    public function deleteAttachment(Model $attachment): void
    {
        $attachment->delete();
    }

    public function listNotifications(int $userId): Collection
    {
        return InAppNotification::query()->where('user_id', $userId)->latest()->get();
    }

    public function findNotification(int $id): ?Model
    {
        return InAppNotification::query()->find($id);
    }

    public function markNotificationRead(Model $notification): Model
    {
        $notification->forceFill(['read_at' => now()])->save();

        return $notification->refresh();
    }

    public function findImportBatch(int $id): ?Model
    {
        return ImportBatch::query()->find($id);
    }

    public function createImportBatch(array $attributes): Model
    {
        return ImportBatch::query()->create($attributes);
    }

    public function updateImportBatch(Model $batch, array $attributes): Model
    {
        $batch->forceFill($attributes)->save();

        return $batch->refresh();
    }

    public function findWalletMember(int $walletId, int $userId): ?Model
    {
        return WalletMember::query()->where('wallet_id', $walletId)->where('user_id', $userId)->first();
    }

    public function importRowExists(int $walletId, string $hash): bool
    {
        return Transaction::query()->where('wallet_id', $walletId)->where('import_row_hash', $hash)->exists();
    }

    public function transactionsForWallets(array $walletIds, string $month): Collection
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

        return Transaction::query()
            ->includedInTotals()
            ->whereIn('wallet_id', $walletIds)
            ->whereBetween('competence_date', [$start->toDateString(), $start->copy()->addMonth()->subDay()->toDateString()])
            ->get();
    }

    public function accountsForWallets(array $walletIds): Collection
    {
        return Account::query()->whereIn('wallet_id', $walletIds)->get();
    }
}
