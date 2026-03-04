<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Índices
            $table->index('name');
            $table->index('active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
