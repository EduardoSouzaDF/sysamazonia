<?php

namespace App\Http\Requests;

use App\Enum\RegistrationStatusEnum;
use App\Models\Category;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class JudgeSelectionRequest extends FormRequest
{
    /**
     * Limite de cards do grid para categorias regulares
     * (spec 0003 / RF-03 — espelha JudgingController::CARD_LIMIT).
     */
    private const CARD_LIMIT = 20;

    /**
     * O perfil julgador é garantido pelo middleware `CheckJudge`
     * (spec 0002 / NM-02); nenhuma regra adicional de autorização aqui.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras da submissão do julgamento de uma categoria (spec 0003):
     * `category_id` obrigatório/existente e estrutura de `inscriptions`
     * (array sem duplicados, `size` = cota efetiva restante; cada item com
     * `type ∈ {registration, nominee}` + `id`).
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],

            'inscriptions' => [
                'required',
                'array',
                $this->distinctInscriptionsRule(),
                $this->quotaSizeRule(),
            ],

            'inscriptions.*.type' => ['required', 'string', 'in:registration,nominee'],
            'inscriptions.*.id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Mensagens customizadas em pt-BR (regra `.clinerules/php.md`).
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.integer' => 'A categoria informada é inválida.',
            'category_id.exists' => 'A categoria informada não existe.',

            'inscriptions.required' => 'A lista de inscrições é obrigatória.',
            'inscriptions.array' => 'A lista de inscrições é inválida.',

            'inscriptions.*.type.required' => 'O tipo de cada inscrição é obrigatório.',
            'inscriptions.*.type.in' => 'O tipo de inscrição deve ser "registration" ou "nominee".',

            'inscriptions.*.id.required' => 'O identificador de cada inscrição é obrigatório.',
            'inscriptions.*.id.integer' => 'O identificador de cada inscrição é inválido.',
            'inscriptions.*.id.min' => 'O identificador de cada inscrição é inválido.',
        ];
    }

    /**
     * Sem duplicidade no payload (par `type` + `id`); a regra de negócio
     * (unique de judge_selections) é revalidada no controller, em transação.
     */
    private function distinctInscriptionsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $keys = collect((array) $value)
                ->map(fn ($item): string => ($item['type'] ?? '?').':'.($item['id'] ?? '?'));

            if ($keys->unique()->count() !== $keys->count()) {
                $fail('A lista de inscrições contém itens duplicados.');
            }
        };
    }

    /**
     * `size` = cota efetiva restante: `min(recipients_count, cards)` menos as
     * seleções já registradas pelo julgador nesta categoria (spec 0003 / RF-06).
     */
    private function quotaSizeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $category = Category::find((int) $this->integer('category_id'));

            if ($category === null) {
                return; // o erro já é apontado por category_id.exists
            }

            $user = $this->user();
            $effective = $this->effectiveQuotaFor($category, $user);
            $selected = $category->judgeSelectionsBy($user);

            if ($effective === 0) {
                $fail('Não há inscrições qualificadas nesta categoria.');

                return;
            }

            if ($selected >= $effective) {
                $fail('Esta categoria já foi concluída.');

                return;
            }

            $expected = $effective - $selected;

            if (count((array) $value) !== $expected) {
                $fail("A seleção deve conter exatamente {$expected} inscrição(ões), conforme a cota desta categoria.");
            }
        };
    }

    /**
     * Cota efetiva da categoria (spec 0003 / RF-06): `recipients_count`
     * limitado pelos cards exibíveis — `Nominee` qualificadas (todas) para
     * honoríficas; top 20 de `Registration` qualificadas para regulares.
     */
    private function effectiveQuotaFor(Category $category, User $user): int
    {
        $editionFilter = $this->judgingEditionFilter();

        $eligible = $category->is_honorific
            ? Nominee::query()
                ->where('category_id', $category->getKey())
                ->where('status', RegistrationStatusEnum::Habilitado->value)
                ->whereHas('category.modality.edition', $editionFilter)
                ->count()
            : min(self::CARD_LIMIT, Registration::query()
                ->where('category_id', $category->getKey())
                ->where('status', RegistrationStatusEnum::Avaliado->value)
                ->whereHas('category.modality.edition', $editionFilter)
                ->count());

        return min((int) $category->recipients_count, $eligible);
    }

    /**
     * Edições ativas (RDD-01) e em julgamento (RDD-03) — espelho do filtro
     * privado de JudgingController (mesma regra de domínio).
     */
    private function judgingEditionFilter(): Closure
    {
        return function (Builder $query): void {
            $query->where('is_registration_active', true)
                ->whereDate('judgment_date', '<', today()->toDateString());
        };
    }
}
