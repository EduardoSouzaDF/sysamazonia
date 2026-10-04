<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ai_settings', 'ai_setting_versions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('base_url', 500)->nullable();
                $table->string('local_type', 30)->default('ollama');
            });
        }
        // Dashboard ranges by creation and completion date, independent of status.
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['completed_at']);
        });
        foreach (['ai_settings', 'ai_setting_versions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['base_url', 'local_type']));
        }
    }
};
