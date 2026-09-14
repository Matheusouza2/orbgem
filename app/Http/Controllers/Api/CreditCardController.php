<?php

namespace App\Http\Controllers\Api;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionListDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditCard\CreateCreditCardRequest;
use App\Http\Requests\CreditCard\ListCreditCardTransactionsRequest;
use App\Http\Resources\CreditCardResource;
use App\Http\Resources\CreditCardTransactionResource;
use App\Models\CreditCard;
use App\UseCases\CreditCard\CreateCreditCardUseCase;
use App\UseCases\CreditCard\DeleteCreditCardUseCase;
use App\UseCases\CreditCard\ListCreditCardsUseCase;
use App\UseCases\CreditCard\ListCreditCardTransactionsUseCase;
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
        return CreditCardResource::collection($useCase->execute($request->integer('wallet_id'), $request->user(), $request->input('month')));
    }

    public function transactions(ListCreditCardTransactionsRequest $request, CreditCard $creditCard, ListCreditCardTransactionsUseCase $useCase)
    {
        $result = $useCase->execute(CreditCardTransactionListDTO::fromArray($request->validated(), $creditCard->id), $creditCard, $request->user());

        return CreditCardTransactionResource::collection($result->transactions)->additional(['invoice_amount' => $result->amount]);
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
