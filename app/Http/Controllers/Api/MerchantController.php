<?php

namespace App\Http\Controllers\Api;

use App\DTO\MerchantDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\CreateMerchantRequest;
use App\Http\Requests\Merchant\ListMerchantRequest;
use App\Http\Resources\MerchantResource;
use App\Models\User;
use App\UseCases\Merchant\CreateMerchantUseCase;
use App\UseCases\Merchant\ListMerchantUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MerchantController extends Controller
{
    public function store(CreateMerchantRequest $request, CreateMerchantUseCase $useCase): MerchantResource
    {
        /** @var User $user */
        $user = $request->user();

        return new MerchantResource($useCase->execute(MerchantDTO::fromArray($request->validated()), $user));
    }

    public function index(ListMerchantRequest $request, ListMerchantUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return MerchantResource::collection($useCase->execute($request->integer('wallet_id'), $user));
    }
}
