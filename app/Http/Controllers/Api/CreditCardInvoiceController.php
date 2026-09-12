<?php

namespace App\Http\Controllers\Api;

use App\DTO\CloseCreditCardInvoiceDTO;
use App\DTO\CreditCardInvoiceListDTO;
use App\DTO\PayCreditCardInvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditCard\ListCreditCardInvoiceRequest;
use App\Http\Requests\CreditCard\PayCreditCardInvoiceRequest;
use App\Http\Resources\CreditCardInvoiceResource;
use App\UseCases\CreditCard\CloseCreditCardInvoiceUseCase;
use App\UseCases\CreditCard\ListCreditCardInvoicesUseCase;
use App\UseCases\CreditCard\PayCreditCardInvoiceUseCase;
use Illuminate\Http\Request;

class CreditCardInvoiceController extends Controller
{
    public function index(ListCreditCardInvoiceRequest $request, ListCreditCardInvoicesUseCase $useCase)
    {
        return CreditCardInvoiceResource::collection($useCase->execute(new CreditCardInvoiceListDTO($request->integer('wallet_id'), $request->integer('credit_card_id') ?: null, $request->input('status')), $request->user()));
    }

    public function close(Request $request, int $invoice, CloseCreditCardInvoiceUseCase $useCase): CreditCardInvoiceResource
    {
        return new CreditCardInvoiceResource($useCase->execute(new CloseCreditCardInvoiceDTO($invoice, 0), $request->user()));
    }

    public function pay(PayCreditCardInvoiceRequest $request, int $invoice, PayCreditCardInvoiceUseCase $useCase)
    {
        return (new CreditCardInvoiceResource($useCase->execute(new PayCreditCardInvoiceDTO($invoice, $request->integer('account_id'), $request->integer('amount'), 0), $request->user())))->response()->setStatusCode(201);
    }
}
