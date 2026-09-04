<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('gemini');
            $table->string('model', 120);
            $table->text('api_key')->nullable();
            $table->longText('technical_prompt')->nullable();
            $table->longText('selection_prompt')->nullable();
            $table->string('technical_prompt_version', 80)->default('technical_evaluator_v1');
            $table->string('selection_prompt_version', 80)->default('selection_reviewer_v1');
            $table->boolean('evaluation_enabled')->default(false);
            $table->boolean('selection_enabled')->default(false);
            $table->foreignId('technical_evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('selection_evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('connect_timeout')->default(5);
            $table->unsignedSmallInteger('timeout')->default(60);
            $table->unsignedTinyInteger('tries')->default(3);
            $table->string('knowledge_version', 80)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('ai_setting_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_setting_id')->constrained('ai_settings')->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('model', 120);
            $table->longText('technical_prompt')->nullable();
            $table->longText('selection_prompt')->nullable();
            $table->string('technical_prompt_version', 80);
            $table->string('selection_prompt_version', 80);
            $table->string('knowledge_version', 80)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_setting_versions');
        Schema::dropIfExists('ai_settings');
    }
};
