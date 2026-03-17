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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->string('title', 120)->nullable(false);
            $table->string('acronym', 10)->unique();

            // Category description
            $table->text('description')->nullable();

            // Honorific category flag
            $table->boolean('is_honorific')->default(false);

            // Number of nominations
            $table->unsignedInteger('nominations_count')->default(0);

            // Number of evaluations
            $table->unsignedInteger('evaluations_count')->default(0);

            // Number of recipients/awardees
            $table->unsignedInteger('recipients_count')->default(0);

            // Submissions per candidate
            $table->unsignedInteger('submissions_per_candidate')->default(1);

            // Judging start date
            $table->date('judging_start')->nullable();

            // Judging end date
            $table->date('judging_end')->nullable();

            // Open for submissions flag
            $table->boolean('is_open_for_submissions')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
