# Task — View da Tela de Julgamento (grid + pré-seleções + Drawer Shoelace + conclusão)

> **Spec**: [../0003-tela-julgamento.md](../0003-tela-julgamento.md)
> **Status**: `done`
> **Agente sugerido**: `ui-ux` + `blade-frontend`

## Objetivo
Implementar `admin.julgar.index` (**hoje vazia**) conforme os protótipos
`specs/protitpos/spec-02/*.png`: header da categoria, grid de cards, drawer de detalhe,
pré-seleções e telas de conclusão/vazio.

## Arquivos-fonte (implementados)
- `resources/views/admin/julgar/index.blade.php` — tela completa (dois estados: categoria corrente e conclusão)
- `resources/views/admin/julgar/partials/drawer-registration.blade.php` — conteúdo do drawer (Registration)
- `resources/views/admin/julgar/partials/drawer-nominee.blade.php` — conteúdo do drawer (Nominee)
- `resources/views/components/julgar/card.blade.php` — card do grid (`x-julgar.card`)
- `resources/views/components/julgar/summary.blade.php` — resumo de conclusão (`x-julgar.summary`)
- `public/js/julgar.js` — estado client-side (pré-seleção, contador, submits)
> **Desvio da task (registrado)**: `card` e `summary` ficaram em
> `resources/views/components/julgar/` (não em `admin/julgar/partial/`) — no Laravel 11,
> componentes `x-*` só resolvem a partir de `views/components/`; `x-admin.julgar.partials.card`
> não é resolvido (`Unable to locate a class or view for component`). `card.blade.php` já
> recebia `$card` do `@include` original, então a mudança foi transparente.

## Critérios de Aceite (BDD)
- **DADO QUE** há categoria atual
  **ENTÃO** a tela estende `admin.content` e mostra "Categoria: {title}", o **grid responsivo de
  cards** (2 colunas no desktop, empilha no mobile) e o contador **"X de Y"** (X = pré-seleções,
  Y = cota efetiva).
- **DADO QUE** o card existe
  **ENTÃO** é um `<button>` acionável por teclado com o rótulo NM-05
  ("PSD 3232 - 2026"), estados **selecionado** (destaque/ring) e **desabilitado** (cota cheia).
- **DADO QUE** o julgador clica no card
  **ENTÃO** abre o **Drawer Shoelace** (`sl-drawer`, `placement="end"`) com o conteúdo RF-05
  (`Registration`: dados de inscrição + anexos + "Avaliações e indicações" em leitura;
  `Nominee`: dados da indicação, sem avaliações) e os botões **"Confirmar"** (adiciona à
  pré-seleção e fecha; inócuo se já selecionada/cota cheia) e **"Fechar"**.
- **DADO QUE** há pré-seleções
  **ENTÃO** a listagem (rótulo NM-05) exibe os cards escolhidos; **"Limpar"** zera; o botão
  **"Confirmar"** de submissão **só renderiza com a cota efetiva completa** e submete o form
  POST (`@csrf`, `category_id` hidden, `inscriptions[][type]`/`inscriptions[][id]` hidden
  mantidos por JS).
- **DADO QUE** o POST redireciona
  **ENTÃO** o flash `success` aparece via `x-messages.alert` e a próxima categoria/conclusão é
  renderizada (seção `conclusao`: "Julgamento concluído!" + resumo por categoria, ou estado vazio).

## Convenções a seguir
- **Tailwind v4**; reutilizar `kt-*`/`components/messages/*`; JS vanilla em `@push('scripts')`
  (padrão dos siblings) — sem jQuery onde o padrão atual não usar.
- **Shoelace via CDN `@shoelace-style/shoelace@2.20.1`** (tema `light.css` +
  `shoelace-autoloader.js`), como em `public/js/forms.edition.js` — **não** instalar pacote novo.
- Acessibilidade: foco gerenciado no drawer, `aria-live` no contador, contraste; `dark:` se o
  projeto usar.
- Rótulos **exclusivamente** os de NM-05.

## Dependências
- [03-controller-rotas.md](03-controller-rotas.md) (dados passados à view)

## Verificação
- [x] Renderização verificada com dados reais (script descartável, transação + rollback): **12/12
  checks** — título, listagem NM-05, contadores, botões Limpar/Confirmar, drawer com "Fechar",
  templates do drawer, card "PNETT2 105 - 2026" (RF-04), script `julgar.js`, seção
  "Avaliações e indicações", conclusão com/sem grid
- [x] `php artisan view:cache` compila todas as views sem erro
- [x] `node --check public/js/julgar.js` — sintaxe OK
- [x] `php artisan test --compact --filter=JudgingAccessTest` — 5/6 (1 falha pré-existente,
  adaptação do teste na task 05)
- [ ] Checagem visual manual nos 3 protótipos (desktop + mobile) — **pendente de validação do
  usuário no navegador** (rodar seed de teste ou usar os dados do dev)
- [x] `npm run build` — não se aplica (assets estáticos em `public/`; nada de Vite para compilar)

## Notas / Débito técnico
- Pré-seleções são **voláteis** (client-side) por decisão da spec — recarregar a página perde.
- Campos "Inscrição:"/"Avaliação:" do protótipo **não** entram (fora de escopo).
- **Implementação (2026-08-27)**:
  - Controller recebeu `->with('modality.edition')` no carregamento de categorias (header e
    conclusão precisam do nome da edição — evita N+1).
  - Drawer usa a **rota existente** `admin.registration.file` para anexos (abre em nova aba).
    Essa rota exige `CheckAdmin:admin,comissao` — julgador **receberá 403** ao clicar no anexo.
    Registrado como débito **de rota/middleware** (liberar leitura para julgadores na
    `RegistrationController@file` ou rota dedicada).
  - "Avaliações e indicações" (RF-05) reaproveita a estrutura do partial da listagem da
    administração (`registration/partial/registration.blade.php`), em versão somente leitura
    para julgador, alimentada pelo eager loading da task 03.
  - Limite do grid: `remainingQuota` ≥ 0 vem do servidor; botão "Confirmar" só renderiza quando
    `selected === effectiveQuota` (RF-06) — reforçado também no store (task 03).
  - Estado do JS vive em um `WeakMap` por card (sem dados duplicados no DOM) e os hidden inputs
    são recriados no submit a partir das pré-seleções.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — `admin.julgar.index` (2 estados), drawers por tipo, `x-julgar.card`/`x-julgar.summary` em `views/components/julgar/`, `public/js/julgar.js`; 12/12 checks de render com dados reais; `view:cache`, `node --check` e suíte OK (5/6, falha pré-existente) | Cline |
