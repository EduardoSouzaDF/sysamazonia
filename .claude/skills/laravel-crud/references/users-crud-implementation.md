# Referência — Implementação do CRUD de Users

Este arquivo documenta a implementação **real** do CRUD de usuários existente no projeto,
servindo de **exemplo canônico** para a skill `laravel-crud` e para novos CRUDs.

## Modelo (`app/Models/User.php`)

- `$fillable`: `name, email, telefone, password, is_judge, is_organizer`.
- `$hidden`: `password, remember_token`.
- `$casts`: `email_verified_at => datetime`.
- Trait: `HasFactory, Notifiable`.
- Relacionamentos: `roles()` (belongsToMany `user_role`, filtro `roles.active`),
  `extraData()` (hasOne `UserExtraData`), `indicatorCategories()` e `evaluatorCategories()`
  (belongsToMany `Category` via pivôs `indicators`/`evaluators`).
- Helpers: `hasRole`, `assignRole`, `removeRole`, `hasAnyRole`, `hasAllRoles`,
  `isJudge`, `isOrganizer`, `isIndicator`, `isEvaluator`.

## Controller (`app/Http/Controllers/UserController.php`)

Métodos: `index` (paginação 15 + busca por nome/email/role), `create`, `store`, `show`,
`edit`, `update`, `destroy`, além de `loginAs` e `returnToAdmin`.

Trechos representativos:

```php
public function index(Request $request)
{
    $query = User::with('roles');
    $search = $request->input('search');

    if ($search) {
        $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            $query->orWhereHas('roles', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            });
        });
    }

    return view('admin.users.index', compact('users'));
}
```

## Rotas (`routes/web.php`)

```php
Route::resource('users', UserController::class)
    ->middleware(['auth', CheckAdmin::class.':admin'])
    ->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
```

## Views
- `resources/views/admin/users/index.blade.php` — listagem.
- `resources/views/admin/users/create.blade.php` — formulário (criação/edição).
- `resources/views/admin/users/partial/form-personal.blade.php`,
  `form-extra.blade.php`, `form-comission.blade.php` — abas do formulário.

## Factory
`database/factories/UserFactory.php` com `fake()` e `Hash::make('password')`.

## Observações / débito técnico
- A validação está **inline** no controller hoje; o padrão correto (regra do `CLAUDE.md` e da
  skill) é **Form Request** (`StoreUserRequest`/`UpdateUserRequest`). Registrado na
  task `02-validation.md`.
- Não há ainda Feature Tests cobrindo o CRUD → task `06-tests.md` pendente.