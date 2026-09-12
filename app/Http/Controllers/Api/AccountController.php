<?php

namespace App\Http\Controllers\Api;

use App\DTO\AccountDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CreateAccountRequest;
use App\Http\Requests\Account\ListAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use App\UseCases\Account\CreateAccountUseCase;
use App\UseCases\Account\ListAccountUseCase;
use App\UseCases\Account\UpdateAccountUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    public function store(CreateAccountRequest $request, CreateAccountUseCase $useCase): AccountResource
    {
        /** @var User $user */
        $user = $request->user();

        return new AccountResource($useCase->execute(AccountDTO::fromArray($request->validated()), $user));
    }

    public function index(ListAccountRequest $request, ListAccountUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return AccountResource::collection($useCase->execute($request->integer('wallet_id'), $user));
    }

    public function update(int $account, UpdateAccountRequest $request, UpdateAccountUseCase $useCase): AccountResource
    {
        /** @var User $user */
        $user = $request->user();

        return new AccountResource($useCase->execute($account, AccountDTO::fromArray($request->validated()), $user));
    }
}
