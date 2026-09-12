<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['id', 'wallet_id']);
            $table->unique(['wallet_id', 'normalized_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
