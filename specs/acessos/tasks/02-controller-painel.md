# Task — Controller do painel do julgador (judging)

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `laravel-backend`

## Objetivo
Criar `App\Http\Controllers\JudgingController` com o método `index` que lista **todas as
Inscrições** (`Registration` e `Nominee`) cujas **edições estão ativas** (RDD-01) **e em
julgamento** (RDD-03), aplicando por tipo: `Registration` status **"Avaliado"** e `Nominee` status
**"Habilitado"**, para o painel do julgador.

## Arquivos-fonte
- `app/Http/Controllers/JudgingController.php` (criar)

## Critérios de Aceite (BDD)
- **DADO QUE** um usuário `is_judge = true` acessa o painel
  **QUANDO** o `index` é chamado
  **ENTÃO** retorna a view `admin.julgar.index` com **todas as Inscrições** de **edições ativas** (RDD-01) **e em julgamento** (RDD-03).
- **DADO QUE** existem Inscrições
  **ENTÃO** a listagem inclui a união de `Registration` (regular) com `status = 4` ("Avaliado") e
  `Nominee` (honorífica) com `status = 3` ("Habilitado") (RDD-02 / FR-03).
- **DADO QUE** os dados existem
  **ENTÃO** a busca usa eager loading para evitar N+1 (não há relacionamento novo).

## Convenções a seguir
- Seguir o padrão dos controllers irmãos (`CategoryController`, `UserController`).
- `php -l` válido; nomes descritivos.
- Aplicar filtro via `whereHas` atravessando `Category → Modality → Edition` (RDD-01 **e** RDD-03);
  as Inscrições **não têm vínculo direto** com a edição.
- Usar `scopeStatus(4)` para `Registration` e `scopeStatus(3)` para `Nominee`.

## Dependências
- `01-menu-jul.md` (para o menu apontar para o painel)

## Verificação
- [ ] `php -l app/Http/Controllers/JudgingController.php`
- [ ] Rota `panel.julgar.index` exibe o painel com as Inscrições de edições ativas em julgamento

## Notas
- **Não há relacionamento novo**: Inscrições **não têm vínculo direto com a Edição**; filtro por
  `Category → Modality → Edition` atendendo RDD-01 (ativa) e RDD-03 (em julgamento).

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
