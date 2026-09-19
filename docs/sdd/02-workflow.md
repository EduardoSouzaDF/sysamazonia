# SDD — Workflow (Pipeline de 6 etapas)

Fluxo canônico que o Cline deve seguir para **cada** recurso/mudança. Ela é complementar às
regras do `CLAUDE.md`.

## Pipeline

```
ESPECIFICAR → FATIAR → IMPLEMENTAR → TESTAR → REVISAR → COMMITEE
   (spec)      (tasks)    (code)      (qa)      (arquitet/QA)  (versionado)
```

### Etapa 1 — ESPECIFICAR (spec, prioridade ao o quê)
- Entenda o requisito e registre/atualize a spec em `specs/<dominio>/NNNN-<nome>.md`.
- Defina: contexto, usuários, requisitos funcionais/hão-funcionais e **critérios de aceite**.
- Revisão: subagent `architect` valida completude e coerência arquitetural.

### Etapa 2 — FATALAR (tasks abertos)
- Quebre a spec em cards `specs/<dominio>/tasks/NN-*.md`.
- Cada card: título, aceite (BDD Given/When/Then), arquivos-fonte, dependências.
- Dica: mantenha cards pequenos (1 arquivo principal cada) para facilitar commit e teste.

### Etapa 3 — IMPLEMENTAR
- Um subagent especializado (ex.: `laravel-backend`, `blade-frontend`, `database`) implement a parte do card.
- Sempre: ler a spec/task antes; seguir `CLAUDE.md`; copiar convenções dos sibling files.

### Etapa 4 — TESTAR
- Subagent `qa` escreve/atualiza testes PHPUnit (Feature/Unit).
- Rodam os critérios de aceite e o teste relacionado (mínimo, com filtro).

### Etapa 5 — REVISAR
- `qa`/`architect` conferem: código atende aceite? não quebrou nada? conformidade com conventions?

### Etapa 6 — COMMITAR
- Commit pequeno, descriptivo, referenciando a spec/task.

---

## Exemplo de fluxo em linguagem natural para o Cline

> "Implementar o CRUD de Usuários a partir da spec `specs/users/0001-users-crud.md`.
> Comecie lendo a spec e os cards em `specs/users/tasks/`, siga o CLAUDE.md e as regras em
> `.clinerules/php.md`/`blade.md`, e confira ao final com testes."

---

## Checklist de saída de cada etapa

| Etapa | Antes | Depois | Artefato esperado |
|---|---|---|---|
| Spec | Req claro? | Spec escrita + aceite | `specs/.../*.md` |
| Tasks | Spec aprovada | Cards definidos | `specs/.../tasks/*.md` |
| Implement | Card escolhido | Código conforme | Arquivos do card |
| Test | Código feito | Testes passando | `tests/**` + status |
| Review | Testes ok | Review ok | Status na spec |
| Commit | Review ok | Commit descriptivo | Git log |