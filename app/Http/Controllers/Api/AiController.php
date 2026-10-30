<?php

namespace App\Http\Controllers\Api;

use App\DTO\AiChatDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiChatRequest;
use App\Http\Requests\Ai\AiSummaryRequest;
use App\Http\Resources\FinancialSummaryResource;
use App\Models\User;
use App\UseCases\Ai\AskFinancialAssistantUseCase;
use App\UseCases\Ai\GetFinancialSummaryUseCase;
use App\UseCases\Ai\RefreshFinancialSummaryUseCase;
use Illuminate\Http\JsonResponse;

class AiController extends Controller
{
    public function summary(AiSummaryRequest $request, GetFinancialSummaryUseCase $useCase): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return (new FinancialSummaryResource($useCase->execute((int) $request->validated('wallet_id'), $user)))->response()->setStatusCode(200);
    }

    public function refresh(AiSummaryRequest $request, RefreshFinancialSummaryUseCase $useCase): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $useCase->execute((int) $request->validated('wallet_id'), $user);

        return response()->json(['status' => 'queued'], 202);
    }

    public function chat(AiChatRequest $request, AskFinancialAssistantUseCase $useCase): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $useCase->execute(AiChatDTO::fromArray($request->validated()), $user)]);
    }
}
