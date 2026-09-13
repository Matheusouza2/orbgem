<?php

namespace App\Http\Controllers\Api;

use App\DTO\TransactionDTO;
use App\DTO\TransactionListFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CreateTransactionRequest;
use App\Http\Requests\Transaction\ListTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\User;
use App\UseCases\Transaction\CreateTransactionUseCase;
use App\UseCases\Transaction\DeleteTransactionUseCase;
use App\UseCases\Transaction\ListTransactionUseCase;
use App\UseCases\Transaction\UpdateTransactionUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function store(CreateTransactionRequest $request, CreateTransactionUseCase $useCase): TransactionResource
    {
        /** @var User $user */
        $user = $request->user();

        return new TransactionResource($useCase->execute(TransactionDTO::fromArray($request->validated()), $user));
    }

    public function index(ListTransactionRequest $request, ListTransactionUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return TransactionResource::collection($useCase->execute(TransactionListFilterDTO::fromArray($request->validated()), $user));
    }

    public function update(UpdateTransactionRequest $request, int $transaction, UpdateTransactionUseCase $useCase): TransactionResource
    {
        /** @var User $user */
        $user = $request->user();

        return new TransactionResource($useCase->execute($transaction, $request->validated(), $user));
    }

    public function destroy(int $transaction, DeleteTransactionUseCase $useCase): \Illuminate\Http\Response
    {
        /** @var User $user */
        $user = request()->user();
        $useCase->execute($transaction, $user);

        return response()->noContent();
    }
}
