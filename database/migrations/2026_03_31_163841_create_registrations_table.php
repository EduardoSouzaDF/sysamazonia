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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Relacionamento com o candidato
            $table->unsignedBigInteger('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates');

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');

            // Dados da proposta
            $table->string('title');
            $table->text('coautores')->nullable(); // Coautores separados por (;) ponto e vírgula

            // Textos da proposta
            $table->longText('resumo'); // Resumo (500-1000 palavras)
            $table->longText('desenvolvimento'); // Desenvolvimento (2000-3000 palavras)
            $table->longText('objetivo'); // Objetivos (1000-2000 palavras)
            $table->longText('conclusao'); // Conclusão (500-1000 palavras)

            // Status da inscrição
            $table->integer('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
