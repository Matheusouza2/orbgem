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
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_wallet_member_id')->nullable();
            $table->foreignId('account_id')->nullable();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->unsignedBigInteger('credit_limit');
            $table->unsignedTinyInteger('closing_day');
            $table->unsignedTinyInteger('due_day');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['id', 'wallet_id']);
            $table->foreign(['owner_wallet_member_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('wallet_members')->restrictOnDelete();
            $table->foreign(['account_id', 'wallet_id'])->references(['id', 'wallet_id'])->on('accounts')->restrictOnDelete();
            $table->index(['wallet_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
