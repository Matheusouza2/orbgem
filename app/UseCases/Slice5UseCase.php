<?php

namespace App\UseCases;

use App\DTO\Slice5DTO;
use App\Enums\WalletMemberRole;
use App\Services\Slice5Service;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class Slice5UseCase
{
    public function __construct(
        private Slice5Service $service,
        private WalletService $wallets,
        private WalletMembershipAuthorization $auth,
    ) {}

    public function consolidation(Slice5DTO $dto): object
    {
        $this->authorizeWallets($dto, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->service->createConsolidation($dto);
    }

    public function listConsolidations(Slice5DTO $dto): Collection
    {
        return $this->service->listConsolidations($dto);
    }

    public function updateConsolidation(Slice5DTO $dto): object
    {
        $consolidation = $this->service->findConsolidation($dto->recordId);
        if ($consolidation === null || $consolidation->user_id !== $dto->userId) {
            throw new AuthorizationException;
        }
        $this->authorizeWallets($dto, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->service->updateConsolidation($dto, $consolidation);
    }

    public function deleteConsolidation(Slice5DTO $dto): void
    {
        $consolidation = $this->service->findConsolidation($dto->recordId);
        if ($consolidation === null || $consolidation->user_id !== $dto->userId) {
            throw new AuthorizationException;
        }
        $this->service->deleteConsolidation($consolidation);
    }

    /** @return array<string, mixed> */
    public function consolidationSummary(Slice5DTO $dto): array
    {
        $consolidation = $this->service->findConsolidation($dto->recordId);
        if ($consolidation === null || $consolidation->user_id !== $dto->userId) {
            throw new AuthorizationException;
        }

        return $this->service->consolidationSummary($consolidation, $dto->month);
    }

    public function goal(Slice5DTO $dto): object
    {
        $this->walletWrite($dto);

        return $this->service->createGoal($dto);
    }

    public function goals(Slice5DTO $dto): Collection
    {
        $this->walletRead($dto);

        return $this->service->listGoals($dto);
    }

    public function updateGoal(Slice5DTO $dto): object
    {
        $goal = $this->service->findGoal($dto->recordId);
        if ($goal === null) {
            throw new AuthorizationException;
        }
        $this->walletWrite($dto->withWallet((int) $goal->wallet_id));

        return $this->service->updateGoal($dto, $goal);
    }

    public function deleteGoal(Slice5DTO $dto): void
    {
        $goal = $this->service->findGoal($dto->recordId);
        if ($goal === null) {
            throw new AuthorizationException;
        }
        $this->walletWrite($dto->withWallet((int) $goal->wallet_id));
        $this->service->deleteGoal($goal);
    }

    public function contribute(Slice5DTO $dto): object
    {
        $goal = $this->service->findGoal($dto->recordId);
        if ($goal === null) {
            throw new AuthorizationException;
        }
        $this->walletWrite($dto->withWallet((int) $goal->wallet_id));

        return $this->service->contributeToGoal($dto, $goal);
    }

    public function tag(Slice5DTO $dto): object
    {
        $this->walletWrite($dto);

        return $this->service->createTag($dto);
    }

    public function tags(Slice5DTO $dto): Collection
    {
        $this->walletRead($dto);

        return $this->service->listTags($dto);
    }

    public function transactionTags(Slice5DTO $dto): object
    {
        $transaction = $this->service->findTransaction($dto->recordId);
        if ($transaction === null) {
            throw new AuthorizationException;
        }
        $this->walletWrite($dto->withWallet((int) $transaction->wallet_id));

        return $this->service->attachTags($transaction, $dto->tagIds);
    }

    public function notifications(Slice5DTO $dto): Collection
    {
        return $this->service->notifications($dto);
    }

    public function readNotification(Slice5DTO $dto): object
    {
        $notification = $this->service->findNotification($dto->recordId);
        if ($notification === null || (int) $notification->user_id !== $dto->userId) {
            throw new AuthorizationException;
        }

        return $this->service->markNotificationRead($notification);
    }

    public function attachments(Slice5DTO $dto): Collection
    {
        $transaction = $this->service->findTransaction($dto->recordId);
        if ($transaction === null) {
            throw new AuthorizationException;
        }
        $this->walletRead($dto->withWallet((int) $transaction->wallet_id));

        return $this->service->attachments($transaction);
    }

    public function uploadAttachment(Slice5DTO $dto): object
    {
        $attachable = $this->service->findAttachable($dto->attachableType, $dto->attachableId);
        if ($attachable === null || (int) $attachable->wallet_id !== $dto->walletId) {
            throw new AuthorizationException;
        }
        $this->walletWrite($dto);
        $member = $this->service->findMember($dto->walletId, $dto->userId);

        return $this->service->uploadAttachment($dto->withMember($member->id), $attachable);
    }

    public function genericAttachments(Slice5DTO $dto): Collection
    {
        $attachable = $this->service->findAttachable($dto->attachableType, $dto->attachableId);
        if ($attachable === null) {
            throw new AuthorizationException;
        }
        $this->walletRead($dto->withWallet((int) $attachable->wallet_id));

        return $this->service->attachments($attachable);
    }

    public function downloadAttachment(Slice5DTO $dto): object
    {
        $attachment = $this->service->findAttachment($dto->recordId);
        if ($attachment === null) {
            throw new AuthorizationException;
        }
        $this->walletRead($dto->withWallet((int) $attachment->wallet_id));

        return $this->service->downloadAttachment($attachment);
    }

    public function deleteAttachment(Slice5DTO $dto): void
    {
        $attachment = $this->service->findAttachment($dto->recordId);
        if ($attachment === null) {
            throw new AuthorizationException;
        }
        $this->walletWrite($dto->withWallet((int) $attachment->wallet_id));
        $this->service->deleteAttachment($attachment);
    }

    public function import(Slice5DTO $dto): object
    {
        $this->walletWrite($dto);

        return $this->service->queueImport($dto);
    }

    public function importBatch(Slice5DTO $dto): object
    {
        $batch = $this->service->findImportBatch($dto->recordId);
        if ($batch === null) {
            throw new AuthorizationException;
        }
        $this->walletRead($dto->withWallet((int) $batch->wallet_id));

        return $batch;
    }

    private function authorizeWallets(Slice5DTO $dto, WalletMemberRole ...$roles): void
    {
        $user = $this->wallets->findUser($dto->userId);
        if ($user === null) {
            throw new AuthorizationException;
        }
        foreach ($dto->walletIds as $walletId) {
            $wallet = $this->wallets->find($walletId);
            if ($wallet === null) {
                throw new AuthorizationException;
            }
            $this->auth->authorize($user, $wallet, ...$roles);
        }
    }

    private function walletWrite(Slice5DTO $dto): void
    {
        $this->authorizeWallet($dto, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
    }

    private function walletRead(Slice5DTO $dto): void
    {
        $this->authorizeWallet($dto, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
    }

    private function authorizeWallet(Slice5DTO $dto, WalletMemberRole ...$roles): void
    {
        $user = $this->wallets->findUser($dto->userId);
        $wallet = $dto->walletId === null ? null : $this->wallets->find($dto->walletId);
        if ($user === null || $wallet === null) {
            throw new AuthorizationException;
        }
        $this->auth->authorize($user, $wallet, ...$roles);
    }
}
