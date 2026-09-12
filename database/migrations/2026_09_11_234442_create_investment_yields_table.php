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
        Schema::create('investment_yields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->date('reference_date');
            $table->decimal('cdi_daily_rate', 12, 10);
            $table->decimal('cdi_percentage', 8, 4);
            $table->unsignedBigInteger('opening_value');
            $table->bigInteger('yield_amount');
            $table->unsignedBigInteger('closing_value');
            $table->timestamps();
            $table->unique(['investment_id', 'reference_date'], 'investment_yields_investment_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_yields');
    }
};
