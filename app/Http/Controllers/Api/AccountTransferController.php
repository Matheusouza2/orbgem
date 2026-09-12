<?php

namespace App\Http\Controllers\Api;

use App\DTO\AccountTransferDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CreateAccountTransferRequest;
use App\Http\Resources\AccountTransferResource;
use App\Models\User;
use App\UseCases\Transaction\CreateAccountTransferUseCase;

class AccountTransferController extends Controller
{
    public function store(CreateAccountTransferRequest $request, CreateAccountTransferUseCase $useCase): AccountTransferResource
    {
        /** @var User $user */
        $user = $request->user();

        return new AccountTransferResource($useCase->execute(AccountTransferDTO::fromArray($request->validated()), $user));
    }
}
