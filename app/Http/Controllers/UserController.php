<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.exists($token)
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = User::with('roles');
        $search = $request->input('search');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
                $query->orWhereHas('roles', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%');
                });
            });
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::active()->get();
        $categories = Category::whereHas('modality.edition', function ($query) {
            $query->where('is_registration_active', true);
        })->get();


        $categoriesEvaluators = Category::whereHas('modality.edition', function ($query) {
            $query->where('is_registration_active', true);
        })->where('is_honorific', false)->get();



        return view('admin.users.create', ['roles' => $roles, 'categories' => $categories , 'categoriesEvaluators' => $categoriesEvaluators]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $messages = [
            'email.unique' => 'O email já está em uso.',
            // Adicione outras mensagens como necessário
        ];
        $request->validate(
            [
                'name' => 'required|min:5',
                'telefone' => 'required|min:5',
                'email' => 'required|email|unique:users',
                'roles' => 'required|array',

                'whatsApp' => 'nullable|string',
                'area_atuacao' => 'nullable|string',
                'indicado' => 'nullable|string',
                'empresa' => 'nullable|string',
                'cargo' => 'nullable|string',
                'cep' => 'nullable|string',
                'cidade' => 'nullable|string',
                'logradouro' => 'nullable|string',
                'complemento' => 'nullable|string',
                'unidade' => 'nullable|string',
                'bairro' => 'nullable|string',
                'instagram' => 'nullable|string',
                'facebook' => 'nullable|string',
                'linkedin' => 'nullable|string',

                // Papéis
                'julgador' => 'nullable|boolean',
                'organizador' => 'nullable|boolean',
                'seindicador' => 'nullable|boolean',
                'seavaliador' => 'nullable|boolean',
                'indicador' => 'nullable|array',
                'avaliador' => 'nullable|array',
            ],
            $messages,
        );

        $password = Hash::make(Str::random(10));

        $extra_data_collumns = ['whatsapp', 'area_atuacao', 'indicado', 'empresa', 'cargo', 'instagram', 'facebook', 'linkedin', 'escolaridade', 'estado', 'cidade', 'cep', 'logradouro', 'complemento', 'unidade', 'bairro'];

        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $password,
            'is_judge' => $request->has('julgador'),
            'is_organizer' => $request->has('organizador'),
        ]);

        $user->roles()->attach($request->roles);

        $anyExtraDataCollumn = array_filter($extra_data_collumns,function($column)use($request){
            return $request[$column] !== null;
        });

        if (sizeof($anyExtraDataCollumn)) {
            $user->extraData()->create([
                'whatsapp' => $request['whatsapp'] ?? null,
                'area_atuacao' => $request['area_atuacao'] ?? null,
                'indicado' => $request['indicado'] ?? null,
                'empresa' => $request['empresa'] ?? null,
                'cargo' => $request['cargo'] ?? null,
                'cep' => $request['cep'] ?? null,
                'estado' => $request['estado'] ?? null,
                'cidade' => $request['cidade'] ?? null,
                'logradouro' => $request['logradouro'] ?? null,
                'complemento' => $request['complemento'] ?? null,
                'unidade' => $request['unidade'] ?? null,
                'bairro' => $request['bairro'] ?? null,
                'instagram' => $request['instagram'] ?? null,
                'facebook' => $request['facebook'] ?? null,
                'linkedin' => $request['linkedin'] ?? null,
                'escolaridade' => $request['escolaridade'] ?? null,
            ]);
        }

        // Associando indicadores se necessário
        if ($request->has('indicador')) {
            $user->indicatorCategories()->sync($request['indicador']);
        }

        // Associando avaliadores se necessário
        if ($request->has('avaliador')) {
            $user->evaluatorCategories()->sync($request['avaliador']);
        }

        try {
            if (! $request->has('organizador')) {
                // Enviar email de boas-vindas e recuperação de senha
                $token = Password::createToken($user);
                $user->notify(new CustomResetPassword($token));
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuário criado ou atualizado com sucesso.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::with(['extraData', 'evaluatorCategories', 'indicatorCategories', 'roles'])->findOrFail($id);
        $roles = Role::active()->get();
        $categories = Category::whereHas('modality.edition', function ($query) {
            $query->where('is_registration_active', true);
        })->get();

        $categoriesEvaluators = Category::whereHas('modality.edition', function ($query) {
            $query->where('is_registration_active', true);
        })->where('is_honorific', false)->get();

        


        return view('admin.users.create', ['roles' => $roles, 'categories' => $categories, 'categoriesEvaluators' => $categoriesEvaluators, 'user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|confirmed',

            // Campos extras
            'whatsapp' => 'nullable|string',
            'area_atuacao' => 'nullable|string',
            'indicado' => 'nullable|string',
            'empresa' => 'nullable|string',
            'cargo' => 'nullable|string',
            'cep' => 'nullable|string',
            'logradouro' => 'nullable|string',
            'complemento' => 'nullable|string',
            'unidade' => 'nullable|string',
            'bairro' => 'nullable|string',
            'instagram' => 'nullable|string',
            'facebook' => 'nullable|string',
            'linkedin' => 'nullable|string',

            // Papéis
            'organizador' => 'nullable|boolean',
            'seindicador' => 'nullable|boolean',
            'indicador' => 'nullable|array',
            'seavaliador' => 'nullable|boolean',
            'avaliador' => 'nullable|array',
        ]);

        // Atualiza usuário
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_judge' => $request->has('julgador'),
            'is_organizer' => $request->has('organizador'),
        ]);

        $user->roles()->sync($request->roles);

        // Atualiza dados extras
        $user->extraData->updateOrCreate(
            ['user_id' => $user->id],
            [
                'whatsapp' => $validated['whatsapp'] ?? null,
                'area_atuacao' => $validated['area_atuacao'] ?? null,
                'indicado' => $validated['indicado'] ?? null,
                'empresa' => $validated['empresa'] ?? null,
                'cargo' => $validated['cargo'] ?? null,
                'cep' => $validated['cep'] ?? null,
                'logradouro' => $validated['logradouro'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'unidade' => $validated['unidade'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'instagram' => $validated['instagram'] ?? null,
                'facebook' => $validated['facebook'] ?? null,
                'linkedin' => $validated['linkedin'] ?? null,
                'escolaridade' => $validated['escolaridade'] ?? null,
            ],
        );

        // Sincronizando indicadores
        if (! empty($validated['indicador'])) {
            $user->indicatorCategories()->sync($validated['indicador']);
        } else {
            $user->indicatorCategories()->detach();
        }

        // Sincronizando avaliadores
        if (! empty($validated['avaliador'])) {
            $user->evaluatorCategories()->sync($validated['avaliador']);
        } else {
            $user->evaluatorCategories()->detach();
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();
        if (request()->ajax()) {
            return response()->json(['message' => 'Usuário removido com sucesso.'], 200);
        }

        return redirect()->route('admin.users.index');
    }

    public function loginAs(Request $request, $id)
    {
        // Verifica se o usuário atual é admin (ou tem permissão)
        if (! $request->user()->hasRole('admin')) {
            // ajuste conforme sua lógica de roles
            abort(403, 'Acesso negado.');
        }

        // Busca o usuário pelo ID
        $user = User::findOrFail($id);

        // Salva o ID do admin na sessão (para voltar depois, se necessário)
        session()->put('admin_user_id', Auth::id());

        // Faz login como o usuário selecionado
        Auth::login($user);

        return redirect()->route('dashboard'); // redireciona para a home ou painel do usuário
    }

    public function returnToAdmin()
    {
        $adminId = session()->pull('admin_user_id');

        if (! $adminId) {
            abort(403, 'Nenhum administrador encontrado.');
        }

        $adminUser = User::find($adminId);
        Auth::login($adminUser);

        return redirect()->route('dashboard');
    }
}
