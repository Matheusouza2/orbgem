<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('import_batch_id')->nullable()->after('financial_commitment_id')->constrained('import_batches')->nullOnDelete();
            $table->string('import_row_hash', 64)->nullable()->after('import_batch_id');
            $table->unique(['wallet_id', 'import_row_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropUnique(['wallet_id', 'import_row_hash']);
            $table->dropForeign(['import_batch_id']);
            $table->dropColumn(['import_batch_id', 'import_row_hash']);
        });
    }
};
