<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTabelaAddNomineesIdAndNullableRegistrationId extends Migration
{
    public function up()
    {
        Schema::table('registrations_files', function (Blueprint $table) {
            // Tornar registration_id nullable
            $table->unsignedBigInteger('registration_id')->nullable()->change();

            // Adicionar o novo campo nominees_id
            $table->unsignedBigInteger('nominee_id')->nullable();

            // Se quiser adicionar foreign key para nominees_id (opcional)
            $table->foreign('nominee_id')->references('id')->on('nominees');
        });
    }

    public function down()
    {
        Schema::table('registrations_files', function (Blueprint $table) {
            $table->dropForeign(['nominee_id']);
        });
    }
}
