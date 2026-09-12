<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_goal_contributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_goal_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('contributed_at');
            $table->string('note')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(
                ['financial_goal_id', 'contributed_at'],
                'financial_goal_contrib_goal_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_goal_contributions');
    }
};
