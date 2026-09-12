<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_wallet_member_id')->nullable();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('bank_code', 8)->nullable();
            $table->string('account_number')->nullable();
            $table->string('type');
            $table->integer('initial_balance')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('show_in_dashboard')->default(true);
            $table->boolean('ignore_in_totals')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['wallet_id', 'type', 'active']);
            $table->foreign(['owner_wallet_member_id', 'wallet_id'])
                ->references(['id', 'wallet_id'])
                ->on('wallet_members')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
