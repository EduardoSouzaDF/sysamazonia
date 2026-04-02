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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('nome');
            $table->string('cpf')->unique();
            $table->date('dt_nascimento');
            $table->string('rg');
            $table->string('rg_expeditor');
            $table->string('rg_uf', 2); // UF do RG
            $table->string('sexo');

            // Dados de Endereço
            $table->string('cep', 9);
            $table->string('ufendereco_uf_endereco', 2);
            $table->string('cidade');
            $table->string('endereco');
            $table->string('numero');
            $table->string('complemento')->nullable();

            // Dados de Contato
            $table->string('ddd');
            $table->string('celular');
            $table->boolean('whatsapp')->default(false);
            $table->string('email')->unique();

            // Outras Informações
            $table->string('instituicao')->nullable();
            $table->string('escolaridade')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('outra_rede_social')->nullable();
            $table->text('resumo_curricular'); // até 500 palavras
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
