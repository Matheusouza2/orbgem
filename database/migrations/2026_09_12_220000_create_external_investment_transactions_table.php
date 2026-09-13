<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'external_investment_transactions';
        $indexName = 'external_investment_transactions_investment_date_idx';

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($indexName): void {
                $table->id();
                $table->foreignId('external_investment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('investment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('source');
                $table->string('external_id');
                $table->string('event_type')->nullable();
                $table->string('description');
                $table->unsignedBigInteger('amount');
                $table->date('transaction_date');
                $table->json('raw_data')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->timestamps();
                $table->unique(['source', 'external_id']);
                $table->index(['investment_id', 'transaction_date'], $indexName);
            });

            return;
        }

        $hasInvestmentDateIndex = collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index): bool => $index['columns'] === ['investment_id', 'transaction_date']);

        if (! $hasInvestmentDateIndex) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->index(['investment_id', 'transaction_date'], $indexName);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('external_investment_transactions');
    }
};
