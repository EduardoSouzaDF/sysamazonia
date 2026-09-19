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
        Schema::create('judge_selections', function (Blueprint $table) {
            $table->id();

            // Julgador (relacionamento com users)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Inscrição selecionada (RDD-02): morph para `registration` | `nominee`
            $table->string('inscription_type');
            $table->unsignedBigInteger('inscription_id');

            $table->timestamps();

            // 1 seleção por inscrição por julgador (spec 0003, §6.1)
            $table->unique(
                ['user_id', 'inscription_type', 'inscription_id'],
                'judge_selections_user_inscription_unique'
            );

            // Consultas por inscrição (resumo por categoria, contagem de cota)
            $table->index(['inscription_type', 'inscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judge_selections');
    }
};
