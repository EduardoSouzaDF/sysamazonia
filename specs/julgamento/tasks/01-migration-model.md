# Task — Migration + Model `JudgeSelection` (tabela `judge_selections`)

> **Spec**: [../0003-tela-julgamento.md](../0003-tela-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `database`

## Objetivo
Criar a tabela `judge_selections`, o model `JudgeSelection` (relação julgador ⇄ Inscrição via
morph `inscription`) e o morph map global (`registration`/`nominee`).

## Arquivos-fonte
- `database/migrations/xxxx_xx_xx_xxxxxx_create_judge_selections_table.php` (criar)
- `app/Models/JudgeSelection.php` (criar)
- `app/Providers/AppServiceProvider.php` (editar — `Relation::enforceMorphMap([...])` no `boot()`)

## Critérios de Aceite (BDD)
- **DADO QUE** a migration roda
  **ENTÃO** `judge_selections` possui `id`, `user_id` (FK → `users`, cascade), `inscription_type`
  (string), `inscription_id` (unsignedBigInteger), `timestamps`, **unique**
  `judge_selections_user_inscription_unique` (`user_id`, `inscription_type`, `inscription_id`) e
  index (`inscription_type`, `inscription_id`).
- **DADO QUE** o model existe
  **ENTÃO** `$fillable = ['user_id', 'inscription_type', 'inscription_id']` (nunca
  `$guarded = []`), `user(): BelongsTo`, `inscription(): MorphTo` e `scopeByUser($query, $userId)`.
- **DADO QUE** o morph map está registrado
  **ENTÃO** uma `Registration` resolve `inscription_type = 'registration'` e um `Nominee`
  resolve `'nominee'` (e o `morphTo()` retorna o model correto).

## Convenções a seguir
- `php artisan make:model JudgeSelection` (migration avulsa, padrão do projeto).
- Sem soft deletes; boolean/datas desnecessários. Pint/PSR-12; `php -l` após cada edição.

## Dependências
- Nenhuma (primeira task da spec).

## Verificação
- [x] `php artisan migrate` (e `php artisan migrate:rollback` / `migrate` idempotente) — rollback + re-migrate executados com sucesso
- [x] Sanity de morph via teste/tinker (criar JudgeSelection apontando para Registration e Nominee): `'registration'` → `App\Models\Registration`, `'nominee'` → `App\Models\Nominee`; `getMorphClass()` dos models retorna os aliases do morph map; `scopeByUser` OK; rollback após o teste
- [x] `vendor/bin/pint --test app/Models/JudgeSelection.php database/migrations/*judge_selections*` — **PASS** (após fix automático de line endings/phpdoc)

## Estrutura confirmada (`php artisan db:table judge_selections`)
- Colunas: `id`, `user_id`, `inscription_type`, `inscription_id`, `created_at`, `updated_at`
- Unique composto `judge_selections_user_inscription_unique` (`user_id`, `inscription_type`, `inscription_id`)
- Index composto `judge_selections_inscription_type_inscription_id_index`
- FK `judge_selections_user_id_foreign` → `users.id` **ON DELETE cascade**

## Notas / Débito técnico
- O morph map é global — verificado que **não há** relações morph pré-existentes no `app/`;
  risco baixo, mas citar no PR.
- `Category->recipients_count` já existe (cast integer) — **não** criar coluna.
- ⚠️ Ambiente de execução: PHP via **Laravel Herd** (`C:\Users\Eduardo\.config\herd\bin\php84\php.exe`;
  o wrapper `php.bat` quebra o quoting do `tinker --execute`). Docker indisponível no host.
- ⚠️ `tests/Feature/JudgingAccessTest.php`: **1 falha pré-existente** (5/6 passam) — o `assertSee`
  espera a listagem da spec 0002, mas a view `admin/julgar/index.blade.php` está vazia (0 bytes)
  no HEAD. Débito já registrado na spec 0003; a **task 05** adaptará este teste.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — Migration `2026_08_27_185551_create_judge_selections_table` + model `JudgeSelection` ($fillable, `user()`, `inscription()` morphTo, `scopeByUser`) + `Relation::enforceMorphMap` no `AppServiceProvider`. `migrate` + rollback/re-migrate OK; estrutura confirmada via `db:table` (unique composto, index, FK cascade); sanity morph via tinker OK (aliases `registration`/`nominee` resolvidos pelo morphTo); Pint PASS; `php -l` OK. Obs.: `JudgingAccessTest` 1/6 falha pré-existente (view vazia no HEAD; task 05 adaptará) | Cline |
