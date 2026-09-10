<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Edition;
use App\Models\EvaluationCriterion;
use App\Models\Indication;
use App\Models\Modality;
use App\Models\Nominee;
use App\Models\Opinion;
use App\Models\Registration;
use App\Models\RegistrationFile;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgeSelectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DADO QUE o julgador acessa GET /julgar com múltiplas categorias elegíveis
     * QUANDO a tela é renderizada
     * ENTÃO vê somente a 1ª categoria regular (RF-02), com o top 20 de
     * `Registration` ordenado por indicações DESC, `evaluation_avg` DESC (nulas
     * por último), `id` ASC (RF-03), o rótulo NM-05 da listagem regular e
     * "Limpar"; inscrições de outras categorias (inclusive honoríficas) não
     * aparecem.
     */
    public function test_julgador_ve_primeira_categoria_regular_com_top20_ordenado(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, [
            'acronym' => 'PSD',
            'recipients_count' => 20,
        ]);

        // Ordem esperada (RF-03): r4 (2 ind, avg 8) → r1 (2 ind, avg 7) →
        // r2 (1 ind) → r3 (0 ind, avg nula → por último).
        $candidate = $this->createCandidate();
        $r1 = $this->createRegistration($category, ['evaluation_avg' => 7, 'candidate_id' => $candidate->id]);
        $r2 = $this->createRegistration($category, ['evaluation_avg' => 9, 'candidate_id' => $candidate->id]);
        $r3 = $this->createRegistration($category, ['evaluation_avg' => null, 'candidate_id' => $candidate->id]);
        $r4 = $this->createRegistration($category, ['evaluation_avg' => 8, 'candidate_id' => $candidate->id]);

        foreach ([$r1, $r1, $r4, $r4, $r2] as $target) {
            Indication::create(['user_id' => $this->judge->id, 'descricao' => 'ok', 'registration_id' => $target->id]);
        }

        // Inscrições de outras categorias NÃO aparecem agora (wizard).
        $nomineeHonorifica = $this->createNominee($category, ['name' => 'Nominee Na Categoria Regular']);
        $otherCategory = $this->createCategoryForEdition($edition, ['recipients_count' => 1]);
        $otherRegistration = $this->createRegistration($otherCategory);
        $honorificCategory = $this->createCategoryForEdition($edition, [
            'is_honorific' => true,
            'recipients_count' => 1,
        ]);
        $honorificNominee = $this->createNominee($honorificCategory);

        $response = $this->actingAs($this->judge)->get('/julgar');

        $response->assertOk();
        $response->assertSee('data-key="registration:'.$r4->id.'"', false);
        $response->assertSeeInOrder([
            'data-key="registration:'.$r4->id.'"',
            'data-key="registration:'.$r1->id.'"',
            'data-key="registration:'.$r2->id.'"',
            'data-key="registration:'.$r3->id.'"',
        ], false);

        // Wizard: só a categoria atual é renderizada.
        $response->assertDontSee('data-key="registration:'.$otherRegistration->id.'"', false);
        $response->assertDontSee('data-key="nominee:'.$nomineeHonorifica->id.'"', false);
        $response->assertDontSee('data-key="nominee:'.$honorificNominee->id.'"', false);

        // NM-05: listagem regular + ações. Cota EFETIVA = min(20, 4 cards) = 4 (RF-06).
        $response->assertSee('Iniciativas selecionadas para esta categoria');
        $response->assertSee('de 4', false);
        $response->assertSee('Restam:', false);
        $response->assertSee('Limpar', false);
        $response->assertSee('disabled>Confirmar', false);
    }

    /**
     * DADO QUE a inscrição é renderizada
     * ENTÃO o label do card segue o formato NM-05/RF-04
     * "{acronym} {id} - {ano de judgment_date}" (ex.: "PSD 101 - 2026").
     */
    public function test_label_do_card_segue_formato_nm05(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, [
            'acronym' => 'PSD',
            'recipients_count' => 1,
        ]);
        $registration = $this->createRegistration($category);

        $expected = 'data-label="PSD '.$registration->id.' - '.$edition->judgment_date->format('Y').'"';

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee($expected, false);
    }

    /**
     * DADO QUE a categoria atual é honorífica
     * ENTÃO todas as `Nominee` "Habilitado" (RDD-02/NM-04) aparecem como cards
     * e a listagem usa o rótulo NM-05 "Indicação para esta categoria";
     * `Nominee` fora do status não aparece.
     */
    public function test_honorifica_mostra_todas_as_habilitadas_com_rotulo_de_indicacao(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, [
            'is_honorific' => true,
            'recipients_count' => 3,
        ]);

        $candidate = $this->createCandidate();
        $n1 = $this->createNominee($category, ['candidate_id' => $candidate->id]);
        $n2 = $this->createNominee($category, ['candidate_id' => $candidate->id]);
        $n3 = $this->createNominee($category, ['candidate_id' => $candidate->id]);
        $n4 = $this->createNominee($category, [
            'candidate_id' => $candidate->id,
            'status' => RegistrationStatusEnum::Rejeitado->value,
        ]);

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('Indicação para esta categoria')
            ->assertSee('data-key="nominee:'.$n1->id.'"', false)
            ->assertSee('data-key="nominee:'.$n2->id.'"', false)
            ->assertSee('data-key="nominee:'.$n3->id.'"', false)
            ->assertDontSee('data-key="nominee:'.$n4->id.'"', false);
    }

    private User $judge;

    protected function setUp(): void
    {
        parent::setUp();

        // POSTs de teste sem token CSRF (só o middleware de CSRF; auth/CheckJudge ativos).
        // No Laravel 11 o grupo web usa o ValidateCsrfToken do framework.
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->judge = User::factory()->judge()->create();
    }

    /**
     * DADO QUE o julgador submete POST /julgar com a cota efetiva completa e válida
     * QUANDO a categoria é concluída
     * ENTÃO grava N `judge_selections` (com o alias do morph map), redireciona com
     * flash de sucesso, a próxima categoria aparece e — esgotadas todas — a tela
     * de conclusão com o resumo por categoria (NM-05).
     */
    public function test_post_valido_grava_avanca_e_conclui(): void
    {
        $edition = $this->createJudgingEdition();
        $categoryA = $this->createCategoryForEdition($edition, ['acronym' => 'CAA', 'recipients_count' => 1]);
        $categoryB = $this->createCategoryForEdition($edition, ['acronym' => 'CBB', 'recipients_count' => 1]);

        $candidate = $this->createCandidate();
        $rA1 = $this->createRegistration($categoryA, ['candidate_id' => $candidate->id]);
        $rA2 = $this->createRegistration($categoryA, ['candidate_id' => $candidate->id]);
        $rB1 = $this->createRegistration($categoryB, ['candidate_id' => $candidate->id]);

        // Categoria A: grava a seleção e avança para a B.
        $this->actingAs($this->judge)
            ->post('/julgar', $this->selectionPayload($categoryA, [['type' => 'registration', 'id' => $rA1->id]]))
            ->assertRedirect(route('panel.julgar.index'))
            ->assertSessionHas('success', 'Seleções registradas com sucesso.');

        $this->assertDatabaseHas('judge_selections', [
            'user_id' => $this->judge->id,
            'inscription_type' => 'registration',
            'inscription_id' => $rA1->id,
        ]);

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('data-key="registration:'.$rB1->id.'"', false)
            ->assertDontSee('data-key="registration:'.$rA1->id.'"', false)
            ->assertDontSee('data-key="registration:'.$rA2->id.'"', false);

        // Categoria B: ao concluir, tela de conclusão com resumo (NM-05).
        $this->actingAs($this->judge)
            ->post('/julgar', $this->selectionPayload($categoryB, [['type' => 'registration', 'id' => $rB1->id]]))
            ->assertRedirect(route('panel.julgar.index'));

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('Julgamento concluído!')
            ->assertSee('CAA '.$rA1->id.' - '.$edition->judgment_date->format('Y'))
            ->assertSee('CBB '.$rB1->id.' - '.$edition->judgment_date->format('Y'));

        $this->assertDatabaseCount('judge_selections', 2);
    }

    /**
     * DADO QUE a categoria tem menos elegíveis que `recipients_count`
     * ENTÃO a cota efetiva é `min(cota, elegíveis)` (RF-06) e o fluxo completa
     * com todas as inscrições disponíveis (sem dead-end).
     */
    public function test_cota_efetiva_menor_que_cota_completa_sem_dead_end(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, ['recipients_count' => 5]);

        $candidate = $this->createCandidate();
        $r1 = $this->createRegistration($category, ['candidate_id' => $candidate->id]);
        $r2 = $this->createRegistration($category, ['candidate_id' => $candidate->id]);

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('de 2', false);

        $this->actingAs($this->judge)
            ->post('/julgar', $this->selectionPayload($category, [
                ['type' => 'registration', 'id' => $r1->id],
                ['type' => 'registration', 'id' => $r2->id],
            ]))
            ->assertRedirect(route('panel.julgar.index'));

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('Julgamento concluído!');

        $this->assertDatabaseCount('judge_selections', 2);
    }

    /**
     * DADO QUE o POST traz cota errada, inscrição de outra categoria, payload
     * duplicado, tipo inválido ou categoria já concluída
     * ENTÃO falha a validação SEM gravar nada (transação com rollback).
     */
    public function test_post_rejeicoes_falham_sem_gravar(): void
    {
        $edition = $this->createJudgingEdition();
        $categoryA = $this->createCategoryForEdition($edition, ['recipients_count' => 1]);
        $categoryB = $this->createCategoryForEdition($edition, ['recipients_count' => 1]);

        $candidate = $this->createCandidate();
        $rA1 = $this->createRegistration($categoryA, ['candidate_id' => $candidate->id]);
        $rA2 = $this->createRegistration($categoryA, ['candidate_id' => $candidate->id]);
        $rB1 = $this->createRegistration($categoryB, ['candidate_id' => $candidate->id]);

        $cases = [
            'cota errada (2 itens para cota 1)' => [
                ['type' => 'registration', 'id' => $rA1->id],
                ['type' => 'registration', 'id' => $rA2->id],
            ],
            'inscrição de outra categoria' => [
                ['type' => 'registration', 'id' => $rB1->id],
            ],
            'item duplicado no payload' => [
                ['type' => 'registration', 'id' => $rA1->id],
                ['type' => 'registration', 'id' => $rA1->id],
            ],
            'tipo inválido' => [
                ['type' => 'juiz', 'id' => $rA1->id],
            ],
        ];

        foreach ($cases as $descricao => $inscriptions) {
            $this->actingAs($this->judge)
                ->from(route('panel.julgar.index'))
                ->post('/julgar', $this->selectionPayload($categoryA, $inscriptions))
                ->assertRedirect(route('panel.julgar.index'))
                ->assertSessionHasErrors();

            $this->assertDatabaseCount('judge_selections', 0);
        }

        // Categoria concluída: completa A e tenta submeter de novo.
        $this->actingAs($this->judge)
            ->post('/julgar', $this->selectionPayload($categoryA, [['type' => 'registration', 'id' => $rA1->id]]))
            ->assertSessionHas('success', 'Seleções registradas com sucesso.');

        $this->actingAs($this->judge)
            ->from(route('panel.julgar.index'))
            ->post('/julgar', $this->selectionPayload($categoryA, [['type' => 'registration', 'id' => $rA2->id]]))
            ->assertRedirect(route('panel.julgar.index'))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('judge_selections', 1);
    }

    /**
     * DADO QUE o grid é de categoria regular
     * ENTÃO exibe no máximo 20 cards (RF-03) — inscrições além do corte ficam
     * fora do DOM.
     */
    public function test_grid_regular_limita_em_20_cards(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, ['recipients_count' => 25]);

        $candidate = $this->createCandidate();
        $registrations = [];
        for ($i = 0; $i < 22; $i++) {
            $registrations[] = $this->createRegistration($category, ['candidate_id' => $candidate->id]);
        }

        // Sem indicações/notas: ordem cai no `id` ASC → os 20 primeiros ids.
        $visible = array_slice($registrations, 0, 20);
        $cut = array_slice($registrations, 20);

        $response = $this->actingAs($this->judge)->get('/julgar');
        $response->assertOk();

        foreach ($visible as $registration) {
            $response->assertSee('data-key="registration:'.$registration->id.'"', false);
        }
        foreach ($cut as $registration) {
            $response->assertDontSee('data-key="registration:'.$registration->id.'"', false);
        }
    }

    /**
     * DADO QUE o card é de `Registration`
     * ENTÃO o drawer contém "Dados da Inscrição" (RF-05), anexos e a seção
     * somente leitura "Avaliações e indicações" (NM-05) com notas por critério
     * e indicações (autores incluídos).
     */
    public function test_drawer_de_registration_mostra_dados_avaliacoes_e_indicacoes(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, ['recipients_count' => 1]);
        $registration = $this->createRegistration($category, ['protocol' => 'PROTO-001']);

        RegistrationFile::create([
            'registration_id' => $registration->id,
            'file_name' => 'parecer-tecnico.pdf',
            'file_path' => 'registrations/parecer-tecnico.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
            'document_type' => 'pdf',
        ]);

        $criterion = EvaluationCriterion::create([
            'category_id' => $category->id,
            'name' => 'Mérito da proposta',
            'weight' => 2,
            'min_score' => 0,
            'max_score' => 10,
        ]);
        $opinion = Opinion::create(['user_id' => $this->judge->id, 'registration_id' => $registration->id]);
        Score::create([
            'opinion_id' => $opinion->id,
            'evaluation_criterion_id' => $criterion->id,
            'valor' => 8,
            'descricao' => 'Proposta consistente e alinhada ao edital.',
        ]);
        Indication::create([
            'user_id' => $this->judge->id,
            'registration_id' => $registration->id,
            'descricao' => 'Indico pela relevância regional.',
        ]);

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('Dados da Inscrição')
            ->assertSee('PROTO-001')
            ->assertSee('Avaliações e indicações')
            ->assertSee('Mérito da proposta: 8', false)
            ->assertSee('Indico pela relevância regional.')
            ->assertSee('parecer-tecnico.pdf');
    }

    /**
     * DADO QUE a categoria atual é honorífica
     * ENTÃO o drawer da `Nominee` mostra "Dados da Indicação" (RF-05) e NÃO
     * contém a seção de avaliações (exclusiva de `Registration`).
     */
    public function test_drawer_de_nominee_mostra_dados_da_indicacao_sem_avaliacoes(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, [
            'is_honorific' => true,
            'recipients_count' => 1,
        ]);
        $candidate = $this->createCandidate();
        $nominee = $this->createNominee($category, [
            'candidate_id' => $candidate->id,
            'justification' => 'Trajetória exemplar.',
        ]);

        $this->actingAs($this->judge)->get('/julgar')
            ->assertOk()
            ->assertSee('Dados da Indicação')
            ->assertSee($candidate->nome)
            ->assertSee('Trajetória exemplar.')
            ->assertDontSee('Avaliações e indicações');
    }

    /**
     * DADO QUE o requisitante não é julgador (ou é convidado)
     * QUANDO acessa GET/POST /julgar
     * ENTÃO é barrado pelo `CheckJudge` com o flash NM-03 na tela de login e
     * nada é gravado.
     */
    public function test_nao_julgador_e_convidado_sao_barrados(): void
    {
        // Convidado.
        $this->get('/julgar')->assertRedirect(route('login'));

        // Usuário comum (is_judge = false): logout + flash NM-03.
        $user = User::factory()->create(['is_judge' => false]);
        $this->actingAs($user)
            ->get('/julgar')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Perfil sem Acesso!');

        // POST também é protegido: nada gravado.
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition);
        $registration = $this->createRegistration($category);

        $this->actingAs($user)
            ->post('/julgar', $this->selectionPayload($category, [
                ['type' => 'registration', 'id' => $registration->id],
            ]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('judge_selections', 0);
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
     * Categoria dentro da cadeia Category → Modality → Edition, com
     * sobrecarga de atributos (ex.: `recipients_count`, `is_honorific`).
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
     * Inscrição regular (Registration) com status "Avaliado" por padrão (RDD-02).
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
     * Inscrição honorífica (Nominee) com status "Habilitado" por padrão (RDD-02).
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

    /**
     * Payload do POST /julgar no formato do `JudgeSelectionRequest`.
     *
     * @param  list<array{type: string, id: int}>  $inscriptions
     * @return array{category_id: int, inscriptions: list<array{type: string, id: int}>}
     */
    private function selectionPayload(Category $category, array $inscriptions): array
    {
        return [
            'category_id' => $category->id,
            'inscriptions' => $inscriptions,
        ];
    }
}
