# Task — Feature Tests (cobertura do CRUD)

> **Spec**: [../0001-users-crud.md](../0001-users-crud.md)
> **Status**: `pending`
> **Agente**: `qa`

## Objetivo
Adicionar testes de Feature (PHPUnit) cobrindo o CRUD de usuários e os critérios de aceite da spec.

## Arquivos-fonte
- `tests/Feature/UserCrudTest.php` (criar)
- `database/factories/UserFactory.php` (usar; ajustar se necessário)

## Critérios de Aceite (BDD)
- **DADO** um admin autenticado
  **ENTÃO** pode listar usuários (200) e a página contém os usuários.
- **DADO** `POST admin/users.store` com dados válidos
  **ENTÃO** cria o usuário e redireciona com `success`.
- **DADO** `POST admin/users.store` com e-mail duplicado
  **ENTÃO** valida e retorna erro (`email.unique` → "O email já está em uso.").
- **DADO** `PUT admin/users.update` com papéis
  **ENTÃO** sincroniza roles/dados extras.
- **DADO** `DELETE admin/users.destroy`
  **ENTÃO** remove o usuário (resposta JSON em ajax).

## Convenções
- Testes PHPUnit (não Pest). Usar `RefreshDatabase`? (verificar uso atual em `phpunit.xml`).
- Rodar apenas o teste relacionado com filtro.

## Verificação
- [ ] `php artisan test --compact --filter=UserCrudTest`

## Notas
- Requer autenticação de admin (middleware `CheckAdmin:admin`); preparar admin via `UserSeeder`
  ou factory com role `admin`.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-26 | pending (cobertura a criar) | Cline |