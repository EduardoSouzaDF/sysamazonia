<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\CommissionGuides;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommissionGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_are_offered_only_to_eligible_profiles(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['is_judge' => false, 'is_organizer' => false]))->get(route('home'))->assertOk()->assertDontSee('data-commission-guides', false);
        $this->actingAs(User::factory()->create(['is_judge' => true]))->get(route('home'))
            ->assertOk()->assertSee('Rever orientações')->assertSee('data-guide-config', false);
        $user = $this->commissionUser();
        $this->assertSame(['evaluator', 'indicator'], app(CommissionGuides::class)->profiles($user));
        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('evaluator-list')->assertSee('indicator-list');
    }

    public function test_progress_is_saved_only_for_the_authenticated_user_and_updates_without_duplicates(): void
    {
        $first = User::factory()->create(['is_judge' => true]);
        $other = User::factory()->create(['is_judge' => true]);
        $payload = ['guide' => 'judge-panel', 'version' => 1, 'step' => 3, 'status' => 'paused', 'user_id' => $other->id];
        $this->actingAs($first)->putJson(route('commission-guides.update'), $payload)->assertOk()->assertJson(['saved' => true]);
        $this->assertDatabaseHas('user_guide_progress', ['user_id' => $first->id, 'guide' => 'judge-panel', 'step' => 3]);
        $this->assertDatabaseMissing('user_guide_progress', ['user_id' => $other->id]);
        $this->assertSame(3, app(CommissionGuides::class)->state($first)['progress']['judge-panel']->step);
        $this->assertSame([], app(CommissionGuides::class)->state($other)['progress']);
        $this->putJson(route('commission-guides.update'), array_replace($payload, ['step' => 6, 'status' => 'completed']))->assertOk();
        $this->assertDatabaseCount('user_guide_progress', 1);
        $this->assertDatabaseHas('user_guide_progress', ['user_id' => $first->id, 'status' => 'completed', 'step' => 6]);
        $this->assertDatabaseCount('judge_selections', 0);
        $this->assertDatabaseCount('opinions', 0);
        $this->assertDatabaseCount('indications', 0);
    }

    public function test_invalid_profile_guide_version_step_and_status_are_rejected(): void
    {
        $this->putJson(route('commission-guides.update'), [])->assertUnauthorized();
        $this->actingAs(User::factory()->create(['is_judge' => false, 'is_organizer' => false]))->putJson(route('commission-guides.update'), [])->assertForbidden();
        $this->actingAs(User::factory()->create(['is_judge' => true]));
        $payload = ['guide' => 'judge-panel', 'version' => 1, 'step' => 0, 'status' => 'paused'];
        foreach ([['guide' => 'evaluator-detail'], ['guide' => 'unknown'], ['version' => 2], ['step' => -1], ['step' => 31], ['status' => 'unknown']] as $override) {
            $this->putJson(route('commission-guides.update'), array_replace($payload, $override))->assertUnprocessable();
        }
        $this->assertDatabaseCount('user_guide_progress', 0);
    }

    public function test_a_new_guide_version_does_not_reuse_previous_completion_and_can_be_replayed(): void
    {
        $user = User::factory()->create(['is_judge' => true]);
        DB::table('user_guide_progress')->insert(['user_id' => $user->id, 'guide' => 'welcome', 'version' => 0, 'step' => 2, 'status' => 'completed']);
        $this->assertSame([], app(CommissionGuides::class)->state($user)['progress']);
        $payload = ['guide' => 'welcome', 'version' => 1, 'step' => 2, 'status' => 'completed'];
        $this->actingAs($user)->putJson(route('commission-guides.update'), $payload)->assertOk();
        $this->putJson(route('commission-guides.update'), array_replace($payload, ['step' => 0, 'status' => 'active']))->assertOk();
        $this->assertDatabaseCount('user_guide_progress', 2);
        $this->assertDatabaseHas('user_guide_progress', ['user_id' => $user->id, 'version' => 1, 'step' => 0, 'status' => 'active']);
    }

    public function test_revoking_a_profile_prevents_saving_its_guides(): void
    {
        $user = $this->commissionUser();
        $payload = ['guide' => 'evaluator-detail', 'version' => 1, 'step' => 1, 'status' => 'paused'];
        $this->actingAs($user)->putJson(route('commission-guides.update'), $payload)->assertOk();
        $user->evaluatorCategories()->detach();
        $this->putJson(route('commission-guides.update'), $payload)->assertUnprocessable();
        $this->assertDatabaseHas('user_guide_progress', ['user_id' => $user->id, 'guide' => 'evaluator-detail', 'step' => 1]);
    }

    public function test_tour_completion_does_not_complete_the_real_task(): void
    {
        $user = User::factory()->create(['is_judge' => true]);
        $this->actingAs($user)->putJson(route('commission-guides.update'), [
            'guide' => 'judge-panel', 'version' => 1, 'step' => 6, 'status' => 'completed',
        ])->assertOk();
        $this->assertSame(['judge'], app(CommissionGuides::class)->state($user)['automaticProfiles']);
    }

    public function test_successful_indication_stops_only_the_indicator_guide(): void
    {
        $user = $this->commissionUser();
        $registration = $this->registrationFor($user);
        $this->actingAs($user)->postJson(route('admin.registration.indicar', $registration->id), [
            'justificativa' => 'Proposta relevante para a categoria.',
        ])->assertOk()->assertSessionHas('commission_guides.'.$user->id.'.completed', ['indicator']);
        $this->assertDatabaseHas('indications', ['user_id' => $user->id, 'registration_id' => $registration->id]);
        $this->assertSame(['evaluator'], app(CommissionGuides::class)->state($user)['automaticProfiles']);
    }

    public function test_failed_evaluation_keeps_guidance_and_successful_evaluation_stops_it(): void
    {
        $user = $this->commissionUser();
        $registration = $this->registrationFor($user);
        $criterion = \App\Models\EvaluationCriterion::create([
            'category_id' => $registration->category_id, 'name' => 'Relevância',
            'description' => 'Relevância da proposta', 'min_score' => 0, 'max_score' => 10, 'weight' => 1,
        ]);
        $url = route('admin.registration.send.opinion', $registration);
        $this->actingAs($user)->postJson($url, [
            'criteria' => [$criterion->id => 11], 'justificativa' => [$criterion->id => 'Motivo'],
        ])->assertUnprocessable()->assertSessionMissing('commission_guides.'.$user->id.'.completed');
        $this->post($url, [
            'criteria' => [$criterion->id => 8], 'justificativa' => [$criterion->id => 'Motivo'],
        ])->assertRedirect(route('admin.registration.index'))
            ->assertSessionHas('commission_guides.'.$user->id.'.completed', ['evaluator']);
        $this->assertDatabaseHas('opinions', ['user_id' => $user->id, 'registration_id' => $registration->id]);
        $this->assertSame(['indicator'], app(CommissionGuides::class)->state($user)['automaticProfiles']);
    }

    public function test_a_new_login_restarts_automatic_guidance(): void
    {
        $user = User::factory()->create(['is_judge' => true]);
        $this->withSession(['commission_guides' => [$user->id => ['completed' => ['judge']]]])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'))->assertSessionMissing('commission_guides');
        $this->assertSame(['judge'], app(CommissionGuides::class)->state($user)['automaticProfiles']);
    }

    private function registrationFor(User $user): \App\Models\Registration
    {
        $candidate = \App\Models\Candidate::create([
            'nome' => 'Candidato', 'cpf' => '12345678901', 'dt_nascimento' => '1990-01-01',
            'rg' => '1234567', 'rg_expeditor' => 'SSP', 'rg_uf' => 'DF', 'sexo' => 'M',
            'cep' => '70000-000', 'ufendereco' => 'DF', 'cidade' => 'Brasília',
            'endereco' => 'Rua Teste', 'numero' => '1', 'ddd' => '61', 'celular' => '999999999',
            'email' => 'candidato@example.com', 'resumo_curricular' => 'Resumo',
        ]);
        return \App\Models\Registration::create([
            'candidate_id' => $candidate->id, 'category_id' => $user->evaluatorCategories()->first()->id,
            'title' => 'Proposta', 'resumo' => 'Resumo', 'desenvolvimento' => 'Desenvolvimento',
            'objetivo' => 'Objetivo', 'conclusao' => 'Conclusão', 'status' => 3,
        ]);
    }

    private function commissionUser(): User
    {
        $user = User::factory()->create(['is_judge' => false, 'is_organizer' => false]);
        $role = Role::firstOrCreate(['name' => 'comissao'], ['active' => true]);
        $user->roles()->attach($role);
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento', 'registration_start' => '2026-01-01',
            'registration_end' => '2026-12-01', 'judgment_date' => '2026-01-02', 'grant_date' => '2026-12-02',
        ]);
        $modality = DB::table('modalities')->insertGetId(['title' => 'Modalidade', 'edition_id' => $edition]);
        $category = DB::table('categories')->insertGetId(['title' => 'Categoria', 'acronym' => 'CAT', 'modality_id' => $modality]);
        $user->evaluatorCategories()->attach($category);
        $user->indicatorCategories()->attach($category);

        return $user;
    }
}
