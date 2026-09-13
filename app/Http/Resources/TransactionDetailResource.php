<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionDetailResource extends TransactionResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'account' => new AccountResource($this->whenLoaded('account')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
        ];
    }
}
