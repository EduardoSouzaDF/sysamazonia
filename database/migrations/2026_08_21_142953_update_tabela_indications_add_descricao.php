<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTabelaIndicationsAddDescricao extends Migration
{
    public function up()
    {
        Schema::table('indications', function (Blueprint $table) {
             $table->text('descricao')->nullable();

        });
    }

    public function down()
    {
        Schema::table('indications', function (Blueprint $table) {
            $table->dropColumn(['descricao']);
        });
    }
}
