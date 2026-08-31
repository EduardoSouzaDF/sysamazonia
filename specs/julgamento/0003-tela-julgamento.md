# SPEC — Tela de Julgamento (seleção do julgador para a premiação)

> **Status**: `aprovada` ·
> **Domínio**: `julgamento` · **Version**: `1.0.0` · **Atualizado**: `2026-08-27`

## 1. Contexto / Problema

A spec [0002-acesso-julgador](../acessos/0002-acesso-julgador.md) deu ao **julgador** (NM-02) a
área restrita com o menu **"Julgar"** (NM-01 → `panel.julgar.index`), porém a view
`resources/views/admin/julgar/index.blade.php` está **em branco** — a task
[05-view-painel](../acessos/tasks/05-view-painel.md) entregou somente o arquivo.

Esta spec define a **tela de julgamento** que complementa a task 05: o julgador percorre as
categorias — **uma por vez** — visualiza as Inscrições (RDD-02) e **seleciona** aquelas que indica
para a **premiação**, respeitando a cota da categoria (`Category->recipients_count`). Protótipos
aprovados (referência visual): `specs/protitpos/spec-02/`

- `Registrations.png` — categoria regular: grid de cards + drawer com Dados de Inscrição/Anexos;
- `Registrations-02.png` — idem + seção **"Avaliações e indicações"** (leitura);
- `Nominees.png` — categoria honorífica: Dados da Indicação.

### Persona
**Julgador**: usuário autenticado com `is_judge = true` (NM-02).

### Dor resolvida
Sem esta tela o julgamento da premiação não acontece: o julgador não tem como ver as Inscrições
nem registrar suas escolhas por categoria.

## 2. Objetivos

- Transformar `/julgar` em um **fluxo guiado categoria-a-categoria** (wizard): o painel exibe
  **apenas 1 categoria elegível por vez**.
- Exibir as Inscrições qualificadas da categoria num **grid de cards** — as **20 melhores** para
  `Registration`; **todas** para `Nominee` — com rótulo `{sigla} {id} - {ano do julgamento}`.
- Abrir um **Drawer** (Shoelace `sl-drawer`) com os dados completos da Inscrição e as
  **"Avaliações e indicações"** em leitura (somente `Registration`).
- Permitir **pré-seleção** das Inscrições no cliente, com **cota efetiva**
  `min(recipients_count, disponíveis)`; o botão **"Confirmar"** de submissão só aparece com a cota
  completa.
- Gravar as escolhas no novo modelo **`JudgeSelection`** (tabela `judge_selections`) via
  `POST /julgar` (`panel.julgar.store`), avançando para a próxima categoria após o redirect.
- Exibir **tela de conclusão** (resumo somente-leitura por categoria) quando não houver mais
  categorias elegíveis.

## 3. Não-escopo / Fora de escopo

- **Lançar nota/parecer do julgador** — os campos "Inscrição:"/"Avaliação:" do protótipo não são
  implementados nesta versão; a seção "Avaliações e indicações" do drawer é **somente-leitura**
  (alimentada pelos models existentes `Opinion`/`Score`/`Indication`).
- **Revisar/editar seleções** após confirmar a categoria; navegação livre entre categorias.
- Alterar **status** das Inscrições (ex.: marcar "Agraciado") ou apurar resultado final da edição.
- Paginação/busca no grid (máx. 20 cards para `Registration`; `Nominee` exibe todas).
- Fluxo de avaliação para `Nominee` (não existe — `getTextEvaluationAvg()` = "Sem Avaliação").
- Painel administrativo das seleções (consulta pela comissão/admin).

## 4. Regras de Domínio e Nomenclaturas aplicáveis

| ID | Regra | Uso nesta spec |
|----|-------|----------------|
| RDD-01 | Edições ativas (`is_registration_active = true`) | filtro das categorias/Inscrições elegíveis |
| RDD-02 | "Inscrições" = união `Registration` + `Nominee` | cards/drawer por tipo |
| RDD-03 | Edições em julgamento (data atual > `judgment_date`) | filtro das categorias elegíveis |
| NM-01/NM-02 | "Julgar" / "julgador" | menu, rota e persona |
| NM-05 | Rótulos oficiais da Tela de Julgamento | cards, listas, botões, conclusão |

## 5. Requisitos

### 5.1 Funcionais

| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-01 | `GET /julgar` calcula as **categorias elegíveis**: com Inscrições qualificadas (`Registration` "Avaliado" / `Nominee` "Habilitado") de edições **ativas** (RDD-01) e **em julgamento** (RDD-03), `recipients_count > 0`, e nas quais o julgador **ainda não completou a cota** (`!isFullyJudgedBy`) | Alta |
| RF-02 | Ordenação das categorias: **primeiro as com inscrições regulares**, depois as **honoríficas** (só `Nominee`); dentro de cada grupo `categories.id ASC`. O painel exibe **somente a primeira** | Alta |
| RF-03 | Grid da categoria: para **`Registration`**, as **20 melhores** ordenadas por (1) quantidade de indicações recebidas (`indications.registration_id`) DESC, (2) `evaluation_avg` DESC (nulas por último), desempate `id ASC`; para **`Nominee`**, **todas** as qualificadas (`id ASC`) | Alta |
| RF-04 | Card da Inscrição exibe `{acronym da categoria} {id da inscrição} - {ano de judgment_date da edição}` (ex.: "PSD 3232 - 2026") | Alta |
| RF-05 | Clique no card abre **Drawer** (Shoelace `sl-drawer`, à direita): `Registration` → protocolo/título/candidato, Dados de Inscrição (Resumo, Objetivos, Desenvolvimento, Conclusão), **Anexos** (links de `files`) e **"Avaliações e indicações"** em leitura (avaliação final `evaluation_avg`, resultado via `getTextEvaluationAvg()` e opiniões/notas por avaliador/indicador); `Nominee` → "Nome do Indicado", Estado, Contato, Apresentação, Atividades, Justificativa e anexos — **sem** avaliações | Alta |
| RF-06 | **Pré-seleção** client-side: "Confirmar" no drawer adiciona o card à listagem de pré-seleções (rótulo NM-05), limitada à **cota efetiva** `min(recipients_count, nº de cards)`; card já selecionado não é adicionado novamente | Alta |
| RF-07 | Listagem de pré-seleções com contador "X de Y" + botões **"Limpar"** (zera pré-seleções) e **"Confirmar"** — este **renderizado somente com a cota efetiva completa** | Alta |
| RF-08 | Drawer tem **"Fechar"** (no lugar de "Voltar/Limpar" do protótipo, nesta versão) | Média |
| RF-09 | `POST /julgar` (`panel.julgar.store`, FormRequest `JudgeSelectionRequest`) valida: `category_id` é a **categoria atual elegível**; quantidade de inscrições **igual à cota efetiva**; todas pertencem à categoria e são qualificadas; sem duplicidade (unique por usuário). Grava `judge_selections` em transação e redireciona para `panel.julgar.index` com flash de sucesso | Alta |
| RF-10 | Após gravar, a categoria sai do filtro e o painel mostra a **próxima** categoria (ou a conclusão) | Alta |
| RF-11 | **Conclusão**: sem categorias elegíveis e existindo seleções → "Julgamento concluído!" + resumo somente-leitura das seleções agrupadas por categoria (cards NM-05); sem categorias e sem seleções → estado vazio "Nenhuma categoria disponível para julgamento." | Média |
| RF-12 | Acesso mantém o gating da spec 0002 (`CheckJudge` por FQCN; não-julgador → logout + "Perfil sem Acesso!") em **GET e POST** | Alta |

### 5.2 Não-funcionais

- Tailwind v4 + layout admin existente; grid responsivo (2 colunas no desktop conforme protótipo,
  empilhando no mobile).
- Shoelace via **CDN `@shoelace-style/shoelace@2.20.1`** (tema light + autoloader), padrão já
  usado em `public/js/forms.edition.js` — sem instalar pacote novo.
- Acessibilidade: foco gerenciado no drawer, cards acionáveis por teclado, contador "X de Y" com
  `aria-live`; suporte `dark:` se o projeto usar.
- Performance: eager loading (`candidate`, `category.modality.edition`, `files`,
  `opinions.scores.evaluationCriterion`, `indications`) — sem N+1; sem paginação (máx. 20 cards
  por categoria regular).
- Pré-seleções são **voláteis** (client-side): recarregar a página as perde (comportamento aceito).

## 6. Modelo de Dados / Contratos

### 6.1 Tabela `judge_selections` (nova)

| Coluna | Tipo | Observação |
|--------|------|------------|
| id | bigint PK | |
| user_id | FK → users (cascade) | julgador |
| inscription_type | string | morph: `registration` \| `nominee` |
| inscription_id | unsignedBigInteger | |
| timestamps | | |

- **Unique** `judge_selections_user_inscription_unique` (`user_id`, `inscription_type`,
  `inscription_id`) — 1 seleção por inscrição por julgador.
- **Index** (`inscription_type`, `inscription_id`).
- **Morph map** em `AppServiceProvider`:
  `Relation::enforceMorphMap(['registration' => Registration::class, 'nominee' => Nominee::class])`
  (não há morphs pré-existentes no `app/`).

### 6.2 Model `JudgeSelection`

- `$fillable`: `user_id`, `inscription_type`, `inscription_id` (nunca `$guarded = []`).
- `user(): BelongsTo` · `inscription(): MorphTo` · `scopeByUser($query, $userId)`.

### 6.3 Relações/helpers novos

- `User::judgeSelections(): HasMany` · `User::hasSelected(Model $inscription): bool`.
- `Registration::judgeSelections()` / `Nominee::judgeSelections(): MorphMany`.
- `Category::judgeSelectionsBy(User $user): int` · `Category::remainingQuotaFor(User $user): int`
  (`max(0, recipients_count − selecionadas)`) · `Category::isFullyJudgedBy(User $user): bool`.
- **Cota efetiva** = `min(recipients_count, nº de inscrições elegíveis)` — calculada no
  controller (depende dos filtros RDD).

### 6.4 Contrato do POST (`JudgeSelectionRequest`)

- `category_id`: required, integer, exists.
- `inscriptions`: required, array, `size = cota efetiva`; cada item com `type` ∈
  {`registration`, `nominee`} + `id`. Regras de negócio (categoria correta, qualificação,
  duplicidade) revalidadas no controller contra a categoria elegível atual.

## 7. Critérios de Aceite (BDD)

- **DADO QUE** um julgador acessa `/julgar`
  **QUANDO** existirem categorias elegíveis
  **ENTÃO** o painel mostra **1 categoria** (regulares primeiro, `id ASC`), o grid de cards e o
  contador "X de Y".
- **DADO QUE** a categoria é regular
  **ENTÃO** o grid traz no máximo **20** cards ordenados por indicações DESC, `evaluation_avg`
  DESC (nulas por último), `id ASC`, rotulados "PSD 3232 - 2026".
- **DADO QUE** a categoria é honorífica
  **ENTÃO** o grid traz **todas** as `Nominee` "Habilitado".
- **DADO QUE** o julgador clica num card
  **ENTÃO** abre-se o Drawer com o conteúdo RF-05 — "Avaliações e indicações" somente para
  `Registration`.
- **DADO QUE** a pré-seleção atinge a cota efetiva
  **ENTÃO** o botão "Confirmar" da listagem aparece; **QUANDO** submete
  **ENTÃO** as seleções são gravadas em `judge_selections`, o flash de sucesso é exibido e a
  próxima categoria (ou a conclusão) é apresentada.
- **DADO QUE** o POST chega com quantidade ≠ cota efetiva, inscrição de outra categoria, inválida,
  duplicada ou categoria já julgada
  **ENTÃO** é rejeitado com erro de validação e **nada** é gravado.
- **DADO QUE** o julgador recarrega `/julgar` antes de confirmar
  **ENTÃO** as pré-seleções voláteis são perdidas (aceito) e a mesma categoria é reapresentada.
- **DADO QUE** a categoria tem menos elegíveis que `recipients_count`
  **ENTÃO** a cota efetiva é `min(cota, elegíveis)` e o fluxo completa ao selecionar todas.
- **DADO QUE** todas as categorias foram julgadas
  **ENTÃO** `/julgar` exibe "Julgamento concluído!" com o resumo somente-leitura por categoria.
- **DADO QUE** não-julgador acessa GET ou POST `/julgar`
  **ENTÃO** logout + login com "Perfil sem Acesso!" (RF-12).

## 8. Arquivos / Camadas afetadas

- `database/migrations/*_create_judge_selections_table.php` (nova) ·
  `app/Models/JudgeSelection.php` (novo)
- `app/Models/{User,Registration,Nominee,Category}.php` (relações/helpers) ·
  `app/Providers/AppServiceProvider.php` (morph map)
- `app/Http/Controllers/JudgingController.php` (wizard + store) ·
  `app/Http/Requests/JudgeSelectionRequest.php` (novo) · `routes/web.php` (POST)
- `resources/views/admin/julgar/index.blade.php` (hoje vazia — implementar) +
  `resources/views/admin/julgar/partial/*` (novos)
- `tests/Feature/JudgeSelectionTest.php` (novo) · `tests/Feature/JudgingAccessTest.php` (adaptar)
- `.clinerules/agents/{ui-ux,blade-frontend}/AGENTS.md` · `.clinerules/nomenclaturas.md` (NM-05) ·
  `docs/sdd/03-agentes-e-skills.md`

## 9. Tasks

- [x] [01-migration-model.md](tasks/01-migration-model.md) (`database`) — done 2026-08-27
- [x] [02-relacoes-cota.md](tasks/02-relacoes-cota.md) (`laravel-backend`) — done 2026-08-27
- [x] [03-controller-rotas.md](tasks/03-controller-rotas.md) (`laravel-backend`) — done 2026-08-27
- [x] [04-view-julgamento.md](tasks/04-view-julgamento.md) (`ui-ux` + `blade-frontend`) — done 2026-08-27
- [x] [05-tests-julgamento.md](tasks/05-tests-julgamento.md) (`qa`) — done 2026-08-27

## 10. Histórico

| Data | Ação | Autor |
|------|------|-------|
| 2026-08-27 | v1.0.0 — Criação via **levantamento interativo de requisitos** com o usuário: modelo `JudgeSelection` (nome aprovado em prompt), wizard categoria-a-categoria (regulares → honoríficas), cota `recipients_count` com cota efetiva `min(cota, disponíveis)` e confirmação só com cota completa, grid top-20 (indicações + `evaluation_avg`) / todas as `Nominee`, drawer Shoelace ("Confirmar"/"Fechar"), conclusão com resumo; protótipos `specs/protitpos/spec-02/*.png` como referência visual | Cline |
| 2026-08-27 | **Implementação concluída (tasks 01–05)**: migration/model/morph map → relações e helpers de cota → rotas GET/POST + `JudgeSelectionRequest` + wizard no `JudgingController` → view (index, drawers, componentes `x-julgar.*`, `public/js/julgar.js`) → testes (`JudgeSelectionTest` 11 + `JudgingAccessTest` adaptado 5 = 16/16 no filtro; suíte completa 17/18, única falha pré-existente no scaffold `ExampleTest`) | Cline |

> **Notas de débito técnico**
> - O **FR-03** da spec 0002 (listagem única de todas as Inscrições) é **substituído** pelo wizard
>   desta spec; `JudgingAccessTest` será adaptado na task 05.
> - Stub pré-existente `Nominee::indications()` (retorna `Collection` vazia com assinatura
>   `HasMany`) é bug latente — **não reaproveitar** (correção pertence à spec de Inscrições).
> - Campos "Inscrição:"/"Avaliação:" do protótipo (entrada de nota do julgador) ficam para versão
>   futura; a seção "Avaliações e indicações" é somente-leitura.
> - Se `recipients_count` for aumentado após a categoria concluída, ela volta a ficar elegível com
>   o saldo restante (comportamento derivado de RF-01).
> - Pré-seleções client-side são voláteis (sem persistência parcial) — decisão de produto aceita.


