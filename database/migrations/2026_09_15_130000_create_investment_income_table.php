<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_income', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('event_type')->nullable();
            $table->unsignedBigInteger('amount');
            $table->date('transaction_date');
            $table->string('source')->default('MANUAL');
            $table->timestamps();
            $table->index(['investment_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_income');
    }
};
