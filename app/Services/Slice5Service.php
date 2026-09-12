<?php

namespace App\Services;

use App\DTO\Slice5DTO;
use App\DTO\TransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\WalletActivityOccurred;
use App\Jobs\ProcessImportBatch;
use App\Repositories\Slice5RepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Slice5Service
{
    public function __construct(
        private Slice5RepositoryInterface $repository,
        private TransactionService $transactions,
        private PlanningReportService $planningReports,
    ) {}

    public function queueImport(Slice5DTO $dto): object
    {
        $disk = config('filesystems.default');
        $path = $dto->file->store('imports/'.$dto->walletId, $disk);
        $batch = $this->repository->createImportBatch(['wallet_id' => $dto->walletId, 'user_id' => $dto->userId, 'original_name' => $dto->file->getClientOriginalName(), 'disk' => $disk, 'path' => $path, 'status' => 'QUEUED']);
        ProcessImportBatch::dispatch($batch->id)->afterCommit();

        return $batch;
    }

    public function createConsolidation(Slice5DTO $dto): object
    {
        $consolidation = $this->repository->createConsolidation($dto->userId, $dto->name, $dto->walletIds);
        foreach ($dto->walletIds as $walletId) {
            $this->notifyWallet($walletId, 'CONSOLIDATION_UPDATED', 'Consolidação atualizada', 'A carteira foi adicionada à consolidação '.$dto->name.'.', ['consolidation_id' => $consolidation->id]);
        }

        return $consolidation;
    }

    public function listConsolidations(Slice5DTO $dto): Collection
    {
        return $this->repository->listConsolidations($dto->userId);
    }

    public function findConsolidation(int $id): ?object
    {
        return $this->repository->findConsolidation($id);
    }

    public function updateConsolidation(Slice5DTO $dto, object $consolidation): object
    {
        return $this->repository->updateConsolidation($consolidation, $dto->name, $dto->walletIds);
    }

    public function deleteConsolidation(object $consolidation): void
    {
        $this->repository->deleteConsolidation($consolidation);
    }

    /** @return array<string, mixed> */
    public function consolidationSummary(object $consolidation, string $month): array
    {
        $walletIds = $consolidation->wallets->pluck('id')->all();
        $rows = $this->repository->transactionsForWallets($walletIds, $month);

        return ['consolidation_id' => $consolidation->id, 'month' => $month, 'wallet_ids' => collect($walletIds)->values(), 'balance' => $this->walletBalance($walletIds), 'actual_income' => (int) $rows->where('type', TransactionType::INCOME)->where('status', TransactionStatus::POSTED)->sum('amount'), 'forecast_income' => (int) $rows->where('type', TransactionType::INCOME)->sum('amount'), 'actual_expenses' => (int) $rows->where('type', TransactionType::EXPENSE)->where('status', TransactionStatus::POSTED)->sum('amount'), 'forecast_expenses' => (int) $rows->where('type', TransactionType::EXPENSE)->sum('amount'), ...$this->planningReports->consolidated(collect($walletIds), $month)];
    }

    public function createGoal(Slice5DTO $dto): object
    {
        return $this->repository->createGoal($dto->toArray());
    }

    public function findGoal(int $id): ?object
    {
        return $this->repository->findGoal($id);
    }

    public function listGoals(Slice5DTO $dto): Collection
    {
        return $this->repository->listGoals($dto->walletId);
    }

    public function updateGoal(Slice5DTO $dto, object $goal): object
    {
        $attributes = $dto->toArray();
        unset($attributes['record_id']);

        return $this->repository->updateGoal($goal, $attributes);
    }

    public function deleteGoal(object $goal): void
    {
        $this->repository->deleteGoal($goal);
    }

    public function contributeToGoal(Slice5DTO $dto, object $goal): object
    {
        $amount = (int) $dto->attributes['amount'];
        $this->repository->createGoalContribution($goal, ['financial_goal_id' => $goal->id, 'amount' => $amount, 'contributed_at' => $dto->attributes['contributed_at'] ?? now()->toDateString(), 'note' => $dto->attributes['note'] ?? null, 'created_by_user_id' => $dto->userId]);
        $this->repository->incrementGoal($goal, $amount);
        $this->notifyWallet($goal->wallet_id, 'GOAL_CONTRIBUTION', 'Meta atualizada', 'Novo aporte em '.$goal->name, ['goal_id' => $goal->id, 'amount' => $amount]);

        return $this->repository->findGoal($goal->id);
    }

    public function createTag(Slice5DTO $dto): object
    {
        return $this->repository->createTag($dto->toArray());
    }

    public function listTags(Slice5DTO $dto): Collection
    {
        return $this->repository->listTags($dto->walletId);
    }

    public function attachTags(object $transaction, array $tagIds): object
    {
        return $this->repository->attachTags($transaction, $tagIds);
    }

    public function findTransaction(int $id): ?object
    {
        return $this->repository->findTransaction($id);
    }

    public function attachments(object $transaction): Collection
    {
        return $this->repository->listAttachments($transaction);
    }

    public function findAttachable(string $type, int $id): ?object
    {
        return $this->repository->findAttachable($type, $id);
    }

    public function findAttachment(int $id): ?object
    {
        return $this->repository->findAttachment($id);
    }

    public function findNotification(int $id): ?object
    {
        return $this->repository->findNotification($id);
    }

    public function findImportBatch(int $id): ?object
    {
        return $this->repository->findImportBatch($id);
    }

    public function findMember(int $walletId, int $userId): ?object
    {
        return $this->repository->findWalletMember($walletId, $userId);
    }

    public function uploadAttachment(Slice5DTO $dto, object $attachable): object
    {
        $path = $dto->file->store('attachments/'.$dto->walletId);
        $attachment = $this->repository->createAttachment(['wallet_id' => $dto->walletId, 'attachable_type' => $attachable->getMorphClass(), 'attachable_id' => $attachable->getKey(), 'disk' => config('filesystems.default'), 'path' => $path, 'original_name' => $dto->file->getClientOriginalName(), 'mime_type' => $dto->file->getMimeType(), 'size' => $dto->file->getSize(), 'uploaded_by_member_id' => $dto->memberId]);
        $this->notifyWallet($dto->walletId, 'ATTACHMENT_ADDED', 'Anexo adicionado', 'Um anexo foi adicionado a uma entidade.', ['attachment_id' => $attachment->id, 'attachable_type' => $attachable->getMorphClass(), 'attachable_id' => $attachable->getKey()]);

        return $attachment;
    }

    public function downloadAttachment(object $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function deleteAttachment(object $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $this->repository->deleteAttachment($attachment);
    }

    public function notifications(Slice5DTO $dto): Collection
    {
        return $this->repository->listNotifications($dto->userId);
    }

    public function markNotificationRead(object $notification): object
    {
        return $this->repository->markNotificationRead($notification);
    }

    public function notifyWallet(int $walletId, string $type, string $title, ?string $body, array $data = []): void
    {
        WalletActivityOccurred::dispatch($walletId, $type, $title, $body, $data);
    }

    public function processImport(object $batch): void
    {
        $this->repository->updateImportBatch($batch, ['status' => 'PROCESSING']);
        $member = $this->repository->findWalletMember($batch->wallet_id, $batch->user_id);
        if ($member === null) {
            $this->repository->updateImportBatch($batch, ['status' => 'FAILED', 'errors' => [['row' => 0, 'message' => 'Importing user is not a wallet member.']]]);

            return;
        }

        $handle = Storage::disk($batch->disk)->readStream($batch->path);
        $errors = [];
        $total = 0;
        $imported = 0;
        if ($handle !== false) {
            $header = array_map(fn ($value): string => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), fgetcsv($handle) ?: []);
            $required = ['description', 'type', 'amount', 'transaction_date'];
            if (array_diff($required, $header) !== []) {
                $this->repository->updateImportBatch($batch, ['status' => 'FAILED', 'errors' => [['row' => 1, 'message' => 'CSV header must contain: '.implode(', ', $required)]], 'failed_rows' => 1]);
                fclose($handle);

                return;
            }
            $positions = array_flip($header);
            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn ($value): bool => $value !== null && $value !== '')) === 0) {
                    continue;
                }
                $total++;
                try {
                    $value = fn (string $key): ?string => isset($positions[$key]) && array_key_exists($positions[$key], $row) ? trim((string) $row[$positions[$key]]) : null;
                    $hash = hash('sha256', $batch->wallet_id.'|'.implode('|', $row));
                    if ($this->repository->importRowExists($batch->wallet_id, $hash)) {
                        continue;
                    }
                    $type = TransactionType::from(strtoupper((string) $value('type')));
                    if ($type === TransactionType::TRANSFER) {
                        throw new \InvalidArgumentException('CSV imports cannot create transfers; import each ledger leg explicitly.');
                    }
                    $this->transactions->create(TransactionDTO::fromArray(['wallet_id' => $batch->wallet_id, 'account_id' => $value('account_id') ? (int) $value('account_id') : null, 'category_id' => $value('category_id') ? (int) $value('category_id') : null, 'description' => (string) $value('description'), 'type' => $type->value, 'effect' => $type === TransactionType::INCOME ? TransactionEffect::CREDIT->value : TransactionEffect::DEBIT->value, 'amount' => (int) $value('amount'), 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value, 'transaction_date' => $value('transaction_date'), 'competence_date' => $value('competence_date') ?: $value('transaction_date'), 'status' => TransactionStatus::POSTED->value, 'import_batch_id' => $batch->id, 'import_row_hash' => $hash], $member->id));
                    $imported++;
                } catch (\Throwable $exception) {
                    $errors[] = ['row' => $total + 1, 'message' => $exception->getMessage()];
                }
            }
            fclose($handle);
        }
        $this->repository->updateImportBatch($batch, ['status' => count($errors) === 0 ? 'COMPLETED' : 'COMPLETED_WITH_ERRORS', 'total_rows' => $total, 'imported_rows' => $imported, 'failed_rows' => count($errors), 'errors' => $errors]);
        $this->notifyWallet($batch->wallet_id, 'IMPORT_COMPLETED', 'Importação concluída', 'O lote '.$batch->id.' foi processado.', ['import_batch_id' => $batch->id, 'imported_rows' => $imported, 'failed_rows' => count($errors)]);
    }

    private function walletBalance(array $walletIds): int
    {
        return (int) $this->repository->accountsForWallets($walletIds)->sum(fn ($account): int => $account->initial_balance + $this->transactions->postedAmountsForAccount($account->id)->sum(fn ($transaction): int => $transaction->effect === TransactionEffect::CREDIT ? $transaction->amount : ($transaction->effect === TransactionEffect::DEBIT ? -$transaction->amount : 0)));
    }
}
