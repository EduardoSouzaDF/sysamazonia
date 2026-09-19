# Task — Relações e helpers de cota (`recipients_count`)

> **Spec**: [../0003-tela-julgamento.md](../0003-tela-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Adicionar as relações do `JudgeSelection` nos models existentes e os **helpers de cota** usados
pelo wizard (elegibilidade de categoria e saldo do julgador).

## Arquivos-fonte
- `app/Models/User.php` (editar)
- `app/Models/Registration.php` (editar)
- `app/Models/Nominee.php` (editar)
- `app/Models/Category.php` (editar)

## Critérios de Aceite (BDD)
- **DADO QUE** o julgador tem seleções
  **ENTÃO** `User::judgeSelections(): HasMany` as retorna; `User::hasSelected(Model $inscription): bool`
  reflete a existência da seleção morph (via `whereMorphedTo`).
- **DADO QUE** uma Inscrição foi selecionada
  **ENTÃO** `Registration::judgeSelections()` / `Nominee::judgeSelections(): MorphMany` a resolve.
- **DADO QUE** a categoria tem N inscrições selecionadas pelo julgador
  **ENTÃO** `Category::judgeSelectionsBy(User $user): int` retorna N (contando seleções cuja
  inscrição morfa para `registrations`/`nominees` desta categoria).
- **DADO QUE** a categoria tem `recipients_count = R`
  **ENTÃO** `Category::remainingQuotaFor(User $user): int = max(0, R − judgeSelectionsBy)` e
  `Category::isFullyJudgedBy(User $user): bool = (remaining === 0 && R > 0)`.

## Convenções a seguir
- Relações nomeadas descritivamente; docblocks com o caminho da relação (padrão do `User`).
- Helpers com retorno tipado; query enxuta (`withCount`/`whereHas`), sem N+1.
- **Não** alterar o stub pré-existente `Nominee::indications()` (bug latente registrado na spec).

## Dependências
- [01-migration-model.md](01-migration-model.md)

## Verificação
- [x] `php -l` nos 4 models — OK
- [x] `vendor/bin/pint --test app/Models` — **os 4 arquivos da task PASS**; 5 arquivos **pré-existentes** continuam fora do padrão (ActionToken, Candidate, Indication, RegistrationFile, UserRole) — débito pré-existente, não alterados
- [x] Sanity tinker: `remainingQuotaFor` antes/depois de gravar seleções — cat#7 (R=1): antes `by=0, rem=1, fully=false`; após 1 seleção `by=1, rem=0, fully=true`; `hasSelected()` e relação `judgeSelections()` OK; rollback

## Notas / Débito técnico
- A **cota efetiva** `min(recipients_count, elegíveis)` é calculada no controller (task 03), pois
  depende do filtro RDD-01/RDD-02/RDD-03 — os helpers do model ficam "burros" (apenas saldo).
- ⚠️ O Pint **normalizou trechos pré-existentes** de `Nominee.php` e `Registration.php`
  (eram arquivos fora do padrão: `new Collection()` → `new Collection`, separação de membros,
  indentação). Diff total dos 4 models: +100/−24. Revisar no PR.
- ⚠️ `User::hasSelected()` usa `whereMorphedTo` (Laravel 10.31+); retorna `false` para
  inscrição `null`/inexistente — comportamento desejado.
- `judgeSelectionsBy()` conta seleções de **qualquer** usuário julgador passado (não valida
  `is_judge` — validação de acesso é papel do middleware/controller, task 03).

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — `User::judgeSelections()/hasSelected()` (whereMorphedTo), `Registration::judgeSelections()` e `Nominee::judgeSelections()` (morphMany `inscription`), `Category::judgeSelectionsBy()/remainingQuotaFor()/isFullyJudgedBy()` (whereHasMorph). `php -l` OK; Pint PASS nos 4 models (normalizou estilo pré-existente de Nominee/Registration — ver notas); sanity tinker OK (saldo 1→0, fully false→true, rollback). Stub `Nominee::indications()` intacto | Cline |
