# Task — Controller resource (UserController)

> **Spec**: [../0001-users-crud.md](../0001-users-crud.md)
> **Status**: `done`
> **Agente**: `laravel-backend`

## Objetivo
`App\Http\Controllers\UserController` com os métodos do CRUD (`index, create, store, show,
edit, update, destroy`) + ações auxiliares (`loginAs`, `returnToAdmin`).

## Arquivos-fonte
- `app/Http/Controllers/UserController.php` (existente)

## Critérios de Aceite (BDD)
- **DADO** `index(Request)` com parâmetro `search`
  **ENTÃO** filtra por nome, e-mail e nome de papel; pagina 15.
- **DADO** `store` com dados válidos
  **ENTÃO** cria usuário (password random hashado), anexa roles, cria dados extras se houver,
  sincroniza indicador/avaliador, e envia e-mail de reset (se não organizador).
- **DADO** `update(User $user)`
  **ENTÃO** atualiza nome/email, sincroniza roles/dados extras/indicadores/avaliadores.
- **DADO** `destroy`
  **ENTÃO** remove usuário; responde JSON em ajax ou redirect.

## Convenções
- Seguir o padrão dos controllers irmãos (`CategoryController`): `findOrFail`, redirect com flash `success`.
- Uso de `Hash::make`, `Str::random`, `Password::createToken` e `CustomResetPassword`.

## Verificação
- [ ] `php -l app/Http/Controllers/UserController.php`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-26 | done (baseline existente) | Cline |