<?php

namespace App\Http\Controllers\Api;

use App\DTO\CreditCardDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditCard\CreateCreditCardRequest;
use App\Http\Resources\CreditCardResource;
use App\Models\CreditCard;
use App\UseCases\CreditCard\CreateCreditCardUseCase;
use App\UseCases\CreditCard\DeleteCreditCardUseCase;
use App\UseCases\CreditCard\ListCreditCardsUseCase;
use App\UseCases\CreditCard\UpdateCreditCardUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CreditCardController extends Controller
{
    public function store(CreateCreditCardRequest $request, CreateCreditCardUseCase $useCase): CreditCardResource
    {
        return new CreditCardResource($useCase->execute(CreditCardDTO::fromArray($request->validated()), $request->user()));
    }

    public function index(Request $request, ListCreditCardsUseCase $useCase)
    {
        return CreditCardResource::collection($useCase->execute($request->integer('wallet_id'), $request->user()));
    }

    public function update(CreateCreditCardRequest $request, CreditCard $creditCard, UpdateCreditCardUseCase $useCase): CreditCardResource
    {
        return new CreditCardResource($useCase->execute($creditCard, CreditCardDTO::fromArray($request->validated()), $request->user()));
    }

    public function destroy(Request $request, CreditCard $creditCard, DeleteCreditCardUseCase $useCase): Response
    {
        $useCase->execute($creditCard, $request->user());

        return response()->noContent();
    }
}
