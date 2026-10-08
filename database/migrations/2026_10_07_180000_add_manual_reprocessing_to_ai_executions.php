<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_executions', function (Blueprint $table) {
            // Existing executions belong to round zero; their counters/history are preserved.
            $table->unsignedInteger('retry_round')->default(0);
            $table->foreignId('parent_execution_id')->nullable()->constrained('ai_executions')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->string('retry_configuration', 20)->nullable();
            $table->text('retry_reason')->nullable();
            // Only one child per source: duplicate submissions cannot open two rounds.
            $table->unique('parent_execution_id', 'ai_executions_parent_unique');
            $table->unique(['registration_id', 'evaluator_id', 'type', 'prompt_version', 'evaluation_configuration_hash', 'retry_round'], 'ai_executions_round_unique');
        });
        Schema::table('ai_executions', fn (Blueprint $table) => $table->dropUnique('ai_executions_semantic_idempotency_unique'));
    }

    public function down(): void
    {
        // Never silently erase audit rounds or recreate an incompatible unique index.
        if (DB::table('ai_executions')->where('retry_round', '>', 0)->exists()) {
            throw new RuntimeException('Há reprocessamentos registrados. Preserve a migration e o histórico; rollback automático não é permitido.');
        }
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->unique(['registration_id', 'evaluator_id', 'type', 'prompt_version', 'evaluation_configuration_hash'], 'ai_executions_semantic_idempotency_unique');
            $table->dropUnique('ai_executions_round_unique');
            $table->dropForeign(['parent_execution_id']);
            $table->dropForeign(['requested_by']);
            $table->dropUnique('ai_executions_parent_unique');
            $table->dropColumn(['retry_round', 'parent_execution_id', 'requested_by', 'requested_at', 'superseded_at', 'retry_configuration', 'retry_reason']);
        });
    }
};
