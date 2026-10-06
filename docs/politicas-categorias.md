# Políticas de avaliação e indicação das categorias

Cada etapa possui uma política independente: `evaluation_mode` para avaliação
técnica e `indication_mode` para indicação estratégica. As quantidades humanas
ficam em `human_evaluations_required` e `human_indications_required`.

| Modo | Humanos | IA | Total calculado |
| --- | --- | --- | --- |
| `human_only` | Quantidade configurada, de 1 a 50 | 0 | Humanos |
| `ai_only` | 0 | 1 | 1 |
| `hybrid` | Quantidade configurada, de 1 a 50 | 1 | Humanos + 1 |

Categorias honoríficas não participam dessas etapas. A quantidade de agraciados
(`recipients_count`) e o limite de inscrições por candidato
(`submissions_per_candidate`) continuam independentes dessas políticas.

Os métodos `Category::requiredEvaluations()` e `requiredIndications()` calculam
os totais. `requiredAiEvaluations()` e `requiredAiIndications()` calculam a parcela
IA. Nenhum desses resultados é armazenado em uma segunda coluna.

O cadastro exige políticas explícitas para categorias regulares. As listagens de
avaliadores e indicadores humanos consideram apenas participações humanas para
verificar a pendência humana, respeitando o modo configurado.

## Migração dos dados existentes

A migration `2026_09_23_120000_backfill_legacy_category_policies` define as
categorias regulares criadas antes das políticas como `human_only`: a quantidade
de humanos exigida passa a ser o total legado (`evaluations_count` /
`nominations_count`) e a parcela IA é zero. Categorias honoríficas e categorias
que já tinham política definida não são alteradas.

As colunas legadas `evaluations_count`, `nominations_count`,
`ai_evaluations_required` e `ai_indications_required` permanecem na tabela, mas
não são lidas nem gravadas pela aplicação.

Esta mudança não exclui pareceres ou indicações, não altera o status das inscrições
e não introduz uma exigência de indicações para entrar no painel de julgamento.
