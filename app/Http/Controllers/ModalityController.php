<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\Modality;
use Illuminate\Http\Request;

class ModalityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = Modality::with('edition')->orderBy('title', 'desc'); // or 'created_at'
        $search = $request->input('search');
        $active = $request->input('active');

        if ($search) {
            $query->where(function ($q) use ($search, $active) {
                $q->where('title', 'like', '%'.$search.'%');
                if ($active) {
                    $q->orWhere('is_active', $active === 'active' ? 1 : ($active === 'inactive' ? 0 : null));
                }
            });
        }

        $modalities = $query->paginate(15)->withQueryString();

        return view('admin.modalities.index', compact('modalities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $editions = Edition::active()->get();

        return view('admin.modalities.create', compact('editions'));
    }

    public function requestValidade(Request $request)
    {
        $messages = [
            'edition_id.required' => 'A edição é obrigatória.',
            'title.required' => 'O título é obrigatório.',
            'candidacy_limit_per_modality.required' => 'A quantidade de Inscrições por candidato é obrigatória',
        ];

        $request->validate([
            'edition_id' => 'required|string',
            'title' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'candidacy_limit_per_modality' => 'required|integer|min:1',
        ], $messages);

    }

    public function getDataRequest(Request $request)
    {
        $data = $request->only([
            'edition_id',
            'title',
            'is_active',
            'applications_per_candidate',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->requestValidade($request);
        $data = $this->getDataRequest($request);
        $modality = Modality::create($data);

        return redirect()->route('admin.modalities.index')
            ->with('success', 'Modalidade criada com sucesso.');
    }

    public function edit(string $id)
    {
        $modality = Modality::with('edition')->findOrFail($id);
        $editions = Edition::all();

        return view('admin.modalities.create', compact('modality', 'editions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $edition = Modality::findOrFail($id);

        $this->requestValidade($request);

        $data = $this->getDataRequest($request);

        $edition->update($data);

        return redirect()->route('admin.modalities.index')
            ->with('success', 'Modalidade atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $modality = Modality::findOrFail($id);

        $modality->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Modalidade removida com sucesso.'], 201);
        }

        return redirect()->route('admin.modalities.index')
            ->with('success', 'Modalidade removida com sucesso.');
    }
}
