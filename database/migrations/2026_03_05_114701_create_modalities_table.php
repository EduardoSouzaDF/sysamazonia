<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModalitiesTable extends Migration
{
    public function up()
    {
        Schema::create('modalities', function (Blueprint $table) {
            $table->id();

            $table->string('title', 120)->nullable(false);
            $table->integer('candidacy_limit_per_modality')->default(1);
            $table->boolean('is_active')->default(true)->comment('Indica se a modalidade está ativa');

            // Foreign key com nome explícito, unsignedBigInteger implícito via foreignId()
            $table->foreignId('edition_id')
                ->constrained('editions') // nome da tabela explícito (segurança e clareza)
                ->onDelete('cascade')
                ->onUpdate('cascade'); // adicionado onUpdate, já que é comum em sistemas dinâmicos

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->dropForeign(['edition_id']);
        });

        Schema::dropIfExists('modalities');
    }
}
