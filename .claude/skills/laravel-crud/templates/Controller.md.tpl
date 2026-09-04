<?php // Template de Controller resource — copie para app/Http/Controllers/{Model}Controller.php
// Padrão de referência: app/Http/Controllers/UserController.php / CategoryController.php

namespace App\Http\Controllers;

use App\Models\{{Model}};
use App\Http\Requests\Store{{Model}}Request;
use App\Http\Requests\Update{{Model}}Request;
use Illuminate\Http\Request;

class {{Model}}Controller extends Controller
{
    public function index(Request $request)
    {
        $query = {{Model}}::query();
        $search = $request->input('search');

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $rows = $query->paginate(15)->withQueryString();

        return view('admin.{{snake}}.index', compact('rows'));
    }

    public function create()
    {
        return view('admin.{{snake}}.create');
    }

    public function store(Store{{Model}}Request $request)
    {
        {{Model}}::create($request->validated());

        return redirect()->route('admin.{{snake}}.index')
            ->with('success', 'Registro criado com sucesso.');
    }

    public function edit(string $id)
    {
        $row = {{Model}}::findOrFail($id);

        return view('admin.{{snake}}.create', compact('row'));
    }

    public function update(Update{{Model}}Request $request, string $id)
    {
        {{Model}}::findOrFail($id)->update($request->validated());

        return redirect()->route('admin.{{snake}}.index')
            ->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(string $id)
    {
        {{Model}}::findOrFail($id)->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Registro removido com sucesso.'], 200);
        }

        return redirect()->route('admin.{{snake}}.index')
            ->with('success', 'Registro removido com sucesso.');
    }
}
