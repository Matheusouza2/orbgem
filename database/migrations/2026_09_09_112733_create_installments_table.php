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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_card_invoice_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedBigInteger('amount');
            $table->date('competence_date');
            $table->date('due_date');
            $table->string('status');
            $table->timestamps();
            $table->unique(['credit_card_purchase_id', 'number']);
            $table->index(['credit_card_invoice_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
