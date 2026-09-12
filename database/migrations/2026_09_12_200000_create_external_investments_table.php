<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_investments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->string('external_id');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_investments');
    }
};
