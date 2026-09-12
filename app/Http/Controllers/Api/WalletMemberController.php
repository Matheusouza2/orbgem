<?php

namespace App\Http\Controllers\Api;

use App\DTO\AddWalletMemberDTO;
use App\DTO\ChangeWalletMemberRoleDTO;
use App\DTO\ListWalletMembersDTO;
use App\DTO\RemoveWalletMemberDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\AddWalletMemberRequest;
use App\Http\Requests\Wallet\ChangeWalletMemberRoleRequest;
use App\Http\Requests\Wallet\ListWalletMembersRequest;
use App\Http\Requests\Wallet\RemoveWalletMemberRequest;
use App\Http\Resources\WalletMemberResource;
use App\Models\User;
use App\UseCases\Wallet\AddWalletMemberUseCase;
use App\UseCases\Wallet\ChangeWalletMemberRoleUseCase;
use App\UseCases\Wallet\ListWalletMembersUseCase;
use App\UseCases\Wallet\RemoveWalletMemberUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WalletMemberController extends Controller
{
    public function index(int $wallet, ListWalletMembersRequest $request, ListWalletMembersUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return WalletMemberResource::collection($useCase->execute(new ListWalletMembersDTO($wallet), $user));
    }

    public function store(int $wallet, AddWalletMemberRequest $request, AddWalletMemberUseCase $useCase): WalletMemberResource
    {
        /** @var User $user */
        $user = $request->user();
        $attributes = [...$request->validated(), 'wallet_id' => $wallet];

        return new WalletMemberResource($useCase->execute(AddWalletMemberDTO::fromArray($attributes), $user));
    }

    public function update(int $wallet, int $member, ChangeWalletMemberRoleRequest $request, ChangeWalletMemberRoleUseCase $useCase): WalletMemberResource
    {
        /** @var User $user */
        $user = $request->user();
        $attributes = [...$request->validated(), 'wallet_id' => $wallet, 'member_id' => $member];

        return new WalletMemberResource($useCase->execute(ChangeWalletMemberRoleDTO::fromArray($attributes), $user));
    }

    public function destroy(int $wallet, int $member, RemoveWalletMemberRequest $request, RemoveWalletMemberUseCase $useCase): Response
    {
        /** @var User $user */
        $user = $request->user();
        $useCase->execute(RemoveWalletMemberDTO::fromArray(['wallet_id' => $wallet, 'member_id' => $member]), $user);

        return response()->noContent();
    }
}
