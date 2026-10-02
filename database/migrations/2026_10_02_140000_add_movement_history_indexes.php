<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(['wallet_id', 'account_id', 'competence_date', 'due_date'], 'transactions_account_period_due_idx');
            $table->index(['wallet_id', 'credit_card_invoice_id', 'competence_date', 'due_date'], 'transactions_card_period_due_idx');
        });

        Schema::table('external_transactions', function (Blueprint $table): void {
            $table->index(['external_account_id', 'transaction_id'], 'external_account_transaction_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_account_period_due_idx');
            $table->dropIndex('transactions_card_period_due_idx');
        });

        Schema::table('external_transactions', function (Blueprint $table): void {
            $table->dropIndex('external_account_transaction_idx');
        });
    }
};
