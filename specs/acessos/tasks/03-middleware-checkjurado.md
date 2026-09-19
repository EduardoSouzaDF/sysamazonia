# Task — Middleware CheckJudge (acesso por is_judge)

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Criar `App\Http\Middleware\CheckJudge` que bloqueia o acesso ao painel do julgador para usuários
sem `is_judge = true`: o middleware deve fazer **logout** e redirecionar ao **login** exibindo a
mensagem **"Perfil sem Acesso!"**.

## Arquivos-fonte
- `app/Http/Middleware/CheckJudge.php` (criar)

> ⚠️ Revalidação v1.5.0: **não** editar `bootstrap/app.php` — o projeto não usa aliases de
> middleware (`withMiddleware()` vazio); o middleware é referenciado por FQCN direto nas rotas.

## Critérios de Aceite (BDD)
- **DADO QUE** um usuário autenticado tem `is_judge = true`
  **QUANDO** o middleware é executado
  **ENTÃO** a request prossegue (`$next($request)`).
- **DADO QUE** um usuário autenticado tem `is_judge = false`
  **QUANDO** o middleware é executado
  **ENTÃO** faz **logout**, redireciona ao login e exibe **"Perfil sem Acesso!"**.
- **DADO QUE** não há usuário autenticado
  **QUANDO** o middleware é executado
  **ENTÃO** o middleware `auth` (externo) redireciona ao login antes dele rodar.

## Convenções a seguir
- Basear-se em `CheckAdmin` para a estrutura, mas checando `isJudge()` e **não** retornando 403
  (logout + redirect com flash `success`/`error` = "Perfil sem Acesso!").
- ⚠️ **Revalidação v1.5.0 (produção mudou)**: o `CheckAdmin` **não é aliado** no
  `bootstrap/app.php` — o `withMiddleware()` está vazio e as rotas usam **FQCN direto**
  (`CheckAdmin::class.':admin'`). Portanto, **não registrar alias**; o `CheckJudge` deve ser
  referenciado por FQCN direto nas rotas (como no esboço da task 04), mantendo o
  `withMiddleware()` vazio. O `CheckAdmin` atual também aceita múltiplos papéis
  (`string ...$roles`) — o `CheckJudge` não precisa desse parâmetro (gating único por `is_judge`).
- Explicitar tipo de retorno e parâmetros com PHPDoc.

## Dependências
- Nenhuma (helper `isJudge()` já existe).

## Verificação
- [ ] `php -l app/Http/Middleware/CheckJudge.php`
- [ ] `php artisan route:list` e teste manual: usuário não-julgador → logout + login "Perfil sem Acesso!"

## Notas
- Não reutilizar `CheckAdmin:admin`, pois o julgador **não** é admin.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — `CheckJudge` criado (logout + `session()->invalidate()` + `regenerateToken()` padrão `AuthController::logout`; redirect `login` com flash `error` = "Perfil sem Acesso!"); sem alias (FQCN direto). Ajuste necessário registrado: view de login tinha `{{ $session('error') }}` (erro 500 ao exibir o flash) — corrigido para `{{ session('error') }}` (FR-04). `php -l` OK | Cline |
