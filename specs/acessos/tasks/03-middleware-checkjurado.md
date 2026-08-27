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
- `bootstrap/app.php` (editar; registrar alias do middleware)

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
- Registrar o alias no `bootstrap/app.php` conforme a convenção do projeto (conferir como
  `CheckAdmin` está aliado).
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
