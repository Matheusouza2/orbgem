<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->string('external_id');
            $table->timestamp('imported_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id']);
            $table->index(['external_account_id', 'imported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_transactions');
    }
};
