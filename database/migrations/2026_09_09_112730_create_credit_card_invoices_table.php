<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_card_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('credit_card_id');
            $table->string('reference_month', 7);
            $table->date('closing_date');
            $table->date('due_date');
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['id', 'wallet_id']);
            $table->unique(['credit_card_id', 'reference_month']);
            $table->foreign(['credit_card_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('credit_cards')->cascadeOnDelete();
            $table->index(['wallet_id', 'reference_month', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_card_invoices');
    }
};
