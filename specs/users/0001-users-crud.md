# SPEC — CRUD de Usuários (Users)

> **Status**: `implementado` (baseline / exemplo canônico)
> **Domínio**: `users` · **Version**: `1.0.0` · **Atualizado**: `2026-08-26`

## 1. Contexto / Problema
O painel administrativo (`admin`) precisa gerenciar os usuários da plataforma **sysamazonia**:
criar, listar, editar e remover usuários, atribuir papéis (`roles`), dados complementares
(`UserExtraData`) e vínculos de indicador/avaliador em categorias. Este documento é a **spec de
referência** para novos CRUDs e para avaliar refatorações (ex.: extração de Form Requests).

## 2. Objetivos
- Permitir CRUD de usuários em `admin/users`.
- Atrelar papéis (`Role` via pivot `user_role`), dados extras (1:1) e papéis de indicador/avaliador.
- Busca e paginação.

## 3. Não-escopo
- Cadastro público (`register`) e recuperação de senha — cobertos por Fortify/AuthController.
- Funcionalidade "login as" — existe mas não faz parte deste CRUD base.

## 4. Requisitos

### 4.1 Funcionais
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-01 | Listar usuários com paginação (15) | Alta |
| RF-02 | Buscar por nome, e-mail e nome de papel | Alta |
| RF-03 | Criar usuário com dados pessoais, extras e papéis | Alta |
| RF-04 | Editar usuário (dados, papéis, indicadores, avaliadores) | Alta |
| RF-05 | Excluir usuário (com resposta ajax ou redirect) | Alta |
| RF-06 | Atribuir/remover `roles` ao usuário | Alta |
| RF-07 | Sincronizar categorias de indicador e de avaliador | Média |
| RF-08 | Enviar e-mail de boas-vindas com reset de senha (se não organizador) | Média |

### 4.2 Não-funcionais
- Segurança: senha gerada/hash; acesso admin (middleware `CheckAdmin:admin`).
- Evitar N+1: `with('roles')`, `with(['extraData','evaluatorCategories','indicatorCategories','roles'])`.

## 5. Modelo de Dados / Contratos

### `users` (App\Models\User)
| Campo | Tipo | Regra |
|---|---|---|
| id | bigint PK | auto |
| name | string | required min:5 |
| email | string unique | required,email |
| telefone | string | required min:5 (criado p/ migração `add_telefone_to_users_table`) |
| password | string | hash |
| is_judge | bool | nullable |
| is_organizer | bool | nullable |
| email_verified_at | datetime nullable | |

### Relacionamentos
- `belongsToMany(Role, 'user_role')` → `roles()` (com `where roles.active`).
- `hasOne(UserExtraData)` → `extraData()`.
- `belongsToMany(Category, 'indicators')` → `indicatorCategories()`.
- `belongsToMany(Category, 'evaluators')` → `evaluatorCategories()`.

### Helpers no model
`hasRole`, `assignRole`, `removeRole`, `hasAnyRole`, `hasAllRoles`, `isJudge`, `isOrganizer`,
`isIndicator`, `isEvaluator`.

## 6. Critérios de Aceite (BDD)
- **DADO** um admin autenticado em `/admin/users`
  **QUANDO** acessa a listagem
  **ENTÃO** vê usuários paginados (15/página) com `roles`.
- **DADO** um termo de busca por nome/e-mail/papel
  **QUANDO** submete a busca
  **ENTÃO** a listagem é filtrada.
- **DADO** um formulário válido de criação
  **QUANDO** submete para `admin.users.store`
  **ENTÃO** cria usuário, papéis e dados extras; redireciona com `success`.
- **DADO** uma edição com papéis
  **QUANDO** submete em `admin.users.update`
  **ENTÃO** sincroniza roles, dados extras, indicadores e avaliadores.
- **DADO** uma exclusão
  **QUANDO** confirma destroy
  **ENTÃO** remove o usuário (JSON se ajax, senão redirect).

## 7. Arquivos / Camadas afetadas
- `app/Models/User.php`
- `app/Models/Role.php`, `app/Models/UserRole.php`, `app/Models/UserExtraData.php`
- `app/Http/Controllers/UserController.php`
- `routes/web.php` (`Route::resource('users', UserController::class)`)
- `resources/views/admin/users/*` (index, create, partial/form-*)
- `database/factories/UserFactory.php`

## 8. Tasks
- [x] [01-model.md](tasks/01-model.md)
- [x] [02-validation.md](tasks/02-validation.md) *(inline hoje — pendente extração FormRequest → débito)*
- [x] [03-controller.md](tasks/03-controller.md)
- [x] [04-routes.md](tasks/04-routes.md)
- [x] [05-views.md](tasks/05-views.md)
- [ ] [06-tests.md](tasks/06-tests.md) — *pendente: cobertura de Feature Tests*

## 9. Histórico
| Data | Ação | Autor |
|------|------|-------|
| 2026-08-26 | Baseline (registro do CRUD existente como referência) | Cline |