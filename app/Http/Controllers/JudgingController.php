<?php

namespace App\Http\Controllers;

use App\Enum\RegistrationStatusEnum;
use App\Http\Requests\JudgeSelectionRequest;
use App\Models\Category;
use App\Models\JudgeSelection;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JudgingController extends Controller
{
    /**
     * Limite de cards do grid para categorias regulares (spec 0003 / RF-03).
     */
    private const CARD_LIMIT = 20;

    /**
     * Painel do julgador — wizard categoria a categoria (spec 0003 / RF-01, RF-02).
     *
     * Calcula as categorias elegíveis (com Inscrições qualificadas — RDD-02 — de
     * edições ativas (RDD-01) e em julgamento (RDD-03), `recipients_count > 0` e
     * cota efetiva ainda não completada pelo julgador), ordena regulares →
     * honoríficas (`id` ASC) e exibe somente a primeira. Sem categoria elegível,
     * a view recebe o resumo de conclusão (ou `null` para o estado vazio
     * "Nenhuma categoria disponível para julgamento." — NM-05).
     *
     * Variáveis da view `admin.julgar.index`:
     * - `$category`       → Category atual (ou null);
     * - `$cards`          → cards da categoria (RF-03/RF-04): coleção de
     *                       `['key', 'type', 'label', 'year', 'inscription']`;
     * - `$effectiveQuota` → cota efetiva `min(recipients_count, cards)` (RF-06);
     * - `$remainingQuota` → saldo restante a selecionar;
     * - `$selectedIds`    → chaves "type:id" já gravadas nesta categoria;
     * - `$summary`        → resumo de conclusão agrupado por categoria (ou null).
     */
    public function index()
    {
        $user = auth()->user();

        $current = $this->eligibleCategoriesFor($user)->first();

        if ($current === null) {
            return view('admin.julgar.index', [
                'category' => null,
                'cards' => collect(),
                'effectiveQuota' => 0,
                'remainingQuota' => 0,
                'selectedIds' => [],
                'summary' => $this->conclusionSummaryFor($user),
            ]);
        }

        $category = $current['category'];
        $cards = $this->cardsFor($category);

        return view('admin.julgar.index', [
            'category' => $category,
            'cards' => $cards,
            'effectiveQuota' => min((int) $category->recipients_count, $cards->count()),
            'remainingQuota' => $current['remaining'],
            'selectedIds' => $this->selectedKeysFor($category, $user),
            'summary' => null,
        ]);
    }

    /**
     * Grava as seleções do julgador para a categoria atual (spec 0003).
     *
     * O `JudgeSelectionRequest` valida a estrutura do payload; aqui, em
     * transação, as regras de negócio são revalidadas (categoria é a atual
     * elegível e não concluída; inscrições pertencem a ela e são qualificadas;
     * cota exata; sem seleções duplicadas). Qualquer violação responde erro de
     * validação (422/redirect back) sem gravar nada (rollback).
     */
    public function store(JudgeSelectionRequest $request)
    {
        $payload = $request->validated();
        $user = $request->user();
        $category = Category::findOrFail($payload['category_id']);

        DB::transaction(function () use ($category, $payload, $user): void {
            $this->assertCurrentCategory($category, $user);
            $this->assertQuota($category, $user, $payload['inscriptions']);

            $inscriptions = $this->resolveInscriptions($category, $payload['inscriptions'], $user);

            foreach ($inscriptions as $inscription) {
                JudgeSelection::create([
                    'user_id' => $user->getKey(),
                    'inscription_type' => $inscription->getMorphClass(),
                    'inscription_id' => $inscription->getKey(),
                ]);
            }
        });

        return redirect()
            ->route('panel.julgar.index')
            ->with('success', 'Seleções registradas com sucesso.');
    }

    /**
     * Categorias elegíveis do julgador (spec 0003 / RF-01, RF-02), ordenadas
     * regulares → honoríficas, `id` ASC.
     *
     * @return Collection<int, array{category: Category, remaining: int}>
     */
    private function eligibleCategoriesFor(User $user): Collection
    {
        $editionFilter = $this->judgingEditionFilter();

        // Categorias com Inscrições qualificadas (RDD-02) em edições
        // ativas (RDD-01) e em julgamento (RDD-03).
        $categoryIds = Registration::query()
            ->where('status', RegistrationStatusEnum::Avaliado->value)
            ->whereHas('category.modality.edition', $editionFilter)
            ->pluck('category_id')
            ->merge(
                Nominee::query()
                    ->where('status', RegistrationStatusEnum::Habilitado->value)
                    ->whereHas('category.modality.edition', $editionFilter)
                    ->pluck('category_id')
            )
            ->unique();

        if ($categoryIds->isEmpty()) {
            return collect();
        }

        return Category::query()
            ->with('modality.edition')
            ->whereIn('id', $categoryIds)
            ->where('recipients_count', '>', 0)
            ->orderBy('is_honorific') // RF-02: regulares primeiro
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => [
                'category' => $category,
                'remaining' => $this->remainingQuotaFor($category, $user),
            ])
            ->filter(fn (array $item): bool => $item['remaining'] > 0)
            ->values();
    }

    /**
     * Primeira categoria elegível (RF-02) — ou null quando não há nenhuma.
     */
    private function currentCategoryFor(User $user): ?Category
    {
        return $this->eligibleCategoriesFor($user)->first()['category'] ?? null;
    }

    /**
     * Cota restante a selecionar na categoria: cota efetiva
     * `min(recipients_count, cards)` menos as seleções já registradas
     * (spec 0003 / RF-06). Cards exibíveis: todas as `Nominee` qualificadas
     * (honoríficas) ou top 20 de `Registration` qualificadas (regulares).
     */
    private function remainingQuotaFor(Category $category, User $user): int
    {
        $editionFilter = $this->judgingEditionFilter();

        $eligible = $category->is_honorific
            ? $category->nominees()
                ->where('status', RegistrationStatusEnum::Habilitado->value)
                ->whereHas('category.modality.edition', $editionFilter)
                ->count()
            : min(self::CARD_LIMIT, $category->registrations()
                ->where('status', RegistrationStatusEnum::Avaliado->value)
                ->whereHas('category.modality.edition', $editionFilter)
                ->count());

        return max(0, min((int) $category->recipients_count, $eligible) - $category->judgeSelectionsBy($user));
    }

    /**
     * Cards da categoria corrente (spec 0003 / RF-03, RF-04, RF-05):
     * `Registration` → top 20 por indicações DESC, `evaluation_avg` DESC
     * (nulas por último), `id` ASC; `Nominee` → todas as "Habilitado", `id` ASC.
     * Eager loading do drawer (RF-05): candidate, category.modality.edition,
     * files e — só para `Registration` — opiniões/notas/indicações com autores.
     *
     * @return Collection<int, array{key: string, type: string, label: string, year: ?string, inscription: Registration|Nominee}>
     */
    private function cardsFor(Category $category): Collection
    {
        if ($category->is_honorific) {
            return $category->nominees()
                ->with(['candidate', 'category.modality.edition', 'files'])
                ->where('status', RegistrationStatusEnum::Habilitado->value)
                ->whereHas('category.modality.edition', $this->judgingEditionFilter())
                ->orderBy('id')
                ->get()
                ->map(fn (Nominee $nominee): array => $this->cardPayload($nominee, 'nominee'))
                ->values();
        }

        return $category->registrations()
            ->with([
                'candidate',
                'category.modality.edition',
                'files',
                'opinions.user',
                'opinions.scores.evaluationCriterion',
                'indications.user',
            ])
            ->withCount('indications')
            ->where('status', RegistrationStatusEnum::Avaliado->value)
            ->whereHas('category.modality.edition', $this->judgingEditionFilter())
            ->orderByDesc('indications_count')
            ->orderByRaw('COALESCE(evaluation_avg, -1) DESC') // nulas por último
            ->orderBy('id')
            ->limit(self::CARD_LIMIT)
            ->get()
            ->map(fn (Registration $registration): array => $this->cardPayload($registration, 'registration'))
            ->values();
    }

    /**
     * Payload do card (RF-04): label "{acronym} {id}" + ano de `judgment_date`
     * da edição → "{label} - {year}" (ex.: "PSD 3232 - 2026").
     *
     * @return array{key: string, type: string, label: string, year: ?string, inscription: Registration|Nominee}
     */
    private function cardPayload(Registration|Nominee $inscription, string $type): array
    {
        $category = $inscription->category;
        $edition = $category?->modality?->edition;

        return [
            'key' => $type.':'.$inscription->getKey(),
            'type' => $type,
            'label' => trim(($category?->acronym ?? '').' '.$inscription->getKey()),
            'year' => $edition?->judgment_date?->format('Y'),
            'inscription' => $inscription,
        ];
    }

    /**
     * Chaves "type:id" das seleções já gravadas pelo julgador nas inscrições
     * da categoria (pré-seleção renderizada — spec 0003 / RF-06).
     *
     * @return list<string>
     */
    private function selectedKeysFor(Category $category, User $user): array
    {
        return $user->judgeSelections()
            ->whereHasMorph(
                'inscription',
                [Registration::class, Nominee::class],
                fn (Builder $query) => $query->where('category_id', $category->getKey())
            )
            ->get()
            ->map(fn (JudgeSelection $selection): string => $selection->inscription_type.':'.$selection->inscription_id)
            ->all();
    }

    /**
     * Resumo de conclusão (BDD "sem categoria elegível"): seleções do julgador
     * agrupadas por categoria, em leitura. `null` quando não há seleções
     * (estado vazio "Nenhuma categoria disponível para julgamento." — NM-05).
     *
     * @return Collection<int, array{category: Category, inscriptions: Collection}>|null
     */
    private function conclusionSummaryFor(User $user): ?Collection
    {
        $selections = $user->judgeSelections()
            ->with('inscription.category.modality.edition')
            ->orderBy('inscription_id')
            ->get();

        if ($selections->isEmpty()) {
            return null;
        }

        return $selections
            ->filter(fn (JudgeSelection $selection): bool => $selection->inscription?->category !== null)
            ->groupBy(fn (JudgeSelection $selection): int => $selection->inscription->category->getKey())
            ->map(fn (Collection $group): array => [
                'category' => $group->first()->inscription->category,
                'inscriptions' => $group->map(
                    fn (JudgeSelection $selection): array => $this->cardPayload(
                        $selection->inscription,
                        $selection->inscription->getMorphClass()
                    )
                ),
            ])
            ->values();
    }

    /**
     * Revalidação (transação): a categoria deve ser a atual elegível e não
     * estar concluída (spec 0003 / RF-01).
     */
    private function assertCurrentCategory(Category $category, User $user): void
    {
        $current = $this->currentCategoryFor($user);

        if ($current === null || $current->isNot($category)) {
            throw ValidationException::withMessages([
                'category_id' => 'A categoria informada não é a categoria atual do julgamento.',
            ]);
        }

        if ($category->isFullyJudgedBy($user)) {
            throw ValidationException::withMessages([
                'category_id' => 'Esta categoria já foi concluída.',
            ]);
        }
    }

    /**
     * Revalidação (transação): a quantidade enviada deve ser exatamente a
     * cota restante (cota efetiva − seleções já gravadas — RF-06).
     *
     * @param  list<array{type: string, id: int|string}>  $items
     */
    private function assertQuota(Category $category, User $user, array $items): void
    {
        $expected = $this->remainingQuotaFor($category, $user);

        if (count($items) !== $expected) {
            throw ValidationException::withMessages([
                'inscriptions' => "A seleção deve conter exatamente {$expected} inscrição(ões), conforme a cota desta categoria.",
            ]);
        }
    }

    /**
     * Revalidação (transação): cada inscrição enviada deve pertencer à
     * categoria, estar qualificada (RDD-01/02/03) e não ter sido selecionada
     * antes (unique de judge_selections como última barreira).
     *
     * @param  list<array{type: string, id: int|string}>  $items
     * @return list<Registration|Nominee>
     */
    private function resolveInscriptions(Category $category, array $items, User $user): array
    {
        $inscriptions = [];

        foreach ($items as $item) {
            $inscription = $item['type'] === 'registration'
                ? $this->qualifiedRegistration($category, (int) $item['id'])
                : $this->qualifiedNominee($category, (int) $item['id']);

            if ($inscription === null) {
                throw ValidationException::withMessages([
                    'inscriptions' => 'A lista contém inscrição que não pertence à categoria ou não está qualificada para julgamento.',
                ]);
            }

            if ($user->hasSelected($inscription)) {
                throw ValidationException::withMessages([
                    'inscriptions' => 'A lista contém inscrição já selecionada anteriormente.',
                ]);
            }

            $inscriptions[] = $inscription;
        }

        return $inscriptions;
    }

    /**
     * `Registration` qualificada ("Avaliado") da categoria, em edição
     * ativa (RDD-01) e em julgamento (RDD-03).
     */
    private function qualifiedRegistration(Category $category, int $id): ?Registration
    {
        return Registration::query()
            ->where('id', $id)
            ->where('category_id', $category->getKey())
            ->where('status', RegistrationStatusEnum::Avaliado->value)
            ->whereHas('category.modality.edition', $this->judgingEditionFilter())
            ->first();
    }

    /**
     * `Nominee` qualificada ("Habilitado") da categoria, em edição
     * ativa (RDD-01) e em julgamento (RDD-03).
     */
    private function qualifiedNominee(Category $category, int $id): ?Nominee
    {
        return Nominee::query()
            ->where('id', $id)
            ->where('category_id', $category->getKey())
            ->where('status', RegistrationStatusEnum::Habilitado->value)
            ->whereHas('category.modality.edition', $this->judgingEditionFilter())
            ->first();
    }

    /**
     * Filtro de edições ativas (RDD-01) e em julgamento (RDD-03):
     * `is_registration_active = true` e data atual > `judgment_date`.
     */
    private function judgingEditionFilter(): Closure
    {
        return function (Builder $query): void {
            $query->where('is_registration_active', true)
                ->whereDate('judgment_date', '<', today()->toDateString());
        };
    }
}
