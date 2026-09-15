<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->date('position_date');
            $table->unsignedBigInteger('value');
            $table->decimal('quantity', 20, 8)->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->timestamps();
            $table->unique(['investment_id', 'position_date']);
            $table->index(['position_date', 'investment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_positions');
    }
};
