<?php

namespace App\Http\Controllers\Api;

use App\DTO\RecurringTransactionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\CreateRecurringTransactionRequest;
use App\Http\Requests\Planning\GenerateRecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Http\Resources\TransactionResource;
use App\Models\RecurringTransaction;
use App\UseCases\Planning\CreateRecurringTransactionUseCase;
use App\UseCases\Planning\DeleteRecurringTransactionUseCase;
use App\UseCases\Planning\GenerateRecurringTransactionUseCase;
use App\UseCases\Planning\ListRecurringTransactionsUseCase;
use App\UseCases\Planning\UpdateRecurringTransactionUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RecurringTransactionController extends Controller
{
    public function store(CreateRecurringTransactionRequest $request, CreateRecurringTransactionUseCase $useCase): RecurringTransactionResource
    {
        return new RecurringTransactionResource($useCase->execute(RecurringTransactionDTO::fromArray($request->validated()), $request->user()));
    }

    public function index(Request $request, ListRecurringTransactionsUseCase $useCase)
    {
        return RecurringTransactionResource::collection($useCase->execute($request->integer('wallet_id'), $request->user()));
    }

    public function update(CreateRecurringTransactionRequest $request, RecurringTransaction $recurring, UpdateRecurringTransactionUseCase $useCase): RecurringTransactionResource
    {
        return new RecurringTransactionResource($useCase->execute($recurring, RecurringTransactionDTO::fromArray($request->validated()), $request->user()));
    }

    public function destroy(Request $request, RecurringTransaction $recurring, DeleteRecurringTransactionUseCase $useCase): Response
    {
        $useCase->execute($recurring, $request->user());

        return response()->noContent();
    }

    public function generate(GenerateRecurringTransactionRequest $request, int $recurring, GenerateRecurringTransactionUseCase $useCase)
    {
        return ['data' => TransactionResource::collection($useCase->execute($recurring, $request->validated()['until'], $request->user()))];
    }
}
