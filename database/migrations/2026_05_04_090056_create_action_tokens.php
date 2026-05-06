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
        Schema::create('action_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('protocol');
            $table->string('token')->unique();
            $table->string('action'); // 'delete' ou 'update'
            $table->timestamp('expires_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('consumed_at')->nullable(); // Para evitar reuso do token de delete
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_tokens');
    }
};
