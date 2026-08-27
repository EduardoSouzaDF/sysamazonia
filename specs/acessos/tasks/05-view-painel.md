# Task — View do painel do julgador (index)

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `blade-frontend`

## Objetivo
Criar o arquivo da view `admin.julgar.index` — **somente o arquivo**; a implementação/visual da
listagem de **todas as Inscrições** (`Registration` e `Nominee`) de **edições ativas** (RDD-01) em
julgamento (RDD-03) fica **a cargo do dev**. O controlador (`JudgingController@index`) fornece a
**collection** completa (sem paginação).

## Arquivos-fonte
- `resources/views/admin/julgar/index.blade.php` (criar — **somente o arquivo**; conteúdo/visual a
  cargo do dev)

## Critérios de Aceite (BDD)
- **DADO QUE** um julgador abre o painel
  **ENTÃO** a view (`admin.julgar.index`) existe e o dev a implementa para listar as Inscrições
  (Registration "Avaliado" e Nominee "Habilitado") de todas as edições ativas em julgamento.
- **DADO QUE** a view é criada
  **ENTÃO** recebe a **collection** passada pelo `JudgingController@index` (sem paginação).

## Convenções a seguir
- ⚠️ **Somente criar o arquivo** — a implementação/visual (Tailwind v4, `components/pages/index`,
  `components/messages/*`, `route('panel.julgar.index')`) fica **a cargo do dev**.
- O controlador passa uma **collection** (sem paginação); a view não deve assumir
  `LengthAwarePaginator`.

## Dependências
- `02-controller-painel.md` (dados do controller)

## Verificação
- [ ] O arquivo `resources/views/admin/julgar/index.blade.php` existe (criado)

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — `resources/views/admin/julgar/index.blade.php` criada estendendo `admin.content` e reutilizando `x-pages.index` (colunas Edição/Categoria/Título/Autor/Status; sem ações — "julgar em si" é fora de escopo; estado vazio nativo do componente). Renderização coberta por testes | Cline |
| 2026-08-27 | v1.7.0 — **View somente criada**: a implementação/visual fica a cargo do dev; o controlador passa **collection** (sem paginação) | Cline |
