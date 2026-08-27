# SPEC — Acesso do Julgador (área restrita + menu "Julgar")

> **Status**: `aprovada` ·
> **Domínio**: `acessos` · **Version**: `1.4.0` · **Atualizado**: `2026-08-27`

## 1. Contexto / Problema

A plataforma **sysamazonia** premia candidatos/inscrições que passam por um processo de
**julgamento**. Esse trabalho é feito por **julgadores**, que hoje não possuem uma área própria:

- O model `User` já possui o flag `is_judge` e o helper `isJudge()`, mas **não há nenhuma tela
  restrita** para o julgador.
- O `MenuBuilder` já possui `getJuradoMenu()` (heading "Julgamento") **órfão**: o método
  `getMenuStructure()` **não o utiliza**, então um usuário julgador autenticado **não vê menu
  nenhum**.
- Criar `getJulgadorMenu()` no `MenuBuilder`: menu com nome **"Julgar"** que deve levar para a
  view criada do painel do julgador.
- Todo o controle de acesso atual usa `CheckAdmin` (baseado em papel `admin`); não há gating por
  `is_judge`.

### Persona
**Julgador**: usuário autenticado com `is_judge = true`. O julgador julgará **todas as
Inscrições** (RDD-02), de edições que são **ativas** (RDD-01) **e** **em julgamento** (RDD-03).

### Dor resolvida
Dar ao julgador uma área própria e segura (rota + menu "Julgar"), sem expor o back-office
`admin`. Caso o usuário **não tenha acesso à rota** `/julgar`, o sistema deve fazer **logout** e
levar o usuário à **tela de login** exibindo a mensagem **"Perfil sem Acesso!"**.

## 2. Objetivos

- Exibir o menu **"Julgar"** na sidebar apenas para usuários com `is_judge = true`, levando à
  view do painel.
- Listar **todas as inscrições** de edições ativas em julgamento, onde o `Registration` esteja
  com status **"Avaliado"** e o `Nominee` com status **"Habilitado"** (RDD-02).
- Garantir que o julgador tenha acesso a **todas as inscrições** de **edições ativas** (RDD-01)
  **e em julgamento** (RDD-03).
- Bloquear o acesso ao painel para quem não for julgador, direcionando ao login com a mensagem
  **"Perfil sem Acesso!"**.

## 3. Não-escopo / Fora de escopo

- **Emitir nota/parecer/"julgar" em si** (tela de avaliação/nota) — recurso futuro.
- CRUD de quem é julgador (atribuição de `is_judge`) — permanece no CRUD de usuários
  (`admin.users`).
- Papel `Role::JURADO` do model `Role` — existe a constante porém **não é semeada**; aqui o
  julgador é definido por `is_judge`. (débito técnico registrado)
- ACL/ABAC genérico multi-papéis — fora do escopo desta área restrita.
- Limitar categorias por vínculo do julgador (`evaluatorCategories`) — o julgador vê **todas**
  as inscrições de edições ativas e em julgamento (ver Regras de Domínio).

## 4. Regras de Domínio

> As **Regras de Domínio** estão centralizadas em `.clinerules/regras-dominio.md` e são **sempre
> levadas em conta**. Nesta spec aplicam-se as seguintes:

| ID | Regra | Referência |
|----|-------|------------|
| RDD-01 | **Edições Ativas** — `is_registration_active = true` | [.clinerules/regras-dominio.md](../../.clinerules/regras-dominio.md) |
| RDD-02 | **Inscrições** = união de `Registration` (regular) + `Nominee` (honorífica); "inscrições honoríficas" = somente `Nominee` | [.clinerules/regras-dominio.md](../../.clinerules/regras-dominio.md) |
| RDD-03 | **Edições em Julgamento** — data atual **>** `Edition->judgment_date` | [.clinerules/regras-dominio.md](../../.clinerules/regras-dominio.md) |

## 5. Requisitos

### 5.1 Funcionais

| ID | Requisito | Prioridade |
|----|-----------|------------|
| FR-01 | Usuário autenticado com `is_judge = true` vê o menu **"Julgar"** na sidebar (via `getJulgadorMenu()`) | Alta |
| FR-02 | Existe rota autenticada do painel do julgador (`GET /julgar` → `panel.julgar.index`) | Alta |
| FR-03 | O painel lista **todas as Inscrições** das **edições ativas** (RDD-01) **e em julgamento** (RDD-03), mostrando `Registration` com status **"Avaliado"** e `Nominee` com status **"Habilitado"** (RDD-02) | Alta |
| FR-04 | Usuário sem `is_judge = true` que acessa `GET /julgar` → faz **logout** e volta ao login com mensagem **"Perfil sem Acesso!"** | Alta |
| FR-05 | Usuário sem `is_judge = true` **não vê** a entrada "Julgar" no menu | Alta |

### 5.2 Não-funcionais

- Segurança: o painel é acessível apenas a autenticados com `is_judge = true` (middleware dedicado).
- Desempenho: evitar N+1 (eager loading) ao listar Inscrições via relação com a edição.
- Reuso: reutilizar componentes existentes (`components/pages/*`, `components/messages/*`).
- Não usar `CheckAdmin:admin` para o painel do julgador; usar checagem por `is_judge`.

## 6. Modelo de Dados / Filtros

### `users` (App\Models\User)

| Campo | Tipo | Relevância |
|-------|------|-----------|
| is_judge | bool nullable | Define se o usuário é julgador (`isJudge()`) |

### Modelos envolvidos nos filtros (somente leitura — sem relacionamento novo)

| Modelo | Campos usados | Observação |
|--------|---------------|------------|
| `Edition` | `is_registration_active` (bool), `judgment_date` (date) | `scopeActive()` já filtra por `is_registration_active = true` |
| `Registration` | `status` (int = 4 "Avaliado"), `scopeStatus()` | Inscrição **regular** |
| `Nominee` | `status` (int = 3 "Habilitado"), `scopeStatus()` | Inscrição **honorífica** |

> **Não há relacionamento novo.** As Inscrições **não têm vínculo direto com as Edições**; a
> relação é indireta, via **`Category → Modality → Edition`**. A consulta deve atravessar essa
> cadeia (ex.: `whereHas`) para aplicar os filtros de edição ativa (RDD-01) e em julgamento
> (RDD-03), bem como os filtros de status de cada Inscrição (FR-03).

## 7. Critérios de Aceite (BDD)

- **DADO** um usuário autenticado com `is_judge = true`
  **QUANDO** visualiza a sidebar
  **ENTÃO** vê o menu **"Julgar"** e **não vê** os itens `admin`; ao clicar, vai para a view do painel.

- **DADO** um usuário autenticado com `is_judge = true`
  **QUANDO** acessa `GET /julgar`
  **ENTÃO** abre o painel listando **todas as Inscrições** das **edições ativas** (RDD-01) **e em
  julgamento** (RDD-03), com `Registration` em status **"Avaliado"** e `Nominee` em status **"Habilitado"** (RDD-02).

- **DADO** um usuário autenticado com `is_judge = false` (ou sem flag)
  **QUANDO** acessa `GET /julgar`
  **ENTÃO** o sistema faz **logout**, redireciona para o login e exibe **"Perfil sem Acesso!"**.

- **DADO** um visitante não autenticado
  **QUANDO** acessa `GET /julgar`
  **ENTÃO** é redirecionado ao login (`auth`).

- **DADO** um usuário autenticado com `is_judge = false`
  **QUANDO** visualiza a sidebar
  **ENTÃO** **não** vê a entrada "Julgar".

## 8. Arquivos / Camadas afetadas (previsão)

- `app/Services/MenuBuilder.php` (criar `getJulgadorMenu()`/ativar para `isJudge()`; item "Julgar"
  → `panel.julgar.index`)
- `app/Http/Controllers/JudgingController.php` (novo; `index` — filtro RDD-01 + RDD-03, e filtros
  de status: Registration "Avaliado" / Nominee "Habilitado")
- `app/Http/Middleware/CheckJudge.php` (novo; `is_judge=false` → logout + login com mensagem)
- `routes/web.php` (rota `panel.julgar.index`, fora de `admin`)
- `resources/views/admin/julgar/index.blade.php` (novo)
- `tests/Feature/JudgingAccessTest.php` (novo)

## 9. Tasks

- [ ] [01-menu-jul.md](tasks/01-menu-jul.md)
- [ ] [02-controller-painel.md](tasks/02-controller-painel.md)
- [ ] [03-middleware-checkjurado.md](tasks/03-middleware-checkjurado.md)
- [ ] [04-routes-painel.md](tasks/04-routes-painel.md)
- [ ] [05-view-painel.md](tasks/05-view-painel.md)
- [ ] [06-tests-acesso.md](tasks/06-tests-acesso.md)

## 10. Histórico

| Data | Ação | Autor |
|------|------|-------|
| 2026-08-27 | Criação (escopo: área restrita + menu "Julgar", por `is_judge`) | Cline |
| 2026-08-27 | v1.1.0 — refino do escopo: julgador julga **todas** as categorias de edições ativas (RDD-01); menu passa a `getJulgadorMenu()`; Inscrições = Registration + Nominee (RDD-02); sem acesso → logout + login com "Perfil sem Acesso!" (FR-04/FR-05) | Cline |
| 2026-08-27 | v1.2.0 — Regras de Domínio centralizadas em `.clinerules/regras-dominio.md` (globais, sempre levadas em conta); IDs renomeadas para `RDD-xx` | Cline |
| 2026-08-27 | v1.3.0 — Regras de Domínio atualizadas: RDD-01 apenas `is_registration_active`; nova RDD-03 "Edições em Julgamento" (data > `judgment_date`); RDD-02 sem status (unio de `Registration`+`Nominee`; honoríficas = só `Nominee`). Spec/tasks sincronizadas | Cline |
| 2026-08-27 | v1.4.0 — Julgador julga **todas as Inscrições** (não mais categorias); listagem com `Registration` status "Avaliado" e `Nominee` status "Habilitado"; não-escopo/objetivos/FR-03/BDD/modelos atualizados; nota de ausência de vínculo direto inscrição↔edição (via `Category → Modality → Edition`) | Cline |

> **Notas de débito técnico**
> - `MenuBuilder::getJuradoMenu()` existia órfão; reaproveitar/renomear para `getJulgadorMenu()`.
> - `Role::JURADO` existe como constante mas não é seedada pelo `RoleSeeder`; **não** é usado
>   como critério de acesso (julgador = `is_judge`).
