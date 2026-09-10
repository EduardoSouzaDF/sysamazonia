# SDD — Specification-Driven Development (Visão Geral)

> Este diretório documenta como o projeto **sysamazonia** usa **SDD** para guiar o
> Cline (via VS Code) na implementação de recursos de forma **especificada, rastreável e testada**.

## O que é SDD

SDD (Desenvolvimento Orientado por Especificação) é uma disciplina em que **toda mudança de
código nasce de uma especificação escrita e aprovada**, e só então vira tarefas pequenas
(cards) atribuídas a agentes especializados. A especificação é a "fonte da verdade"; o código
é sua materialização.

### Por que usar no Cline

- **Contexto completo**: agentes leem a spec e as tasks antes de codar — menos suposição.
- **Rastreabilidade**: cada arquivo criado tem origem numa spec + task documentada.
- **Revisão por papéis**: subagents especializados (arquitetura, backend, frontend, banco, QA).
- **Reuso**: skills encapsulam passos reutilizáveis (ex.: `laravel-crud`).

---

## Hierarquia de camadas no repositório

```
docs/sdd/            → explicações e templates do processo SDD
specs/<dominio>/     → specs (pedaço grande de produto) + tasks (cards pequenos)
.clinerules/         → regras globais que o Cline lê + definição de agentes (subagents)
.claude/skills/      → skills reutilizáveis do Cline (SKILL.md + templates)
CLAUDE.md            → regras do Laravel Boost (camada superior; sempre respeitar)
```

## Fontes de verdade por camada

| Camada | Arquivo | Função |
|---|---|---|
| Regras globais | `.clinerules/sdd.md`, `.clinerules/php.md`, `.clinerules/blade.md` | Comportamento mínimo obrigatório do assistente |
| Agent | `.clinerules/agents/<nome>/AGENTS.md` | Papel, escopo, entrada/saída de um subagente |
| Skill | `.claude/skills/<nome>/SKILL.md` | Passos reutilizáveis + templates |
| Spec | `specs/<dominio>/NNNN-<nome>.md` | O quê construir (requisitos + aceite) |
| Task | `specs/<dominio>/tasks/NN-<nome>.md` | Um pedaço acionável (BDD Given/When/Then) |

---

## Ciclo por recurso (resumo)

1. **Spec**: escrever/atualizar `specs/<dominio>/NNNN-*.md` com contexto, requisitos e aceite.
2. **Tasks**: quebrar a spec em cards em `specs/<dominio>/tasks/`.
3. **Build**: subagent especializado implementa cada card, **respeitando o CLAUDE.md**.
4. **Test**: subagent QA valida com testes PHPUnit + revisão de critérios de aceite.
5. **Review**: conferência da spec vs resultado; atualiza status.
6. **Commit**: commit pequeno e descritivo.

Ver detalhes no [02-workflow.md](./02-workflow.md) e catálogo em [03-agentes-e-skills.md](./03-agentes-e-skills.md).

---

### Regras de ouro (não esquecer)

- **Sempre** leia a spec/task antes de implementar.
- **Nunca** crie código sem referência à spec ou task aberta.
- **Sempre** respeite o `CLAUDE.md` (Laravel Boost) como camada superior (FormRequest, testes PHPUnit, convenções de sibling files).
- **Nunca** apague testes existentes sem aprovação.
- **Sempre** atualize o status da task/spec ao concluir (done/blocked/pending).