<?php

namespace App\Models;

use App\Enums\WalletMemberRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Wallet extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [];
    }

    public function members(): HasMany
    {
        return $this->hasMany(WalletMember::class);
    }

    public function ownerMemberships(): HasMany
    {
        return $this->members()->where('role', WalletMemberRole::OWNER->value);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function consolidations(): BelongsToMany
    {
        return $this->belongsToMany(Consolidation::class);
    }
}
