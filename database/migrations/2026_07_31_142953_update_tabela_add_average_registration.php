<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTabelaAddAverageRegistration extends Migration
{
    public function up()
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->unsignedTinyInteger('evaluation_avg')->nullable();
        });
    }

    public function down()
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['evaluation_avg']);
        });
    }
}
