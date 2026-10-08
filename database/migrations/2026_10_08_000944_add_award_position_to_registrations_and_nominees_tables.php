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
        foreach (['registrations', 'nominees'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedTinyInteger('award_position')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['registrations', 'nominees'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('award_position');
            });
        }
    }
};
