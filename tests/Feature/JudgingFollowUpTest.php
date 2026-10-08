<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Edition;
use App\Models\JudgeSelection;
use App\Models\Modality;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\User;
use App\Services\MenuBuilder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JudgingFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = $this->createAdmin();
    }

    /**
     * DADO QUE um não-admin acessa qualquer rota `admin.acompanhamento.*`
     * ENTÃO o acesso é negado como nas demais rotas admin (RF-01).
     */
    public function test_nao_admin_e_bloqueado_em_todas_as_rotas(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition);
        $judge = User::factory()->judge()->create();

        $this->actingAs($judge)->get(route('admin.acompanhamento.index'))->assertForbidden();
        $this->actingAs($judge)->post(route('admin.acompanhamento.reset', [$edition, $judge]))->assertForbidden();
        $this->actingAs($judge)->post(route('admin.acompanhamento.close', $edition))->assertForbidden();
        $this->actingAs($judge)->get(route('admin.acompanhamento.awardees', $category))->assertForbidden();
        $this->actingAs($judge)->post(route('admin.acompanhamento.awardees.store', $category))->assertForbidden();
        $this->actingAs($judge)->get(route('admin.acompanhamento.edition-awardees', $edition))->assertForbidden();

        $this->assertNull($edition->fresh()->voting_closed_at);
    }

    /**
     * DADO QUE o admin abre o menu
     * ENTÃO "Acompanhamento" aparece logo após "Inscrições" (RF-13).
     */
    public function test_menu_do_admin_tem_acompanhamento_apos_inscricoes(): void
    {
        $routes = array_column(MenuBuilder::getAdminMenu(), 'route');
        $position = array_search('admin.registration.index', $routes, true);

        $this->assertSame('admin.acompanhamento.index', $routes[$position + 1]);
    }

    /**
     * DADO QUE um julgador completou a cota de uma categoria
     * ENTÃO a célula mostra "Finalizada" e as demais "Aberta" (RF-04), com as
     * categorias regulares antes das honoríficas (RF-03).
     */
    public function test_matriz_mostra_finalizada_e_aberta(): void
    {
        $edition = $this->createJudgingEdition();
        $honorific = $this->createCategoryForEdition($edition, ['acronym' => 'HON', 'is_honorific' => true, 'recipients_count' => 1]);
        $regular = $this->createCategoryForEdition($edition, ['acronym' => 'REG', 'recipients_count' => 1]);
        $registration = $this->createRegistration($regular);
        $this->createNominee($honorific);

        $judgeA = User::factory()->judge()->create(['name' => 'Ana Julgadora']);
        $judgeB = User::factory()->judge()->create(['name' => 'Bruno Julgador']);
        $this->vote($judgeA, $registration);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index'))
            ->assertOk()
            ->assertSee('Acompanhamento da Comissão')
            ->assertSeeInOrder(['data-column="'.$regular->id.'"', 'data-column="'.$honorific->id.'"'], false)
            ->assertSeeInOrder(['Ana Julgadora', 'Bruno Julgador'])
            ->assertSee('data-cell="'.$judgeA->id.':'.$regular->id.'" data-status="finalizada"', false)
            ->assertSee('data-cell="'.$judgeA->id.':'.$honorific->id.'" data-status="aberta"', false)
            ->assertSee('data-cell="'.$judgeB->id.':'.$regular->id.'" data-status="aberta"', false);
    }

    /**
     * DADO QUE o admin deixa a tela aberta
     * ENTÃO a matriz e os votos são regiões atualizadas automaticamente a cada 10 s.
     */
    public function test_tela_marca_regioes_de_atualizacao_automatica(): void
    {
        $edition = $this->createJudgingEdition();
        $this->createRegistration($this->createCategoryForEdition($edition));
        User::factory()->judge()->create();

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index'))
            ->assertOk()
            ->assertSee('data-refresh-interval="10000"', false)
            ->assertSee('data-live="comissao"', false)
            ->assertSee('data-live="votos"', false)
            ->assertSee('data-live-updated-at', false);
    }

    /**
     * DADO QUE 3 de 4 julgadores escolheram uma Inscrição
     * ENTÃO a barra mostra "3 de 4 votos", ordenada por votos DESC (RF-07);
     * Inscrição sem voto não aparece no card.
     */
    public function test_votos_mostram_x_de_n_julgadores_em_ordem(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, ['acronym' => 'VOT', 'recipients_count' => 2]);
        $leastVoted = $this->createRegistration($category);
        $mostVoted = $this->createRegistration($category, ['title' => 'Projeto Mais Votado']);
        $withoutVotes = $this->createRegistration($category);

        $judges = User::factory()->judge()->count(4)->create();
        $this->vote($judges[0], $mostVoted);
        $this->vote($judges[1], $mostVoted);
        $this->vote($judges[2], $mostVoted);
        $this->vote($judges[0], $leastVoted);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index'))
            ->assertOk()
            ->assertSee('Acompanhamento dos votos por categoria')
            ->assertSeeInOrder([
                'data-vote="registration:'.$mostVoted->id.'"',
                '3 de 4 votos',
                'data-vote="registration:'.$leastVoted->id.'"',
                '1 de 4 votos',
            ], false)
            ->assertSee('VOT '.$mostVoted->id.' — Projeto Mais Votado')
            ->assertDontSee('data-vote="registration:'.$withoutVotes->id.'"', false);
    }

    /**
     * DADO QUE duas Inscrições empatam em votos
     * ENTÃO a de menor `id` aparece primeiro (RF-07).
     */
    public function test_votos_empatados_desempatam_por_id(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, ['recipients_count' => 2]);
        $lowerId = $this->createRegistration($category);
        $higherId = $this->createRegistration($category);
        $judge = User::factory()->judge()->create();

        $this->vote($judge, $higherId);
        $this->vote($judge, $lowerId);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'data-vote="registration:'.$lowerId->id.'"',
                'data-vote="registration:'.$higherId->id.'"',
            ], false);
    }

    /**
     * DADO QUE há uma edição em julgamento e outra fora de julgamento
     * ENTÃO a tela abre na edição em julgamento com ações (RF-02) e, ao
     * selecionar a outra, fica somente leitura.
     */
    public function test_edicao_padrao_em_julgamento_e_outra_somente_leitura(): void
    {
        $judging = $this->createJudgingEdition(['title' => 'Edicao Em Julgamento']);
        $past = $this->createJudgingEdition([
            'title' => 'Edicao Encerrada',
            'is_registration_active' => false,
        ]);
        foreach ([$judging, $past] as $edition) {
            $this->createRegistration($this->createCategoryForEdition($edition));
        }
        User::factory()->judge()->create();

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index'))
            ->assertOk()
            ->assertSee('<option value="'.$judging->id.'" selected', false)
            ->assertSee('data-action="finalizar"', false)
            ->assertSee('data-action="resetar"', false);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index', ['edition' => $past->id]))
            ->assertOk()
            ->assertSee('<option value="'.$past->id.'" selected', false)
            ->assertSee('Somente leitura')
            ->assertDontSee('data-action="finalizar"', false)
            ->assertDontSee('data-action="resetar"', false);
    }

    /**
     * DADO QUE o admin reseta um julgador nesta edição
     * ENTÃO apaga só as seleções desta edição e preserva as de outra edição (RF-05).
     */
    public function test_resetar_apaga_somente_selecoes_da_edicao(): void
    {
        $edition = $this->createJudgingEdition();
        $otherEdition = $this->createJudgingEdition();
        $registration = $this->createRegistration($this->createCategoryForEdition($edition));
        $otherRegistration = $this->createRegistration($this->createCategoryForEdition($otherEdition));

        $judge = User::factory()->judge()->create();
        $this->vote($judge, $registration);
        $kept = $this->vote($judge, $otherRegistration);

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.reset', [$edition, $judge]))
            ->assertRedirect(route('admin.acompanhamento.index', ['edition' => $edition->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('judge_selections', 1);
        $this->assertModelExists($kept);
    }

    /**
     * DADO QUE a votação está finalizada, a edição não está em julgamento ou
     * o usuário não é julgador
     * ENTÃO o reset é rejeitado sem apagar nada (RF-05/RF-06).
     */
    public function test_resetar_rejeitado_quando_nao_permitido(): void
    {
        $edition = $this->createJudgingEdition();
        $registration = $this->createRegistration($this->createCategoryForEdition($edition));
        $judge = User::factory()->judge()->create();
        $this->vote($judge, $registration);

        $notJudge = User::factory()->create(['is_judge' => false]);
        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.reset', [$edition, $notJudge]))
            ->assertSessionHasErrors();

        $pastEdition = $this->createJudgingEdition(['is_registration_active' => false]);
        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.reset', [$pastEdition, $judge]))
            ->assertSessionHasErrors();

        $edition->forceFill(['voting_closed_at' => now()])->save();
        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.reset', [$edition, $judge]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('judge_selections', 1);
    }

    /**
     * DADO QUE há julgador com categoria aberta
     * ENTÃO o modal de "Finalizar votação" lista a pendência (RF-08).
     */
    public function test_finalizar_lista_pendencias(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition, ['acronym' => 'PND']);
        $this->createRegistration($category);
        User::factory()->judge()->create(['name' => 'Julgador Pendente']);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index'))
            ->assertOk()
            ->assertSee('data-pending="Julgador Pendente — PND"', false);
    }

    /**
     * DADO QUE o admin finaliza a votação
     * ENTÃO grava `voting_closed_at`, não permite finalizar de novo e o
     * `/julgar` deixa de oferecer a edição (RF-08/RF-09).
     */
    public function test_finalizar_grava_e_bloqueia_julgar(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition);
        $registration = $this->createRegistration($category);
        $judge = User::factory()->judge()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.close', $edition))
            ->assertRedirect(route('admin.acompanhamento.index', ['edition' => $edition->id]))
            ->assertSessionHas('success');

        $closedAt = $edition->fresh()->voting_closed_at;
        $this->assertNotNull($closedAt);

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.close', $edition))
            ->assertSessionHasErrors();

        $this->actingAs($judge)->get('/julgar')
            ->assertOk()
            ->assertDontSee('data-key="registration:'.$registration->id.'"', false);

        $this->actingAs($judge)
            ->post('/julgar', [
                'category_id' => $category->id,
                'inscriptions' => [['type' => 'registration', 'id' => $registration->id]],
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('judge_selections', 0);
    }

    /**
     * DADO QUE a votação ainda está aberta
     * ENTÃO a tela e a confirmação de agraciados ficam indisponíveis (RF-10/RF-11).
     */
    public function test_agraciados_indisponiveis_com_votacao_aberta(): void
    {
        $edition = $this->createJudgingEdition();
        $category = $this->createCategoryForEdition($edition);
        $registration = $this->createRegistration($category);

        $this->actingAs($this->admin)
            ->get(route('admin.acompanhamento.awardees', $category))
            ->assertRedirect(route('admin.acompanhamento.index', ['edition' => $edition->id]))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.awardees.store', $category), ['inscriptions' => [$registration->id]])
            ->assertSessionHasErrors();

        $this->assertSame(RegistrationStatusEnum::Avaliado->value, $registration->fresh()->status);
    }

    /**
     * DADO QUE a votação está finalizada
     * ENTÃO a tela de agraciados mostra as 10 mais votadas em destaque e a
     * lista completa (inclusive sem votos), sem nada marcado (RF-10).
     */
    public function test_tela_de_agraciados_mostra_top10_e_lista_completa_sem_marcacao(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $category = $this->createCategoryForEdition($edition, ['recipients_count' => 3]);
        $judges = User::factory()->judge()->count(2)->create();

        $voted = [];
        for ($i = 0; $i < 11; $i++) {
            $voted[] = $registration = $this->createRegistration($category);
            $this->vote($judges[0], $registration);
        }
        $this->vote($judges[1], $voted[10]);
        $withoutVotes = $this->createRegistration($category);

        $response = $this->actingAs($this->admin)->get(route('admin.acompanhamento.awardees', $category));

        $response->assertOk()
            ->assertSee('Mais votadas')
            ->assertSee('data-top="registration:'.$voted[10]->id.'"', false)
            ->assertSee('data-top="registration:'.$voted[8]->id.'"', false)
            ->assertDontSee('data-top="registration:'.$voted[9]->id.'"', false)
            ->assertSee('data-option="'.$voted[9]->id.'"', false)
            ->assertSee('data-option="'.$withoutVotes->id.'"', false)
            ->assertSee('data-limit="3"', false)
            ->assertSee('Quantidade a ser agraciada: 3')
            ->assertSee('data-ranking', false);

        $form = str($response->getContent())->after('id="awardees-form"')->before('</form>');
        $this->assertStringNotContainsString('checked', (string) $form);
    }

    /**
     * DADO QUE o admin confirma os agraciados em uma ordem
     * ENTÃO as Inscrições ficam "Agraciado" com a posição (1º, 2º…) na ordem
     * enviada, a categoria é travada e uma nova confirmação é rejeitada (RF-12).
     */
    public function test_confirmar_agraciados_grava_status_posicao_e_trava_categoria(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $category = $this->createCategoryForEdition($edition, ['acronym' => 'AGR', 'recipients_count' => 2]);
        $secondPlace = $this->createRegistration($category);
        $firstPlace = $this->createRegistration($category);
        $notChosen = $this->createRegistration($category);
        $this->createRegistration($this->createCategoryForEdition($edition)); // categoria ainda pendente

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.awardees.store', $category), ['inscriptions' => [$firstPlace->id, $secondPlace->id]])
            ->assertRedirect(route('admin.acompanhamento.awardees', $category))
            ->assertSessionHas('success');

        $this->assertSame(RegistrationStatusEnum::Agraciado->value, $firstPlace->fresh()->status);
        $this->assertSame(1, $firstPlace->fresh()->award_position);
        $this->assertSame(RegistrationStatusEnum::Agraciado->value, $secondPlace->fresh()->status);
        $this->assertSame(2, $secondPlace->fresh()->award_position);
        $this->assertSame(RegistrationStatusEnum::Avaliado->value, $notChosen->fresh()->status);
        $this->assertNull($notChosen->fresh()->award_position);
        $this->assertNotNull($category->fresh()->awardees_confirmed_at);

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.awardees.store', $category), ['inscriptions' => [$notChosen->id]])
            ->assertSessionHasErrors();

        $this->assertSame(RegistrationStatusEnum::Avaliado->value, $notChosen->fresh()->status);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.awardees', $category))
            ->assertOk()
            ->assertSee('Agraciados confirmados')
            ->assertSeeInOrder(['1º lugar', 'AGR '.$firstPlace->id, '2º lugar', 'AGR '.$secondPlace->id])
            ->assertDontSee('data-option=', false);
    }

    /**
     * DADO QUE o admin confirma a última categoria pendente da edição
     * ENTÃO é levado à tela final com os agraciados da edição; antes disso,
     * volta para a tela da categoria.
     */
    public function test_confirmar_ultima_categoria_leva_a_tela_final(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $categoryA = $this->createCategoryForEdition($edition);
        $categoryB = $this->createCategoryForEdition($edition);
        $registrationA = $this->createRegistration($categoryA);
        $registrationB = $this->createRegistration($categoryB);

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.awardees.store', $categoryA), ['inscriptions' => [$registrationA->id]])
            ->assertRedirect(route('admin.acompanhamento.awardees', $categoryA));

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index', ['edition' => $edition->id]))
            ->assertDontSee(route('admin.acompanhamento.edition-awardees', $edition));

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.awardees.store', $categoryB), ['inscriptions' => [$registrationB->id]])
            ->assertRedirect(route('admin.acompanhamento.edition-awardees', $edition));

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.index', ['edition' => $edition->id]))
            ->assertSee(route('admin.acompanhamento.edition-awardees', $edition));
    }

    /**
     * DADO QUE as categorias da edição tiveram os agraciados confirmados
     * ENTÃO a tela final lista os agraciados por categoria (regulares antes
     * das honoríficas), em ordem de colocação.
     */
    public function test_tela_final_lista_agraciados_da_edicao_em_ordem(): void
    {
        $edition = $this->createJudgingEdition(['title' => 'Edicao Premiada', 'voting_closed_at' => now()]);
        $honorific = $this->createCategoryForEdition($edition, ['title' => 'Categoria Honorifica', 'acronym' => 'HON', 'is_honorific' => true]);
        $regular = $this->createCategoryForEdition($edition, ['title' => 'Categoria Regular', 'acronym' => 'REG', 'recipients_count' => 2]);
        $secondPlace = $this->createRegistration($regular, ['title' => 'Projeto Segundo']);
        $firstPlace = $this->createRegistration($regular, ['title' => 'Projeto Primeiro']);
        $nominee = $this->createNominee($honorific, ['name' => 'Pessoa Homenageada']);

        $this->actingAs($this->admin)->post(route('admin.acompanhamento.awardees.store', $regular), ['inscriptions' => [$firstPlace->id, $secondPlace->id]]);
        $this->actingAs($this->admin)->post(route('admin.acompanhamento.awardees.store', $honorific), ['inscriptions' => [$nominee->id]]);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.edition-awardees', $edition))
            ->assertOk()
            ->assertSee('Agraciados da edição')
            ->assertSee('Edicao Premiada')
            ->assertSeeInOrder([
                'Categoria Regular', '1º lugar', 'Projeto Primeiro', '2º lugar', 'Projeto Segundo',
                'Categoria Honorifica', '1º lugar', 'Pessoa Homenageada',
            ]);
    }

    /**
     * DADO QUE um agraciado foi confirmado antes da classificação existir (sem posição)
     * ENTÃO a tela final mostra "Agraciado" no lugar da posição.
     */
    public function test_tela_final_mostra_agraciado_sem_posicao(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $category = $this->createCategoryForEdition($edition);
        $this->createRegistration($category, ['title' => 'Projeto Antigo', 'status' => RegistrationStatusEnum::Agraciado->value]);
        $category->forceFill(['awardees_confirmed_at' => now()])->save();

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.edition-awardees', $edition))
            ->assertOk()
            ->assertSeeInOrder(['>Agraciado<', 'Projeto Antigo'], false)
            ->assertDontSee('>º lugar<', false);
    }

    /**
     * DADO QUE ainda há categoria sem agraciados confirmados
     * ENTÃO a tela final indica a pendência; com a votação aberta, não abre.
     */
    public function test_tela_final_mostra_pendencias_e_exige_votacao_finalizada(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $category = $this->createCategoryForEdition($edition, ['title' => 'Categoria Pendente']);
        $this->createRegistration($category);

        $this->actingAs($this->admin)->get(route('admin.acompanhamento.edition-awardees', $edition))
            ->assertOk()
            ->assertSeeInOrder(['Categoria Pendente', 'Aguardando confirmação']);

        $openEdition = $this->createJudgingEdition();
        $this->actingAs($this->admin)->get(route('admin.acompanhamento.edition-awardees', $openEdition))
            ->assertRedirect(route('admin.acompanhamento.index', ['edition' => $openEdition->id]))
            ->assertSessionHas('error');
    }

    /**
     * DADO QUE a categoria é honorífica
     * ENTÃO a confirmação marca as `Nominee` como "Agraciado".
     */
    public function test_confirmar_agraciados_em_categoria_honorifica(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $category = $this->createCategoryForEdition($edition, ['is_honorific' => true, 'recipients_count' => 1]);
        $nominee = $this->createNominee($category);

        $this->actingAs($this->admin)
            ->post(route('admin.acompanhamento.awardees.store', $category), ['inscriptions' => [$nominee->id]])
            ->assertSessionHas('success');

        $this->assertSame(RegistrationStatusEnum::Agraciado->value, $nominee->fresh()->status);
        $this->assertSame(1, $nominee->fresh()->award_position);
    }

    /**
     * DADO QUE o POST traz 0 itens, mais que `recipients_count`, Inscrição de
     * outra categoria ou não qualificada
     * ENTÃO é rejeitado e nada é gravado (RF-11).
     */
    public function test_confirmar_agraciados_rejeicoes_nao_gravam(): void
    {
        $edition = $this->createJudgingEdition(['voting_closed_at' => now()]);
        $category = $this->createCategoryForEdition($edition, ['recipients_count' => 1]);
        $first = $this->createRegistration($category);
        $second = $this->createRegistration($category);
        $rejected = $this->createRegistration($category, ['status' => RegistrationStatusEnum::Rejeitado->value]);
        $otherCategory = $this->createRegistration($this->createCategoryForEdition($edition));

        $cases = [
            'nenhum item' => [],
            'acima do limite' => [$first->id, $second->id],
            'outra categoria' => [$otherCategory->id],
            'não qualificada' => [$rejected->id],
        ];

        foreach ($cases as $inscriptions) {
            $this->actingAs($this->admin)
                ->post(route('admin.acompanhamento.awardees.store', $category), ['inscriptions' => $inscriptions])
                ->assertSessionHasErrors();
        }

        $this->assertNull($category->fresh()->awardees_confirmed_at);
        $this->assertSame(0, Registration::query()->where('status', RegistrationStatusEnum::Agraciado->value)->count());
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        return $user->fresh();
    }

    private function vote(User $judge, Registration|Nominee $inscription): JudgeSelection
    {
        return JudgeSelection::create([
            'user_id' => $judge->id,
            'inscription_type' => $inscription->getMorphClass(),
            'inscription_id' => $inscription->id,
        ]);
    }

    /**
     * Edição ativa (RDD-01) e em julgamento (RDD-03: judgment_date no passado).
     */
    private function createJudgingEdition(array $attributes = []): Edition
    {
        $votingClosedAt = $attributes['voting_closed_at'] ?? null;
        unset($attributes['voting_closed_at']);

        $edition = Edition::create(array_merge([
            'title' => 'Edicao '.uniqid(),
            'regulation' => 'Regulamento da edicao',
            'registration_start' => today()->subDays(30),
            'registration_end' => today()->subDays(10),
            'grant_date' => today()->addDays(30),
            'judgment_date' => today()->subDay(),
            'is_registration_active' => true,
        ], $attributes));

        if ($votingClosedAt !== null) {
            $edition->forceFill(['voting_closed_at' => $votingClosedAt])->save();
        }

        return $edition;
    }

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
            'recipients_count' => 1,
        ], $attributes));
    }

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
