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
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->json('rubric')->nullable()->after('max_score');
            $table->string('rubric_version', 80)->nullable()->after('rubric');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropColumn(['rubric', 'rubric_version']);
        });
    }
};
