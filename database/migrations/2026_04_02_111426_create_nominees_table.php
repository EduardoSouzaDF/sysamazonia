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
        Schema::create('nominees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');

            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            $table->string('name'); // Nome do indicado
            $table->string('state'); // Estado de residência do indicado
            $table->string('contact_data'); // Dados de contato do indicado
            $table->text('presentation'); // Apresentação do Indicado
            $table->text('activities'); // Atividades desempenhadas
            $table->text('justification'); // Justifique a indicação
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nominees');
    }
};
