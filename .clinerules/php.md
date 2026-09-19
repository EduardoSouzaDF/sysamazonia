# Regras para arquivos PHP (.clinerules/php.md)

Regras aplicadas a qualquer arquivo `*.php` neste repositório. Complementa o `CLAUDE.md`.

## Controllers & Validação
- Sempre crie **Form Requests** para validação (não inline), com mensagens customizadas em pt-BR.
- Use `findOrFail` para recursos.
- Métodos de recurso seguem o padrão dos controllers irmãos (ex.: `CategoryController`).
- Ações que isolem usuário/admin respeitam middleware (`CheckAdmin`).

## Models & Eloquent
- Prefira `scopeActive`, `scopeByName` similares ao `Role`.
- Use `$fillable` (nunca `$guarded = []`).
- Relacionamentos nomeados descritivamente; evite N+1 com `with()`.
- Booleans em `$casts` quando necessário.

## Rotas
- Agrupe rotas com `Route::resource` quando for CRUD; nomes `admin.{recurso}.*`.
- Proteja rotas admin com `['auth', CheckAdmin::class.':admin']`.

## Convenções gerais
- `php -l` válido ao final de cada edição.
- Siga o PS R-12/Pint (o projeto usa Laravel Pint).
- Busca compartilhada pode virar um FormRequest de dados (Ex.: `CandidateRequest`, `RegistrationRequest`).