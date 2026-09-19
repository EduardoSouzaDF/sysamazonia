---
name: specification
description: >-
  Escrever especificações de produto (SDD - Specification-Driven Development) no estilo do
  github/spec-kit antes de qualquer código. Use quando o pedido envolver criar/planejar um
  recurso, feature nova, tela, módulo, CRUD, correção de bug, ou refino de requisitos e quiser
  uma spec rastreável com user stories priorizadas, critérios de aceite (Given/When/Then),
  requisitos funcionais (FR-xxx), sucesso mensurável (SC-xxx) e plano de implementação.
  Também aciona quando o usuário citar "spec", "spec-driven", "SDD", "especificação",
  "feature spec", "escrever a spec", "definir o que construir antes de construir".
---

# Skill: specification

Guia o processo de **Specification-Driven Development** (baseado em
[github/spec-kit](https://github.com/github/spec-kit)) para transformar uma ideia vaga em uma
especificação **precisa, completa e executável** — antes de qualquer implementação. O princípio:
> *"Define what to build before building it"* — a spec é a fonte da verdade; o código é só a
> expressão dela.

## Objetivo da Skill

1. Transformar a ideia do usuário em uma **Feature Specification** com prioridades e aceite.
2. Gerar/garantir um **Plano de Implementação** (plan + design) e uma lista de **Tasks**.
3. **Sempre respeitar** o `CLAUDE.md` (Laravel Boost) e as regras `.clinerules/sdd.md`.
4. Registrar os artefatos em `specs/` de forma versionável e revisável.

## Quando NÃO usar

- Se o pedido é trivial e já está claro (um hotfix imediato que o usuário não pediu pra especificar).
- Se já existe spec suficiente e o usuário só quer implementar.

## Fluxo de trabalho (spec-kit adaptado)

### Etapa 0 — Intake / Clarificação
- Leia o pedido e registre o **escopo** (o quê) e o **não-escopo** (o que fica fora).
- Faça **perguntas de clarificação** para pontos ambíguos; marque como
  `[NEEDS CLARIFICATION: ...]` quando for preciso perguntar.
- Converse até que as **user stories** façam sentido.

### Etapa 1 — User Stories priorizadas (MVP em fatias)
- Escreva **user stories/jornadas priorizadas** (P1 > P2 > P3), cada uma **independentemente testável**.
- Cada story: descrição em linguagem simples + **Why this priority** + **Independent Test** +
  **Acceptance Scenarios** no formato **Given / When / Then**.

### Etapa 2 — Requisitos (FR) e explore
- Liste **Functional Requirements** com IDs `FR-001`, `FR-002`... usando linguagem imperativa
  (`System MUST ...`).
- Marque incertezas com `[NEEDS CLARIFICATION: ...]`.
- **Environmental Edge Cases** e **Key Entities**.

### Etapa 3 — Success Criteria (mensurável)
- Defina **SC-001...** medidas, **agnósticas de tecnologia** e **mensuráveis**.

### Etapa 4 — Plano de implementação (plan)
- Preencha o `plan` na pasta da feature: contexto técnico, dados, arquitetura, estrutura de pastas.
- Para **Laravel/sysamazonia**, use as convenções já existentes (ver `03-agentes-e-skills.md` e `.clinerules/`).

### Etapa 5 — Tasks
- Quebre em tasks agrupadas por user story (fase: setup → foundation → stories → integração).

## Estrutura de artefatos

Convenção spec-kit (branch : feature-name):

```text
specs/[###-feature-name]/
├── spec.md          # FEATURE spec (this skill, Etapas 1-3)
├── plan.md          # plano de implementação + dados + arquitetura (Etapa 4)
├── tasks.md         # lista de tasks por user story (Etapa 5)
└── maybe research.md, data-model.md, contracts/, quickstart.md
```

> ⚠️ Se o projeto já tiver `specs/<dominio>/` (ex.: `specs/users/0001-users-crud.md`), **reuse a
> estrutura existente** em vez de criar uma nova; apenas anexe/aumente o plano/task se necessário.
> O importante é a disciplina (spec → plan → tasks), não o nome da pasta.

## Templates
Use os templates em `templates/`:
- `templates/spec.md` — estrutura da Feature Specification (Etapas 1-3).
- `templates/plan.md` — plano de implementação (Etapa 4).
- `templates/tasks.md` — lista de tasks (Etapa 5).

Consulte também `references/spec-kit-adaptation.md` para a equivalência com o spec-kit original.

## Regras de qualidade (sempre/nunca)

- **Sempre**: user stories com prioridade e critérios de aceite independentes.
- **Sempre**: requisitos numerados `FR-xxx`; incertos marcados `[NEEDS CLARIFICATION]`.
- **Sempre**: critérios de sucesso `SC-xxx` mensuráveis.
- **Sempre**: seguir o `CLAUDE.md` (FormRequest, testes PHPUnit, convenções) nas tasks.
- **Nunca**: partir para implementação sem a spec aprovada; inventar requisitos; sobrescrever specs existentes sem aviso.
- **Nunca**: registrar specs fora da pasta `specs/`.

## Dica de gatilho para os comandos do Cline

Você pode invocar esta skill explicitamente com `/specification` (dígite after o `/` no chat).
Ou basta escrever um pedido como: "**spec do módulo de avaliações**", "**quero definir o escopo
do formulário de edição antes de codar**", "**ajuda-me a desenhar a spec para o recurso X**".