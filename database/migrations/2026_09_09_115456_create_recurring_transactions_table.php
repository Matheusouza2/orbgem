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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('description');
            $table->string('type');
            $table->unsignedBigInteger('amount');
            $table->string('frequency');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->boolean('auto_create')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign(['account_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('accounts')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['wallet_id', 'active', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
