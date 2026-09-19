# Adaptação do github/spec-kit para o Cline

Este documento explica como a skill `specification` mapeia a metodologia do
[github/spec-kit](https://github.com/github/spec-kit) para o ecossistema **Cline** deste projeto.

## O que o spec-kit é (original)

- Toolkit aberto para **Spec-Driven Development** com qualquer agente de IA.
- Filosofia: *"Define what to build before building it"* — a spec é executável; o código é a
  expressão dela ("power inversion": o código serve à spec).
- Processo: ideia → clarificação → **Feature Spec** (user stories priorizadas, FR, aceite) →
  **plan** (plano técnico + dados + arquitetura) → **tasks** → implementação → testes → review.
- Estrutura de artefatos: `specs/[###-feature-name]/` com `spec.md`, `plan.md`, `tasks.md`
  (+ `research.md`, `data-model.md`, `contracts/`, `quickstart.md` opcionais).
- Templates oficiais: `templates/spec-template.md`, `plan-template.md`, `tasks-template.md`,
  `checklist-template.md`, `constitution-template.md`.

## Como foi adaptado ao Cline

| Conceito spec-kit | Implementação no Cline (este repo) |
|---|---|
| CLI `specify` (python/uv) | Skill `specification` (`.claude/skills/specification/`) |
| `specs/<branch>/spec.md` | Skill gera spec em `specs/` (reusa `specs/<dominio>/` do projeto) |
| `spec-template.md` | `templates/spec.md` (user stories P1/P2/P3, Given/When/Then, FR-xxx, SC-xxx, Key Entities, Assumptions, Edge Cases) |
| `plan-template.md` | `templates/plan.md` (Contexto técnico, Data Model, Architecture, Risks, Acceptance Mapping) |
| `tasks-template.md` | `templates/tasks.md` (fases: Setup → Foundational → User Stories → Verification) |
| Comandos `/specify`, `/plan`, `/tasks` | Invocação da skill via `/specification` ou auto-match por `description` |
| Subagentes (research, implement...) | Reutiliza os subagentes do Cline em `.clinerules/agents/` (architect, laravel-backend, blade-frontend, database, qa) |
| Constitution | Regras globais `.clinerules/` + `CLAUDE.md` (Laravel Boost) |

## Diferenças importantes

1. **Sem CLI Python**: não instalamos `uv`/`specify_cli`; a skill **não depende de runtime** —
   o próprio agente Cline lê o `SKILL.md`, segue o fluxo e preenche os templates com o editor.
2. **Pastas do projeto**: o repo já usa `specs/users/0001-users-crud.md` + `specs/users/tasks/`.
   A skill respeita isso: se a feature pertence a um domínio existente, cria/anexa lá; senão, segue
   a convenção spec-kit `specs/<branch>/spec.md`.
3. **Regras Laravel**: as tasks/plano devem refletir o `CLAUDE.md` (Form Requests, testes PHPUnit,
   convenções de sibling, não criar pastas base sem aprovação).

## Boas práticas ao usar

- Peça a spec **antes de implementar** ("escreva a spec de X").
- Aprove o conteúdo (status `Draft` → `Approved`) antes que o agente parta para o código.
- Para implementação após a spec aprovada, ative a skill `laravel-crud` (se for CRUD) ou os
  subagentes de backend/blade/QA.
