---
description: "Task list template for feature implementation"
---

# Tasks: [FEATURE NAME]

**Input**: Design documents from `/specs/[###-feature-name]/`

**Prerequisites**: plan.md (required), spec.md (required for user stories)

**Organization**: Tasks grouped by user story to enable independent implementation and testing
of each story (entrega como MVP incremental).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Pode rodar em paralelo (arquivos diferentes, sem dependências)
- **[Story]**: A qual user story a task pertence (US1, US2, US3)
- Inclua caminhos exatos de arquivos nas descrições

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Initialização e estrutura básica

- [ ] T001 Criar estrutura de pastas/camadas conforme o plan.md
- [ ] T002 Configurar dependências/ferramentas base do Laravel (se necessário)
- [ ] T003 [P] Configurar linting (Pint) e ferramentas de formatação

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestrutura central que DEVE estar completa antes de QUALQUER user story

- [ ] T010 Definição do modelo de dados / migrações (entidades-chave)
- [ ] T011 Form Requests de validação com mensagens pt-BR
- [ ] T012 Rotas base (resource) protegidas por middleware admin

> ⚠️ Nenhum trabalho de user story pode começar enquanto esta fase não estiver completa.

---

## Phase 3: [User Story 1] — [Título] (P1)

**Purpose**: [Valor entregue por esta story]

- [ ] T020 [US1] Implementar [componente/feature da story] em [caminho]
- [ ] T021 [US1] Teste de feature cobrindo [cenário de aceite]
- [ ] T022 [P] [US1] Ajustar [parte independente]

---

## Phase 4: [User Story 2] — [Título] (P2)

**Purpose**: [Valor entregue por esta story]

- [ ] T030 [US2] Implementar [componente]
- [ ] T031 [US2] Teste de feature
- [ ] T032 [P] [US2] Ajustar [parte independente]

---

## Phase 5: [User Story 3] — [Título] (P3)

**Purpose**: [Valor entregue por esta story]

- [ ] T040 [US3] Implementar [componente]
- [ ] T041 [US3] Teste de feature

---

## Verification / Integração

- [ ] Rodar suíte de testes completa: `php artisan test --compact`
- [ ] Rodar lint: `vendor/bin/pint --dirty`
- [ ] Revisar critérios de aceite mapeados no plan.md
