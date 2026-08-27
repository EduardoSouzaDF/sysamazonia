# Task — View do painel do julgador (index)

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `blade-frontend`

## Objetivo
Criar a view `admin.julgar.index` que lista **todas as Inscrições** (`Registration` e `Nominee`)
de **edições ativas** (RDD-01) **em julgamento** (RDD-03) — `Registration` com status "Avaliado" e
`Nominee` com status "Habilitado" — para o painel do julgador.

## Arquivos-fonte
- `resources/views/admin/julgar/index.blade.php` (criar)

## Critérios de Aceite (BDD)
- **DADO QUE** um julgador abre o painel
  **ENTÃO** a view lista as Inscrições (Registration "Avaliado" e Nominee "Habilitado") de todas
  as edições ativas em julgamento.
- **DADO QUE** não há inscrições disponíveis
  **ENTÃO** exibe uma mensagem de vazio (estado sem registros).
- **DADO QUE** a view é renderizada
  **ENTÃO** reutiliza os componentes existentes do projeto (`components/pages/index`,
  `components/messages/*`).

## Convenções a seguir
- Tailwind v4 (`@import "tailwindcss"`).
- Reutilizar `components/pages/index` para cabeçalho/ações (espelho de `admin.*`).
- Usar `route('panel.julgar.index')` / links nomeados.
- Mensagens de sucesso/erro via componente `components/messages/alert`.

## Dependências
- `02-controller-painel.md` (dados do controller)

## Verificação
- [ ] Abrir o painel em `/julgar` e conferir listagem/vazio/visual
- [ ] `npm run build` se houver asset novo

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
