<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Edition;
use App\Models\Modality;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationRoleListTest extends TestCase
{
    use RefreshDatabase;

    private Category $evaluationCategory;

    private Category $indicationCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $edition = Edition::create([
            'title' => 'Edicao Teste',
            'regulation' => 'Regulamento',
            'registration_start' => today()->subDays(30),
            'registration_end' => today()->subDays(10),
            'grant_date' => today()->addDays(30),
            'judgment_date' => today()->addDays(10),
            'is_registration_active' => true,
        ]);
        $modality = Modality::create(['title' => 'Modalidade', 'edition_id' => $edition->id]);

        $this->evaluationCategory = Category::create([
            'modality_id' => $modality->id,
            'title' => 'Categoria Avaliacao Humana',
            'acronym' => 'EVA',
            'evaluation_mode' => 'human_only',
            'human_evaluations_required' => 2,
            'indication_mode' => 'ai_only',
            'human_indications_required' => 0,
        ]);

        $this->indicationCategory = Category::create([
            'modality_id' => $modality->id,
            'title' => 'Categoria Indicacao Humana',
            'acronym' => 'IND',
            'evaluation_mode' => 'ai_only',
            'human_evaluations_required' => 0,
            'indication_mode' => 'human_only',
            'human_indications_required' => 1,
        ]);
    }

    /**
     * DADO QUE o usuário é avaliador e indicador das mesmas categorias
     * ENTÃO a listagem mostra as inscrições pendentes de avaliação E as
     * pendentes de indicação (os filtros se somam com "OU", não com "E").
     */
    public function test_avaliador_e_indicador_ve_pendentes_de_avaliacao_e_de_indicacao(): void
    {
        $user = $this->committeeUser(evaluator: true, indicator: true);

        $this->createRegistration($this->evaluationCategory, 'Pendente de avaliacao', RegistrationStatusEnum::Habilitado);
        $this->createRegistration($this->indicationCategory, 'Pendente de indicacao', RegistrationStatusEnum::Avaliado);
        $this->createRegistration($this->indicationCategory, 'Ainda inscrita', RegistrationStatusEnum::Inscrito);

        $this->actingAs($user)->get(route('admin.registration.index'))
            ->assertOk()
            ->assertSee('Pendente de avaliacao')
            ->assertSee('Pendente de indicacao')
            ->assertDontSee('Ainda inscrita');
    }

    /**
     * DADO QUE o usuário é só avaliador
     * ENTÃO vê apenas as pendentes de avaliação.
     */
    public function test_somente_avaliador_ve_apenas_pendentes_de_avaliacao(): void
    {
        $user = $this->committeeUser(evaluator: true, indicator: false);

        $this->createRegistration($this->evaluationCategory, 'Pendente de avaliacao', RegistrationStatusEnum::Habilitado);
        $this->createRegistration($this->indicationCategory, 'Pendente de indicacao', RegistrationStatusEnum::Avaliado);

        $this->actingAs($user)->get(route('admin.registration.index'))
            ->assertOk()
            ->assertSee('Pendente de avaliacao')
            ->assertDontSee('Pendente de indicacao');
    }

    private function committeeUser(bool $evaluator, bool $indicator): User
    {
        $user = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'comissao', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        foreach ([$this->evaluationCategory, $this->indicationCategory] as $category) {
            $pivot = ['user_id' => $user->id, 'category_id' => $category->id, 'created_at' => now(), 'updated_at' => now()];
            if ($evaluator) {
                DB::table('evaluators')->insert($pivot);
            }
            if ($indicator) {
                DB::table('indicators')->insert($pivot);
            }
        }

        return $user->fresh();
    }

    private function createRegistration(Category $category, string $title, RegistrationStatusEnum $status): Registration
    {
        $candidate = Candidate::create([
            'nome' => 'Candidato '.uniqid(),
            'cpf' => sprintf('%011d', random_int(0, 99999999999)),
            'dt_nascimento' => '1990-01-01',
            'rg' => '1234567',
            'rg_expeditor' => 'SSP',
            'rg_uf' => 'DF',
            'sexo' => 'M',
            'cep' => '70000-000',
            'ufendereco' => 'DF',
            'cidade' => 'Brasilia',
            'endereco' => 'Rua Teste',
            'numero' => '1',
            'ddd' => '61',
            'celular' => '999999999',
            'email' => uniqid().'@example.com',
            'resumo_curricular' => 'Resumo curricular',
        ]);

        return Registration::create([
            'candidate_id' => $candidate->id,
            'category_id' => $category->id,
            'title' => $title,
            'resumo' => 'Resumo',
            'desenvolvimento' => 'Desenvolvimento',
            'objetivo' => 'Objetivo',
            'conclusao' => 'Conclusao',
            'status' => $status->value,
        ]);
    }
}
