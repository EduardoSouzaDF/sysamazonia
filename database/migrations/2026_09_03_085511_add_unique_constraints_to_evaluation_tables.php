<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateCounts = [
            'opinions' => DB::table('opinions')
                ->select('user_id', 'registration_id')
                ->groupBy('user_id', 'registration_id')
                ->havingRaw('COUNT(*) > 1')->get()->count(),
            'scores' => DB::table('scores')
                ->select('opinion_id', 'evaluation_criterion_id')
                ->groupBy('opinion_id', 'evaluation_criterion_id')
                ->havingRaw('COUNT(*) > 1')->get()->count(),
            'indications' => DB::table('indications')
                ->select('user_id', 'registration_id')
                ->groupBy('user_id', 'registration_id')
                ->havingRaw('COUNT(*) > 1')->get()->count(),
        ];

        $duplicates = array_filter($duplicateCounts);
        if ($duplicates !== []) {
            $report = collect($duplicates)
                ->map(fn (int $count, string $table): string => "{$table}: {$count} grupo(s)")
                ->implode('; ');
            throw new RuntimeException(
                'Não foi possível criar índices únicos. Duplicatas encontradas: '.$report
            );
        }

        Schema::table('opinions', function (Blueprint $table) {
            $table->unique(['user_id', 'registration_id'], 'opinions_user_registration_unique');
        });
        Schema::table('scores', function (Blueprint $table) {
            $table->unique(['opinion_id', 'evaluation_criterion_id'], 'scores_opinion_criterion_unique');
        });
        Schema::table('indications', function (Blueprint $table) {
            $table->unique(['user_id', 'registration_id'], 'indications_user_registration_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indications', function (Blueprint $table) {
            $table->dropUnique('indications_user_registration_unique');
        });
        Schema::table('scores', function (Blueprint $table) {
            $table->dropUnique('scores_opinion_criterion_unique');
        });
        Schema::table('opinions', function (Blueprint $table) {
            $table->dropUnique('opinions_user_registration_unique');
        });
    }
};
