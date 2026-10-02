<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_positions', function (Blueprint $table): void {
            $table->string('entry_type', 20)->default('SNAPSHOT')->after('position_date');
            $table->dropUnique('investment_positions_investment_id_position_date_unique');
            $table->unique(['investment_id', 'position_date', 'entry_type'], 'investment_positions_investment_date_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('investment_positions', function (Blueprint $table): void {
            $table->dropUnique('investment_positions_investment_date_type_unique');
            $table->dropColumn('entry_type');
            $table->unique(['investment_id', 'position_date']);
        });
    }
};
