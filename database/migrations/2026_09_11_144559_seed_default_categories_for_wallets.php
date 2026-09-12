<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            ['Alimentação', 'EXPENSE'],
            ['Moradia', 'EXPENSE'],
            ['Lazer', 'EXPENSE'],
            ['Saúde', 'EXPENSE'],
            ['Salário', 'INCOME'],
            ['Rendimento', 'INCOME'],
        ];

        foreach (DB::table('wallets')->pluck('id') as $walletId) {
            foreach ($defaults as [$name, $type]) {
                $exists = DB::table('categories')
                    ->where('wallet_id', $walletId)
                    ->where('name', $name)
                    ->where('type', $type)
                    ->exists();

                if (! $exists) {
                    DB::table('categories')->insert([
                        'wallet_id' => $walletId,
                        'parent_id' => null,
                        'name' => $name,
                        'type' => $type,
                        'icon' => null,
                        'icon_color' => null,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Defaults are indistinguishable from user-created categories after insertion.
    }
};
