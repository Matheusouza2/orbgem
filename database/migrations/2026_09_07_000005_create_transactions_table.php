<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->unique(['id', 'wallet_id']);
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('type');
            $table->string('effect');
            $table->unsignedBigInteger('amount');
            $table->string('financial_instrument_type');
            $table->date('transaction_date');
            $table->date('competence_date');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status');
            $table->string('payment_channel')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recurring_transaction_id')->nullable();
            $table->foreignId('credit_card_invoice_id')->nullable();
            $table->foreignId('installment_id')->nullable();
            $table->string('transfer_group_id')->nullable();
            $table->unsignedBigInteger('reversal_of_transaction_id')->nullable();
            $table->unsignedBigInteger('created_by_member_id');
            $table->unsignedBigInteger('updated_by_member_id');
            $table->timestamps();
            $table->unique(['id', 'wallet_id']);
            $table->foreign(['account_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('accounts')->restrictOnDelete();
            $table->foreign(['created_by_member_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('wallet_members')->restrictOnDelete();
            $table->foreign(['updated_by_member_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('wallet_members')->restrictOnDelete();
            $table->foreign(['reversal_of_transaction_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('transactions')->restrictOnDelete();
            $table->index(['wallet_id', 'account_id', 'status', 'competence_date']);
            $table->index(['wallet_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropUnique(['id', 'wallet_id']);
        });
    }
};
