# Task — Criar/ativar menu "Julgar" no MenuBuilder

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Criar `MenuBuilder::getJulgadorMenu()` (ou reaproveitar/renomear o `getJuradoMenu()` órfão) com o
item **"Julgar"**, e ativá-lo no `getMenuStructure()` apenas para usuários com `is_judge = true`.

## Arquivos-fonte
- `app/Services/MenuBuilder.php` (editar)

## Critérios de Aceite (BDD)
- **DADO QUE** um usuário autenticado tem `is_judge = true`
  **QUANDO** o `getMenuStructure()` é chamado
  **ENTÃO** o resultado inclui o menu do julgador (heading "Julgamento" + item "Julgar").
- **DADO QUE** um usuário autenticado tem `is_judge = false`
  **QUANDO** o `getMenuStructure()` é chamado
  **ENTÃO** **não** inclui o menu do julgador.
- **DADO QUE** o menu do julgador existe
  **ENTÃO** o item usa título **"Julgar"** e aponta para `panel.julgar.index` (a view criada).

## Convenções a seguir
- Nome do método: `getJulgadorMenu()`; reutilizar a estrutura do `getJuradoMenu()` órfão.
- Respeitar o padrão do componente `components/menu-item` (título, ícone `ki-*`, rota nomeada).
- Ativação no `getMenuStructure()`:
  `if ($user->isJudge()) { $menus = array_merge($menus, self::getJulgadorMenu()); }`.

## Dependências
- Nenhuma (o helper `User::isJudge()` já existe).

## Verificação
- [ ] `php -l app/Services/MenuBuilder.php`
- [ ] Conferir menu da sidebar para um usuário `is_judge = true` e um `false`

## Notas / Débito técnico
- `getJuradoMenu()` existia **órfão**; renomear para `getJulgadorMenu()` conforme RD da spec.
- O critério de acesso do julgador é `is_judge` (não o papel `Role::JURADO`, que não é seedado).

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
