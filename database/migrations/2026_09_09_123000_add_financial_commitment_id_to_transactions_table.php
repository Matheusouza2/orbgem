<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('financial_commitment_id')->nullable()->after('recurring_transaction_id');
            $table->foreign('financial_commitment_id')->references('id')->on('financial_commitments')->nullOnDelete();
            $table->index(['wallet_id', 'financial_commitment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['financial_commitment_id']);
            $table->dropIndex(['wallet_id', 'financial_commitment_id']);
            $table->dropColumn('financial_commitment_id');
        });
    }
};
