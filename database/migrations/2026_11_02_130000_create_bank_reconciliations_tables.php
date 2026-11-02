<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->char('month', 7);
                $table->string('original_name');
                $table->string('file_type', 8);
                $table->char('file_hash', 64);
                $table->string('status', 24)->default('PREVIEW');
                $table->string('document_account_number_last4', 4)->nullable();
                $table->date('document_period_start')->nullable();
                $table->date('document_period_end')->nullable();
                $table->bigInteger('opening_balance')->nullable();
                $table->bigInteger('closing_balance')->nullable();
                $table->bigInteger('total_credits')->nullable();
                $table->bigInteger('total_debits')->nullable();
                $table->unsignedInteger('statement_row_count')->default(0);
                $table->unsignedInteger('imported_count')->default(0);
                $table->json('warnings')->nullable();
                $table->timestamps();
                $table->index(['account_id', 'month', 'created_at']);
                $table->index(['wallet_id', 'status']);
            });
        }

        if (! Schema::hasTable('bank_reconciliation_entries')) {
            Schema::create('bank_reconciliation_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('bank_reconciliation_id')->constrained()->cascadeOnDelete();
                $table->string('entry_kind', 16);
                $table->string('external_id')->nullable();
                $table->date('transaction_date');
                $table->text('description');
                $table->bigInteger('signed_amount');
                $table->bigInteger('balance_after')->nullable();
                $table->string('match_status', 32);
                $table->foreignId('matched_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->foreignId('suggested_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->foreignId('duplicate_of_entry_id')->nullable()->constrained('bank_reconciliation_entries')->nullOnDelete();
                $table->foreignId('imported_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->char('import_row_hash', 64)->nullable();
                $table->json('flags')->nullable();
                $table->timestamps();
            });
        }

        $indexes = [
            'bank_rec_entries_kind_status_idx' => ['bank_reconciliation_id', 'entry_kind', 'match_status'],
            'bank_rec_entries_external_id_idx' => ['bank_reconciliation_id', 'external_id'],
            'bank_rec_entries_row_hash_idx' => ['bank_reconciliation_id', 'import_row_hash'],
        ];

        foreach ($indexes as $name => $columns) {
            if (! Schema::hasIndex('bank_reconciliation_entries', $name)) {
                Schema::table('bank_reconciliation_entries', function (Blueprint $table) use ($name, $columns): void {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_entries');
        Schema::dropIfExists('bank_reconciliations');
    }
};
