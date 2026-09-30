<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryPolicyMigrationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $backupStorage = null;

    protected function tearDown(): void
    {
        if ($this->backupStorage !== null) {
            File::deleteDirectory($this->backupStorage);
        }
        parent::tearDown();
    }

    public function test_migration_backs_up_original_values_preserves_policies_and_can_be_reversed(): void
    {
        $migration = $this->migration();
        $migration->down();
        $modality = $this->modality();
        foreach ([['hybrid', 2, 1], ['human_only', 3, 0], ['ai_only', 0, 1]] as $index => [$mode, $human, $ai]) {
            DB::table('categories')->insert([
                'modality_id' => $modality, 'title' => 'Categoria', 'acronym' => 'C'.$index,
                'evaluation_mode' => $mode, 'human_evaluations_required' => $human,
                'ai_evaluations_required' => $ai, 'evaluations_count' => $human + $ai,
                'indication_mode' => $mode, 'human_indications_required' => $human,
                'ai_indications_required' => $ai, 'nominations_count' => $human + $ai,
                'recipients_count' => 3, 'submissions_per_candidate' => 2,
            ]);
        }
        DB::table('categories')->insert([
            'modality_id' => $modality, 'title' => 'Honorífica', 'acronym' => 'HON',
            'is_honorific' => true, 'nominations_count' => 1,
        ]);
        $before = DB::table('categories')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $migration->up();
        $backups = File::glob($this->backupStorage.'/app/private/backups/*.json');
        $this->assertCount(1, $backups);
        $this->assertSame($before, json_decode(File::get($backups[0]), true, flags: JSON_THROW_ON_ERROR));
        $this->assertFalse(Schema::hasColumn('categories', 'evaluations_count'));
        foreach (DB::table('categories')->orderBy('id')->get() as $index => $row) {
            foreach ((array) $row as $field => $value) {
                $this->assertSame($before[$index][$field], $value);
            }
        }
        $migration->down();
        foreach (DB::table('categories')->where('is_honorific', false)->orderBy('id')->get() as $index => $row) {
            $this->assertEquals($before[$index], (array) $row);
        }
        $migration->up();
    }

    public function test_ambiguous_legacy_policy_blocks_migration_before_dropping_columns(): void
    {
        $migration = $this->migration();
        $migration->down();
        $id = DB::table('categories')->insertGetId([
            'modality_id' => $this->modality(), 'title' => 'Legada', 'acronym' => 'LEG',
            'evaluations_count' => 2,
        ]);
        try {
            $migration->up();
            $this->fail('A política legada deve ser definida antes da remoção dos totais.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('ambígua', $exception->getMessage());
            $this->assertTrue(Schema::hasColumn('categories', 'evaluations_count'));
            $this->assertDatabaseHas('categories', ['id' => $id, 'evaluations_count' => 2]);
        } finally {
            DB::table('categories')->where('id', $id)->delete();
            $migration->up();
        }
    }

    private function migration(): object
    {
        $this->backupStorage = sys_get_temp_dir().'/category-policy-test-'.bin2hex(random_bytes(8));
        $this->app->useStoragePath($this->backupStorage);

        return require database_path('migrations/2026_09_24_120000_remove_redundant_category_policy_counts.php');
    }

    private function modality(): int
    {
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento', 'registration_start' => now(),
            'registration_end' => now()->addDay(), 'grant_date' => now()->addMonth(), 'judgment_date' => now()->addWeek(),
        ]);

        return DB::table('modalities')->insertGetId(['title' => 'Modalidade', 'edition_id' => $edition]);
    }
}
