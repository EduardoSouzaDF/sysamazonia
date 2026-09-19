# Task — Rotas + `JudgingController` (wizard) + `JudgeSelectionRequest`

> **Spec**: [../0003-tela-julgamento.md](../0003-tela-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Implementar o fluxo servidor do wizard: `GET /julgar` passa a resolver a **categoria atual
elegível** (ou a conclusão) e `POST /julgar` grava as seleções do julgador.

## Arquivos-fonte
- `routes/web.php` (editar — `POST /julgar` no **mesmo grupo/estado** da GET: grupo `auth` +
  `CheckJudge` por FQCN, sem alias no `bootstrap/app.php`): `panel.julgar.store`
- `app/Http/Controllers/JudgingController.php` (editar)
- `app/Http/Requests/JudgeSelectionRequest.php` (criar)

## Critérios de Aceite (BDD)
- **DADO QUE** um julgador faz `GET /julgar`
  **ENTÃO** o controller calcula as **categorias elegíveis** (Inscrições qualificadas —
  `Registration` "Avaliado"/`Nominee` "Habilitado" — de edições RDD-01+RDD-03,
  `recipients_count > 0`, não `isFullyJudgedBy`), ordena **regulares → honoríficas, id ASC** e
  passa à view a primeira com: categoria, cards (tipo/label/ano), cota efetiva
  `min(recipients_count, elegíveis)` e eager loading (RF-05: `candidate`,
  `category.modality.edition`, `files`, `opinions.scores.evaluationCriterion`, `indications`).
- **DADO QUE** `Registration`
  **ENTÃO** cards = **top 20** por `COUNT(indications) DESC`, `evaluation_avg` DESC (nulas por
  último), `id ASC`; label `{acronym} {id} - {ano de judgment_date}`; para `Nominee`: **todas**
  as "Habilitado" (`id ASC`).
- **DADO QUE** não há categoria elegível
  **ENTÃO** a view recebe o **resumo de conclusão** (seleções do julgador agrupadas por categoria)
  ou o estado vazio.
- **DADO QUE** o julgador submete `POST /julgar` (`JudgeSelectionRequest`)
  **ENTÃO** valida `category_id` (obrigatório/existe) e estrutura de `inscriptions` (array
  `size = cota efetiva`; cada item `type ∈ {registration, nominee}` + `id`), e o controller —
  em **transação** — revalida: categoria é a atual elegível; inscrições pertencem a ela e são
  qualificadas; sem duplicidade (unique). Grava os `JudgeSelection` e redireciona para
  `panel.julgar.index` com flash `success` ("Seleções registradas com sucesso.").
- **DADO QUE** o payload viola qualquer regra (cota errada, inscrição de outra categoria,
  inválida/duplicada, categoria já concluída, type inválido)
  **ENTÃO** responde erro de validação (422/redirect back) **sem gravar nada**.

## Convenções a seguir
- FormRequest com **mensagens customizadas em pt-BR** (regra `php.md`); nada de validação inline.
- Reutilizar `judgingEditionFilter()` e o padrão `toBase()->merge()` já existentes no controller.
- Nomes oficiais: rota `panel.julgar.store`; flash via `with('success', ...)` (NM-05/blade.md).

## Dependências
- [01-migration-model.md](01-migration-model.md) · [02-relacoes-cota.md](02-relacoes-cota.md)

## Verificação
- [x] `php artisan route:list --name=panel.julgar` (GET + POST)
- [x] `php artisan test --compact --filter=JudgingAccessTest` — 5/6 passam; a 1 falha é
  **pré-existente/prevista** (asserts da listagem antiga da 0002 sobre a view vazia; a
  adaptação ao wizard é da task 05 — `JudgeSelection` testará o novo fluxo)
- [x] Smoke funcional (script descartável com transação + rollback, dados reais do dev):
  GET resolve categoria elegível + cota efetiva + cards ("PNETT2 105 - 2026" — RF-04);
  FormRequest rejeita cota errada ("A seleção deve conter exatamente 1 inscrição(ões)…");
  POST grava `JudgeSelection` com **alias do morph map** + flash "Seleções registradas com
  sucesso."; re-submit falha **sem gravar nada** (rollback); wizard avança para a próxima
  categoria; após esgotar todas, resumo de conclusão agrupado por categoria
- [x] `php -l` / `vendor/bin/pint --test` nos 3 arquivos (PASS)

## Notas / Débito técnico
- O antigo `index()` (listagem única da 0002/v1.7.0) é **substituído** — ajuste dos testes na
  task 05 (spec 0003 §Débito).
- Se `recipients_count` crescer depois de concluída, a categoria volta a ficar elegível com o
  saldo restante (derivado de RF-01) — não tratar como erro.
- **Implementação (2026-08-27)**:
  - Elegibilidade = `remainingQuotaFor() > 0`, onde saldo = `min(recipients_count, cards)`
    (cards = top 20 de `Registration` para regulares; todas as `Nominee` para honoríficas) −
    seleções já gravadas. Interpretação de RF-01/RF-06: uma categoria cuja **cota efetiva** já
    foi completa (ex.: cota 5, elegíveis 3, 3 selecionadas) **não** volta ao wizard — sem
    dead-end; `isFullyJudgedBy()` (cota bruta) continua como revalidação explícita no store.
  - FormRequest valida estrutura + `size` = **cota efetiva restante** e duplicidade composta
    (`type`+`id`); o controller revalida tudo em transação (defesa em profundidade — espelha o
    filtro RDD-01/03, documentado no código).
  - `inscriptions` vazio cai na regra `required` ("A lista de inscrições é obrigatória."); cota
    errada (maior/menor não-vazio) cai na regra de `size` — ambas em pt-BR.
  - Gravação usa `getMorphClass()` → aliases `registration`/`nominee` do morph map (task 01).
  - View recebe: `$category`, `$cards[]` (`key`, `type`, `label`, `year`, `inscription`),
    `$effectiveQuota`, `$remainingQuota`, `$selectedIds[]` ("type:id"), `$summary` — contrato
    documentado no docblock de `index()` para a task 04.
  - Smoke fora do pipeline HTTP exigiu `validateResolved()` manual — detalhe do harness de
    teste, **não** afeta o fluxo HTTP real.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — rotas GET+POST `panel.julgar.*` (mesmo grupo `auth`+`CheckJudge`), `JudgeSelectionRequest` (pt-BR), `JudgingController` wizard (`index`/`store`) com revalidação em transação; verificado com `route:list`, `php -l`, Pint PASS, smoke funcional completo com rollback e suíte (5/6, falha pré-existente) | Cline |
