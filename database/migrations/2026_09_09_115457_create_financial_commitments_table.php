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
        Schema::create('financial_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('type');
            $table->unsignedBigInteger('original_amount');
            $table->unsignedBigInteger('installment_amount');
            $table->unsignedInteger('installment_count');
            $table->unsignedInteger('current_installment')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('creditor')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['wallet_id', 'active', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_commitments');
    }
};
