<?php

namespace App\Http\Controllers;

use App\Enum\RegistrationStatusEnum;
use App\Http\Requests\ConfirmAwardeesRequest;
use App\Models\Category;
use App\Models\Edition;
use App\Models\JudgeSelection;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JudgingFollowUpController extends Controller
{
    /**
     * Limite de cards do grid de categorias regulares no `/julgar`
     * (espelha `JudgingController::CARD_LIMIT`), usado na cota efetiva.
     */
    private const CARD_LIMIT = 20;

    /**
     * Quantidade de Inscrições em destaque na tela de agraciados (spec 0004 / RF-10).
     */
    private const TOP_LIMIT = 10;

    /**
     * Status considerados no acompanhamento: as qualificadas para julgamento
     * (RDD-02) e as já agraciadas, para a apuração continuar visível depois
     * da confirmação.
     *
     * @var array<string, list<int>>
     */
    private const FOLLOW_UP_STATUSES = [
        'registration' => [RegistrationStatusEnum::Avaliado->value, RegistrationStatusEnum::Agraciado->value],
        'nominee' => [RegistrationStatusEnum::Habilitado->value, RegistrationStatusEnum::Agraciado->value],
    ];

    /**
     * Painel de acompanhamento (spec 0004 / RF-02 a RF-08): matriz julgador ×
     * categoria e votos por categoria da edição selecionada.
     */
    public function index(Request $request): View
    {
        $editions = Edition::query()->orderByDesc('judgment_date')->orderByDesc('id')->get();
        $edition = $request->filled('edition')
            ? $editions->firstWhere('id', (int) $request->query('edition'))
            : $editions->first(fn (Edition $edition): bool => $edition->isInJudging());

        if ($edition === null) {
            return view('admin.acompanhamento.index', [
                'editions' => $editions,
                'edition' => null,
            ]);
        }

        $categories = $this->categoriesFor($edition);
        $judges = User::query()->where('is_judge', true)->orderBy('name')->get();
        $inscriptions = $this->inscriptionsFor($categories);
        $selections = $this->selectionsFor($inscriptions);

        $matrix = $this->matrixFor($judges, $categories, $inscriptions, $selections);

        return view('admin.acompanhamento.index', [
            'editions' => $editions,
            'edition' => $edition,
            'isReadOnly' => ! $edition->isInJudging(),
            'canManage' => $edition->isInJudging() && ! $edition->isVotingClosed(),
            'categories' => $categories,
            'judges' => $judges,
            'matrix' => $matrix,
            'pending' => $this->pendingFor($judges, $categories, $matrix),
            'votes' => $this->votesFor($categories, $inscriptions, $selections),
            'judgesCount' => $judges->count(),
            'allAwardeesConfirmed' => $this->allAwardeesConfirmed($categories),
        ]);
    }

    /**
     * Apaga todas as seleções do julgador nas categorias da edição (spec 0004 / RF-05, RF-06).
     */
    public function reset(Edition $edition, User $user): RedirectResponse
    {
        $this->assertCanManage($edition);

        if (! $user->isJudge()) {
            throw ValidationException::withMessages([
                'user' => 'O usuário informado não é julgador.',
            ]);
        }

        $deleted = $user->judgeSelections()
            ->whereHasMorph(
                'inscription',
                [Registration::class, Nominee::class],
                fn (Builder $query) => $query->whereHas(
                    'category.modality',
                    fn (Builder $modality) => $modality->where('edition_id', $edition->getKey())
                )
            )
            ->delete();

        return redirect()
            ->route('admin.acompanhamento.index', ['edition' => $edition->getKey()])
            ->with('success', "Seleções de {$user->name} apagadas ({$deleted}). O julgador pode votar novamente.");
    }

    /**
     * Finaliza a votação da edição, de forma definitiva (spec 0004 / RF-08).
     */
    public function close(Edition $edition): RedirectResponse
    {
        $this->assertCanManage($edition);

        $edition->forceFill(['voting_closed_at' => now()])->save();

        return redirect()
            ->route('admin.acompanhamento.index', ['edition' => $edition->getKey()])
            ->with('success', 'Votação finalizada. A confirmação de agraciados está liberada.');
    }

    /**
     * Tela de escolha dos agraciados da categoria (spec 0004 / RF-10).
     */
    public function awardees(Category $category): View|RedirectResponse
    {
        $edition = $category->modality->edition;

        if (! $edition->isVotingClosed()) {
            return redirect()
                ->route('admin.acompanhamento.index', ['edition' => $edition->getKey()])
                ->with('error', 'Finalize a votação antes de confirmar os agraciados.');
        }

        $type = $this->inscriptionTypeOf($category);
        $options = $this->inscriptionQuery($category)
            ->whereIn('status', self::FOLLOW_UP_STATUSES[$type])
            ->withCount('judgeSelections')
            ->orderBy('id')
            ->get();

        return view('admin.acompanhamento.agraciados', [
            'category' => $category,
            'edition' => $edition,
            'type' => $type,
            'top' => $options
                ->where('judge_selections_count', '>', 0)
                ->sortBy([['judge_selections_count', 'desc'], ['id', 'asc']])
                ->take(self::TOP_LIMIT)
                ->values(),
            'options' => $options,
            'awardees' => $options->where('status', RegistrationStatusEnum::Agraciado->value)->sortBy('award_position')->values(),
            'judgesCount' => User::query()->where('is_judge', true)->count(),
        ]);
    }

    /**
     * Confirma os agraciados da categoria na ordem recebida (1º lugar primeiro)
     * e trava a categoria (spec 0004 / RF-11, RF-12).
     */
    public function confirmAwardees(ConfirmAwardeesRequest $request, Category $category): RedirectResponse
    {
        $ids = array_map('intval', $request->validated('inscriptions'));

        DB::transaction(function () use ($category, $ids): void {
            $category = Category::query()->lockForUpdate()->findOrFail($category->getKey());

            if (! $category->modality->edition->isVotingClosed()) {
                throw ValidationException::withMessages([
                    'inscriptions' => 'Finalize a votação antes de confirmar os agraciados.',
                ]);
            }

            if ($category->areAwardeesConfirmed()) {
                throw ValidationException::withMessages([
                    'inscriptions' => 'Os agraciados desta categoria já foram confirmados.',
                ]);
            }

            if (count($ids) > (int) $category->recipients_count) {
                throw ValidationException::withMessages([
                    'inscriptions' => "Selecione no máximo {$category->recipients_count} agraciado(s).",
                ]);
            }

            $qualifiedStatus = $category->is_honorific
                ? RegistrationStatusEnum::Habilitado->value
                : RegistrationStatusEnum::Avaliado->value;

            foreach (array_values($ids) as $index => $id) {
                $updated = $this->inscriptionQuery($category)
                    ->where('id', $id)
                    ->where('status', $qualifiedStatus)
                    ->update([
                        'status' => RegistrationStatusEnum::Agraciado->value,
                        'award_position' => $index + 1,
                    ]);

                if ($updated !== 1) {
                    throw ValidationException::withMessages([
                        'inscriptions' => 'A lista contém inscrição que não pertence à categoria ou não está qualificada.',
                    ]);
                }
            }

            $category->forceFill(['awardees_confirmed_at' => now()])->save();
        });

        $edition = $category->modality->edition;

        if ($this->allAwardeesConfirmed($this->categoriesFor($edition))) {
            return redirect()
                ->route('admin.acompanhamento.edition-awardees', $edition)
                ->with('success', 'Agraciados confirmados. Todas as categorias da edição foram concluídas.');
        }

        return redirect()
            ->route('admin.acompanhamento.awardees', $category)
            ->with('success', 'Agraciados confirmados.');
    }

    /**
     * Tela final com os agraciados da edição por categoria, em ordem de
     * colocação; categorias ainda não confirmadas aparecem como pendentes.
     */
    public function editionAwardees(Edition $edition): View|RedirectResponse
    {
        if (! $edition->isVotingClosed()) {
            return redirect()
                ->route('admin.acompanhamento.index', ['edition' => $edition->getKey()])
                ->with('error', 'Finalize a votação antes de consultar os agraciados da edição.');
        }

        $categories = $this->categoriesFor($edition);

        return view('admin.acompanhamento.agraciados-edicao', [
            'edition' => $edition,
            'categories' => $categories,
            'awardeesByCategory' => $categories->mapWithKeys(fn (Category $category): array => [
                $category->getKey() => $this->inscriptionQuery($category)
                    ->where('status', RegistrationStatusEnum::Agraciado->value)
                    ->orderBy('award_position')
                    ->get(),
            ]),
            'isComplete' => $this->allAwardeesConfirmed($categories),
        ]);
    }

    /**
     * @param  EloquentCollection<int, Category>  $categories
     */
    private function allAwardeesConfirmed(EloquentCollection $categories): bool
    {
        return $categories->isNotEmpty()
            && $categories->every(fn (Category $category): bool => $category->areAwardeesConfirmed());
    }

    /**
     * Resetar e Finalizar só valem para edição em julgamento com votação aberta.
     */
    private function assertCanManage(Edition $edition): void
    {
        if (! $edition->isInJudging()) {
            throw ValidationException::withMessages([
                'edition' => 'A edição não está em julgamento.',
            ]);
        }

        if ($edition->isVotingClosed()) {
            throw ValidationException::withMessages([
                'edition' => 'A votação desta edição já foi finalizada.',
            ]);
        }
    }

    /**
     * Categorias da edição com Inscrições acompanhadas e `recipients_count > 0`,
     * regulares antes das honoríficas e `id` ASC (spec 0004 / RF-03).
     *
     * @return EloquentCollection<int, Category>
     */
    private function categoriesFor(Edition $edition): EloquentCollection
    {
        return Category::query()
            ->whereHas('modality', fn (Builder $query) => $query->where('edition_id', $edition->getKey()))
            ->where('recipients_count', '>', 0)
            ->where(fn (Builder $query) => $query
                ->whereHas('registrations', fn (Builder $registrations) => $registrations->whereIn('status', self::FOLLOW_UP_STATUSES['registration']))
                ->orWhereHas('nominees', fn (Builder $nominees) => $nominees->whereIn('status', self::FOLLOW_UP_STATUSES['nominee'])))
            ->withCount([
                'registrations as follow_up_registrations_count' => fn (Builder $query) => $query->whereIn('status', self::FOLLOW_UP_STATUSES['registration']),
                'nominees as follow_up_nominees_count' => fn (Builder $query) => $query->whereIn('status', self::FOLLOW_UP_STATUSES['nominee']),
            ])
            ->orderBy('is_honorific')
            ->orderBy('id')
            ->get();
    }

    /**
     * Inscrições acompanhadas das categorias, indexadas por chave "type:id".
     *
     * @param  EloquentCollection<int, Category>  $categories
     * @return Collection<string, Registration|Nominee>
     */
    private function inscriptionsFor(EloquentCollection $categories): Collection
    {
        $categoryIds = $categories->modelKeys();

        $registrations = Registration::query()
            ->whereIn('category_id', $categoryIds)
            ->whereIn('status', self::FOLLOW_UP_STATUSES['registration'])
            ->get(['id', 'category_id', 'title', 'status'])
            ->keyBy(fn (Registration $registration): string => 'registration:'.$registration->id);

        $nominees = Nominee::query()
            ->whereIn('category_id', $categoryIds)
            ->whereIn('status', self::FOLLOW_UP_STATUSES['nominee'])
            ->get(['id', 'category_id', 'name', 'status'])
            ->keyBy(fn (Nominee $nominee): string => 'nominee:'.$nominee->id);

        return $registrations->toBase()->merge($nominees->toBase());
    }

    /**
     * Seleções dos julgadores nas Inscrições acompanhadas.
     *
     * @param  Collection<string, Registration|Nominee>  $inscriptions
     * @return EloquentCollection<int, JudgeSelection>
     */
    private function selectionsFor(Collection $inscriptions): EloquentCollection
    {
        $idsByType = $inscriptions->groupBy(fn (Registration|Nominee $inscription): string => $inscription->getMorphClass())
            ->map(fn (Collection $group): array => $group->map->getKey()->values()->all());

        if ($idsByType->isEmpty()) {
            return new EloquentCollection;
        }

        return JudgeSelection::query()
            ->where(function (Builder $query) use ($idsByType): void {
                foreach ($idsByType as $type => $ids) {
                    $query->orWhere(fn (Builder $byType) => $byType->where('inscription_type', $type)->whereIn('inscription_id', $ids));
                }
            })
            ->get(['user_id', 'inscription_type', 'inscription_id']);
    }

    /**
     * Status de cada célula "{user}:{category}": `finalizada` quando o julgador
     * completou a cota efetiva `min(recipients_count, elegíveis)` (spec 0004 / RF-04).
     *
     * @param  EloquentCollection<int, User>  $judges
     * @param  EloquentCollection<int, Category>  $categories
     * @param  Collection<string, Registration|Nominee>  $inscriptions
     * @param  EloquentCollection<int, JudgeSelection>  $selections
     * @return array<string, string>
     */
    private function matrixFor(EloquentCollection $judges, EloquentCollection $categories, Collection $inscriptions, EloquentCollection $selections): array
    {
        $selectionsByCell = $selections->countBy(function (JudgeSelection $selection) use ($inscriptions): string {
            $inscription = $inscriptions->get($selection->inscription_type.':'.$selection->inscription_id);

            return $selection->user_id.':'.$inscription->category_id;
        });

        $matrix = [];

        foreach ($judges as $judge) {
            foreach ($categories as $category) {
                $cell = $judge->getKey().':'.$category->getKey();
                $quota = $this->effectiveQuotaFor($category);

                $matrix[$cell] = $quota > 0 && $selectionsByCell->get($cell, 0) >= $quota ? 'finalizada' : 'aberta';
            }
        }

        return $matrix;
    }

    /**
     * Cota efetiva igual à do `/julgar` (spec 0003 / RF-06).
     */
    private function effectiveQuotaFor(Category $category): int
    {
        $eligible = $category->is_honorific
            ? (int) $category->follow_up_nominees_count
            : min(self::CARD_LIMIT, (int) $category->follow_up_registrations_count);

        return min((int) $category->recipients_count, $eligible);
    }

    /**
     * Pendências "Julgador — SIGLA" exibidas na confirmação de "Finalizar votação" (RF-08).
     *
     * @param  EloquentCollection<int, User>  $judges
     * @param  EloquentCollection<int, Category>  $categories
     * @param  array<string, string>  $matrix
     * @return list<string>
     */
    private function pendingFor(EloquentCollection $judges, EloquentCollection $categories, array $matrix): array
    {
        $pending = [];

        foreach ($judges as $judge) {
            foreach ($categories as $category) {
                if ($matrix[$judge->getKey().':'.$category->getKey()] === 'aberta') {
                    $pending[] = $judge->name.' — '.($category->acronym ?: $category->title);
                }
            }
        }

        return $pending;
    }

    /**
     * Votos por categoria: Inscrições com ao menos 1 voto, votos DESC e `id` ASC (RF-07).
     *
     * @param  EloquentCollection<int, Category>  $categories
     * @param  Collection<string, Registration|Nominee>  $inscriptions
     * @param  EloquentCollection<int, JudgeSelection>  $selections
     * @return array<int, list<array{key: string, label: string, votes: int}>>
     */
    private function votesFor(EloquentCollection $categories, Collection $inscriptions, EloquentCollection $selections): array
    {
        $votesByKey = $selections->countBy(fn (JudgeSelection $selection): string => $selection->inscription_type.':'.$selection->inscription_id);

        return $categories->mapWithKeys(fn (Category $category): array => [
            $category->getKey() => $votesByKey
                ->map(fn (int $votes, string $key): array => [
                    'key' => $key,
                    'inscription' => $inscriptions->get($key),
                    'votes' => $votes,
                ])
                ->filter(fn (array $vote): bool => $vote['inscription']->category_id === $category->getKey())
                ->sortBy([['votes', 'desc'], [fn (array $a, array $b): int => $a['inscription']->getKey() <=> $b['inscription']->getKey()]])
                ->map(fn (array $vote): array => [
                    'key' => $vote['key'],
                    'label' => trim($category->acronym.' '.$vote['inscription']->getKey()).' — '
                        .($vote['inscription'] instanceof Nominee ? $vote['inscription']->name : $vote['inscription']->title),
                    'votes' => $vote['votes'],
                ])
                ->values()
                ->all(),
        ])->all();
    }

    private function inscriptionTypeOf(Category $category): string
    {
        return $category->is_honorific ? 'nominee' : 'registration';
    }

    /**
     * Inscrições da categoria conforme o tipo (honorífica → `Nominee`; regular → `Registration`).
     */
    private function inscriptionQuery(Category $category): Builder
    {
        return $category->is_honorific
            ? Nominee::query()->where('category_id', $category->getKey())
            : Registration::query()->where('category_id', $category->getKey());
    }
}
