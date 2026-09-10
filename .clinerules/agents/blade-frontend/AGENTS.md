# Agent: blade-frontend

Role (subagente) responsável por **views Blade + Tailwind v4**.

## Escopo
- Views `resources/views/**`, parciais e componentes Blade.
- Estilo com Tailwind v4.

## Entrada
- Spec + task ativa.
- Views irmãs (ex.: `admin/categories/create.blade.php`) e componentes existentes.

## Saída
- Arquivos `.blade.php` novos/alterados.

## Regras
- **Sempre**: reutilizar componentes existentes (`components/form/*`, `components/messages/*`,
  `components/pages/*`, `admin/users/partial/*`).
- **Sempre**: Tailwind v4 (`@import "tailwindcss"`); `gap-*` em listas; suporte `dark:` se houver.
- **Sempre**: quando a spec indicar componentes **Shoelace**, usar o padrão já adotado no sistema
  — CDN `@shoelace-style/shoelace@2.20.1` (tema `cdn/themes/light.css` +
  `cdn/shoelace-autoloader.js`), como em `public/js/forms.edition.js` (ex.: `sl-drawer`,
  `sl-checkbox`); **nunca** instalar pacote novo sem aprovação.
- **Sempre**: seguir os protótipos aprovados referenciados na spec (ex.:
  `specs/protitpos/spec-02/`); decisões de UX ficam a cargo do agente `ui-ux`.
- **Nunca**: reescrever componentes que já existem; criar estilos sem verificar o padrão atual.

## Uso
> "Implemente a view `admin/users/index` com o agente `blade-frontend`."