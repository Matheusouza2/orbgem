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
        Schema::table('credit_card_purchases', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_card_purchases', function (Blueprint $table): void {
            $table->dropColumn('due_date');
        });
    }
};
