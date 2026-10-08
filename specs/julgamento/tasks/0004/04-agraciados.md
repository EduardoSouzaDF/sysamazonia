# Task — Confirmar agraciados por categoria

> **Spec**: [../../0004-acompanhamento-julgamento.md](../../0004-acompanhamento-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `laravel-backend` + `blade-frontend`

## Objetivo
Implementar a escolha e a confirmação dos agraciados de uma categoria (RF-10 a RF-12).

## Arquivos-fonte
- `app/Http/Controllers/JudgingFollowUpController.php` (editar: `awardees`, `confirmAwardees`)
- `app/Http/Requests/ConfirmAwardeesRequest.php` (criar)
- `routes/web.php` (GET + POST `.../categorias/{category}/agraciados`)
- `resources/views/admin/acompanhamento/agraciados.blade.php` (criar)

## Critérios de Aceite (BDD)
- **DADO QUE** a votação da edição está finalizada
  **QUANDO** o admin abre os agraciados de uma categoria
  **ENTÃO** vê as 10 Inscrições mais votadas em destaque e a lista completa das qualificadas com
  busca, nenhuma marcada, e o contador "X de {recipients_count}".
- **DADO QUE** o admin marcou `recipients_count` Inscrições
  **ENTÃO** os demais checkboxes ficam desabilitados.
- **DADO QUE** o admin confirma
  **ENTÃO** em transação as Inscrições viram `Agraciado` e `awardees_confirmed_at` é gravado.
- **DADO QUE** a votação está aberta, a categoria já foi confirmada, a quantidade está fora de
  1..`recipients_count` ou há Inscrição de outra categoria/não qualificada
  **ENTÃO** a requisição é rejeitada e nada é gravado.
- **DADO QUE** a categoria já foi confirmada
  **ENTÃO** a página mostra os agraciados em modo somente leitura.

## Convenções a seguir
- `ConfirmAwardeesRequest` com regras em array (padrão de `JudgeSelectionRequest`) e mensagens
  em português; regras de negócio revalidadas no controller dentro da transação.

## Dependências
- Tasks 01 e 02.

## Verificação
- [x] Testes de agraciados da task 05
- [x] `vendor/bin/pint --dirty`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-10-07 | pending | Claude |
| 2026-10-07 | done | Claude |
