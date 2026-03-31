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
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade');

            $table->string('name', 120);              // Nome do critério
            $table->text('description')->nullable();   // Descrição do critério
            $table->decimal('weight', 5, 2)->default(1); // Peso (ex: 1.00, 2.50)
            $table->decimal('min_score', 5, 2)->default(0); // Nota mínima
            $table->decimal('max_score', 5, 2)->default(10); // Nota máxima

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria');
    }
};
