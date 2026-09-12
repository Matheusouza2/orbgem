<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->foreignId('pluggy_item_id')->nullable()->after('wallet_id');
            $table->string('pluggy_account_id')->nullable()->after('pluggy_item_id');
            $table->integer('pluggy_balance')->nullable()->after('initial_balance');
            $table->index(['pluggy_item_id', 'pluggy_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropIndex(['pluggy_item_id', 'pluggy_account_id']);
            $table->dropColumn(['pluggy_item_id', 'pluggy_account_id', 'pluggy_balance']);
        });
    }
};
