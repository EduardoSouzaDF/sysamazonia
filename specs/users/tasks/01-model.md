# Task — Modelo User (fillable, casts e relações)

> **Spec**: [../0001-users-crud.md](../0001-users-crud.md)
> **Status**: `done`
> **Agente**: `laravel-backend` / `database`

## Objetivo
Modelo `App\Models\User` com campos protegidos e relacionamentos/helpers.

## Arquivos-fonte
- `app/Models/User.php` (existente)

## Critérios de Aceite (BDD)
- **DADO** o model User
  **ENTÃO** expõe `name, email, telefone, password, is_judge, is_organizer` como `$fillable`.
- **DADO** o model User
  **ENTÃO** esconde `password` e `remember_token` (serialização).
- **DADO** as relações
  **ENTÃO** existem `roles`, `extraData`, `indicatorCategories`, `evaluatorCategories`.

## Convenções
- `HasFactory` e `Notifiable`; seguir `Role.php` (uso de `scopeActive`, etc.).
- `password` não é `hashed` no `$casts` aqui; hash é aplicado no controller (via `Hash::make`).

## Verificação
- [ ] `php artisan tinker --execute="print_r((new App\\Models\\User)->getFillable());"` (opcional)

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-26 | done (baseline existente) | Cline |