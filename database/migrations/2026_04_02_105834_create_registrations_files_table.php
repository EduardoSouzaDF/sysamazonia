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
        Schema::create('registrations_files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->unsignedBigInteger('registration_id');
            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
            // Dados do arquivo
            $table->string('file_name'); // Nome original do arquivo
            $table->string('file_path'); // Caminho do arquivo no storage
            $table->string('file_type')->nullable(); // Tipo MIME do arquivo
            $table->unsignedBigInteger('file_size')->nullable(); // Tamanho do arquivo em bytes
            $table->string('description')->nullable(); // Descrição opcional do arquivo

            // Tipo de documento (opcional, para categorizar os anexos)
            $table->string('document_type')->nullable(); // ex: 'curriculo', 'portfolio', 'anexo'

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations_files');
    }
};
