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
        Schema::table('nominees', function (Blueprint $table) {
            $table->text('status')->nullable(); // ou ->nullable(false) se for obrigatório
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nominees', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
