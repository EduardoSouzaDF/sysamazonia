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
- Se `UserFactory` não expor `is_judge`, adicionar ao `$definition`/método `judge()`.

## Histórico
| Data | Status | Autor |
|------|--------|-------|
| 2026-08-27 | pending | Cline |
