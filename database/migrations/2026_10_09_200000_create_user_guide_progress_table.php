<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_guide_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('guide', 50);
            $table->unsignedSmallInteger('version');
            $table->unsignedSmallInteger('step')->default(0);
            $table->string('status', 20)->default('paused');
            $table->timestamps();
            $table->unique(['user_id', 'guide', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_guide_progress');
    }
};
