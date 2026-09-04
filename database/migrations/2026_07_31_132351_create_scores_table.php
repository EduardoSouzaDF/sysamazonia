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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();

            // Parecer ao qual a nota pertence
            $table->foreignId('opinion_id')
                ->constrained('opinions')
                ->cascadeOnDelete();

            // Critério de avaliação (relacionamento com evaluation_criteria)
            $table->foreignId('evaluation_criterion_id')
                ->constrained('evaluation_criteria')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('valor')->nullable();
            $table->text('descricao')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
