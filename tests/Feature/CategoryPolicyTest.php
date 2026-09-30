<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public static function policies(): array
    {
        return [
            'humana' => ['human_only', 3, 0, 3],
            'IA' => ['ai_only', 0, 1, 1],
            'híbrida' => ['hybrid', 2, 1, 3],
        ];
    }

    #[DataProvider('policies')]
    public function test_create_and_edit_preserve_policy_without_storing_redundant_counts(string $mode, int $human, int $ai, int $total): void
    {
        $payload = $this->payload() + [
            'evaluation_mode' => $mode, 'human_evaluations_required' => $human,
            'indication_mode' => $mode, 'human_indications_required' => $human,
            // Valores antigos enviados por um cliente não podem alterar a política.
            'evaluations_count' => 99, 'nominations_count' => 99,
            'ai_evaluations_required' => 99, 'ai_indications_required' => 99,
        ];
        $this->post(route('admin.categories.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $category = Category::sole();
        $this->assertSame($ai, $category->requiredAiEvaluations());
        $this->assertSame($total, $category->requiredEvaluations());
        $this->assertSame($ai, $category->requiredAiIndications());
        $this->assertSame($total, $category->requiredIndications());

        $this->put(route('admin.categories.update', $category), array_replace($payload, [
            'evaluation_mode' => 'hybrid', 'human_evaluations_required' => 4,
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(5, $category->refresh()->requiredEvaluations());
        $this->assertSame($total, $category->requiredIndications());
        $this->get(route('admin.categories.edit', $category))->assertOk()
            ->assertSee('data-policy-summary', false)
            ->assertDontSee('name="evaluations_count"', false)
            ->assertDontSee('name="nominations_count"', false)
            ->assertDontSee('name="ai_evaluations_required"', false)
            ->assertDontSee('name="ai_indications_required"', false);

        foreach (['evaluations_count', 'nominations_count', 'ai_evaluations_required', 'ai_indications_required'] as $column) {
            $this->assertFalse(Schema::hasColumn('categories', $column));
        }
    }

    public function test_honorific_does_not_require_policies_and_preserves_other_limits(): void
    {
        $this->post(route('admin.categories.store'), $this->payload() + ['is_honorific' => 1])
            ->assertSessionHasNoErrors()->assertRedirect();
        $category = Category::sole();
        $this->assertNull($category->evaluation_mode);
        $this->assertNull($category->indication_mode);
        $this->assertSame(0, $category->requiredEvaluations());
        $this->assertSame(0, $category->requiredIndications());
        $this->assertSame(3, $category->recipients_count);
        $this->assertSame(2, $category->submissions_per_candidate);
    }

    public function test_regular_category_requires_explicit_consistent_policies(): void
    {
        $payload = $this->payload();
        $this->post(route('admin.categories.store'), $payload)->assertSessionHasErrors(['evaluation_mode', 'indication_mode']);
        foreach ([['hybrid', 0], ['human_only', 0], ['ai_only', 1]] as [$mode, $human]) {
            $this->post(route('admin.categories.store'), $payload + [
                'evaluation_mode' => $mode, 'human_evaluations_required' => $human,
                'indication_mode' => 'ai_only', 'human_indications_required' => 0,
            ])->assertSessionHasErrors('evaluation_mode');
        }
        $this->assertDatabaseCount('categories', 0);
    }

    private function payload(): array
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'], ['active' => true]));
        $this->actingAs($admin->refresh())->withSession(['_token' => 'category-test']);
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento',
            'registration_start' => now(), 'registration_end' => now()->addDay(),
            'grant_date' => now()->addMonth(), 'judgment_date' => now()->addWeek(),
        ]);
        $modality = DB::table('modalities')->insertGetId(['title' => 'Modalidade', 'edition_id' => $edition]);

        return [
            '_token' => 'category-test', 'modality_id' => (string) $modality,
            'title' => 'Categoria', 'acronym' => 'CAT', 'description' => 'Descrição',
            'recipients_count' => 3, 'submissions_per_candidate' => 2,
            'judging_start' => '2026-10-01', 'judging_end' => '2026-10-09',
        ];
    }
}
