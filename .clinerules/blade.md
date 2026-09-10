# Regras para arquivos Blade (.clinerules/blade.md)

Regras aplicadas a qualquer `*.blade.php`. Complementa o `CLAUDE.md`/Tailwind v4.

## Reuso de componentes
- Prefira os componentes/partials já existentes:
  - `components/form/input`, `components/form/select`
  - `components/messages/alert`, `components/messages/confirm`, `components/messages/error`
  - `components/pages/index`, `components/pages/crud/create`
  - `components/elements/tabs`
- Para CRUD de usuários, reutilize `resources/views/admin/users/partial/*`.

## Estilo (Tailwind v4)
- Use Tailwind v4 (`@import "tailwindcss"`); sem `tailwind.config.js`.
- Use `gap-*` em listas; suporte `dark:` se o projeto usar.
- Classes em ordem legível; remova redundâncias.

## Estrutura
- Views de listagem usam `components/pages/index`; formulários usam
  `components/pages/crud/create` para reaproveitar cabeçalho/ações.
- Mensagens de sucesso via flash `with('success', ...)` exibidas por `components/messages/alert`.