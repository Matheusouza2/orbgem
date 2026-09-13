<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->boolean('is_third_party')->default(false)->after('notes');
            $table->index(['wallet_id', 'is_third_party', 'competence_date'], 'transactions_wallet_third_party_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_wallet_third_party_date_idx');
            $table->dropColumn('is_third_party');
        });
    }
};
