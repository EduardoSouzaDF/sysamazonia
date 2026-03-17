<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Modality;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {

        $query = Category::with('madality')->orderBy('title', 'desc'); // or 'created_at'
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
            ->with(['edition' => function ($query) {
                $query->where('is_registration_active', true);
            }])->get();

        return view('admin.categories.create', compact('modalities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
