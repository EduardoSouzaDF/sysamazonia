# Cline Rules — sysamazonia

Este diretório contém **regras globais** que o Cline carrega automaticamente e a **definição de
subagentes (agents)** usados no fluxo SDD (Specification-Driven Development).

## Estrutura

```
.clinerules/
├── README.md                 ← você está aqui (como adicionar regras/agentes)
├── sdd.md                    ← fluxo SDD obrigatório (spec → task → build → test → review)
├── php.md                    ← regras específicas para arquivos *.php
├── blade.md                  ← regras específicas para arquivos *.blade.php
├── regras-dominio.md         ← Regras de Domínio globais (RDD-xx; sempre levadas em conta)
├── nomenclaturas.md          ← Nomenclaturas oficiais de UI/rotas/mensagens (NM-xx)
└── agents/
    ├── architect/AGENTS.md
    ├── laravel-backend/AGENTS.md
    ├── blade-frontend/AGENTS.md
    ├── database/AGENTS.md
    └── qa/AGENTS.md
```

## Como o Cline lê estas regras (convenção oficial)

- `.clinerules/*.md` — regras aplicadas **globalmente** (todos os arquivos do projeto).
- Regras por extensão de arquivo: crie `.clinerules/{ext}.md` (ex.: `php.md` aplica a `*.php`);
  use subpasta na raiz do repo (ex.: `app/.clinerules/php.md`) para escopar a um diretório.
- `.clinerules/agents/{nome}/AGENTS.md` — define um **subagente/papel** acionável.
- Skills: `.claude/skills/{nome}/SKILL.md` (ver `03-agentes-e-skills.md`).

## Como adicionar

1. **Regra global nova** → novo `.clinerules/{nome}.md` ou acrescente ao arquivo existente.
2. **Regra por tipo de arquivo** → `.clinerules/{ext}.md`.
3. **Regra de Domínio** → acrescente em `.clinerules/regras-dominio.md` (numerada `RDD-xx`) e
   referencie nas specs em que se aplica; o Cline sempre a leva em conta.
4. **Nomenclatura** → acrescente em `.clinerules/nomenclaturas.md` (numerada `NM-xx`); o Cline
   usa os rótulos oficiais em specs, tasks, código e testes.
5. **Novo agente/subagente** → crie `.clinerules/agents/{nome}/AGENTS.md` com escopo, entrada,
   saída, sempre/nunca; depois registre no catálogo `docs/sdd/03-agentes-e-skills.md`.
6. **Nova skill** → crie `.claude/skills/{nome}/SKILL.md` + templates, e registre no catálogo.

> ⚠️ O `CLAUDE.md` (Laravel Boost) é a camada superior de regras e **deve** ser sempre respeitado
> em conjunto com estas regras.