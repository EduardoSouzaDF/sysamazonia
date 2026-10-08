# Task — View "Acompanhamento"

> **Spec**: [../../0004-acompanhamento-julgamento.md](../../0004-acompanhamento-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `ui-ux` + `blade-frontend`

## Objetivo
Implementar a tela do protótipo `acompanhar.jfif`: seletor de edição, matriz da comissão e cards
de votos por categoria.

## Arquivos-fonte
- `resources/views/admin/acompanhamento/index.blade.php` (criar)
- `resources/views/admin/acompanhamento/partial/*` (criar, se necessário)

## Critérios de Aceite (BDD)
- **DADO QUE** a tela abre
  **ENTÃO** mostra o título "Acompanhamento", o seletor de edição, a seção "Acompanhamento da
  Comissão" (matriz com badges "Finalizada" verde / "Aberta" laranja e "Resetar" por linha) e o
  botão "Finalizar votação".
- **DADO QUE** há votos
  **ENTÃO** a seção "Acompanhamento dos votos por categoria" mostra um card por categoria com
  barras "X de N votos" (grid de 2 colunas no desktop, 1 no mobile).
- **DADO QUE** o admin clica em "Resetar" ou "Finalizar votação"
  **ENTÃO** um modal de confirmação descreve a consequência; o de Finalizar lista as pendências
  (julgador × categoria) quando houver.
- **DADO QUE** a edição é somente leitura ou a votação está finalizada
  **ENTÃO** os botões indisponíveis não aparecem/ficam desabilitados, e cada card de categoria
  oferece "Confirmar agraciados" (votação finalizada) ou a lista de agraciados (categoria travada).
- **DADO QUE** a tela é aberta em 800px
  **ENTÃO** a matriz rola na horizontal com a coluna de julgadores fixa.

## Convenções a seguir
- Layout `admin.content`, Tailwind v4 e KTUI; seguir as cores/cards já usados em
  `admin/registration` e `admin/julgar`.

## Dependências
- Task 02.

## Verificação
- [x] `npm run build` e conferência visual (Playwright) em 1280px e 800px
- [x] Testes de view da task 05

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-10-07 | pending | Claude |
| 2026-10-07 | done | Claude |
