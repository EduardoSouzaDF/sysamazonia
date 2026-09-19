# Task — Views Blade (admin.users.*)

> **Spec**: [../0001-users-crud.md](../0001-users-crud.md)
> **Status**: `done`
> **Agente**: `blade-frontend`

## Objetivo
Views do CRUD de usuários em `resources/views/admin/users/`.

## Arquivos-fonte
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php` (usada também na edição)
- `resources/views/admin/users/partial/form-personal.blade.php`
- `resources/views/admin/users/partial/form-extra.blade.php`
- `resources/views/admin/users/partial/form-comission.blade.php`

## Critérios de Aceite (BDD)
- **DADO** a listagem
  **ENTÃO** exibe tabela de usuários com roles e ações editar/excluir, paginação e busca.
- **DADO** formulário criar/editar
  **ENTÃO** agrupa em abas/partes (pessoal, extra, comissão) e envia para store/update.
- **DADO** componentes existentes
  **ENTÃO** reusa os componentes do projeto (`components/form/input`, `components/messages/*`,
  etc.) em vez de recriar.

## Convenções
- Tailwind v4; seguir `categories/create.blade.php` e `components/pages/*` como referência.
- Mensagens de sucesso via flash (`with('success', ...)`).

## Verificação
- [ ] `npm run build` (se houver mudança de assets) / inspeção visual
- [ ] Navegar em `/admin/users`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-26 | done (baseline existente) | Cline |