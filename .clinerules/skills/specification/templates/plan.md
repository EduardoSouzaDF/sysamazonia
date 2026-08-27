# Implementation Plan: [FEATURE]

**Branch**: `[###-feature-name]` | **Date**: [DATA] | **Spec**: [link para spec.md]

**Input**: Feature specification from `/specs/[###-feature-name]/spec.md`

---

## Summary

[Extrair da feature spec: requisito primário + abordagem técnica a partir da research]

## Technical Context

**Language/Version**: [ex.: PHP 8.4 / Laravel 12]
**Primary Dependencies**: [ex.: laravel/framework, laravel/fortify, tailwindcss]
**Storage**: [ex.: MySQL]
**Testing**: [ex.: PHPUnit (tests/Feature, tests/Unit)]
**Target Platform**: [ex.: Web server / painel admin]
**Project Type**: [ex.: web-service]
**Performance Goals**: [ex.: ..., conforme aplicável]
**Constraints**: [ex.: <200ms p95, ...]
**Scale/Scope**: [ex.: ...]

## Project Structure

### Documentação (deste feature)

```text
specs/[###-feature]/
├── spec.md              # spec da feature (Skill specification — Etapa 1-3)
├── plan.md              # este arquivo
├── data-model.md        # (opcional) modelo de dados
├── contracts/           # (opcional) contratos/endpoints
└── tasks.md             # lista de tasks (Skill specification — Etapa 5)
```

### Código-fonte
- [Listar camadas a alterar: app/Models, app/Http/Controllers, routes, resources/views, tests]

## Data Model (se aplicável)

- **[Entidade]**: campos, tipos, relacionamentos (alinhar com as migrações existentes do projeto).

## Architecture / Design Decisions

- [Decisões técnicas: seguir padrão dos controllers irmãos, Form Requests, scopes, componentes reuse]

## Dependencies / Risks

- [Dependências externas e riscos técnicos]

## Acceptance Mapping

- [Mapa: user story → FR → como será verificado em testes]
