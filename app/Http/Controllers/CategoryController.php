<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Modality;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {

        $query = Category::with('modality.edition')->orderBy('title', 'desc'); // or 'created_at'
        $search = $request->input('search');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%');
            });
        }

        $categories = $query->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modalities = Modality::active()
            ->whereHas('edition', function ($query) {
                $query->where('is_registration_active', true);
            })->get();

        return view('admin.categories.create', compact('modalities'));
    }

    public function requestValidade(Request $request)
    {

        $fieldsToInt = [
            'nominations_count' => 1,
            'evaluations_count' => 0,
            'recipients_count' => 0,
            'submissions_per_candidate' => 1,
        ];

        foreach ($fieldsToInt as $field => $value) {
            if (! $request->has($field) || $request->input($field) === null) {
                $request->merge([$field => $value]);
            }
        }

        $messages = [
            'edition_id.required' => 'A edição é obrigatória.',
            'title.required' => 'O título é obrigatório.',
            'candidacy_limit_per_modality.required' => 'A quantidade de Inscrições por candidato é obrigatória',
            'judging_end.after' => 'Deve ser uma data posterior a Início do Julgamento.',
        ];

        $rules = [
            'modality_id' => 'required|string',
            'title' => 'required|string|max:255',
            'acronym' => 'required|string|max:10|unique:categories,acronym',
            'description' => 'required|filled|string',
            'nominations_count' => 'integer',
            'evaluations_count' => 'integer',
            'recipients_count' => 'integer',
            'submissions_per_candidate' => 'integer',
            'judging_start' => 'required|date',
            'judging_end' => 'required|date|after:judging_start',
            'is_open_for_submissions' => 'nullable|boolean',
        ];

        if ($request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $categoryId = $request->route('id'); // ou $request->route()->parameter('id'), dependendo da sua versão do Laravel
            $rules['acronym'] .= ','.$categoryId; // Ignora o ID atual na verificação única
        }

        $request->validate($rules, $messages);

    }

    public function getDataRequest(Request $request)
    {
        $data = $request->only([
            'modality_id',
            'title',
            'acronym',
            'description',
            'nominations_count',
            'evaluations_count',
            'recipients_count',
            'submissions_per_candidate',
            'judging_start',
            'judging_end',
            'is_open_for_submissions',
        ]);

        $data['is_open_for_submissions'] = $request->boolean('is_open_for_submissions');
        $data['is_honorific'] = $request->boolean('is_honorific');

        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->requestValidade($request);
        $data = $this->getDataRequest($request);
        $category = Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoria criada com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $category = Category::with('modality')->findOrFail($id);
        $modalities = Modality::active()
            ->whereHas('edition', function ($query) {
                $query->where('is_registration_active', true);
            })->get();

        return view('admin.categories.create', compact('modalities', 'category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->requestValidade($request);
        $data = $this->getDataRequest($request);
        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoria atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        $category->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Categoria removida com sucesso.'], 201);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Modalidade removida com sucesso.');
    }
}
