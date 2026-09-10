# App Prêmios — Sisamazonia

Sistema de inscrições, avaliação técnica e indicação estratégica. Laravel mantém regras, MySQL, permissões e auditoria; FastAPI usa Agno para consultar um provider de LLM, sem acesso ao banco.

```text
Browser → Laravel → MySQL (inscrições, pareceres, notas, indicações, ai_executions)
             ↓ fila ai-evaluations / execução síncrona
          FastAPI (Bearer AI_SERVICE_TOKEN)
             ↓
       Provider comercial / LLM local
```

## Requisitos

- PHP `^8.2`, Composer; Laravel `12.47.0` no composer.lock. CI usa PHP 8.4.
- MySQL configurado em `.env` (versão mínima não fixada no repositório); PDO MySQL. Testes usam PDO SQLite em memória.
- Node compatível com Vite 7: `20.19+` ou `22.12+`; npm. CI usa Node 22; dependências em package-lock.json.
- Python 3.11+ (uso de `StrEnum`); CI usa Python 3.13. pip e venv.
- Dependências Python em `ai-service/requirements.txt`: Agno 2, FastAPI, Uvicorn, Pydantic Settings, OpenAI, Google GenAI, Anthropic, HTTPX e pytest.
- Provider com acesso a modelo de texto/saída estruturada, ou servidor local OpenAI-compatible. Ollama não é instalado por este projeto.

## Instalação

Após clonar e entrar no diretório do projeto:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm ci
python3 -m venv ai-service/.venv
ai-service/.venv/bin/python -m pip install -r ai-service/requirements.txt
cp ai-service/.env.example ai-service/.env
```

Configure `APP_URL=http://127.0.0.1:8001`, banco e email em `.env`. Crie previamente o banco MySQL e configure `QUEUE_CONNECTION=database`, `AI_SERVICE_URL=http://127.0.0.1:8000`. Não reutilize credenciais de exemplos ou desabilite a verificação TLS. Execute:

```bash
php artisan migrate
npm run build
php artisan config:clear
```

As migrations preservam os dados existentes. A migration de 10/09/2026 adiciona Base URL/tipo local às Settings e suas versões, mais índices de datas em `ai_executions`. Não é necessário recriar ou semear o banco existente. Crie usuários/perfis e vínculos de categorias pelo procedimento administrativo da instituição.

## AI_SERVICE_TOKEN

Gere uma única vez, em terminal privado:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Copie o MESMO resultado para `AI_SERVICE_TOKEN` em `.env` e `ai-service/.env`. O token deve ter ao menos 32 caracteres ASCII, sem espaços. Não o cole em logs, commits ou chamados. Exemplos deixam o campo vazio deliberadamente.

**Restart não muda o token.** Rotação é operação administrativa: atualizar os dois ambientes coordenadamente, reconstruir o cache Laravel se usado e reiniciar FastAPI e workers. O código não gera tokens durante boot.

Laravel lê `config('ai_evaluation.token')`; `config:cache` congela o valor até reconstrução. FastAPI sempre carrega `ai-service/.env` por caminho absoluto, com UTF-8. Variáveis exportadas pelo processo têm precedência sobre esse arquivo; confira também Supervisor/shell. Mudanças de `.env` Python exigem restart. Token ausente/inválido impede startup com mensagem sanitizada.

## Desenvolvimento e recuperação após reboot

Inicie MySQL primeiro. Depois:

```bash
composer dev
```

Esse comando inicia Laravel em `8001`, FastAPI em `8000`, worker das filas `ai-evaluations,default` e Vite. O encerramento de um processo encerra os demais via concurrently `--kill-others`. FastAPI usa o virtualenv `ai-service/.venv`; `AI_PYTHON` permite indicar outro executável explicitamente. Não há Controller iniciando processos.

Alternativa em terminais separados, a partir da raiz:

```bash
bash scripts/ai-service.sh
php artisan serve --host=127.0.0.1 --port=8001
php artisan queue:work --queue=ai-evaluations,default --timeout=70
npm run dev
```

Não use a mesma porta para Laravel e FastAPI. Reiniciar somente `php artisan serve` não inicia FastAPI nem worker. `AI_SERVICE_PORT` altera a porta do script Python; ajuste também `AI_SERVICE_URL` no Laravel.

```bash
curl --fail http://127.0.0.1:8000/health
```

`/health` confirma somente o processo. `/v1/diagnostics` verifica o Bearer interno sem consultar providers. `/ready` preserva a verificação das configurações legadas do ambiente. O painel usa as Settings salvas, não `/ready`.

## Providers e modelos

Acesse **Agente IA** como administrador. Salve provider, modelo e credencial antes de usar **Testar conexão** ou **Atualizar modelos**.

| Provider | API Key | Base URL | Implementação |
|---|---|---|---|
| OpenAI | Obrigatória | Fixa | Agno OpenAIChat |
| Google Gemini | Obrigatória | Fixa | Agno Gemini |
| Anthropic Claude | Obrigatória | Fixa | Agno Claude / SDK Anthropic |
| Mistral AI | Obrigatória | `https://api.mistral.ai/v1` | OpenAI-compatible |
| Groq | Obrigatória | `https://api.groq.com/openai/v1` | OpenAI-compatible |
| OpenAI-compatible/custom | Obrigatória | HTTPS em allowlist | Agno OpenAIChat |
| Local | Opcional | IP privado/loopback em allowlist | Ollama ou OpenAI-compatible |

API Keys são criptografadas pelo Laravel (`APP_KEY`) e mascaradas na tela. Campo vazio mantém a chave somente se provider e endpoint forem os mesmos; ao mudar o destino, informe a chave correspondente. O token interno não é armazenado em Settings nem enviado ao browser. As credenciais não ficam no cache compartilhado de Settings.

Settings prevalecem sobre os defaults `.env`; as variáveis Python de credenciais são fallback legado por provider. Não duplique chaves quando usar o painel. Execuções mantêm a versão do provider/modelo/prompts/endpoint; uma chave atual só acompanha a versão se provider e endpoint coincidirem. Se a versão antiga precisar de credencial, seu fallback deve ser provisionado com cuidado pela infraestrutura.

Não há lista fixa de modelos. **Atualizar modelos** consulta metadados e usa cache de 5 minutos por versão de Settings. A lista é uma sugestão limitada à página retornada pela API e a 1.000 IDs; campo manual sempre disponível. Nem todo modelo listado suporta avaliação estruturada. **Testar conexão** verifica acesso aos metadados do modelo, sem inferência, pareceres, notas, indicações ou mudança de status. Não garante qualidade nem suporte de inferência; acompanhe uma inscrição de teste antes de lotes.

## LLM local

Se Ollama já estiver instalado e executando com um modelo baixado, configure:

```text
Provider: Local
Tipo: Ollama
Base URL: http://127.0.0.1:11434
Modelo: ID exato do modelo instalado
API Key: vazia, se o servidor não exigir
```

O adapter acrescenta `/v1`. Para servidor genérico, escolha tipo **OpenAI-compatible** e informe a URL completa, por exemplo `http://10.0.0.10:8000/v1`. Autorize a URL exata em `ai-service/.env`:

```dotenv
AI_LOCAL_ALLOWED_URLS=http://127.0.0.1:11434,http://10.0.0.10:8000/v1
AI_CUSTOM_ALLOWED_URLS=https://llm.example.org/v1
```

Reinicie FastAPI após mudar a allowlist. URLs locais usam IP literal privado/loopback; DNS/`localhost` não é aceito nesse campo para impedir rebinding. Não são aceitos link-local/metadata, credenciais embutidas, query, fragmento, redirects ou esquemas diferentes de HTTP/HTTPS. Custom exige HTTPS, URL exata autorizada e resolução somente para IP público, fixado na conexão com SNI preservado. Respostas limitadas a 2 MB; TLS permanece validado.

O deploy encontrado usa Supervisor, sem Docker Compose. Se você introduzir containers, `127.0.0.1` aponta para o próprio container. Use um IP privado alcançável pelo FastAPI e autorize-o; não suponha que `host.docker.internal` exista ou seja aceito pela política de IP literal local.

O timeout Laravel configurável fica entre 5 e 60 segundos; Python recebe até 55 segundos. Modelos locais lentos podem exigir hardware/modelo mais adequado. Alterar esse teto requer revisar job, lock e `retry_after` juntos. Defaults: Python até 55s < HTTP 60s < job 70s < retry_after 90s.

## Operação

Selecione Avaliador IA e ativador técnico no primeiro bloco; Indicador IA e ativador estratégico no bloco seguinte. Cada usuário precisa existir e estar vinculado às respectivas categorias (`evaluators` / `indicators`). O processamento continua verificando esses vínculos; a seleção depende também da ativação técnica.

Pendentes oferecem fila ou uma execução síncrona. Os gráficos entre pendentes e execuções recentes mostram status das execuções criadas no período, taxa de sucesso entre concluídas/falhas dessa população e conclusões diárias por tipo. Períodos 7/30/90 dias; timezone `America/Sao_Paulo`, conforme config/app.php; cache de 30 segundos. A tabela diária inclui conclusões de execuções criadas antes do período. Números e tabela acessível funcionam sem JS. Não inferimos status de inscrições a partir de tentativas de execução.

Leia o [manual operacional](docs/manual-operacao-avaliacao-ia.md) e a [arquitetura de avaliação](docs/ai-evaluation.md). Comandos existentes:

```bash
php artisan ai:evaluate-habilitados
php artisan ai:evaluate-habilitados --dispatch
php artisan ai:select-avaliados
php artisan ai:select-avaliados --dispatch
```

## Testes

```bash
php artisan test --compact
PYTHONPATH=ai-service ai-service/.venv/bin/python -m pytest ai-service/tests -q
npm run build
python3 scripts/test-ai-restart.py
```

O teste de restart abre portas locais `18000/18001`, usa token fictício e diretório temporário privado, inicializa Laravel sem banco, repete o ciclo com cache de configuração isolado e remove os processos ao terminar. Não modifica `.env`. Testes Laravel usam SQLite em memória; Python usa mocks, sem consumo de LLM. Veja [resultados e limites da validação](docs/ai-validation.md).

## Troubleshooting

| Sintoma | Diagnóstico e ação |
|---|---|
| FastAPI offline / connection refused | Confira processo, venv, porta e endereço. Rode `bash scripts/ai-service.sh`. `/health` não exige token. |
| HTTP 401 interno | Serviço respondeu; confira o mesmo `AI_SERVICE_TOKEN`, shell/Supervisor e cache Laravel. Não gere outro token por simples restart. |
| HTTP 403 interno | Serviço/proxy negou acesso; confira a política de infraestrutura. |
| Token diferente / cache Laravel | Atualize coordenadamente; `php artisan config:clear` em dev ou `php artisan config:cache` no deploy; reinicie workers e FastAPI. |
| Porta ocupada | Separe Laravel `8001` e FastAPI `8000`; confira o processo antes de encerrar qualquer serviço. |
| Worker parado / pending | Inicie a fila correta; confira `QUEUE_CONNECTION=database`, Supervisor e jobs falhos. |
| Provider 401/403 | Credencial/permissão do provider; diferente do token interno. |
| Provider 429 | Aguarde a janela/quota; tentativas usam backoff existente. |
| Timeout | Diferencie FastAPI de provider; confira disponibilidade e capacidade do modelo local. |
| Local indisponível | Confira servidor, modelo instalado, rede vista pelo FastAPI e allowlist; container não compartilha localhost com host. |
| Modelo inexistente | Atualize sugestões ou informe ID manual exato; listagem não garante capacidade de saída estruturada. |
| Configuração inválida | Confira Base URL, allowlist, provider, modelo e credencial; nunca desabilite TLS. |

Logs Laravel: `storage/logs/laravel.log`; Supervisor: caminhos do arquivo de deploy. Informe somente IDs/correlation_id, código e horário, nunca headers, token, chave ou proposta. Logs de SDKs são sanitizados; erros exibidos não incluem respostas brutas ou stack traces.

## Produção

`php artisan serve` é apenas para desenvolvimento. Use o servidor PHP/web da infraestrutura, `APP_DEBUG=false`, TLS e secrets de ambiente/secret manager. Adapte caminhos/usuário de `deploy/supervisor/app-premios.conf.example`; ele monitora FastAPI e worker, encerra grupos de processos e reinicia após falhas/reboot. Não execute Uvicorn a partir de requests HTTP.

Após instalar dependências/build, aplique migrations, reconstrua cache e reinicie processos pelo Supervisor. Mantenha `DB_QUEUE_RETRY_AFTER` acima do timeout do job. Proteja `APP_KEY`, backups e banco: ela é necessária para descriptografar as API Keys existentes. Não reescreva histórico nem publique `.env`.
