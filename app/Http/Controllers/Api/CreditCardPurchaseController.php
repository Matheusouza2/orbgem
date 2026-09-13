<?php

namespace App\Http\Controllers\Api;

use App\DTO\CreditCardPurchaseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditCard\CreateCreditCardPurchaseRequest;
use App\Http\Requests\CreditCard\UpdateCreditCardInstallmentRequest;
use App\Http\Requests\CreditCard\UpdateCreditCardPurchaseRequest;
use App\Http\Resources\CreditCardPurchaseResource;
use App\Http\Resources\CreditCardTransactionResource;
use App\UseCases\CreditCard\CreateCreditCardPurchaseUseCase;
use App\UseCases\CreditCard\DeleteCreditCardInstallmentUseCase;
use App\UseCases\CreditCard\DeleteCreditCardPurchaseUseCase;
use App\UseCases\CreditCard\UpdateCreditCardInstallmentUseCase;
use App\UseCases\CreditCard\UpdateCreditCardPurchaseUseCase;
use Illuminate\Http\Response;

class CreditCardPurchaseController extends Controller
{
    public function store(CreateCreditCardPurchaseRequest $request, CreateCreditCardPurchaseUseCase $useCase): CreditCardPurchaseResource
    {
        return new CreditCardPurchaseResource($useCase->execute(CreditCardPurchaseDTO::fromArray($request->validated(), 0), $request->user()));
    }

    public function update(UpdateCreditCardPurchaseRequest $request, int $purchase, UpdateCreditCardPurchaseUseCase $useCase): CreditCardPurchaseResource
    {
        return new CreditCardPurchaseResource($useCase->execute($purchase, $request->validated(), $request->user()));
    }

    public function destroy(int $purchase, DeleteCreditCardPurchaseUseCase $useCase): Response
    {
        $useCase->execute($purchase, request()->user());

        return response()->noContent();
    }

    public function updateInstallment(UpdateCreditCardInstallmentRequest $request, int $transaction, UpdateCreditCardInstallmentUseCase $useCase): CreditCardTransactionResource
    {
        return new CreditCardTransactionResource($useCase->execute($transaction, $request->validated(), $request->user()));
    }

    public function destroyInstallment(int $transaction, DeleteCreditCardInstallmentUseCase $useCase): Response
    {
        $useCase->execute($transaction, request()->user());

        return response()->noContent();
    }
}
