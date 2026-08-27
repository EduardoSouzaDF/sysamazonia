# Task — Rotas do painel do julgador (panel.julgar)

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Registrar a rota autenticada do painel do julgador, **fora** do grupo `admin`, protegida por
`auth` + `CheckJudge`, com nome `panel.julgar.*`.

## Arquivos-fonte
- `routes/web.php` (editar)

## Critérios de Aceite (BDD)
- **DADO QUE** um usuário autenticado e julgador acessa `GET /julgar`
  **ENTÃO** é atendido pelo `JudgingController@index` com rota nomeada `panel.julgar.index`.
- **DADO QUE** um usuário autenticado e não-julgador acessa `GET /julgar`
  **ENTÃO** faz **logout**, redireciona ao login e exibe **"Perfil sem Acesso!"**.
- **DADO QUE** um visitante não autenticado acessa `GET /julgar`
  **ENTÃO** é redirecionado ao login (`auth`).

## Rotas a registrar (esboço)
```php
Route::middleware(['auth', CheckJudge::class])
    ->get('/julgar', [JudgingController::class, 'index'])
    ->name('panel.julgar.index');
```

## Convenções a seguir
- Fora do prefixo `admin` (não expor o back-office).
- Usar nomes nomeados `panel.*`.
- Registrar o alias `CheckJudge` no `bootstrap/app.php` (task 03) antes de referenciar.

## Dependências
- `03-middleware-checkjurado.md`
- `02-controller-painel.md`

## Verificação
- [ ] `php artisan route:list --name=panel.julgar`
- [ ] Acessos 200 (julgador), logout+login (não-julgador), redirect login (guest)

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
