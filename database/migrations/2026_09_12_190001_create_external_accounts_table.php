<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('type')->nullable();
            $table->string('subtype')->nullable();
            $table->nullableMorphs('accountable');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['financial_connection_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_accounts');
    }
};
