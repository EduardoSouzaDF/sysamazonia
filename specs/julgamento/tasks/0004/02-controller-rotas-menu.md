# Task — Controller, rotas, bloqueio do `/julgar` e menu

> **Spec**: [../../0004-acompanhamento-julgamento.md](../../0004-acompanhamento-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Criar o `JudgingFollowUpController` com a montagem da matriz e dos votos (RF-02 a RF-07), as
ações Resetar e Finalizar (RF-05, RF-06, RF-08), o bloqueio do `/julgar` após o encerramento
(RF-09) e o item de menu (RF-13).

## Arquivos-fonte
- `app/Http/Controllers/JudgingFollowUpController.php` (criar: `index`, `reset`, `close`)
- `app/Http/Controllers/JudgingController.php` (editar `judgingEditionFilter`)
- `routes/web.php` (grupo `admin`, middleware `CheckAdmin::class.':admin'`)
- `app/Services/MenuBuilder.php` (editar `getAdminMenu`)

## Critérios de Aceite (BDD)
- **DADO QUE** um admin acessa `GET /admin/acompanhamento[?edition={id}]`
  **ENTÃO** a view recebe: edição exibida (padrão: em julgamento), lista de edições para o
  seletor, flag somente-leitura, julgadores, categorias (ordem RF-03), status de cada célula
  (RF-04) e votos por categoria com X e N (RF-07).
- **DADO QUE** o admin envia `POST .../julgadores/{user}/resetar`
  **ENTÃO** apaga as `judge_selections` do julgador limitadas às categorias da edição; com a
  votação finalizada, a edição fora de julgamento ou `{user}` sem `is_judge` → erro, nada apagado.
- **DADO QUE** o admin envia `POST .../finalizar`
  **ENTÃO** grava `voting_closed_at`; se já finalizada → erro.
- **DADO QUE** a votação da edição foi finalizada
  **ENTÃO** o filtro de `JudgingController` exclui a edição (`whereNull('voting_closed_at')`).
- **DADO QUE** o admin abre o menu
  **ENTÃO** "Acompanhamento" aparece após "Inscrições" em "Administração Prêmios".

## Convenções a seguir
- Reutilizar a lógica de cota/ordenação do `JudgingController`, extraindo para um service ou
  para os helpers de `Category` se for preciso compartilhar, sem duplicar regra.
- Contagens agregadas (sem N+1); escrita em `DB::transaction`; flash `success`/`error`.

## Dependências
- Task 01.

## Verificação
- [x] Testes da task 05 relacionados a matriz/reset/finalizar
- [x] `php artisan test --compact tests/Feature/JudgeSelectionTest.php` continua passando
- [x] `vendor/bin/pint --dirty`

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-10-07 | pending | Claude |
| 2026-10-07 | done | Claude |
