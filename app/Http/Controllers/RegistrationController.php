<?php

namespace App\Http\Controllers;

use App\Enum\RegistrationStatusEnum;
use App\Http\Requests\StoreOpinionRequest;
use App\Models\Edition;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\RegistrationFile;
use App\Services\OpinionSubmissionService;
use Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {

        $user = Auth::user();
        $editions = Edition::all();
        $page = $request->input('page', 1);
        $perPage = 15;

        // Query para Registration
        $registrationQuery = Registration::with(['candidate', 'category.modality.edition', 'files', 'opinions']);
        $nomineeQuery = Nominee::with(['candidate', 'category.modality.edition', 'files']);

        $registrationQuery = $this->setIndexFilters($registrationQuery, $request);
        $nomineeQuery = $this->setIndexFilters($nomineeQuery, $request);

        if ($user->isEvaluator()) {
            $registrationQuery = $this->filterEvaluatorCategories($registrationQuery, $user);
        }

        if ($user->isIndicator()) {
            $registrationQuery = $this->filterIndicatorCategories($registrationQuery, $user);
        }

        // Obter todos os resultados (ou limitar conforme necessário)
        $registrations = $registrationQuery->get();
        $nominees = $nomineeQuery->get();
        if ($user->isEvaluator() || $user->isIndicator()) {
            $nominees = Collection::empty();
        }

        // Combinar os resultados
        $allItems = $registrations->merge($nominees);

        // Ordenar por created_at (ou outro campo)
        $allItems = $allItems->sortByDesc('created_at');

        // Paginação manual
        $total = $allItems->count();
        $items = $allItems->forPage($page, $perPage);
        $list = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'pageName' => 'page',
        ]);

        return view('admin.registration.index', compact('list', 'editions'));
    }

    private function setIndexFilters($query, $request)
    {
        $search = $request->input('search');
        $edition = $request->input('edition');
        $status = $request->input('status');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function ($subQ) use ($search) {
                        $subQ->where('title', 'like', '%'.$search.'%')
                            ->orWhere('acronym', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('candidate', function ($subQ) use ($search) {
                        $subQ->where('cpf', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($edition) {
            $query->where(function ($q) use ($edition) {
                $q->whereHas('category.modality.edition', function ($subq) use ($edition) {
                    $subq->where('id', $edition);
                });
            });
        }

        if ($status) {
            $query->where(function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        return $query;
    }

    private function filterIndicatorCategories($query, $user)
    {
        $query->whereHas('category', function ($q) use ($user) {
            $q->whereIn('id', $user->indicatorCategories()->pluck('categories.id'))
                ->where('is_honorific', false)
                ->where(function ($cat) {
                    $cat->whereRaw('(
                            SELECT COUNT(*)
                            FROM indications
                            WHERE indications.registration_id = registrations.id
                        ) < categories.nominations_count');
                });
        });

        //     // somente das edições ativar para Avaliador
        $query->whereHas('category.modality.edition', function ($q) {
            $q->where('is_registration_active', true);
        });

        //     // não mostra as inscrições que já avaliou
        $query->whereDoesntHave('indications', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        $query->where('status', RegistrationStatusEnum::Habilitado)->orWhere('status', RegistrationStatusEnum::Avaliado);

        return $query;
    }

    private function filterEvaluatorCategories($query, $user)
    {
        // somente das categorias do usuário logado
        $query->whereHas('category', function ($q) use ($user) {
            $q->whereIn('id', $user->evaluatorCategories()->pluck('categories.id'))
                ->where('is_honorific', false)
                ->where(function ($cat) {
                    $cat->whereRaw('(
                            SELECT COUNT(*)
                            FROM opinions
                            WHERE opinions.registration_id = registrations.id
                        ) < categories.evaluations_count');
                });
        });

        // somente das edições ativar para Avaliador
        $query->whereHas('category.modality.edition', function ($q) {
            $q->where('is_registration_active', true);
        });

        // não mostra as inscrições que já avaliou
        $query->whereDoesntHave('opinions', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        $query->where('status', RegistrationStatusEnum::Habilitado);

        return $query;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, string $type, Request $request)
    {

        if ($type == Nominee::class) {
            $registration = Nominee::findOrFail($id);
        } else {
            $registration = Registration::findOrFail($id);
        }
        $registrations = $registration->candidate->registrations->concat($registration->candidate->nominees);

        $candidate = $registration->candidate;

        $user = Auth::user();
        if (! $user->isAdmin()) {
            $registrations = [$registration];
            $candidate = [];
        } else {

        }

        return view('admin.registration.view', compact('registrations', 'candidate'));
    }

    public function file(string $file)
    {

        $file = RegistrationFile::findOrFail($file);
        if (! Storage::disk('private')->exists($file->file_path)) {
            abort(404);
        }
        $disk = Storage::disk('private');

        return $disk->response($file->file_path);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Registration $registration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Registration $registration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Registration $registration)
    {
        //
    }

    public function habilitar($id, $type)
    {
        if ($type == 'AppModelsNominee') {
            $registration = Nominee::findOrFail($id);
        } else {
            $registration = Registration::findOrFail($id);
        }
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            try {
                $registration->status = 3;
                $registration->update();

                return response()->json(['message' => 'Registro Habilitado'], 200);
            } catch (\Throwable $th) {
                return response()->json(['message' => 'Erro na operação'], 400);
            }

        }

        return response()->json(['message' => 'Usuário sem permissão'], 419);
    }

    public function indicar($id)
    {

        $registration = Registration::findOrFail($id);
        $user = auth()->user();
        if ($user->isIndicator()) {
            try {
                $justificativa = request('justificativa');
                DB::transaction(function () use ($user, $registration, $justificativa) {
                    $registration->indications()->create([
                        'user_id' => $user->id,
                        'descricao' => $justificativa,
                    ]);
                });

                return response()->json(['message' => 'Registro Indicado'], 200);
            } catch (\Throwable $th) {
                return response()->json(['message' => 'Erro na operação'], 400);
            }

        }

        return response()->json(['message' => 'Usuário sem permissão'], 419);
    }

    public function rejeitar($id, $type)
    {

        if ($type == 'AppModelsNominee') {
            $registration = Nominee::findOrFail($id);
        } else {
            $registration = Registration::findOrFail($id);
        }

        $user = auth()->user();
        if ($user->hasRole('admin')) {
            try {
                $registration->status = 2;
                $registration->update();

                return response()->json(['message' => 'Registro Rejeitado'], 200);
            } catch (\Throwable $th) {
                return response()->json(['message' => 'Erro na operação'], 400);
            }

        }

        return response()->json(['message' => 'Usuário sem permissão'], 419);
    }

    /**
     * Registra um parecer (Opinion) do avaliador logado para a inscrição,
     * com seus respectivos Scores por critério de avaliação.
     */
    public function sendOpinion(StoreOpinionRequest $request, Registration $registration, OpinionSubmissionService $submissionService)
    {
        $user = Auth::user();

        try {
            $submissionService->submit($registration, $user, $request->scores());

            return redirect()
                ->route('admin.registration.index')
                ->with('success', 'Avaliação enviada com sucesso!');
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return redirect()
                ->route('admin.registration.index')
                ->with('error', 'Esta avaliação já foi enviada.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $th) {
            return redirect()
                ->route('admin.registration.index')
                ->with('error', 'Favor tente novamente');
        }
    }
}
