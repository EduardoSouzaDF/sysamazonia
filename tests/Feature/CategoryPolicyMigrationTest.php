<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryPolicyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restores_removed_columns_without_changing_existing_policies(): void
    {
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento', 'registration_start' => now(),
            'registration_end' => now()->addDay(), 'grant_date' => now()->addMonth(), 'judgment_date' => now()->addWeek(),
        ]);
        $modality = DB::table('modalities')->insertGetId(['title' => 'Modalidade', 'edition_id' => $edition]);
        foreach (['hybrid', 'human_only', 'ai_only'] as $mode) {
            DB::table('categories')->insert([
                'modality_id' => $modality, 'title' => $mode, 'acronym' => $mode,
                'evaluation_mode' => $mode, 'human_evaluations_required' => $mode === 'ai_only' ? 0 : 2,
                'indication_mode' => $mode, 'human_indications_required' => $mode === 'ai_only' ? 0 : 3,
            ]);
        }
        Schema::table('categories', fn ($table) => $table->dropColumn([
            'evaluations_count', 'nominations_count', 'ai_evaluations_required', 'ai_indications_required',
        ]));
        $migration = require database_path('migrations/2026_09_23_120000_backfill_legacy_category_policies.php');
        $migration->up();
        foreach (['hybrid', 'human_only', 'ai_only'] as $mode) {
            $ai = $mode === 'human_only' ? 0 : 1;
            $this->assertDatabaseHas('categories', [
                'evaluation_mode' => $mode, 'indication_mode' => $mode,
                'evaluations_count' => ($mode === 'ai_only' ? 0 : 2) + $ai,
                'nominations_count' => ($mode === 'ai_only' ? 0 : 3) + $ai,
                'ai_evaluations_required' => $ai, 'ai_indications_required' => $ai,
            ]);
        }
        $before = DB::table('categories')->get()->toJson();
        $migration->up();
        $this->assertSame($before, DB::table('categories')->get()->toJson());
    }

    public function test_backfills_legacy_human_counts_without_overwriting_them(): void
    {
        $this->test_restores_removed_columns_without_changing_existing_policies();
        DB::table('categories')->where('acronym', 'human_only')->update([
            'evaluation_mode' => null, 'indication_mode' => null,
            'evaluations_count' => 4, 'nominations_count' => 5,
        ]);
        $migration = require database_path('migrations/2026_09_23_120000_backfill_legacy_category_policies.php');
        $migration->up();
        $this->assertDatabaseHas('categories', [
            'acronym' => 'human_only', 'evaluation_mode' => 'human_only', 'indication_mode' => 'human_only',
            'human_evaluations_required' => 4, 'human_indications_required' => 5,
            'evaluations_count' => 4, 'nominations_count' => 5,
        ]);
    }
}
