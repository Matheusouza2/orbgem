<?php

namespace App\Http\Controllers\Api;

use App\DTO\TransactionReversalDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ReverseTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\User;
use App\UseCases\Transaction\ReverseTransactionUseCase;

class TransactionReversalController extends Controller
{
    public function store(ReverseTransactionRequest $request, int $transaction, ReverseTransactionUseCase $useCase): TransactionResource
    {
        /** @var User $user */
        $user = $request->user();

        return new TransactionResource($useCase->execute($transaction, TransactionReversalDTO::fromArray($request->validated()), $user));
    }
}
