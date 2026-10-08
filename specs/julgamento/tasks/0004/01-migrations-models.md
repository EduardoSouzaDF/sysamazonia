# Task — Migrations `voting_closed_at` / `awardees_confirmed_at` + helpers

> **Spec**: [../../0004-acompanhamento-julgamento.md](../../0004-acompanhamento-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `database`

## Objetivo
Registrar no banco o encerramento da votação por edição e a confirmação de agraciados por
categoria (spec 0004, §6.1/§6.2).

## Arquivos-fonte
- `database/migrations/xxxx_xx_xx_xxxxxx_add_voting_closed_at_to_editions_table.php` (criar)
- `database/migrations/xxxx_xx_xx_xxxxxx_add_awardees_confirmed_at_to_categories_table.php` (criar)
- `app/Models/Edition.php` · `app/Models/Category.php` (editar)

## Critérios de Aceite (BDD)
- **DADO QUE** as migrations rodam
  **ENTÃO** `editions.voting_closed_at` e `categories.awardees_confirmed_at` existem como
  `timestamp` nullable, e o `down()` remove as colunas.
- **DADO QUE** os models foram atualizados
  **ENTÃO** as colunas têm cast `datetime`, `Edition::isVotingClosed(): bool` e
  `Category::areAwardeesConfirmed(): bool` retornam `true` só com a coluna preenchida.

## Convenções a seguir
- `php artisan make:migration ... --no-interaction`; seguir o formato de casts de cada model
  (ambos usam a propriedade `$casts`).
- Pint nos arquivos alterados.

## Dependências
- Nenhuma.

## Verificação
- [x] `php artisan migrate` + `migrate:rollback --step=2` + `migrate`
- [x] `vendor/bin/pint --dirty`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-10-07 | pending | Claude |
| 2026-10-07 | done | Claude |
