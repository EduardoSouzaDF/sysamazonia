<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('regulation');
            $table->string('regulation_file_path')->nullable(false)->default('');
            $table->date('registration_start');
            $table->date('registration_end');
            $table->date('grant_date');
            $table->date('judgment_date');
            $table->boolean('is_registration_active')->default(false);
            $table->integer('applications_per_candidate')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('editions');
    }
}
