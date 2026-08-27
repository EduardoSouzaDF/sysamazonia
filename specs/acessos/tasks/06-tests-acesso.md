# Task — Feature Tests de acesso do julgador

> **Spec**: [../0002-acesso-julgador.md](../0002-acesso-julgador.md)
> **Status**: `pending`
> **Agente sugerido**: `qa`

## Objetivo
Adicionar Feature Tests (PHPUnit) cobrindo os critérios de aceite da spec: menu "Julgar", rota do
painel, filtro de edições ativas (RDD-01) + em julgamento (RDD-03), status por tipo
(Registration "Avaliado" / Nominee "Habilitado"), e logout+login para não-julgadores.

## Arquivos-fonte
- `tests/Feature/JudgingAccessTest.php` (criar)
- `database/factories/UserFactory.php` (usar; ajustar para suportar `is_judge` se necessário)

## Critérios de Aceite (BDD)
- **DADO QUE** um usuário com `is_judge = true` está autenticado
  **ENTÃO** `GET /julgar` retorna **200** e a página contém as Inscrições de edições
  ativas (RDD-01) e em julgamento (RDD-03), com `Registration` "Avaliado" e `Nominee` "Habilitado".
- **DADO QUE** um usuário com `is_judge = false` está autenticado
  **ENTÃO** `GET /julgar` faz **logout**, redireciona ao login e exibe **"Perfil sem Acesso!"**.
- **DADO QUE** um visitante não autenticado acessa `GET /julgar`
  **ENTÃO** é redirecionado ao login (`auth`).
- **DADO QUE** um usuário `is_judge = true` monta o menu
  **ENTÃO** `MenuBuilder::getMenuStructure()` contém o menu "Julgar".
- **DADO QUE** há Inscrições em edição não ativa ou com data `<= judgment_date`
  **ENTÃO** essas Inscrições **não** aparecem no painel.

## Convenções a seguir
- Testes PHPUnit (não Pest). Verificar uso de `RefreshDatabase` em `phpunit.xml`/siblings.
- Preparar usuário julgador via factory com `is_judge => true`; criar Edições e Inscrições para
  cobrir RDD-01, RDD-02 e RDD-03.
- Rodar apenas o teste relacionado com filtro.

## Dependências
- Todas as tasks anteriores (01 a 05)

## Verificação
- [ ] `php artisan test --compact --filter=JudgingAccessTest`

## Notas
- O acesso é gated por `is_judge` (não por papel `Role::JURADO`).
- ⚠️ **Revalidação v1.5.0 (confirmado)**: o `UserFactory` atual **não** expõe `is_judge` — o
  `$definition` não inclui o campo e não há estado `judge()`. Adicionar método de estado
  `judge()` (`return $this->state(fn () => ['is_judge' => true]);`) seguindo o padrão de
  `unverified()`.
- ⚠️ **Revalidação v1.5.0**: usar `RegistrationStatusEnum::Avaliado->value` (4) e
  `::Habilitado->value` (3) nos factories/asserções (padrão da produção); observar que
  `Registration` **não tem cast** de `status` (o `Nominee` tem cast integer) — criar registros
  passando int explícito.
- Rodar apenas o teste relacionado com filtro.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
| 2026-08-27 | done — `tests/Feature/JudgingAccessTest.php` criado (6 testes / 27 assertions, todos passando): menu julgador (com/sem `is_judge`), painel 200 com Inscrições corretas, exclusões RDD-01/RDD-03/status inválidos, logout+"Perfil sem Acesso!" na tela de login, guest → login. `UserFactory::judge()` adicionado. Obs.: `ExampleTest` falha por 302 em `/` — pré-existente, fora do escopo | Cline |
