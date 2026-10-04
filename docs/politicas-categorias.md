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

A migration `2026_09_24_120000_remove_redundant_category_policy_counts` remove
`evaluations_count`, `nominations_count`, `ai_evaluations_required` e
`ai_indications_required`.

Antes de remover as colunas, ela verifica se as categorias regulares têm políticas
explícitas e se os totais anteriores correspondem às quantidades configuradas.
Se houver uma categoria legada ou inconsistente, a migração para antes de alterar
o esquema; a política deve ser definida explicitamente, sem inferir a distribuição
humana/IA a partir de um total.

Se houver categorias, uma cópia completa dos registros anteriores é gravada em
`storage/app/private/backups/category-policies-*.json`, com permissão `0600`.
Esse arquivo também preserva os antigos totais sem uso das categorias honoríficas.
Não é um backup completo do banco.

O rollback recria as quatro colunas e calcula seus valores a partir das políticas
vigentes; para honoríficas, os totais são zero e a parcela IA é nula. Valores
históricos sem significado operacional permanecem disponíveis na cópia JSON.

Esta mudança não exclui pareceres ou indicações, não altera o status das inscrições
e não introduz uma exigência de indicações para entrar no painel de julgamento.
