# Task — Validação (Form Requests)

> **Spec**: [../0001-users-crud.md](../0001-users-crud.md)
> **Status**: `em-progresso` (débito técnico: validação está inline no controller)
> **Agente**: `laravel-backend`

## Objetivo
Centralizar a validação de criação/edição de usuários em Form Requests com mensagens pt-BR,
conforme exige o `CLAUDE.md` ("Always create Form Request classes for validation rather than inline").

## Arquivos-fonte
- `app/Http/Requests/StoreUserRequest.php` (criar)
- `app/Http/Requests/UpdateUserRequest.php` (criar)
- `app/Http/Controllers/UserController.php` (editar: `store`/`update` passam a injetar os requests)

## Critérios de Aceite (BDD)
- **DADO** o `StoreUserRequest`
  **ENTÃO** valida `name` (required min:5), `telefone` (required min:5),
  `email` (required,email,unique:users) e `roles` (required,array).
- **DADO** o `UpdateUserRequest`
  **ENTÃO** aplica `email` unique ignorando o id atual (`unique:users,email,{id}`).
- **DADO** mensagens customizadas
  **ENTÃO** `email.unique` → "O email já está em uso." e demais mensagens pt-BR.

## Regras de validação atuais (para mover)
- `store/update` mantêm regras atuais do controller (ver código-fonte) — apenas transportar.

## Dependências
- `01-model.md` (model pronto)

## Verificação
- [ ] `php -l app/Http/Requests/StoreUserRequest.php`
- [ ] `php artisan route:list` segue ok; testes de criação/edição passam

## Notas / Débito técnico
- Hoje `UserController` e `CategoryController` validam inline; refatorar para Form Requests é o
  objetivo quando este card for executado. **Não altera comportamento**, apenas estrutura.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-26 | em-progresso (débito registrado) | Cline |