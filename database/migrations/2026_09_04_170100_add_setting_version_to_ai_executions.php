<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->foreignId('ai_setting_version_id')->nullable()->after('evaluator_id')->constrained('ai_setting_versions')->nullOnDelete();
            $table->unsignedSmallInteger('service_http_status')->nullable()->after('duration_ms');
        });
    }

    public function down(): void
    {
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_setting_version_id');
            $table->dropColumn('service_http_status');
        });
    }
};
