<?php

namespace App\Http\Controllers\Api;

use App\DTO\Slice5DTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Slice5\AttachmentRequest;
use App\Http\Requests\Slice5\ConsolidationRequest;
use App\Http\Requests\Slice5\FinancialGoalRequest;
use App\Http\Requests\Slice5\GenericAttachmentRequest;
use App\Http\Requests\Slice5\GoalContributionRequest;
use App\Http\Requests\Slice5\ImportRequest;
use App\Http\Requests\Slice5\Slice5ActionRequest;
use App\Http\Requests\Slice5\Slice5QueryRequest;
use App\Http\Requests\Slice5\TagRequest;
use App\Http\Requests\Slice5\TransactionTagsRequest;
use App\Http\Requests\Slice5\UpdateConsolidationRequest;
use App\Http\Requests\Slice5\UpdateFinancialGoalRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ConsolidationResource;
use App\Http\Resources\FinancialGoalResource;
use App\Http\Resources\ImportBatchResource;
use App\Http\Resources\InAppNotificationResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TransactionResource;
use App\UseCases\Slice5UseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class Slice5Controller extends Controller
{
    public function createConsolidation(ConsolidationRequest $request, Slice5UseCase $useCase): ConsolidationResource
    {
        return new ConsolidationResource($useCase->consolidation(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function listConsolidations(Slice5QueryRequest $request, Slice5UseCase $useCase): mixed
    {
        return ConsolidationResource::collection($useCase->listConsolidations(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function updateConsolidation(UpdateConsolidationRequest $request, int $consolidation, Slice5UseCase $useCase): ConsolidationResource
    {
        $data = $request->validated();
        $data['record_id'] = $consolidation;

        return new ConsolidationResource($useCase->updateConsolidation(Slice5DTO::fromArray($data, (int) $request->user()->getAuthIdentifier())));
    }

    public function deleteConsolidation(Slice5ActionRequest $request, int $consolidation, Slice5UseCase $useCase): Response
    {
        $useCase->deleteConsolidation(Slice5DTO::fromArray(['record_id' => $consolidation], (int) $request->user()->getAuthIdentifier()));

        return response()->noContent();
    }

    public function consolidationSummary(Slice5QueryRequest $request, int $consolidation, Slice5UseCase $useCase): JsonResponse
    {
        $data = $request->validated();
        $data['record_id'] = $consolidation;

        return response()->json(['data' => $useCase->consolidationSummary(Slice5DTO::fromArray($data, (int) $request->user()->getAuthIdentifier()))]);
    }

    public function createGoal(FinancialGoalRequest $request, Slice5UseCase $useCase): FinancialGoalResource
    {
        return new FinancialGoalResource($useCase->goal(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function listGoals(Slice5QueryRequest $request, Slice5UseCase $useCase): mixed
    {
        return FinancialGoalResource::collection($useCase->goals(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function updateGoal(UpdateFinancialGoalRequest $request, int $goal, Slice5UseCase $useCase): FinancialGoalResource
    {
        $data = $request->validated();
        $data['record_id'] = $goal;

        return new FinancialGoalResource($useCase->updateGoal(Slice5DTO::fromArray($data, (int) $request->user()->getAuthIdentifier())));
    }

    public function deleteGoal(Slice5ActionRequest $request, int $goal, Slice5UseCase $useCase): Response
    {
        $useCase->deleteGoal(Slice5DTO::fromArray(['record_id' => $goal], (int) $request->user()->getAuthIdentifier()));

        return response()->noContent();
    }

    public function contributeToGoal(GoalContributionRequest $request, int $goal, Slice5UseCase $useCase): FinancialGoalResource
    {
        $data = $request->validated();
        $data['record_id'] = $goal;

        return new FinancialGoalResource($useCase->contribute(Slice5DTO::fromArray($data, (int) $request->user()->getAuthIdentifier())));
    }

    public function createTag(TagRequest $request, Slice5UseCase $useCase): TagResource
    {
        return new TagResource($useCase->tag(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function listTags(Slice5QueryRequest $request, Slice5UseCase $useCase): mixed
    {
        return TagResource::collection($useCase->tags(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function tagTransaction(TransactionTagsRequest $request, int $transaction, Slice5UseCase $useCase): TransactionResource
    {
        $data = $request->validated();
        $data['record_id'] = $transaction;

        return new TransactionResource($useCase->transactionTags(Slice5DTO::fromArray($data, (int) $request->user()->getAuthIdentifier())));
    }

    public function listNotifications(Slice5QueryRequest $request, Slice5UseCase $useCase): mixed
    {
        return InAppNotificationResource::collection($useCase->notifications(Slice5DTO::fromArray([], (int) $request->user()->getAuthIdentifier())));
    }

    public function readNotification(Slice5ActionRequest $request, int $notification, Slice5UseCase $useCase): InAppNotificationResource
    {
        return new InAppNotificationResource($useCase->readNotification(Slice5DTO::fromArray(['record_id' => $notification], (int) $request->user()->getAuthIdentifier())));
    }

    public function uploadAttachment(AttachmentRequest $request, int $transaction, Slice5UseCase $useCase): AttachmentResource
    {
        $data = $request->validated();
        $data['attachable_type'] = 'transaction';
        $data['attachable_id'] = $transaction;

        return new AttachmentResource($useCase->uploadAttachment(Slice5DTO::fromArray($data, (int) $request->user()->getAuthIdentifier())));
    }

    public function listAttachments(Slice5QueryRequest $request, int $transaction, Slice5UseCase $useCase): mixed
    {
        return AttachmentResource::collection($useCase->attachments(Slice5DTO::fromArray(['record_id' => $transaction], (int) $request->user()->getAuthIdentifier())));
    }

    public function uploadGenericAttachment(GenericAttachmentRequest $request, Slice5UseCase $useCase): AttachmentResource
    {
        return new AttachmentResource($useCase->uploadAttachment(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function listGenericAttachments(Slice5QueryRequest $request, Slice5UseCase $useCase): mixed
    {
        return AttachmentResource::collection($useCase->genericAttachments(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier())));
    }

    public function downloadAttachment(Slice5ActionRequest $request, int $attachment, Slice5UseCase $useCase): object
    {
        return $useCase->downloadAttachment(Slice5DTO::fromArray(['record_id' => $attachment], (int) $request->user()->getAuthIdentifier()));
    }

    public function deleteAttachment(Slice5ActionRequest $request, int $attachment, Slice5UseCase $useCase): Response
    {
        $useCase->deleteAttachment(Slice5DTO::fromArray(['record_id' => $attachment], (int) $request->user()->getAuthIdentifier()));

        return response()->noContent();
    }

    public function import(ImportRequest $request, Slice5UseCase $useCase): JsonResponse
    {
        $batch = $useCase->import(Slice5DTO::fromArray($request->validated(), (int) $request->user()->getAuthIdentifier()));

        return (new ImportBatchResource($batch))->response()->setStatusCode(202);
    }

    public function importBatch(Slice5ActionRequest $request, int $import, Slice5UseCase $useCase): ImportBatchResource
    {
        return new ImportBatchResource($useCase->importBatch(Slice5DTO::fromArray(['record_id' => $import], (int) $request->user()->getAuthIdentifier())));
    }
}
