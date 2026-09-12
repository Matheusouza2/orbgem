<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{user: User, token: string} $authentication */
        $authentication = $this->resource;

        return [
            'token' => $authentication['token'],
            'token_type' => 'Bearer',
            'user' => [
                'id' => $authentication['user']->id,
                'name' => $authentication['user']->name,
                'email' => $authentication['user']->email,
            ],
        ];
    }
}
