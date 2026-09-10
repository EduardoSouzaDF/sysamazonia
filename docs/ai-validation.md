# Diagnóstico e validação — 10/09/2026

## Diagnóstico confirmado

Não havia geração automática de `AI_SERVICE_TOKEN` no código. Os dois `.env` locais tinham valores iguais, sem whitespace, sem override de shell e sem `bootstrap/cache/config.php`; nenhum valor foi registrado no relatório.

Foram encontrados problemas reproduzíveis no ciclo de desenvolvimento:

1. Python usava `env_file=".env"`, dependente do diretório atual. O carregamento a partir de `/tmp` falhava; iniciar na raiz também podia ler as configurações Laravel em vez das Python.
2. `composer dev` não iniciava FastAPI e escutava apenas a fila padrão. Além disso, Laravel e o exemplo FastAPI disputavam a porta padrão 8000.
3. A tela reduzia health a um booleano, e o botão de conexão apenas construía um objeto de modelo sem consultar o provider.

A primeira tentativa de teste de rede foi bloqueada pelo sandbox; não foi tratada como prova de conexão recusada no servidor do usuário. Fora dessa restrição, o script corrigido iniciou/reiniciou FastAPI a partir de `/tmp` com o token local existente e obteve HTTP 200 nos dois ciclos. Não é possível atribuir retrospectivamente todos os incidentes anteriores a uma única causa sem seus logs; os defeitos de startup acima foram demonstrados e corrigidos.

## Arquitetura final

Laravel mantém Models/Settings/Jobs/Processors e auditoria. AgnoEvaluationService envia runtime e Bearer a FastAPI. A factory Agno suporta OpenAI, Gemini, Anthropic, Mistral, Groq, custom e Local; Pydantic e Laravel validam as respostas antes de persistir. Idempotência e proteção contra resultados desatualizados foram preservadas.

Avaliador e ativador ficam no primeiro bloco; indicador e ativador logo abaixo. Providers, Base URL e tipo local têm validação administrativa. Dashboard reutiliza ApexCharts, com duas queries agregadas, cache curto e tabela acessível. A migração adiciona `base_url`/`local_type` em Settings e versões e índices `created_at`/`completed_at` em execuções; não modifica histórico. Migrations foram executadas nos bancos SQLite temporários dos testes. Na finalização, a migration 2026_09_10_120000 foi aplicada com sucesso também ao MySQL local, sem executar outras migrations.

## Resultados executados

| Comando/verificação | Resultado |
|---|---|
| `php artisan test --compact` | 36 testes, 142 assertions aprovados |
| `PYTHONPATH=ai-service ai-service/.venv/bin/python -m pytest ai-service/tests -q` | 55 testes aprovados |
| `npm run build` | PASS; Vite 7.3.1 |
| Pint nos PHP alterados pela tarefa | PASS |
| `node --check resources/js/ai-dashboard.js` e `node --check vite.config.js` | PASS |
| `bash -n scripts/ai-service.sh` | PASS |
| `python -m compileall -q ai-service/app scripts/test-ai-restart.py` | PASS |
| `php artisan view:cache` | PASS |
| `composer validate --no-check-publish` | PASS com aviso preexistente da constraint `*` do Larapex |
| `git diff --check` | PASS |
| Comparação dos arquivos alterados com os secrets conhecidos dos ambientes/README antigo | Nenhuma ocorrência |

Os testes cobrem token ausente/inválido/válido, reload, schema, sete adapters, key local opcional, SSRF/allowlist, metadata, connection refused, timeout, 401/403/404/429, resposta inválida, limite de tamanho, redirects, DNS privado, logs sanitizados e inferência OpenAI-compatible simulada passando pelo avaliador real. No Laravel: autorização, criptografia/máscara, dados de formulário, troca de destino, cache de modelos, gráficos vazios, fronteiras de data, agregações, flags e fluxos existentes.

## Restart real

`python3 scripts/test-ai-restart.py` inicia processos HTTP locais, inicializa o Laravel real sem acessar banco, verifica liveness/Bearer e encerra todos os processos no final. Cache/roteador de ensaio ficam em diretório temporário privado. O cenário usa token fictício e não altera `.env`.

| Cenário | Sem cache | Com config:cache isolado |
|---|---|---|
| FastAPI restart | PASS | PASS |
| Laravel restart | PASS | PASS |
| Token remained valid | PASS | PASS |
| Offline / 401 distintos | PASS | PASS |
| Token ausente dos logs | PASS | PASS |

## Smoke test da interface

Foi renderizada a view real via Laravel/PHPUnit, com usuário administrador fictício, SQLite em memória e HTTP fake. O HTML e os assets da build foram abertos em Chromium via Playwright, em 1440 e 390 pixels. Confirmados: sete providers, campos local/modelo, seletores/ativadores empilhados, gráficos renderizados, números/estados vazios, ausência de overflow da página após resize e nenhum erro JavaScript. Tabelas mantêm rolagem horizontal interna no mobile.

O teste descobriu dois problemas adicionais corrigidos: importação ES module do ApexCharts (não existe global `window.ApexCharts` na build) e bundle legado do tema com `exports` dentro de strings eval. O Vite agora decodifica apenas os módulos estáticos desse bundle antes de transformar; o arquivo do fornecedor foi preservado. Isso eliminou o erro de dropdown e os avisos de eval.

Os fluxos de gravação/validação, flags e teste de conexão foram exercitados pelos testes HTTP Laravel; não houve edição de Settings reais pelo browser. Não foi realizada inferência paga em serviços comerciais nem instalado/acionado um Ollama real. O transporte OpenAI-compatible foi testado com respostas simuladas válidas e inválidas. O teste de conexão do painel verifica metadados, não promete qualidade ou capacidade de inferência.

## Avisos e segurança

Permanece o aviso de bundle acima de 500 kB do ApexCharts já existente; não foram adicionados frameworks frontend. Python 3.14 local emitiu quatro avisos de depreciação de Google GenAI/Starlette/AnyIO; a CI usa Python 3.13.

API Keys seguem criptografadas; não ficam no cache de Settings, em respostas de erro ou em dados antigos do formulário. Token interno não é enviado ao browser nem incluído nos logs testados. Local/custom têm allowlists exatas, sem redirects, limites e proteção DNS/TLS.

O README anterior continha uma credencial SMTP em texto claro. Ela foi removida do arquivo atual, mas o histórico não foi reescrito: o responsável precisa rotacioná-la. `.env`, virtualenv, caches, logs, node_modules e assets compilados não são versionados.

Alterações prévias em `app/Services/Ai/AiExecutionManager.php` e `tests/Feature/AiEvaluationWorkflowTest.php` foram preservadas e excluídas da cópia isolada de revisão e dos commits desta tarefa. Os resultados Laravel incluem essas alterações presentes no workspace; a verificação adicional do conteúdo a ser commitado é registrada na entrega.


## Estado final do Git

A finalização foi retomada após a liberação da revisão automática. Branch de entrega: `feat/ai-startup-providers-dashboard`. Remote: `origin` (`git@github.com:DouglasMuller/app_premios.git`). Implementação/testes e documentação são registrados em commits separados; não há merge em main, force push ou alteração de histórico. As duas modificações anteriores do usuário permanecem locais e fora da entrega.

A migration local foi concluída com `php artisan migrate --path=database/migrations/2026_09_10_120000_add_provider_endpoints_to_ai_settings.php --force`. Na conferência final, `php artisan test --compact` repetiu 36 testes e 142 assertions aprovados; `git diff --check` e a busca por secrets conhecidos nos arquivos alterados não identificaram problemas. Os hashes e o resultado do push são informados na entrega.

Uma cópia de revisão fora do repositório, montada a partir de HEAD mais apenas as mudanças desta tarefa, executou a suíte Laravel sem incluir as duas alterações prévias: 35 testes sem falhas, 134 assertions, exit 0. Na cópia sem .env, o runner marcou 34 testes com warnings; a primeira execução também mostrou depreciações por APP_URL ausente no config/filesystems.php preexistente. A suíte do workspace original teve 36 testes aprovados sem esses avisos. Isso verifica o conjunto destinado aos futuros commits separadamente do workspace original.
