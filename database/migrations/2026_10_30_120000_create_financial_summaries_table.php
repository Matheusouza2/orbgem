<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('facts');
            $table->json('analysis')->nullable();
            $table->string('data_hash', 40);
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_summaries');
    }
};
