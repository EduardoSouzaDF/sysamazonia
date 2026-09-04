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
        Schema::create('ai_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('opinion_id')->nullable()->constrained('opinions')->nullOnDelete();
            $table->foreignId('indication_id')->nullable()->constrained('indications')->nullOnDelete();
            $table->string('type', 40);
            $table->string('status', 20)->default('pending');
            $table->uuid('correlation_id')->unique();
            $table->string('provider', 80)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('prompt_version', 80);
            $table->string('rubric_version', 80)->nullable();
            $table->string('knowledge_version', 80)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('response_metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['registration_id', 'evaluator_id', 'type', 'prompt_version'],
                'ai_executions_idempotency_unique'
            );
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_executions');
    }
};
