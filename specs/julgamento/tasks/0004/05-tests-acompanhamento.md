# Task — Testes do Acompanhamento do Julgamento

> **Spec**: [../../0004-acompanhamento-julgamento.md](../../0004-acompanhamento-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `qa`

## Objetivo
Cobrir com PHPUnit os critérios de aceite da spec 0004.

## Arquivos-fonte
- `tests/Feature/JudgingFollowUpTest.php` (criar via `php artisan make:test --phpunit`)
- `tests/Feature/JudgeSelectionTest.php` (adicionar caso de votação finalizada)

## Casos mínimos
- Acesso: admin vê a tela; não-admin é bloqueado em GET e em todos os POST.
- Edição padrão = em julgamento; seletor com edição fora de julgamento → somente leitura.
- Matriz: "Finalizada" com cota completa, "Aberta" caso contrário; ordem das categorias.
- Votos: "X de N" com N = total de julgadores; ordenação por votos DESC.
- Resetar: apaga só as seleções da edição; preserva outra edição; rejeitado após finalizar e para
  usuário sem `is_judge`.
- Finalizar: grava `voting_closed_at`; segunda chamada é rejeitada; `/julgar` deixa de oferecer
  a edição e o POST de seleção é rejeitado.
- Agraciados: rejeitado com votação aberta; limite `recipients_count`; mínimo 1; Inscrição de
  outra categoria/não qualificada; categoria já confirmada; sucesso grava status `Agraciado` e
  `awardees_confirmed_at` para `Registration` e `Nominee`.

## Dependências
- Tasks 01 a 04.

## Verificação
- [x] `php artisan test --compact tests/Feature/JudgingFollowUpTest.php`
- [x] `php artisan test --compact tests/Feature/JudgeSelectionTest.php`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-10-07 | pending | Claude |
| 2026-10-07 | done | Claude |
