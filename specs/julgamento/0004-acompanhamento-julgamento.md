# SPEC — Acompanhamento do Julgamento (painel do admin)

> **Status**: `implementada` ·
> **Domínio**: `julgamento` · **Version**: `1.0.0` · **Atualizado**: `2026-10-07`

## 1. Contexto / Problema

A spec [0003-tela-julgamento](0003-tela-julgamento.md) entregou o fluxo do **julgador** em
`/julgar`: ele percorre as categorias e grava suas escolhas em `judge_selections`. Ficaram
**fora de escopo** dela, e são o objeto desta spec:

- o **painel administrativo das seleções** (consulta do andamento pela administração);
- **revisar** as seleções de um julgador depois de confirmadas;
- **apurar o resultado** e marcar as Inscrições como **"Agraciado"**.

Protótipo aprovado (referência visual): `prototipos/prototipos/acompanhar.jfif` (tela
"Acompanhamento"), com duas seções: **Acompanhamento da Comissão** (matriz julgador × categoria) e
**Acompanhamento dos votos por categoria** (barras "X de N votos").

### Persona
**Administrador**: usuário autenticado com perfil `admin` (`CheckAdmin::class.':admin'`).

### Dor resolvida
Hoje o admin não tem como saber quais julgadores concluíram cada categoria, não consegue liberar
um julgador para refazer o julgamento, nem tem um ponto no sistema para encerrar a votação e
registrar os agraciados.

## 2. Objetivos

- Exibir o **andamento por julgador**: matriz julgadores × categorias com "Finalizada"/"Aberta".
- Permitir **Resetar** todas as seleções de um julgador na edição, para ele votar de novo.
- Exibir a **apuração por categoria**: votos recebidos por Inscrição, em barras "X de N votos".
- **Finalizar a votação** da edição (definitivo), bloqueando novos votos.
- **Confirmar agraciados** por categoria, após a votação finalizada, alterando o status das
  Inscrições escolhidas para "Agraciado".
- Item **"Acompanhamento"** no menu do admin.

## 3. Não-escopo / Fora de escopo

- **Reabrir** uma votação finalizada (decisão de produto: finalizar é definitivo).
- **Desfazer** a confirmação de agraciados de uma categoria.
- Resetar **uma única categoria** de um julgador (o reset é sempre da edição inteira).
- Acesso de organizador/comissão à tela (somente admin nesta versão).
- Vincular julgadores a edições específicas: hoje `is_judge` é global (ver Notas).
- Notificar julgadores (e-mail/alerta) sobre reset ou encerramento.
- Exportar a apuração (PDF/planilha).

## 4. Regras de Domínio e Nomenclaturas aplicáveis

| ID | Regra | Uso nesta spec |
|----|-------|----------------|
| RDD-01 | Edições ativas (`is_registration_active = true`) | edição padrão da tela |
| RDD-02 | "Inscrições" = união `Registration` + `Nominee` | votos e agraciados por tipo |
| RDD-03 | Edições em julgamento (data atual > `judgment_date`) | edição padrão da tela |
| NM-02 | "julgador" (`is_judge = true`) | linhas da matriz e total de votos |
| NM-05 | Rótulos oficiais da Tela de Julgamento | rótulo das Inscrições |

## 5. Requisitos

### 5.1 Funcionais

| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-01 | `GET /admin/acompanhamento` (`admin.acompanhamento.index`), protegido por `CheckAdmin::class.':admin'`. Não-admin recebe o mesmo tratamento das demais rotas admin | Alta |
| RF-02 | **Edição exibida**: por padrão, a edição **ativa e em julgamento** (RDD-01 + RDD-03, mesmo filtro de `JudgingController::judgingEditionFilter`). Um **seletor de edição** permite consultar outras edições; edições que não estão em julgamento são exibidas **somente leitura** (sem Resetar/Finalizar/Confirmar). Sem nenhuma edição em julgamento → estado vazio com o seletor | Alta |
| RF-03 | **Matriz da comissão**: linhas = usuários com `is_judge = true` (ordem `name ASC`); colunas = categorias da edição com Inscrições qualificadas e `recipients_count > 0`, rotuladas pelo `acronym` (fallback: `title`), regulares antes das honoríficas e `id ASC` (mesma ordem do RF-02 da 0003) | Alta |
| RF-04 | Célula = **"Finalizada"** (verde) quando a cota restante do julgador na categoria é 0 (mesma regra de `remainingQuotaFor` da 0003); senão **"Aberta"** (laranja) | Alta |
| RF-05 | **Resetar** (por linha): após confirmação ("Apagar todas as seleções de {julgador} nesta edição?"), apaga **todas** as `judge_selections` do julgador cujas Inscrições pertençam a categorias **da edição exibida**. Seleções de outras edições não são afetadas. `POST /admin/acompanhamento/{edition}/julgadores/{user}/resetar` | Alta |
| RF-06 | **Resetar** fica desabilitado (e o POST é rejeitado) com a votação da edição finalizada | Alta |
| RF-07 | **Votos por categoria**: um card por categoria com `acronym`/título e as Inscrições que receberam ao menos 1 voto, ordenadas por votos DESC e `id ASC`, cada uma com barra **"X de N votos"**, em que **X** = nº de `judge_selections` da Inscrição e **N** = total de julgadores (`is_judge = true`) | Alta |
| RF-08 | **Finalizar votação**: `POST /admin/acompanhamento/{edition}/finalizar` grava `editions.voting_closed_at = now()`. Com células "Aberta" na matriz, a confirmação lista as pendências (julgador × categoria) antes de enviar. A ação é **definitiva**: não há reabertura | Alta |
| RF-09 | Com `voting_closed_at` preenchido, `/julgar` **não oferece** categorias dessa edição: o filtro de edição do `JudgingController` passa a exigir `voting_closed_at IS NULL`, e o POST de seleção dessa edição é rejeitado | Alta |
| RF-10 | **Confirmar agraciados** fica disponível **somente** com a votação finalizada, **por categoria**. A tela da categoria mostra em destaque as **10 Inscrições mais votadas** (com votos) e, abaixo, a **lista completa** das Inscrições qualificadas da categoria, com **busca** (rótulo NM-05, título/nome). **Nenhuma vem pré-marcada** | Alta |
| RF-11 | O admin marca de 1 até `recipients_count` Inscrições; ao atingir o limite, as demais ficam desabilitadas. `POST /admin/acompanhamento/categorias/{category}/agraciados` (FormRequest `ConfirmAwardeesRequest`) valida: votação da edição finalizada; categoria ainda não confirmada; 1 ≤ quantidade ≤ `recipients_count`; Inscrições pertencem à categoria e são qualificadas | Alta |
| RF-12 | Ao confirmar, em transação: as Inscrições escolhidas passam a `status = Agraciado` (`RegistrationStatusEnum::Agraciado`) e `categories.awardees_confirmed_at = now()`. A categoria fica **travada** (somente leitura, mostrando os agraciados) | Alta |
| RF-13 | **Menu**: item "Acompanhamento" no `MenuBuilder::getAdminMenu()`, seção "Administração Prêmios", logo após "Inscrições", rota `admin.acompanhamento.index` | Média |
| RF-14 | **Atualização automática**: a matriz e os votos se atualizam a cada **10 segundos** sem recarregar a página (busca da própria página e troca das regiões `[data-live]`), com "Atualizado às HH:MM:SS". Pausa com a aba oculta ou com um modal de confirmação aberto, preserva a rolagem horizontal da matriz e para se a sessão expirar | Média |
| RF-15 | **Classificação dos agraciados**: a tela informa "Quantidade a ser agraciada: {recipients_count}" e um painel "Classificação" com as posições 1º lugar até o `recipients_count`. A ordem de seleção define a posição, reordenável pelas setas; ao confirmar, cada Inscrição grava `award_position` conforme a ordem enviada | Alta |
| RF-16 | **Agraciados da edição**: `GET /admin/acompanhamento/{edition}/agraciados` (`admin.acompanhamento.edition-awardees`, somente com votação finalizada) lista, por categoria (ordem RF-03), os agraciados em ordem de colocação, com categorias pendentes indicadas e botão Imprimir. Ao confirmar a última categoria pendente, o admin é levado a essa tela; a tela principal mostra o atalho quando todas estão confirmadas | Alta |

### 5.2 Não-funcionais

- Layout admin existente (`admin.content`), Tailwind v4 e componentes KTUI já usados no projeto.
  A matriz rola na horizontal em telas estreitas, com a coluna de julgadores fixa.
- Confirmações (Resetar, Finalizar, Confirmar agraciados) em modal, com o texto da consequência.
- Performance: contagens de votos e cotas agregadas por query (`withCount`/`groupBy`), sem N+1
  na matriz (julgadores × categorias).
- Todas as ações de escrita em transação e com flash de sucesso/erro.

## 6. Modelo de Dados / Contratos

### 6.1 Colunas novas

| Tabela | Coluna | Tipo | Observação |
|--------|--------|------|------------|
| `editions` | `voting_closed_at` | `timestamp` nullable | preenchida em "Finalizar votação" |
| `categories` | `awardees_confirmed_at` | `timestamp` nullable | preenchida em "Confirmar agraciados" |
| `registrations` / `nominees` | `award_position` | `unsignedTinyInteger` nullable | posição do agraciado (1 = 1º lugar), gravada em "Confirmar agraciados" |

Casts `datetime` nos models `Edition` e `Category`; incluir nos `$fillable` somente se forem
atribuídas em massa.

### 6.2 Helpers

- `Edition::isVotingClosed(): bool` · `Category::areAwardeesConfirmed(): bool`.
- Contagem de votos por Inscrição via `judgeSelections()` (`MorphMany`), já existente em
  `Registration`/`Nominee`.

### 6.3 Contratos

- `POST .../julgadores/{user}/resetar`: sem payload; `{user}` precisa ter `is_judge = true`.
- `POST .../finalizar`: sem payload.
- `POST .../categorias/{category}/agraciados` (`ConfirmAwardeesRequest`): `inscriptions`
  required, array, `min:1`, `max:recipients_count`; cada item é o `id` (inteiro, distinto) da
  Inscrição. O tipo vem da categoria (honorífica → `Nominee`; regular → `Registration`), então
  não é enviado.

## 7. Critérios de Aceite (BDD)

- **DADO QUE** um admin abre "Acompanhamento" no menu
  **ENTÃO** vê a edição em julgamento, a matriz julgadores × categorias e os cards de votos.
- **DADO QUE** um não-admin acessa qualquer rota `admin.acompanhamento.*`
  **ENTÃO** o acesso é negado como nas demais rotas admin.
- **DADO QUE** um julgador completou a cota de uma categoria
  **ENTÃO** a célula correspondente mostra "Finalizada"; nas demais, "Aberta".
- **DADO QUE** o admin confirma "Resetar" de um julgador
  **ENTÃO** todas as seleções dele **na edição exibida** são apagadas, as células voltam a
  "Aberta", os votos dos cards diminuem e o julgador volta a ver a primeira categoria em `/julgar`.
- **DADO QUE** o julgador tem seleções em outra edição
  **QUANDO** é resetado nesta edição
  **ENTÃO** as seleções da outra edição permanecem.
- **DADO QUE** 3 de 4 julgadores escolheram uma Inscrição
  **ENTÃO** a barra dela mostra "3 de 4 votos".
- **DADO QUE** há células "Aberta" e o admin clica em "Finalizar votação"
  **ENTÃO** a confirmação lista as pendências; **QUANDO** confirma
  **ENTÃO** `voting_closed_at` é gravado, Resetar fica desabilitado e "Confirmar agraciados" é
  liberado.
- **DADO QUE** a votação da edição está finalizada
  **ENTÃO** `/julgar` não apresenta categorias dessa edição e um POST de seleção é rejeitado.
- **DADO QUE** a votação está finalizada e o admin abre os agraciados de uma categoria
  **ENTÃO** vê as 10 mais votadas em destaque e a lista completa com busca, **sem** nada marcado.
- **DADO QUE** o admin marcou `recipients_count` Inscrições
  **ENTÃO** as demais ficam desabilitadas.
- **DADO QUE** o admin confirma os agraciados
  **ENTÃO** as Inscrições ficam com status "Agraciado", a categoria fica travada e exibe os
  agraciados.
- **DADO QUE** o POST de agraciados chega com votação aberta, categoria já confirmada, 0 ou mais
  de `recipients_count` itens, ou Inscrição de outra categoria/não qualificada
  **ENTÃO** é rejeitado com erro de validação e **nada** é gravado.
- **DADO QUE** o admin seleciona uma edição que não está em julgamento
  **ENTÃO** a tela é exibida somente leitura, sem Resetar/Finalizar/Confirmar.

## 8. Arquivos / Camadas afetadas

- `database/migrations/*_add_voting_closed_at_to_editions_table.php` ·
  `database/migrations/*_add_awardees_confirmed_at_to_categories_table.php` (novas)
- `app/Models/{Edition,Category}.php` (casts + helpers)
- `app/Http/Controllers/JudgingFollowUpController.php` (novo: index, resetar, finalizar,
  agraciados) · `app/Http/Requests/ConfirmAwardeesRequest.php` (novo)
- `app/Http/Controllers/JudgingController.php` (filtro `voting_closed_at IS NULL`, RF-09)
- `routes/web.php` (grupo `admin`, middleware `CheckAdmin::class.':admin'`)
- `app/Services/MenuBuilder.php` (RF-13)
- `resources/views/admin/acompanhamento/*` (novas)
- `tests/Feature/JudgingFollowUpTest.php` (novo) · `tests/Feature/JudgeSelectionTest.php`
  (caso de votação finalizada)

## 9. Tasks

- [x] [01-migrations-models.md](tasks/0004/01-migrations-models.md) (`database`) — done 2026-10-07
- [x] [02-controller-rotas-menu.md](tasks/0004/02-controller-rotas-menu.md) (`laravel-backend`) — done 2026-10-07
- [x] [03-view-acompanhamento.md](tasks/0004/03-view-acompanhamento.md) (`ui-ux` + `blade-frontend`) — done 2026-10-07
- [x] [04-agraciados.md](tasks/0004/04-agraciados.md) (`laravel-backend` + `blade-frontend`) — done 2026-10-07
- [x] [05-tests-acompanhamento.md](tasks/0004/05-tests-acompanhamento.md) (`qa`) — done 2026-10-07

## 10. Histórico

| Data | Ação | Autor |
|------|------|-------|
| 2026-10-07 | v1.0.0 — Criação a partir do protótipo `acompanhar.jfif` e de levantamento interativo com o usuário: N = total de julgadores; Resetar apaga todas as seleções do julgador na edição; Finalizar votação é definitivo, com aviso de pendências, bloqueia `/julgar` e libera agraciados; agraciados por categoria com top 10 em destaque + lista completa com busca, sem pré-marcação e limite `recipients_count`; acesso só admin; menu em "Administração Prêmios"; edição em julgamento por padrão + seletor somente leitura | Claude |
| 2026-10-07 | **Implementação concluída (tasks 01–05)**: migrations `voting_closed_at`/`awardees_confirmed_at`, `JudgingFollowUpController` + `ConfirmAwardeesRequest`, bloqueio do `/julgar` após finalizar, menu, views `admin/acompanhamento/*` + `public/js/acompanhamento.js`. POST de agraciados envia só os ids (tipo derivado da categoria). Caso de votação finalizada do `/julgar` coberto em `JudgingFollowUpTest`. Testes: 15 novos + 10 da 0003 + 6 de acesso passando (31); 12 falhas pré-existentes em `AiEvaluationWorkflowTest` | Claude |
| 2026-10-07 | RF-14 incluído a pedido do usuário (atualização automática a cada 10 s). Correção no layout `admin/content` (`min-w-0` nos containers flex), que alargava a página com conteúdo largo e causava rolagem horizontal em telas estreitas | Claude |
| 2026-10-08 | RF-15 e RF-16 incluídos a pedido do usuário: classificação (1º, 2º… lugar) com coluna `award_position` e tela final com os agraciados da edição. Agraciado confirmado antes da classificação aparece como "Agraciado", sem posição | Claude |

> **Notas / riscos**
> - `is_judge` é **global**: N (total de julgadores) e as linhas da matriz consideram todos os
>   julgadores do sistema, não só os de uma edição. Se um julgador for desativado após votar, seus
>   votos continuam contando em X mas ele sai de N. Avaliar vínculo julgador ⇄ edição numa spec
>   futura.
> - O protótipo traz "MEAUR" e "MEARU" (provável erro de digitação). As siglas vêm do campo
>   `acronym` de cada categoria, não do protótipo.
> - Em `RF-09`, o bloqueio por `voting_closed_at` complementa o filtro de datas (RDD-03) da
>   0003; os testes da 0003 precisam continuar passando.
