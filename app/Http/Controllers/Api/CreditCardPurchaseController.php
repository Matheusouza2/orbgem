<?php

namespace App\Http\Controllers\Api;

use App\DTO\CreditCardPurchaseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditCard\CreateCreditCardPurchaseRequest;
use App\Http\Resources\CreditCardPurchaseResource;
use App\UseCases\CreditCard\CreateCreditCardPurchaseUseCase;

class CreditCardPurchaseController extends Controller
{
    public function store(CreateCreditCardPurchaseRequest $request, CreateCreditCardPurchaseUseCase $useCase): CreditCardPurchaseResource
    {
        return new CreditCardPurchaseResource($useCase->execute(CreditCardPurchaseDTO::fromArray($request->validated(), 0), $request->user()));
    }
}
