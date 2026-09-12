<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ticker', 20)->nullable();
            $table->string('type');
            $table->string('institution')->nullable();
            $table->decimal('quantity', 20, 8);
            $table->integer('average_price')->default(0);
            $table->integer('invested_amount')->default(0);
            $table->integer('current_value')->default(0);
            $table->date('acquired_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['wallet_id', 'type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
