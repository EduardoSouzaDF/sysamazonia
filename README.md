# Sisamazonia

Sistema Laravel para inscrições, avaliação técnica, seleção estratégica por IA e acompanhamento estatístico.

## Implantação em homologação

O guia mantido para esta versão está em **[Implantação de homologação via SSH](docs/implantacao-homologacao-ssh.md)**.

Ele cobre publicação/clonagem da branch, requisitos, configuração dos dois ambientes, banco novo ou importado, anexos, Nginx/HTTPS, serviços systemd, atualizações, backups e retorno à versão anterior. Os exemplos de configuração estão em [`deploy/homologacao/`](deploy/homologacao/).

Requisitos desta revisão: **PHP 8.4** (exigido pelo `composer.lock`), Composer 2, Node 22.12+ ou 24 LTS, MySQL 8 e Python 3.11+. O PHP mínimo declarado em `composer.json` não substitui os requisitos dos pacotes fixados no lock.

Ao copiar um banco existente, preserve sua `APP_KEY` e transfira `storage/app` separadamente. Não execute seeders de demonstração nem gere outra chave sobre dados importados. Credenciais reais não devem ser registradas em documentação ou Git.

## Documentação funcional

- [Dashboard estatístico](docs/dashboard-estatistico.md).
- [Operação da avaliação por IA](docs/manual-operacao-avaliacao-ia.md).
- [Serviço Python de IA](ai-service/README.md).

## 🧭 Desenvolvimento Orientado a Especificação (SDD) com o Cline

Este projeto usa **SDD (Specification-Driven Development)** para guiar o assistente Cline no VS Code.

- **Visão geral & workflow**: [`docs/sdd/`](docs/sdd/01-visao-geral.md)
- **Regras do Cline (global + agentes)**: [`.clinerules/`](.clinerules/README.md)
- **Skills reutilizáveis**: [`.claude/skills/`](.claude/skills/laravel-crud/SKILL.md)
- **Specs de recursos**: [`specs/`](specs/users/0001-users-crud.md)

> O `CLAUDE.md` (Laravel Boost) permanece como camada superior de regras e deve ser sempre respeitado.

