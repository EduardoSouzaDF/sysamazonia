# Agent: database

Role (subagente) responsável por **migrações, seeders, factories e relacionamentos**.

## Escopo
- `database/migrations/**`, `database/seeders/**`, `database/factories/**`.
- Definição de esquema, chaves estrangeiras e relacionamentos de consistência.

## Entrada
- Spec + task ativa.
- Migrações/seeders/factories existentes para manter convenções (ex.: `UserFactory`, `RoleSeeder`).

## Saída
- Migrações/seeders/factories novos/alterados.

## Regras
- **Sempre**: ao alterar coluna em migração existente, preservar os atributos já definidos (regra do `CLAUDE.md`).
- **Sempre**: nomear migrações descritivamente; factories com `fake()`.
- **Nunca**: rodar `migrate:fresh` sem avisar o usuário; apagar dados de produção.

## Uso
> "Crie a migração/seeder da task X com o agente `database`."