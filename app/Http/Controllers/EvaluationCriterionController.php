<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\EvaluationCriterion;
use Illuminate\Http\Request;

class EvaluationCriterionController extends Controller
{
    public function index(Request $request)
    {
        $query = EvaluationCriterion::with('category.modality.edition')->orderBy('name', 'asc');
        $search = $request->input('search');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function ($cq) use ($search) {
                        $cq->where('title', 'like', '%'.$search.'%');
                    });
            });
        }

        $criterias = $query->paginate(15)->withQueryString();

        return view('admin.criteria.index', compact('criterias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::whereHas('modality.edition', function ($query) {
            $query->where('is_registration_active', true);
        })->get();

        return view('admin.criteria.create', compact('categories'));
    }

    private function validateRequest(Request $request)
    {
        $messages = [
            'category_id.required' => 'A categoria é obrigatória.',
            'name.required' => 'O nome é obrigatório.',
            'description.required' => 'A descrição é obrigatória.',
            'weight.required' => 'O peso é obrigatório.',
            'min_score.required' => 'A pontuação mínima é obrigatória.',
            'max_score.required' => 'A pontuação máxima é obrigatória.',
            'max_score.gt' => 'A pontuação máxima deve ser maior que a mínima.',
        ];

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'weight' => 'required|numeric|min:0.01',
            'min_score' => 'required|integer|min:1|max:254',
            'max_score' => 'required|integer|gt:min_score|max:255',
        ];

        $request->validate($rules, $messages);
    }

    private function getDataFromRequest(Request $request)
    {
        return $request->only([
            'category_id',
            'name',
            'description',
            'weight',
            'min_score',
            'max_score',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);
        $data = $this->getDataFromRequest($request);
        EvaluationCriterion::create($data);

        return redirect()->route('admin.criteria.index')
            ->with('success', 'Critério de avaliação criado com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $criterion = EvaluationCriterion::with('category')->findOrFail($id);
        $categories = Category::whereHas('modality.edition', function ($query) {
            $query->where('is_registration_active', true);
        })->get();

        return view('admin.criteria.create', compact('criterion', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $criterion = EvaluationCriterion::findOrFail($id);
        $this->validateRequest($request, $id);
        $data = $this->getDataFromRequest($request);
        $criterion->update($data);

        return redirect()->route('admin.criteria.index')
            ->with('success', 'Critério de avaliação atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $criterion = EvaluationCriterion::findOrFail($id);
        $criterion->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Critério removido com sucesso.'], 200);
        }

        return redirect()->route('admin.criteria.index')
            ->with('success', 'Critério removido com sucesso.');
    }
}
