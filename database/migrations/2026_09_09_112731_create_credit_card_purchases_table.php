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
        Schema::create('credit_card_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('credit_card_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->string('description');
            $table->date('purchase_date');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedInteger('installment_count');
            $table->timestamps();
            $table->foreign(['credit_card_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('credit_cards')->cascadeOnDelete();
            $table->foreign(['merchant_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('merchants')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['wallet_id', 'purchase_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_card_purchases');
    }
};
