<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListAccountMovementRequest;
use App\Http\Resources\AccountMovementResource;
use App\Models\User;
use App\UseCases\Account\ListAccountMovementsUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountMovementController extends Controller
{
    public function index(int $account, ListAccountMovementRequest $request, ListAccountMovementsUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $result = $useCase->execute($account, $request->validated(), $user);

        return AccountMovementResource::collection($result['movements'])
            ->additional(['total_amount' => $result['total_amount']]);
    }
}
