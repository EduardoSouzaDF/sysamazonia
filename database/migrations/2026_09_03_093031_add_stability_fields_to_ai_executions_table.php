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
        if (! Schema::hasColumn('ai_executions', 'evaluation_configuration_hash')) {
            Schema::table('ai_executions', function (Blueprint $table) {
                $table->char('evaluation_configuration_hash', 64)->nullable()->after('prompt_version');
            });
        }

        if (! Schema::hasColumn('ai_executions', 'error_code')) {
            Schema::table('ai_executions', function (Blueprint $table) {
                $table->string('error_code', 80)->nullable()->after('response_metadata');
            });
        }

        if (! $this->hasIndex('ai_executions_semantic_idempotency_unique')) {
            Schema::table('ai_executions', function (Blueprint $table) {
                $table->unique(
                    ['registration_id', 'evaluator_id', 'type', 'prompt_version', 'evaluation_configuration_hash'],
                    'ai_executions_semantic_idempotency_unique'
                );
            });
        }

        if ($this->hasIndex('ai_executions_idempotency_unique')) {
            Schema::table('ai_executions', function (Blueprint $table) {
                $table->dropUnique('ai_executions_idempotency_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->hasIndex('ai_executions_idempotency_unique')) {
            Schema::table('ai_executions', function (Blueprint $table) {
                $table->unique(
                    ['registration_id', 'evaluator_id', 'type', 'prompt_version'],
                    'ai_executions_idempotency_unique'
                );
            });
        }

        if ($this->hasIndex('ai_executions_semantic_idempotency_unique')) {
            Schema::table('ai_executions', function (Blueprint $table) {
                $table->dropUnique('ai_executions_semantic_idempotency_unique');
            });
        }

        $columns = array_values(array_filter(
            ['evaluation_configuration_hash', 'error_code'],
            fn (string $column): bool => Schema::hasColumn('ai_executions', $column),
        ));
        if ($columns !== []) {
            Schema::table('ai_executions', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('ai_executions'))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
