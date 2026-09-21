<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushToken\DeletePushTokenRequest;
use App\Http\Requests\PushToken\StorePushTokenRequest;
use App\Http\Resources\DevicePushTokenResource;
use App\Models\DevicePushToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PushTokenController extends Controller
{
    public function store(StorePushTokenRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $token = DevicePushToken::query()->where('token', $data['token'])->first();
        $created = $token === null;
        $token ??= new DevicePushToken;
        $token->fill([...$data, 'user_id' => $user->id, 'last_seen_at' => now()]);
        $token->save();

        return (new DevicePushTokenResource($token))->response()->setStatusCode($created ? 201 : 200);
    }

    public function destroy(DeletePushTokenRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        DevicePushToken::query()
            ->where('user_id', $user->id)
            ->where('token', $request->validated('token'))
            ->delete();

        return response()->noContent();
    }
}
