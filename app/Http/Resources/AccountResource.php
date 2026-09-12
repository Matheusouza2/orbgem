<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'owner_wallet_member_id' => $this->owner_wallet_member_id,
            'name' => $this->name,
            'institution' => $this->institution,
            'bank_code' => $this->bank_code,
            'account_number' => $this->account_number,
            'type' => $this->type,
            'initial_balance' => $this->initial_balance,
            'pluggy_item_id' => $this->pluggy_item_id,
            'pluggy_account_id' => $this->pluggy_account_id,
            'pluggy_balance' => $this->pluggy_balance,
            'is_default' => $this->is_default,
            'show_in_dashboard' => $this->show_in_dashboard,
            'ignore_in_totals' => $this->ignore_in_totals,
            'active' => $this->active,
            'created_at' => $this->created_at,
        ];
    }
}
