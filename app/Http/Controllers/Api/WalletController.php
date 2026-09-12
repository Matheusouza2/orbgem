<?php

namespace App\Http\Controllers\Api;

use App\DTO\WalletDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\CreateWalletRequest;
use App\Http\Requests\Wallet\ListWalletRequest;
use App\Http\Requests\Wallet\UpdateWalletRequest;
use App\Http\Resources\WalletResource;
use App\Models\User;
use App\UseCases\Wallet\CreateWalletUseCase;
use App\UseCases\Wallet\ListWalletUseCase;
use App\UseCases\Wallet\UpdateWalletUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    public function store(CreateWalletRequest $request, CreateWalletUseCase $useCase): WalletResource
    {
        /** @var User $user */
        $user = $request->user();

        return new WalletResource($useCase->execute(WalletDTO::fromArray($request->validated()), $user));
    }

    public function index(ListWalletRequest $request, ListWalletUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return WalletResource::collection($useCase->execute($user));
    }

    public function update(int $wallet, UpdateWalletRequest $request, UpdateWalletUseCase $useCase): WalletResource
    {
        /** @var User $user */
        $user = $request->user();

        return new WalletResource($useCase->execute($wallet, WalletDTO::fromArray($request->validated()), $user));
    }
}
