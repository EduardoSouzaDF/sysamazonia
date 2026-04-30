<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EditionController extends Controller
{


    public function formEdition(Request $request){

         return response()
        ->view('forms.edition.register')
        ->withHeaders([
            'Access-Control-Allow-Origin' => '*',
        ]);

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = Edition::orderBy('grant_date', 'desc'); // or 'created_at'
        $search = $request->input('search');
        $active = $request->input('active');

        if ($search) {
            $query->where(function ($q) use ($search, $active) {
                $q->where('title', 'like', '%'.$search.'%');
                if ($active) {
                    $q->orWhere('is_registration_active', $active === 'active' ? 1 : ($active === 'inactive' ? 0 : null));
                }
            });
        }

        $editions = $query->paginate(15)->withQueryString();

        return view('admin.editions.index', compact('editions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No modalities needed in creation (can be added later)
        return view('admin.editions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'title.required' => 'O título é obrigatório.',
            'registration_start.required' => 'A data de início de inscrição é obrigatória.',
            'registration_end.required' => 'A data de término de inscrição é obrigatória.',
            'registration_end.after' => 'A data de término deve ser após o início.',
            'grant_date.required' => 'A data de concessão é obrigatória.',
            'judgment_date.required' => 'A data de julgamento é obrigatória.',
            'regulation_file.mimes' => 'O arquivo deve ser PDF.',
            'regulation_file.max' => 'O arquivo deve ter no máximo 5MB.',
        ];

        $request->validate([
            'title' => 'required|string|max:255',
            'regulation' => 'required|filled|string',
            'regulation_file' => 'required|file|mimes:pdf|max:5120',
            'registration_start' => 'required|date',
            'registration_end' => 'required|date|after:registration_start',
            'grant_date' => 'required|date',
            'judgment_date' => 'required|date',
            'is_registration_active' => 'nullable|boolean',
            'applications_per_candidate' => 'required|integer|min:1',
        ], $messages);

        $data = $request->only([
            'title',
            'regulation',
            'registration_start',
            'registration_end',
            'grant_date',
            'judgment_date',
            'is_registration_active',
            'applications_per_candidate',
        ]);

        // Handle file upload if present
        if ($request->hasFile('regulation_file')) {
            /** @var UploadedFile $file */
            $file = $request->file('regulation_file');
            $path = $file->store('editions/regulations', 'public');
            $data['regulation_file_path'] = $path;
        }

        // Ensure boolean cast works (checkbox sends "on" or null)
        $data['is_registration_active'] = $request->boolean('is_registration_active');

        $edition = Edition::create($data);

        return redirect()->route('admin.editions.index')
            ->with('success', 'Edição criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $edition = Edition::with('modalities')->findOrFail($id);

        return view('admin.editions.show', compact('edition'));
    }

    public function showRegulation($file)
    {
        $path = 'editions/regulations/'.$file;

        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->response($path);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $edition = Edition::with('modalities')->findOrFail($id);

        return view('admin.editions.create', compact('edition'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $edition = Edition::findOrFail($id);

        $messages = [
            'title.required' => 'O título é obrigatório.',
            'registration_start.required' => 'A data de início de inscrição é obrigatória.',
            'registration_end.required' => 'A data de término de inscrição é obrigatória.',
            'registration_end.after' => 'A data de término deve ser após o início.',
            'grant_date.required' => 'A data de concessão é obrigatória.',
            'judgment_date.required' => 'A data de julgamento é obrigatória.',
            'regulation_file.mimes' => 'O arquivo deve ser PDF.',
            'regulation_file.max' => 'O arquivo deve ter no máximo 5MB.',
        ];

        $request->validate([
            'title' => 'required|string|max:255',
            'regulation' => 'required|filled|string',
            'regulation_file' => 'file|mimes:pdf|max:5120',
            'registration_start' => 'required|date',
            'registration_end' => 'required|date|after:registration_start',
            'grant_date' => 'required|date',
            'judgment_date' => 'required|date',
            'is_registration_active' => 'nullable|boolean',
            'applications_per_candidate' => 'required|integer|min:1',
        ], $messages);

        $data = $request->only([
            'title',
            'regulation',
            'registration_start',
            'registration_end',
            'grant_date',
            'judgment_date',
            'is_registration_active',
            'applications_per_candidate',
        ]);

        // Replace file only if new one uploaded
        if ($request->hasFile('regulation_file')) {
            // Delete old file (optional)
            if ($edition->regulation_file_path && Storage::disk('public')->exists($edition->regulation_file_path)) {
                Storage::disk('public')->delete($edition->regulation_file_path);
            }
            $path = $request->file('regulation_file')->store('editions/regulations', 'public');
            $data['regulation_file_path'] = $path;
        }

        $data['is_registration_active'] = isset($data['is_registration_active']);

        $edition->update($data);

        return redirect()->route('admin.editions.index')
            ->with('success', 'Edição atualizada com sucesso.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $edition = Edition::findOrFail($id);

        // Delete regulation file if exists
        if ($edition->regulation_file_path && Storage::disk('public')->exists($edition->regulation_file_path)) {
            Storage::disk('public')->delete($edition->regulation_file_path);
        }

        // Optionally delete related modalities? (Cascade or soft delete recommended)
        // Edition::find($id)->modalities()->delete();

        $edition->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Edição removida com sucesso.'], 201);
        }

        return redirect()->route('admin.editions.index')
            ->with('success', 'Edição removida com sucesso.');
    }
}
