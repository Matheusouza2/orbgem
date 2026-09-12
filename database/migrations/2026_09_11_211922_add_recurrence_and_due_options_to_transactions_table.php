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
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('recurrence_type')->default('NONE')->after('due_date');
            $table->unsignedInteger('installment_initial')->nullable()->after('recurrence_type');
            $table->unsignedInteger('installment_count')->nullable()->after('installment_initial');
            $table->string('installment_periodicity')->nullable()->after('installment_count');
            $table->boolean('auto_post_on_due_date')->default(false)->after('installment_periodicity');
            $table->index(['wallet_id', 'recurrence_type', 'status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['wallet_id', 'recurrence_type', 'status', 'due_date']);
            $table->dropColumn(['recurrence_type', 'installment_initial', 'installment_count', 'installment_periodicity', 'auto_post_on_due_date']);
        });
    }
};
