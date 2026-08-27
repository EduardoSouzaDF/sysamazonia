<?php

namespace App\Http\Controllers;

use App\Enum\RegistrationStatusEnum;
use App\Models\Nominee;
use App\Models\Registration;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class JudgingController extends Controller
{
    /**
     * Painel do julgador: lista todas as Inscrições (RDD-02) de edições ativas
     * (RDD-01) e em julgamento (RDD-03), com `Registration` em status "Avaliado"
     * e `Nominee` em status "Habilitado" (spec 0002 / FR-03).
     *
     * Sem paginação (v1.7.0): retorna a view com a collection completa de
     * Inscrições (união via `toBase()->merge()`), mais recentes primeiro.
     */
    public function index()
    {
        $registrations = $this->judgingRegistrations()->get();
        $nominees = $this->judgingNominees()->get();

        // União das Inscrições (RDD-02), mais recentes primeiro.
        // toBase() + merge(): `Eloquent\Collection::merge()` deduplica por id do model
        // (Registration e Nominee têm ids independentes e podem colidir), descartando
        // registros indevidamente.
        $list = $registrations->toBase()->merge($nominees)->sortByDesc('created_at');

        return view('admin.julgar.index', compact('list'));
    }

    /**
     * Inscrições regulares (Registration) aptas a julgamento.
     */
    private function judgingRegistrations(): Builder
    {
        return Registration::query()
            ->with(['candidate', 'category.modality.edition', 'files'])
            ->where('status', RegistrationStatusEnum::Avaliado->value)
            ->whereHas('category.modality.edition', $this->judgingEditionFilter());
    }

    /**
     * Inscrições honoríficas (Nominee) aptas a julgamento.
     */
    private function judgingNominees(): Builder
    {
        return Nominee::query()
            ->with(['candidate', 'category.modality.edition', 'files'])
            ->where('status', RegistrationStatusEnum::Habilitado->value)
            ->whereHas('category.modality.edition', $this->judgingEditionFilter());
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
