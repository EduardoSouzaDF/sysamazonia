# SDD — Agentes e Skills

Catálogo dos **subagents (agentes)** e **skills** configurados para este projeto no Cline.

## Agentes (subagents)

Definidos em `.clinerules/agents/<nome>/AGENTS.md`. São papéis que o Cline pode "chamar" como
subagentes para segmentar responsabilidades.

| Agente | Diretório | Responsabilidade |
|---|---|---|
| `architect` | `.clinerules/agents/architect/AGENTS.md` | Revê specs/arquitetura, planeja, detecta débito/rwm |
| `laravel-backend` | `.clinerules/agents/laravel-backend/AGENTS.md` | Models, Controllers, Form Requests, Services, rotas |
| `blade-frontend` | `.clinerules/agents/blade-frontend/AGENTS.md` | Views Blade + Tailwind v4, componentes |
| `database` | `.clinerules/agents/database/AGENTS.md` | Migrações, seeders, factories, relacionamentos |
| `qa` | `.clinerules/agents/qa/AGENTS.md` | Testes PHPUnit, revisão de aceite, regressão |

Cada `AGENTS.md` segue o padrão:
- **Escopo / Responsabilidade** — o que este agente faz e não faz.
- **Entrada** — o que ele precisa ler (spec, task, arquivos irmãos).
- **Saída** — artefatos que ele gera.
- **Regras** — "sempre..." e "nunca...", alinhadas ao `CLAUDE.md`.

## Skills

Definidas em `.claude/skills/<nome>/SKILL.md`. Pack de instruções + templates reutilizáveis.

| Skill | Uso | Anexos |
|---|---|---|
| `specification` | Escrever a **spec** de um recurso (estilo github/spec-kit) antes de implementar | `templates/spec.md`, `templates/plan.md`, `templates/tasks.md`, `references/spec-kit-adaptation.md` |
| `laravel-crud` | Gerar um CRUD completo a partir de uma spec | `templates/*.tpl`, `references/users-crud-implementation.md` |

### Como acionar um agente ou skill no Cline

- **Agente**: mencione o papel para o Cline, ex.: "Use o agente `laravel-backend` para..."
  (ou o Cline o invoca conforme o contexto da task).
- **Skill**: peça explicitamente: "Use a skill `laravel-crud` para implementar o CRUD de X".

---

## Quando usar o quê

| Situação | Recurso |
|---|---|
| Pedido amplo/ambíguo (sem spec) | `architect` escreve/valida a spec primeiro |
| CRUD pronto de um model | Skill `laravel-crud` |
| Implementar model/controller/form em PHP | Agente `laravel-backend` |
| Criar/editar view Blade + Tailwind | Agente `blade-frontend` |
| Migração/seeder/factory/relacionamento | Agente `database` |
| Garantir testes e critérios de aceite | Agente `qa` |