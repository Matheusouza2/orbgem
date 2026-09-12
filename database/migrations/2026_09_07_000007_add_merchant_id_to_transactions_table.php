<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('merchant_id')->nullable()->after('category_id');
            $table->foreign(['merchant_id', 'wallet_id'])
                ->references(['id', 'wallet_id'])
                ->on('merchants')
                ->restrictOnDelete();
            $table->index(['wallet_id', 'merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['merchant_id', 'wallet_id']);
            $table->dropIndex(['wallet_id', 'merchant_id']);
            $table->dropColumn('merchant_id');
        });
    }
};
