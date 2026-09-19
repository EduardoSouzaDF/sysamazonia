# Task — Rotas resource (admin.users)

> **Spec**: [../0001-users-crud.md](../0001-users-crud.md)
> **Status**: `done`
> **Agente**: `laravel-backend`

## Objetivo
Registrar as rotas do CRUD de usuários sob o prefixo `admin`, protegidas por
`['auth', CheckAdmin::class.':admin']`, com nomes `admin.users.*`.

## Arquivos-fonte
- `routes/web.php` (existente)

## Critérios de Aceite (BDD)
- **DADO** um admin autenticado
  **ENTÃO** `GET /admin/users` → `admin.users.index` (e `create/store/edit/update/destroy`).
- **DADO** as rotas extras
  **ENTÃO** existem `admin.users.login-as` e `admin.users.return-to-admin`.

## Rotas registradas
```php
Route::resource('users', UserController::class)
    ->middleware(['auth', CheckAdmin::class.':admin'])
    ->names([... 'admin.users.index' etc ...]);
```

## Verificação
- [ ] `php artisan route:list --name=admin.users`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-26 | done (baseline existente) | Cline |