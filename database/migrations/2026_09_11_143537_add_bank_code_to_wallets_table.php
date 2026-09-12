<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bank details are defined in the accounts table creation migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Bank details are reverted with the accounts table.
    }
};
