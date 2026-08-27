---
name: laravel-crud
description: Implementar um CRUD completo em Laravel a partir de uma SDD spec (migration → model → Form Requests → controller resource → routes → views → tests). Use quando o pedido envolver criar/gerenciar um recurso administrável com listagem, criação, edição e exclusão. Referência canônica: o CRUD de Users.
---

# Skill: laravel-crud

Gera um CRUD **completo e idiomático** do Laravel seguindo as convenções do projeto **sysamazonia**
e o fluxo SDD. Consulte a spec e as tasks primeiro.

## Pré-requisitos
1. Existe (ou foi criada) uma spec em `specs/<dominio>/NNNN-*.md` com critérios de aceite.
2. As tasks foram abertas em `specs/<dominio>/tasks/`.

## Fluxo de implementação (ordem)

1. **Migration** — crie a tabela (agente `database`): colunas, índices, FKs, `timestamps`.
   - Sempre preserve atributos existentes ao alterar colunas (regra `CLAUDE.md`).
2. **Model** — defina `$fillable`, `$casts`, `HasFactory`, e relacionamentos/scopes descritivos
   (no padrão de `Role.php`).
3. **Factory** (se houver teste/seed) — com `fake()`; `UserFactory` como referência.
4. **Form Requests** — um para criar (`StoreXRequest`) e um para editar (`UpdateXRequest`),
   com regras e mensagens **pt-BR** (nunca validação inline).
5. **Controller** — recurso (`index/create/store/show/edit/update/destroy`), `findOrFail`,
   redirect com flash `success`; siga `CategoryController`/`UserController`.
6. **Rotas** — `Route::resource('{recurso}', XController::class)` sob `admin`, com
   `['auth', CheckAdmin::class.':admin']` e nomes `admin.{recurso}.*`.
7. **Views** — reutilize `components/pages/index`, `components/pages/crud/create`,
   `components/form/*`, `components/messages/*`; Tailwind v4.
8. **Testes** — Feature tests PHPUnit cobrindo critérios de aceite (agente `qa`).

## Templates
Veja `templates/`:
- `FormRequest.md.tpl` → classe de validação.
- `Controller.md.tpl` → controller resource.
- `routes.md.tpl` → bloco de rotas.

## Exemplo canônico
O CRUD completo de **Users** está registrado em `specs/users/0001-users-crud.md` e a implementação
real em `references/users-crud-implementation.md`. Use-o como referência de estilo/estrutura.

## Verificação final
- [ ] `php -l` em todos os arquivos PHP criados/alterados.
- [ ] `php artisan route:list` contém as rotas novas.
- [ ] Testes relacionados passam (`php artisan test --compact --filter=...`).
- [ ] Status das tasks e da spec atualizado.
