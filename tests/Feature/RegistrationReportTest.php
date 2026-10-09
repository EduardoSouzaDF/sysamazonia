<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\User;
use App\Services\RegistrationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_download_a_utf8_markdown_report(): void
    {
        $this->seedReportRecords();
        $admin = $this->admin();
        $token = 'registration-report-token';

        $this->actingAs($admin)->withSession(['_token' => $token])->get(route('admin.registration-reports.index'))
            ->assertOk()->assertSee('Relatórios de inscrições');

        $preview = $this->actingAs($admin)->post(route('admin.registration-reports.generate'), [
            'status' => RegistrationStatusEnum::Habilitado->value,
            'action' => 'preview',
            '_token' => $token,
        ]);
        $preview->assertOk()->assertSee('Obra \*Amazônia\*', false)->assertSee('Pessoa Indicada');

        $download = $this->actingAs($admin)->post(route('admin.registration-reports.generate'), [
            'status' => RegistrationStatusEnum::Habilitado->value,
            'action' => 'download',
            '_token' => $token,
        ]);
        $download->assertOk()->assertHeader('content-type', 'text/markdown; charset=UTF-8');
        $this->assertStringContainsString('filename="relatorio-habilitados.md"', (string) $download->headers->get('content-disposition'));
        $this->assertTrue(mb_check_encoding($download->getContent(), 'UTF-8'));
    }

    public function test_report_uses_justification_for_honorific_records_and_does_not_expose_contact_data(): void
    {
        $this->seedReportRecords();
        $report = app(RegistrationReportService::class)->generate(RegistrationStatusEnum::Habilitado->value);

        $this->assertSame(2, $report['total']);
        $this->assertStringContainsString('**Justificativa da indicação:**', $report['markdown']);
        $this->assertStringContainsString('Justificativa cadastrada', $report['markdown']);
        $this->assertStringNotContainsString('contato-secreto@example.test', $report['markdown']);
        $this->assertSame(2, substr_count($report['markdown'], '#### '));
    }

    public function test_non_admin_cannot_access_reports_and_invalid_status_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.registration-reports.index'))->assertForbidden();
        $this->actingAs($this->admin())->withSession(['_token' => 'invalid-status-token'])->post(route('admin.registration-reports.generate'), [
            'status' => RegistrationStatusEnum::Inscrito->value,
            'action' => 'preview',
            '_token' => 'invalid-status-token',
        ])->assertSessionHasErrors('status');
    }

    public function test_report_status_filters_include_evaluated_with_enabled_and_keep_other_groups_separate(): void
    {
        $this->seedReportRecords();
        foreach (['registrations', 'nominees'] as $table) {
            $record = (array) DB::table($table)->first();
            unset($record['id']);
            foreach ([1, 2, 4, 5] as $status) {
                DB::table($table)->insert(array_replace($record, ['status' => $status]));
            }
        }
        $service = app(RegistrationReportService::class);
        $this->assertSame(4, $service->generate(3)['total']);
        $this->assertSame(2, $service->generate(2)['total']);
        $this->assertSame(2, $service->generate(5)['total']);
        $this->assertSame('relatorio-agraciados.md', $service->generate(5)['filename']);

        $token = 'rejected-report-token';
        $this->actingAs($this->admin())->withSession(['_token' => $token])
            ->post(route('admin.registration-reports.generate'), ['status' => 2, 'action' => 'preview', '_token' => $token])
            ->assertOk()->assertSee('Relatório de Rejeitados')
            ->assertSee('name="status" value="2"', false)
            ->assertViewHas('report', fn ($report) => $report['total'] === 2);
        $this->post(route('admin.registration-reports.generate'), ['status' => 2, 'action' => 'download', '_token' => $token])
            ->assertOk()->assertHeader('content-disposition', 'attachment; filename="relatorio-rejeitados.md"');
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
