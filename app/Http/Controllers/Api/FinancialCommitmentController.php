<?php

namespace App\Http\Controllers\Api;

use App\DTO\FinancialCommitmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\CreateFinancialCommitmentRequest;
use App\Http\Requests\Planning\GenerateFinancialCommitmentRequest;
use App\Http\Resources\FinancialCommitmentResource;
use App\Http\Resources\TransactionResource;
use App\Models\FinancialCommitment;
use App\UseCases\Planning\CreateFinancialCommitmentUseCase;
use App\UseCases\Planning\DeleteFinancialCommitmentUseCase;
use App\UseCases\Planning\GenerateFinancialCommitmentUseCase;
use App\UseCases\Planning\ListFinancialCommitmentsUseCase;
use App\UseCases\Planning\UpdateFinancialCommitmentUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FinancialCommitmentController extends Controller
{
    public function store(CreateFinancialCommitmentRequest $request, CreateFinancialCommitmentUseCase $useCase): FinancialCommitmentResource
    {
        return new FinancialCommitmentResource($useCase->execute(FinancialCommitmentDTO::fromArray($request->validated()), $request->user()));
    }

    public function index(Request $request, ListFinancialCommitmentsUseCase $useCase)
    {
        return FinancialCommitmentResource::collection($useCase->execute($request->integer('wallet_id'), $request->user()));
    }

    public function update(CreateFinancialCommitmentRequest $request, FinancialCommitment $commitment, UpdateFinancialCommitmentUseCase $useCase): FinancialCommitmentResource
    {
        return new FinancialCommitmentResource($useCase->execute($commitment, FinancialCommitmentDTO::fromArray($request->validated()), $request->user()));
    }

    public function destroy(Request $request, FinancialCommitment $commitment, DeleteFinancialCommitmentUseCase $useCase): Response
    {
        $useCase->execute($commitment, $request->user());

        return response()->noContent();
    }

    public function generate(GenerateFinancialCommitmentRequest $request, int $commitment, GenerateFinancialCommitmentUseCase $useCase)
    {
        return ['data' => TransactionResource::collection($useCase->execute($commitment, $request->validated()['until'], $request->user()))];
    }
}
