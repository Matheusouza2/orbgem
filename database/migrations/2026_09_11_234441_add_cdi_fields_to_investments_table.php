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
        Schema::table('investments', function (Blueprint $table): void {
            $table->boolean('cdi_linked')->default(false)->after('active');
            $table->decimal('cdi_percentage', 8, 4)->nullable()->after('cdi_linked');
            $table->date('last_yield_date')->nullable()->after('cdi_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table): void {
            $table->dropColumn(['cdi_linked', 'cdi_percentage', 'last_yield_date']);
        });
    }
};
