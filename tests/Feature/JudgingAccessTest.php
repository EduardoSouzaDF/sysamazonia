<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Edition;
use App\Models\Modality;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\User;
use App\Services\MenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgingAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DADO um usuário autenticado com is_judge = true
     * QUANDO monta o menu
     * ENTÃO getMenuStructure() contém heading "Julgamento" + item "Julgar" → panel.julgar.index,
     * e não contém itens admin.
     */
    public function test_julgador_ve_menu_julgar_e_nao_ve_itens_admin(): void
    {
        $judge = User::factory()->judge()->create();
        $this->actingAs($judge);

        $menus = MenuBuilder::getMenuStructure();

        $julgarItems = array_filter($menus, fn (array $item) => ($item['title'] ?? null) === 'Julgar');

        $this->assertNotEmpty($julgarItems, 'Menu "Julgar" não encontrado para o julgador.');
        $this->assertContains('Julgamento', array_map(
            fn (array $item) => $item['heading'] ?? null,
            $menus
        ));

        $julgar = array_values($julgarItems)[0];
        $this->assertSame('panel.julgar.index', $julgar['route']);

        // Julgador puro não vê itens do back-office admin
        $adminItems = array_filter($menus, fn (array $item) => str_starts_with($item['route'] ?? '', 'admin.'));
        $this->assertEmpty($adminItems, 'Julgador não deveria ver itens admin.');
    }

    /**
     * DADO um usuário autenticado com is_judge = false
     * QUANDO monta o menu
     * ENTÃO não inclui a entrada "Julgar".
     */
    public function test_nao_julgador_nao_ve_menu_julgar(): void
    {
        $user = User::factory()->create(['is_judge' => false]);
        $this->actingAs($user);

        $menus = MenuBuilder::getMenuStructure();

        $julgarItems = array_filter($menus, fn (array $item) => ($item['title'] ?? null) === 'Julgar');
        $this->assertEmpty($julgarItems, 'Não-julgador não deveria ver o menu "Julgar".');
    }

    /**
     * DADO um usuário autenticado com is_judge = true
     * QUANDO acessa GET /julgar
     * ENTÃO abre o wizard (spec 0003) com a 1ª categoria regular elegível:
     * header com categoria + edição (RDD-01/03), card NM-05 "{acronym} {id} - {ano}"
     * da Registration "Avaliado" (RDD-02) com drawer "Avaliações e indicações"
     * (RF-05) e o rótulo NM-05 da listagem regular; a Nominee "Habilitado" de
     * outra categoria ainda não aparece (será a vez dela depois — RF-02).
     */
    public function test_julgador_acessa_painel_e_ve_inscricoes_apropriadas(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, [
            'acronym' => 'PAIN',
            'recipients_count' => 1,
        ]);
        $honorific = $this->createCategoryForEdition($edition, [
            'is_honorific' => true,
            'recipients_count' => 1,
        ]);

        $registration = $this->createRegistration($category, ['title' => 'Inscricao Avaliada Visivel']);
        $nominee = $this->createNominee($honorific, ['name' => 'Homenageado Habilitado Visivel']);

        $judge = User::factory()->judge()->create();

        $response = $this->actingAs($judge)->get('/julgar');

        $response->assertOk();
        // Header: categoria corrente + edição (RDD-01/03).
        $response->assertSee($edition->title);
        // Wizard RF-02: somente a 1ª categoria regular; Nominee não é a vez.
        $response->assertSee('Iniciativas selecionadas para esta categoria');
        $response->assertDontSee('Homenageado Habilitado Visivel');
        // Card NM-05/RF-04: "{acronym} {id} - {ano do julgamento}".
        $response->assertSee('data-label="PAIN '.$registration->id.' - '.$edition->judgment_date->format('Y').'"', false);
        // Drawer RF-05 (conteúdo renderizado em <template>).
        $response->assertSee('Dados da Inscrição');
        $response->assertSee('Avaliações e indicações');

        $this->assertSame(RegistrationStatusEnum::Avaliado->value, $registration->status);
        $this->assertSame(RegistrationStatusEnum::Habilitado->value, $nominee->status);
    }

    /**
     * DADO que há Inscrições em edição não ativa (RDD-01), com judgment_date futuro (RDD-03)
     * ou com status diferente do esperado por tipo
     * ENTÃO essas Inscrições não aparecem no painel.
     */
    public function test_painel_exclui_edicoes_inativas_fora_do_julgamento_e_status_invalidos(): void
    {
        $editionJudging = $this->createJudgingEdition();

        // Edição não ativa (RDD-01 violado)
        $editionInactive = $this->createJudgingEdition(['is_registration_active' => false]);
        $categoryInactive = $this->createCategoryForEdition($editionInactive);
        $this->createRegistration($categoryInactive, ['title' => 'Excluida Edicao Inativa']);
        $this->createNominee($categoryInactive, ['name' => 'Excluida Nominee Edicao Inativa']);

        // Edição com judgment_date no futuro (RDD-03 violado)
        $editionNotJudging = $this->createJudgingEdition(['judgment_date' => today()->addDay()]);
        $categoryNotJudging = $this->createCategoryForEdition($editionNotJudging);
        $this->createRegistration($categoryNotJudging, ['title' => 'Excluida Fora Do Julgamento']);
        $this->createNominee($categoryNotJudging, ['name' => 'Excluida Nominee Fora Do Julgamento']);

        // Status inválidos por tipo (FR-03): Registration "Habilitado" e Nominee "Avaliado"
        $categoryJudging = $this->createCategoryForEdition($editionJudging);
        $this->createRegistration($categoryJudging, [
            'title' => 'Excluida Status Habilitado',
            'status' => RegistrationStatusEnum::Habilitado->value,
        ]);
        $this->createNominee($categoryJudging, [
            'name' => 'Excluida Nominee Status Avaliado',
            'status' => RegistrationStatusEnum::Avaliado->value,
        ]);

        $judge = User::factory()->judge()->create();

        $response = $this->actingAs($judge)->get('/julgar');

        $response->assertOk();
        $response->assertDontSee('Excluida Edicao Inativa');
        $response->assertDontSee('Excluida Nominee Edicao Inativa');
        $response->assertDontSee('Excluida Fora Do Julgamento');
        $response->assertDontSee('Excluida Nominee Fora Do Julgamento');
        $response->assertDontSee('Excluida Status Habilitado');
        $response->assertDontSee('Excluida Nominee Status Avaliado');
    }

    /**
     * DADO um usuário autenticado com is_judge = false
     * QUANDO acessa GET /julgar
     * ENTÃO o sistema faz logout, redireciona ao login e exibe "Perfil sem Acesso!".
     */
    public function test_nao_julgador_recebe_logout_e_mensagem_perfil_sem_acesso(): void
    {
        $user = User::factory()->create(['is_judge' => false]);

        $response = $this->actingAs($user)->get('/julgar');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Perfil sem Acesso!');
        $this->assertGuest();

        // A mensagem é exibida na tela de login (após o redirect)
        $this->followingRedirects()
            ->actingAs(User::factory()->create(['is_judge' => false]))
            ->get('/julgar')
            ->assertOk()
            ->assertSee('Perfil sem Acesso!');
    }

    /**
     * DADO um visitante não autenticado
     * QUANDO acessa GET /julgar
     * ENTÃO é redirecionado ao login (auth).
     */
    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get('/julgar')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Edição ativa (RDD-01) e em julgamento (RDD-03: judgment_date no passado).
     */
    private function createJudgingEdition(array $attributes = []): Edition
    {
        return Edition::create(array_merge([
            'title' => 'Edicao '.uniqid(),
            'regulation' => 'Regulamento da edicao',
            'registration_start' => today()->subDays(30),
            'registration_end' => today()->subDays(10),
            'grant_date' => today()->addDays(30),
            'judgment_date' => today()->subDay(),
            'is_registration_active' => true,
        ], $attributes));
    }

    /**
     * Categoria dentro da cadeia Category → Modality → Edition.
     */
    private function createCategoryForEdition(Edition $edition, array $attributes = []): Category
    {
        $modality = Modality::create([
            'title' => 'Modalidade '.uniqid(),
            'edition_id' => $edition->id,
        ]);

        return Category::create(array_merge([
            'modality_id' => $modality->id,
            'title' => 'Categoria '.uniqid(),
            'acronym' => 'C'.substr(uniqid(), -6),
        ], $attributes));
    }

    /**
     * Candidato com campos obrigatórios únicos (cpf/email são unique).
     */
    private function createCandidate(): Candidate
    {
        return Candidate::create([
            'nome' => 'Candidato '.uniqid(),
            'cpf' => sprintf('%011d', random_int(0, 99999999999)),
            'dt_nascimento' => '1990-01-01',
            'rg' => (string) random_int(1000000, 9999999),
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
    }

    /**
     * Inscrição regular (Registration) com status "Avaliado" por padrão (FR-03).
     */
    private function createRegistration(Category $category, array $attributes = []): Registration
    {
        return Registration::create(array_merge([
            'candidate_id' => $this->createCandidate()->id,
            'category_id' => $category->id,
            'title' => 'Inscricao '.uniqid(),
            'resumo' => 'Resumo da proposta',
            'desenvolvimento' => 'Desenvolvimento da proposta',
            'objetivo' => 'Objetivo da proposta',
            'conclusao' => 'Conclusao da proposta',
            'status' => RegistrationStatusEnum::Avaliado->value,
        ], $attributes));
    }

    /**
     * Inscrição honorífica (Nominee) com status "Habilitado" por padrão (FR-03).
     */
    private function createNominee(Category $category, array $attributes = []): Nominee
    {
        return Nominee::create(array_merge([
            'candidate_id' => $this->createCandidate()->id,
            'category_id' => $category->id,
            'name' => 'Homenageado '.uniqid(),
            'state' => 'DF',
            'contact_data' => uniqid().'@example.com',
            'presentation' => 'Apresentacao do indicado',
            'activities' => 'Atividades do indicado',
            'justification' => 'Justificativa da indicacao',
            'status' => RegistrationStatusEnum::Habilitado->value,
        ], $attributes));
    }
}
