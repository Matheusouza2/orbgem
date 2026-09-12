<?php

namespace App\Http\Controllers\Api;

use App\DTO\LoginDTO;
use App\DTO\PasswordResetDTO;
use App\DTO\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\TokenResource;
use App\UseCases\Auth\LoginUseCase;
use App\UseCases\Auth\LogoutUseCase;
use App\UseCases\Auth\RegisterUseCase;
use App\UseCases\Auth\RequestPasswordResetUseCase;
use App\UseCases\Auth\ResetPasswordUseCase;
use App\UseCases\Auth\TokenUseCase;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function store(LoginRequest $request, LoginUseCase $useCase): AuthResource
    {
        return new AuthResource($useCase->execute(LoginDTO::fromArray($request->validated())));
    }

    public function register(RegisterRequest $request, RegisterUseCase $useCase): JsonResponse
    {
        return (new AuthResource($useCase->execute(RegisterDTO::fromArray($request->validated()))))
            ->response()
            ->setStatusCode(201);
    }

    public function logout(Request $request, LogoutUseCase $useCase): Response
    {
        $useCase->execute($request->user());

        return response()->noContent();
    }

    public function logoutAll(Request $request, LogoutUseCase $useCase): Response
    {
        $useCase->executeAll($request->user());

        return response()->noContent();
    }

    public function tokens(Request $request, TokenUseCase $useCase)
    {
        return TokenResource::collection($useCase->list($request->user()));
    }

    public function revokeToken(Request $request, int $token, TokenUseCase $useCase): Response
    {
        $useCase->revoke($request->user(), $token);

        return response()->noContent();
    }

    public function forgotPassword(ForgotPasswordRequest $request, RequestPasswordResetUseCase $useCase): JsonResponse
    {
        $useCase->execute($request->validated()['email']);

        return response()->json(['message' => __('passwords.sent')], 202);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordUseCase $useCase): JsonResponse
    {
        $status = $useCase->execute(PasswordResetDTO::fromArray($request->validated()));

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => __($status)]);
    }
}
