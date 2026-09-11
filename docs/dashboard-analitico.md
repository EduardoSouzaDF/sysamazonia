# Dashboard Analítico / Assistente de Dados

Disponível em **Relatórios → Assistente de Dados**, `/admin/analytics`. Implementação na branch `feature-ia`, inicialmente limpa. Não altera inscrições, notas, candidatos nem o fluxo de avaliação.

## Arquitetura e schema inspecionados

Laravel 12, MySQL, Blade, JavaScript/Vite e tema existente. ApexCharts e Leaflet já estavam no repositório. FastAPI usa Agno e `LlmProviderFactory`, com autenticação Bearer entre serviços e configurações versionadas de providers administradas pelo Laravel.

Fluxo: pergunta → Laravel → `POST /v1/analytics/plan` → schema Pydantic → validação independente Laravel → Query Builder com agregações SQL → JSON → KPI/tabela/gráfico local. Não há conexão MySQL no interpretador nem ferramentas para executar SQL. Texto de resposta é determinístico; não há segunda chamada ao provider para narrativa.

| Entidade real | Caminho / regra |
| --- | --- |
| `registrations` | `candidate_id`, `category_id`, `status`, `created_at`, `evaluation_avg` |
| `candidates` | `dt_nascimento`, `ufendereco`, `cidade`, `escolaridade`; endereço e contatos pessoais excluídos |
| `categories` → `modalities` → `editions` | Edição não está diretamente em registrations. `registration_start`, `registration_end`, `is_registration_active` estão em editions |
| `opinions` | `registration_id`, `user_id`; **não tem status próprio** |
| `scores` → `evaluation_criteria` | `valor`, `opinion_id`, `evaluation_criterion_id`; critérios com pesos e escalas |
| `indications` | `registration_id`, `user_id`, `decision`; NULL representa indicação humana legada |
| `ai_executions` | status, tipo, evaluator_id, duration_ms, created_at, started_at, completed_at, correlation_id |
| `evaluators`, `indicators`, `commissions` | Vinculação de usuários às categorias; não concede acesso global ao assistente |
| `nominees` | Nomeações honoríficas separadas; não entram nas contagens de registrations |

Inspecionados também `User`, `Role`, `UserRole`, `UserExtraData`, `RegistrationFile` e serviços de conclusão/indicação. Nenhuma data exclusiva de conclusão da inscrição foi encontrada. `updated_at` não é usada como conclusão. Nota consolidada segue `EvaluationCompletionService`: escala 0–100; não reutiliza a conversão antiga `evaluation_avg / 2`.

## Permissões e privacidade

Acesso somente ao papel **admin**, pelo middleware existente `CheckAdmin:admin`, além de autenticação e CSRF. Comissão, avaliador, indicador e leitor não recebem acesso global. Não foi inventado um papel gestor. Ampliar acesso exige definir o escopo por categoria em todas as consultas, catálogo e cache.

Allowlist de métricas, dimensões, filtros e visualizações em `AnalyticsRegistry`; validação em `AnalyticsPlan`; SQL exclusivamente interno em `AnalyticsQuery`. Campos pessoais como CPF, RG, nome do candidato, telefone, email, endereço detalhado, currículos e anexos não são dimensões ou filtros disponíveis. O avaliador é identificado por `Avaliador #id`, somente para o administrador; isso é pseudonimização, não anonimização.

O provider recebe pergunta (com remoção de emails e sequências numéricas longas), último plano validado e catálogo, incluindo metadados de até 50 edições. **Não recebe resultados, linhas de inscrições ou dados de candidatos**. Detecção textual de pedidos proibidos é defesa adicional; a segurança não depende de regex nem da obediência do modelo. Texto livre pode conter dados pessoais inesperados: não cole dados pessoais na pergunta. Não há filtro de supressão de pequenos grupos: resultados restritos a administradores.

Perguntas não são gravadas em auditoria nem em histórico no servidor. O navegador mantém até 20 entradas; a sessão guarda somente o último plano validado. Limpar conversa remove ambos. Cache de 30 segundos contém agregados e usa ID do usuário e plano na chave. Logs `analytics.query` contêm usuário, métrica, duração, sucesso e tipo de erro; não contêm pergunta, filtros, credenciais ou resposta do provider. Seguem retenção e acesso do logging existente. Nenhuma tabela nova de auditoria foi necessária.

## Catálogo de métricas

| Métrica | Definição |
| --- | --- |
| `registrations_count` | Quantidade de inscrições, não candidatos distintos |
| `enabled_registrations_count` | Status 3, 4 ou 5 |
| `evaluated_registrations_count` | Status 4 ou 5 |
| `evaluation_rate` | Avaliadas/agraciadas ÷ inscrições filtradas × 100 |
| `indicated_count` | Pelo menos uma decisão INDICADA ou indicação legada com decision NULL |
| `not_indicated_count` | Tem indicação negativa, nenhuma positiva; não inclui pendentes |
| `indication_rate` | Indicadas ÷ todas as inscrições filtradas × 100 |
| `average_score` | AVG(evaluation_avg), ignora NULL; nota oficial 0–100 |
| `average_criterion_score` | AVG(scores.valor); nota bruta registrada, pode incluir parecer incompleto, não substitui a nota oficial |
| `ai_execution_count` | Quantidade de execuções, não inscrições |
| `ai_failure_count` | Execuções failed |
| `ai_success_rate` | completed ÷ (completed + failed) × 100 |
| `ai_average_duration_ms` | AVG(duration_ms) somente completed com duração registrada |
| `invalid_birth_date_count` | Nascimento inválido/ausente, futuro ou idade superior a 120 na inscrição; referência ausente também é desconhecida |
| `missing_education_count`, `missing_state_count`, `missing_city_count`, `missing_category_count` | Ausência do respectivo campo/relacionamento |
| `missing_fields_count` | Perfil de ausências por campo; uma inscrição pode contribuir em mais de um campo |

Indicações são pré-agregadas antes do join: múltiplos indicadores não multiplicam inscrições. Positiva prevalece sobre negativa para classificar a inscrição. Ausência de decisão não significa decisão negativa. Taxas sem denominador e médias sem valores são NULL, apresentados como “Sem dados”.

## Dimensões, filtros e cruzamentos

Dimensões: `edition`, `category`, `state`, `city`, `region`, `education`, `age_group`, `status`, `indication_status`, `day`, `week`, `month`, `days_to_deadline`, `ai_status`, `criterion`, `evaluator`, `quality_field`.

`ai_status` somente com métricas IA; `criterion` e `evaluator` somente com `average_criterion_score`; `quality_field` somente com `missing_fields_count`, isoladamente. Outras combinações aceitam até duas dimensões. Exemplos: edição × categoria/UF, UF × categoria/escolaridade/faixa etária, categoria × escolaridade/faixa etária/indicação, critério × categoria. IDs acompanham títulos de edição/categoria/critérios para distinguir nomes iguais; municípios incluem UF.

Filtros: `edition_id`, `edition_ids` (até cinco), `edition_year` (ano do início das inscrições), `category_id`, `state` (siglas oficiais), `status`, `date_from`, `date_to`, `last_days`. Datas vêm em pares, inclusive primeiro e último dia. `last_days` é relativo ao encerramento de cada edição, não à data de hoje. `days_to_deadline`: 0 no encerramento e negativos antes dele; permite alinhar curvas de duas edições. Para “edição atual”, o interpretador usa a única ativa ou única disponível; caso contrário pede esclarecimento.

Idade na data de criação da inscrição, sem persistir idade calculada. Faixas centralizadas na expressão do executor: menor de 18; 18–30; 31–40; 41–50; 51–60; acima de 60; inválida/desconhecida. Não presume que menores sejam inexistentes. Não é reconstrução do perfil pessoal histórico: alterações posteriores nos dados de candidates se refletem nos resultados.

Tempo baseado em `registrations.created_at`, ou `ai_executions.created_at` para métricas IA; timezone da aplicação `America/Sao_Paulo`, seguindo o armazenamento local existente. Semana é rotulada pela segunda-feira. Acumulado somente de inscrições com uma dimensão temporal, opcionalmente uma segunda dimensão. Soma apenas grupos agregados e rejeita curvas truncadas. Dias sem registros não são materializados; a curva mostra pontos observados, sem imputação estatística.

## Perguntas e contexto

- “Quantas inscrições tivemos em 2023?” → edição pelo ano, zero se não houver registros.
- “Mostre por estado.” → preserva métrica/edição anteriores.
- “Agora somente Amazonas.” → filtro AM.
- “Compare Amazonas e Pará.” → AM e PA na dimensão UF.
- “Mostre como gráfico de barras.” / “Agora por categoria.”
- “Qual categoria teve mais inscrições?” → ordenação por valor decrescente.
- “Mostre escolaridade.” / “Mostre inscrições por faixa etária.”
- “Existem datas de nascimento inválidas?” / “Qual campo apresenta mais dados ausentes?”
- “Quantas inscrições foram avaliadas?” / “Quantas foram indicadas?”
- “Qual a média das notas por categoria?” / “Qual estado tem maior taxa de indicação?”
- “Em qual dia recebemos mais inscrições?”
- “Compare a curva dos últimos 30 dias de duas edições.” → exige identificar as edições quando ambíguo.

Follow-ups usam o plano da sessão, não somente texto histórico. Remoção por botão envia novo plano sem o filtro, sem depender da LLM. Recarregar a página restaura filtros. “Melhores inscrições” pede esclarecimento; não há listagem individual de candidatos.

## API e visualizações

GET `/admin/analytics`; POST `/admin/analytics/query` com `question` ou `plan`; DELETE `/admin/analytics/context`. A entrada `plan` permite usar métricas deterministicamente, sem provider. Não é endpoint SQL.

Exemplo de plano:

```json
{"intent":"analytics","metric":"registrations_count","dimensions":["state"],"filters":{"edition_year":2026},"sort":[{"field":"value","direction":"desc"}],"limit":100,"visualization":{"type":"bar"},"cumulative":false}
```

Resposta inclui answer, metric, filters, context, kpis, columns, rows, total_groups, chart, warnings, source, period, records_aggregated, queried_at e calculation. “Como este resultado foi calculado?” mostra definição e proveniência. KPI representa todo o conjunto filtrado, mesmo quando tabela foi limitada.

Renderers locais: KPI, tabela, barras verticais/horizontais, linha, área, donut e barras empilhadas (métricas não aditivas usam barras agrupadas). Sem JS gerado pelo modelo. Donut com mais de oito grupos é substituído por barras. `map_brazil` tem fallback explícito para barras/tabela: Leaflet existe, mas não foi encontrada malha geográfica adequada. Scatter não é oferecido, pois o contrato inicial tem uma métrica por consulta.

Tabela sempre disponível, com ordenação e páginas de 20 linhas. Exportação CSV das linhas da resposta, com proteção contra fórmulas. Rótulos são texto; gráficos removem caracteres de markup. Layout usa componentes existentes e rolagem horizontal para tabelas, com legendas, labels, status e tabela alternativa acessíveis. Revisão visual real em navegador/mobile ainda necessária.

## Limites e operação

Até duas dimensões, 100 linhas retornadas, 500 grupos calculados antes de pedir refinamento, cinco edições e 27 UFs. Intervalo explícito máximo de 3660 dias; last_days até 366. Histórico visual de 20 entradas, um plano por sessão. Até 20 perguntas/minuto. FastAPI: conexão de 3 s e timeout de 30 s; provider recebe timeout de 25 s. MySQL recebe MAX_EXECUTION_TIME de 5000 ms por consulta agregada. Não há carga de inscrições completas em PHP; somas acumuladas operam nos grupos limitados.

Índices de FKs existentes atendem joins; EXPLAIN da contagem por categoria confirmou covering index scan em `registrations_category_id_foreign`. Banco local tem apenas 121 inscrições. Não há evidência para justificar novos índices; não foram criadas migrations. Índices de created_at/completed_at de ai_executions presentes no banco local não constam nas migrations inspecionadas: verificar drift em manutenção separada.

Erros seguros: 401/403 de autenticação/autorização, 422 de plano ou limites, 429 de throttle/provider, 503 com código de offline/timeout/configuração/consulta. Sem stack trace na resposta do assistente. A conexão autenticada FastAPI reutiliza `AI_SERVICE_URL`, `AI_SERVICE_TOKEN` e `AiSettingsService`. Prompt separado `analytics_v1`, fixo no código, não usa prompts editáveis de avaliação ou seleção. Reinicie o FastAPI após atualizar o código e execute `npm run build` para publicar os assets conforme rotina existente.

## Verificação e limitações da entrega

Executar:

```bash
php artisan test
PYTHONPATH=ai-service ai-service/.venv/bin/python -m pytest ai-service/tests -q
node tests/js/analytics-charts.test.mjs
npm run build
git diff --check
```

Providers são mocks nos testes. Testes Laravel usam SQLite isolado em memória, não fazem migrate:fresh no MySQL local. A suíte Python HTTP precisou ser executada fora da restrição de rede do sandbox; dentro dela um teste preexistente bloqueia. Avisos de depreciação de dependências e tamanho do bundle ApexCharts não são falhas.

Conferência inicial no MySQL local: total 121, avaliadas/agraciadas 5, indicadas 5, nota média 63,8; Amazonas 45, Amazonas+Pará 69, edição 2023 sem inscrições. As contagens por categoria/UF/escolaridade somam 121. Executadas 237 combinações válidas no MySQL; cada grupo de UF foi conferido independentemente. Últimos 30 dias da edição: 47 inscrições em 12 dias com registros. Valores são observações do ambiente em 11/09/2026, não fixtures ou dados incorporados ao produto.

A validação manual com provider real foi bloqueada pela revisão automática de aprovação por envolver envio de perguntas e metadados de edições ao provider configurado. A instância temporária FastAPI foi encerrada e o serviço existente não foi reiniciado. Logo, não se declara homologação de perguntas reais ponta a ponta. Também não se declara revisão visual mobile/a11y em navegador, nem validação estatística do relatório histórico (arquivo não fornecido). Mapa geográfico, escopo para não administradores, supressão de pequenos grupos e materialização de dias vazios são extensões possíveis.

### Resultado dos checks nesta entrega

- Laravel: **42 testes, 685 assertions, passando**.
- Python: **71 testes, passando**, com mocks (quatro avisos de depreciação).
- JavaScript: **5 testes, passando**.
- `npm run build`, Pint e `git diff --check`: passando; aviso de tamanho do ApexCharts existente.
- Provider configurado identificado sem expor credenciais: Gemini, modelo `gemini-3.1-flash-lite`. Validação real depende da aprovação do envio das perguntas e do catálogo.

### Arquivos

- Laravel: `app/Services/Analytics/{AnalyticsRegistry,AnalyticsPlan,AnalyticsQuery,AnalyticsPlanner}.php`, `app/Http/Controllers/AnalyticsController.php`, `routes/web.php`, `app/Services/MenuBuilder.php`.
- FastAPI: `ai-service/app/main.py`, `ai-service/app/agents/analytics.py`, `ai-service/app/prompts/analytics.py`, `ai-service/app/schemas/analytics.py`.
- Frontend: `resources/views/admin/analytics/index.blade.php`, `resources/js/{app,analytics-chat,analytics-charts}.js`.
- Testes: `tests/Feature/AnalyticsTest.php`, `tests/js/analytics-charts.test.mjs`, `ai-service/tests/test_analytics.py`.
- Documentação: este documento, `README.md`, `ai-service/README.md`.
