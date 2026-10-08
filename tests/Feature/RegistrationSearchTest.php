<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_each_supported_field_and_preserves_both_record_types(): void
    {
        $this->withoutExceptionHandling();
        $this->seedReportRecords();
        $this->actingAs($this->admin());

        foreach ([
            'Autora Principal' => 2,
            '00000000001' => 2,
            'Amazônia' => 1,
            'Pessoa Indicada' => 1,
            'PROJ' => 1,
            'Personalidade' => 1,
            'hAbiLiTaDo' => 2,
            '  Autora Principal  ' => 2,
            'inexistente' => 0,
            'Rejeitado' => 0,
            '' => 2,
        ] as $search => $total) {
            $this->get(route('admin.registration.index', ['search' => $search]))
                ->assertOk()
                ->assertViewHas('list', fn ($list) => $list->total() === $total);
        }
    }

    public function test_search_combines_filters_and_retains_form_values(): void
    {
        $this->withoutExceptionHandling();
        $this->seedReportRecords();
        $this->actingAs($this->admin());
        $edition = DB::table('editions')->value('id');
        $filters = ['search' => 'Autora Principal', 'edition' => $edition, 'status' => 3];

        $response = $this->get(route('admin.registration.index', $filters))
            ->assertOk()
            ->assertViewHas('list', fn ($list) => $list->total() === 2)
            ->assertViewHas('summary', ['people' => 1, 'total' => 2, 'regular' => 1, 'honorary' => 1])
            ->assertSee('Autores e indicadores únicos')
            ->assertSee('value="Autora Principal"', false);
        $this->assertMatchesRegularExpression('/<option value="'.$edition.'" selected[^>]*>Edição<\/option>/', $response->getContent());
        $this->assertMatchesRegularExpression('/<option value="3" selected[^>]*>Habilitado<\/option>/', $response->getContent());

        foreach ([['status' => 2], ['edition' => 99999]] as $override) {
            $this->get(route('admin.registration.index', array_replace($filters, $override)))
                ->assertOk()->assertViewHas('list', fn ($list) => $list->total() === 0)
                ->assertViewHas('summary', ['people' => 0, 'total' => 0, 'regular' => 0, 'honorary' => 0]);
        }
    }

    public function test_pagination_keeps_search_and_filters(): void
    {
        $this->withoutExceptionHandling();
        $this->seedReportRecords();
        $record = (array) DB::table('registrations')->first();
        unset($record['id']);
        for ($i = 0; $i < 10; $i++) {
            DB::table('registrations')->insert($record);
        }
        $this->actingAs($this->admin());
        $filters = ['search' => 'Autora Principal', 'edition' => DB::table('editions')->value('id'), 'status' => 3];
        $response = $this->get(route('admin.registration.index', $filters))
            ->assertOk()->assertViewHas('list', fn ($list) => $list->total() === 12 && $list->count() === 10);
        $url = $response->viewData('list')->url(2);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => 2], $query);
        $this->get($url)->assertOk()
            ->assertViewHas('summary', ['people' => 1, 'total' => 12, 'regular' => 11, 'honorary' => 1])
            ->assertViewHas('list', fn ($list) => $list->total() === 12 && $list->count() === 2);
    }

    public function test_category_filter_combines_with_search_edition_and_status(): void
    {
        $this->seedReportRecords();
        $this->actingAs($this->admin());
        $regular = DB::table('registrations')->value('category_id');
        $honorific = DB::table('nominees')->value('category_id');
        $filters = ['search' => 'Autora Principal', 'edition' => DB::table('editions')->value('id'), 'status' => 3, 'category' => $regular];
        $response = $this->get(route('admin.registration.index', $filters))->assertOk()
            ->assertViewHas('summary', ['people' => 1, 'total' => 1, 'regular' => 1, 'honorary' => 0])
            ->assertSee('Todas as categorias');
        $this->assertMatchesRegularExpression('/<option value="'.$regular.'" selected[^>]*>PROJ/', $response->getContent());
        parse_str(parse_url($response->viewData('list')->url(2), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => 2], $query);

        $this->get(route('admin.registration.index', array_replace($filters, ['category' => $honorific])))
            ->assertOk()->assertViewHas('summary', ['people' => 1, 'total' => 1, 'regular' => 0, 'honorary' => 1]);
        $this->get(route('admin.registration.index', array_replace($filters, ['category' => 99999])))
            ->assertOk()->assertViewHas('list', fn ($list) => $list->total() === 0);
        $this->get(route('admin.registration.index', array_replace($filters, ['category' => ''])))
            ->assertOk()->assertViewHas('list', fn ($list) => $list->total() === 2);
    }

    public function test_summary_counts_distinct_responsible_people_and_filtered_types(): void
    {
        $this->seedReportRecords();
        $candidate = (array) DB::table('candidates')->first();
        unset($candidate['id']);
        $candidate['cpf'] = '00000000002';
        $candidate['email'] = 'outra@example.test';
        $candidateId = DB::table('candidates')->insertGetId($candidate);
        DB::table('nominees')->update(['candidate_id' => $candidateId]);
        $this->actingAs($this->admin());

        $this->get(route('admin.registration.index'))->assertOk()
            ->assertViewHas('summary', ['people' => 2, 'total' => 2, 'regular' => 1, 'honorary' => 1]);
        $this->get(route('admin.registration.index', ['search' => 'Pessoa Indicada']))->assertOk()
            ->assertViewHas('summary', ['people' => 1, 'total' => 1, 'regular' => 0, 'honorary' => 1]);
    }

    private function seedReportRecords(): void
    {
        $candidateId = DB::table('candidates')->insertGetId([
            'nome' => 'Autora Principal', 'cpf' => '00000000001', 'dt_nascimento' => '1990-01-01',
            'rg' => '12345', 'rg_expeditor' => 'SSP', 'rg_uf' => 'AM', 'sexo' => 'F', 'cep' => '69000-000',
            'ufendereco' => 'AM', 'cidade' => 'Manaus', 'endereco' => 'Rua Teste', 'numero' => '1',
            'ddd' => '92', 'celular' => '999999999', 'whatsapp' => false,
            'email' => 'autora@example.test', 'resumo_curricular' => 'Currículo',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento', 'registration_start' => now(),
            'registration_end' => now(), 'grant_date' => now(), 'judgment_date' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $modality = DB::table('modalities')->insertGetId([
            'title' => 'Artes', 'edition_id' => $edition, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $regularCategory = DB::table('categories')->insertGetId([
            'modality_id' => $modality, 'title' => 'Projetos', 'acronym' => 'PROJ', 'is_honorific' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $honorificCategory = DB::table('categories')->insertGetId([
            'modality_id' => $modality, 'title' => 'Personalidade', 'acronym' => 'PERS', 'is_honorific' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('registrations')->insert([
            'candidate_id' => $candidateId, 'category_id' => $regularCategory, 'title' => 'Obra *Amazônia*',
            'coautores' => 'Coautora Um; Coautor Dois', 'resumo' => '<p>Primeiro parágrafo.</p><p>Segundo parágrafo.</p>',
            'desenvolvimento' => 'Texto', 'objetivo' => 'Texto', 'conclusao' => 'Texto',
            'status' => RegistrationStatusEnum::Habilitado->value, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('nominees')->insert([
            'candidate_id' => $candidateId, 'category_id' => $honorificCategory, 'name' => 'Pessoa Indicada',
            'state' => 'PA', 'contact_data' => 'contato-secreto@example.test', 'presentation' => 'Apresentação',
            'activities' => 'Atividades', 'justification' => 'Justificativa cadastrada',
            'status' => RegistrationStatusEnum::Habilitado->value, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        return $user->fresh();
    }
}
