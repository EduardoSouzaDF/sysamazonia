# Task — Testes da Tela de Julgamento

> **Spec**: [../0003-tela-julgamento.md](../0003-tela-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `qa`

## Objetivo
Cobrir com PHPUnit os critérios de aceite da spec 0003 (wizard, ordenação, cota, gravação,
conclusão e acesso) e **adaptar** o teste da spec 0002 ao novo formato do painel.

## Arquivos-fonte
- `tests/Feature/JudgeSelectionTest.php` (criar)
- `tests/Feature/JudgingAccessTest.php` (editar — adaptar FR-03 ao wizard)
- `database/factories/*` (ajustar/criar estados, ex.: `Category` com `recipients_count`,
  `Edition` com `judgment_date` no passado, se não existirem)

## Critérios de Aceite (BDD)
- **DADO QUE** o julgador acessa `GET /julgar`
  **ENTÃO** vê **1 categoria** (regulares primeiro, id ASC), somente com cards qualificados
  (RDD-01/02/03), cota "X de Y" e — para regular — **top 20** por indicações DESC,
  `evaluation_avg` DESC, `id ASC`; para honorífica, todas as "Habilitado".
- **DADO QUE** a inscrição é renderizada
  **ENTÃO** o label segue `{acronym} {id} - {ano de judgment_date}` (ex.: "PSD 3232 - 2026").
- **DADO QUE** o julgador submete `POST /julgar` com a **cota efetiva** completa e válida
  **ENTÃO** grava N `judge_selections`, redireciona e a próxima categoria (ou conclusão) aparece.
- **DADO QUE** o POST traz cota errada / inscrição de outra categoria / duplicada / tipo inválido /
  categoria concluída
  **ENTÃO** falha a validação **sem gravar**.
- **DADO QUE** a categoria tem menos elegíveis que `recipients_count`
  **ENTÃO** a cota efetiva é `min(cota, elegíveis)` e o fluxo completa com todas.
- **DADO QUE** todas as categorias foram julgadas
  **ENTÃO** `GET /julgar` mostra a conclusão com o resumo por categoria.
- **DADO QUE** não-julgador acessa GET/POST
  **ENTÃO** logout + "Perfil sem Acesso!" (mantém FR-04/05 da 0002).

## Convenções a seguir
- Testes **PHPUnit** (não Pest) com `RefreshDatabase`; `UserFactory::judge()` já existe.
- Criar dados cobrindo: categoria regular, honorífica, edições fora dos filtros (negativos) e
  `EvaluationCriterion`/`Opinion`/`Score`/`Indication` mínimos para ordenação/leitura.
- Rodar **apenas o teste relacionado** com filtro.

## Dependências
- [01-migration-model.md](01-migration-model.md) · [02-relacoes-cota.md](02-relacoes-cota.md) ·
  [03-controller-rotas.md](03-controller-rotas.md) · [04-view-julgamento.md](04-view-julgamento.md)

## Verificação
- [x] `php artisan test --compact --filter=JudgeSelection` — **11 testes passam**
- [x] `php artisan test --compact --filter=JudgingAccessTest` — **5 testes passam** (FR-03
  reescrito para o wizard; a falha pré-existente foi eliminada)
- [x] Suíte completa: 17/18 — a única falha é o `ExampleTest` (scaffold do Laravel: espera
  200 em `/`, mas a app redireciona 302 → login) — **pré-existente e alheio** à spec 0003
- [x] `php -l` + Pint nos 2 arquivos de teste (PASS)

## Notas / Débito técnico
- O teste antigo que esperava a **listagem única** de Inscrições foi reescrito para o wizard
  (a 0003 substitui o FR-03 da 0002) em `test_julgador_acessa_painel_e_ve_inscricoes_apropriadas`:
  categoria com `recipients_count`, card NM-05 `data-label="PAIN {id} - {ano}"`, drawer em
  `<template>` ("Dados da Inscrição" / "Avaliações e indicações") e a Nominee de outra categoria
  fora da vez (RF-02).
- `JudgeSelectionTest` (11 testes, `RefreshDatabase`) cobre os BDDs desta task com **helpers
  inline** (padrão do `JudgingAccessTest`) — não foram criados factories novos (`database/factories/*`
  permaneceu intacto): edição em julgamento (RDD-03), categorias regular/honorífica com cota,
  candidato (campos únicos), inscrições "Avaliado"/"Habilitado" (RDD-02), opiniões/notas/critérios/
  indicações para ordenação e anexo para o drawer.
- CSRF nos POSTs de teste: `$this->withoutMiddleware(ValidateCsrfToken::class)` no `setUp()`
  (escopo do teste, sem alteração no middleware da aplicação).
- `assertSee` com aspas (`data-label="..."`) usa o parâmetro `escape = false`.
- **Fix colateral no helper antigo**: `createCategoryForEdition()` recebia `$attributes` na
  chamada mas o **ignorava** (categoria ficava sem `recipients_count`/`acronym`) — assinatura
  corrigida para `(Edition $edition, array $attributes = [])`.
- Débito pré-existente: `ExampleTest` (scaffold) falha na suíte completa — fora do escopo da 0003.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — `JudgeSelectionTest` criado (11 testes: fluxo do wizard, cota efetiva, ordenação top-20, honoríficas, conclusão com resumo, rejeições sem gravar, não-julgador GET/POST, drawers) e `JudgingAccessTest` adaptado ao wizard (5 testes); 16/16 no filtro combinado, suíte completa 17/18 (única falha: `ExampleTest` scaffold pré-existente); `php -l`/Pint OK | Cline |
