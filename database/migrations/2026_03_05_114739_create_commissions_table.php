<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedBigInteger('category_id')->nullable(); // 👈 novo campo
            $table->boolean('is_organizing')->default(false);
            $table->boolean('is_evaluating')->default(false);
            $table->boolean('is_nominating')->default(false);
            $table->boolean('is_judging')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // 📌 Chave estrangeira para categories
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null'); // ou 'cascade', 'restrict', etc.
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['category_id']); // ⚠️ importante: remover FK antes de dropar coluna
        });

        Schema::dropIfExists('commissions');
    }
};
