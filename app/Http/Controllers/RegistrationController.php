<?php

namespace App\Http\Controllers;

use App\Enum\RolesEnum;
use App\Http\Controllers\Api\EditionController;
use App\Models\Candidate;
use App\Models\Edition;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\RegistrationFile;
use App\Models\Role;
use File;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Type\Integer;

class RegistrationController extends Controller
{


    public function index(Request $request)
    {

        $editions = Edition::all();
        $search = $request->input('search');
        $edition = $request->input('edition');
        $status = $request->input('status');
        $perPage = 10;
        $page = $request->input('page', 1);

        // Query para Registration
        $registrationQuery = Registration::with(['candidate', 'category.modality.edition', 'files']);
        if ($search) {

            $registrationQuery->where(function($q) use ($search){
                $q->where('title', 'like', '%' . $search . '%')
                ->orWhereHas('category', function ($subQ) use ($search) {
                    $subQ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('acronym', 'like', '%' . $search . '%');
                })
                ->orWhereHas('candidate', function ($subQ) use ($search) {
                    $subQ->where('cpf', 'like', '%' . $search . '%');
                });


            });

        }



        // Query para Nominee
        $nomineeQuery = Nominee::with(['candidate', 'category.modality.edition', 'files']);
        if ($search) {
             $nomineeQuery->where(function($q) use ($search){
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhereHas('category', function ($subQ) use ($search) {
                    $subQ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('acronym', 'like', '%' . $search . '%');
                })
                 ->orWhereHas('candidate', function ($subQ) use ($search) {
                    $subQ->where('cpf', 'like', '%' . $search . '%');
                });

            });
        }

        if($edition){
            $registrationQuery->where(function($q) use($edition){
                $q->whereHas('category.modality.edition',function($subq)use($edition){
                    $subq->where('id',$edition);
                });
            });

            $nomineeQuery->where(function($q) use($edition){
                $q->whereHas('category.modality.edition',function($subq)use($edition){
                    $subq->where('id',$edition);
                });
            });
        }

        if($status){
            $registrationQuery->where(function($q) use($status){
                $q->where('status',$status);
            });

            $nomineeQuery->where(function($q) use($status){
                $q->where('status',$status);
            });


        }

        // Obter todos os resultados (ou limitar conforme necessário)
        $registrations = $registrationQuery->get();
        $nominees = $nomineeQuery->get();

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


        return view('admin.registration.index', compact('list','editions'));
    }

      /**
     * Display the specified resource.
     */
    public function show(String $id, String $type,Request $request)
    {

        $view = $request->input('search');

       if($type == Nominee::class){
        $registration = Nominee::findOrFail($id);
       }else{
        $registration = Registration::findOrFail($id);
       }

       $registrations  = $registration->candidate->registrations->concat($registration->candidate->nominees);
       $candidate = $registration->candidate;


       return view('admin.registration.view', compact('registrations','candidate'));
    }

    public function file(String $file){

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

    public function habilitar($id,$type){
            if($type == 'AppModelsNominee'){
                $registration = Nominee::findOrFail($id);
            }else{
                $registration = Registration::findOrFail($id);
            }
            $user = auth()->user();
            if($user->hasRole('admin')){
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

     public function rejeitar($id,$type){

        if($type == 'AppModelsNominee'){
            $registration = Nominee::findOrFail($id);
        }else{
            $registration = Registration::findOrFail($id);
        }

        $user = auth()->user();
        if($user->hasRole('admin')){
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
}
