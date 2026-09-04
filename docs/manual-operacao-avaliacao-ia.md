# Manual simples de operação da avaliação por IA

## 1. Para que serve

O sistema utiliza IA em duas etapas:

1. **Avaliação técnica:** analisa cada critério da categoria, atribui notas e registra justificativas.
2. **Seleção estratégica:** depois da conclusão dos pareceres técnicos, decide entre `INDICADA` e `NAO_INDICADA` e registra a justificativa.

O Laravel continua responsável pelos dados, permissões, cálculos e mudanças de status. O serviço Python apenas produz as respostas estruturadas da IA.

## 2. O que precisa estar funcionando

Antes de iniciar uma avaliação, confirme:

- MySQL em funcionamento;
- aplicação Laravel configurada;
- serviço Python/FastAPI ativo na porta configurada;
- worker da fila Laravel ativo;
- chave do provedor de IA válida;
- usuários técnicos vinculados às categorias corretas;
- critérios e escalas das categorias revisados.

## 3. Configuração pelo painel

Entre como administrador e abra **Agente IA > Configurações de IA**.

1. Selecione Gemini ou OpenAI e informe o modelo.
2. Informe a API Key. Depois de salva, ela aparece apenas mascarada. Em edições futuras, deixe o campo vazio para manter a chave atual.
3. Selecione o avaliador técnico e o indicador estratégico. Eles precisam estar associados às respectivas categorias.
4. Ajuste timeout, tentativas e, se aplicável, a versão da base de conhecimento.
5. Revise os dois prompts. Cada alteração gera uma nova versão auditável.
6. Clique em **Testar conexão**.
7. Ative a avaliação técnica e a seleção estratégica e salve.

As configurações operacionais passam a valer imediatamente, sem `config:clear`. A chave é criptografada no banco e nunca é exibida integralmente.

## 4. Configuração de infraestrutura

O `.env` do Laravel ainda contém somente a conexão protegida entre Laravel e FastAPI e os fallbacks para uma migração segura:

```dotenv
AI_EVALUATION_ENABLED=true
AI_SELECTION_ENABLED=true
AI_SERVICE_URL=http://127.0.0.1:8000
AI_SERVICE_TOKEN=<token-seguro>
AI_EVALUATION_QUEUE=ai-evaluations
```

No `ai-service/.env`, mantenha o mesmo token. Provider, modelo e chave abaixo funcionam como fallback até a configuração ser salva no painel:

```dotenv
AI_SERVICE_TOKEN=<mesmo-token-do-laravel>
LLM_PROVIDER=gemini
LLM_MODEL=<modelo-configurado>
GEMINI_API_KEY=<chave-do-provedor>
LLM_TIMEOUT=45
KNOWLEDGE_VERSION=
KNOWLEDGE_MAX_CHARS=50000
```

Se utilizar OpenAI, configure `LLM_PROVIDER=openai` e `OPENAI_API_KEY`.

Os tokens e chaves nunca devem ser enviados ao Git.

## 5. Manter os processos ativos

### Serviço Python

No diretório `ai-service`, ative o ambiente virtual e execute:

```bash
uvicorn app.main:app --host 127.0.0.1 --port 8000
```

Confirme a saúde do serviço:

```bash
curl http://127.0.0.1:8000/health
```

Resultado esperado:

```json
{"status":"ok"}
```

O painel apenas verifica a saúde do FastAPI; iniciar e reiniciar processos é responsabilidade da infraestrutura.

### Worker da fila

Na raiz do projeto Laravel, execute:

```bash
php artisan queue:work database --queue=ai-evaluations --tries=3 --timeout=75
```

Em produção, utilize Supervisor com o modelo disponível em `deploy/supervisor/app-premios.conf.example`.

## 6. Realizar uma avaliação técnica

No painel administrativo:

1. Abra a lista de inscrições.
2. Confira os dados e documentos da inscrição.
3. Clique na ação para **habilitar** a inscrição.
4. A inscrição passará para `Habilitado`.
5. O sistema criará automaticamente um trabalho na fila.
6. A IA avaliará os critérios e registrará o parecer.
7. Quando o quórum da categoria for atingido, a inscrição passará para `Avaliado`.

Não é necessário executar comandos para novas inscrições. O disparo acontece automaticamente pela mudança de status.

## 7. Realizar a seleção estratégica

Quando a inscrição muda para `Avaliado`:

1. o sistema cria automaticamente uma solicitação de seleção;
2. a IA analisa a proposta e os pareceres técnicos;
3. a decisão `INDICADA` ou `NAO_INDICADA` é registrada em `indications`;
4. os pareceres e notas técnicas não são alterados.

A seleção somente ocorre quando `AI_SELECTION_ENABLED=true`.

## 8. Processar registros antigos pelo painel

Em **Agente IA > Configurações de IA**, as seções de avaliações e seleções mostram os registros pendentes:

- **Processar em fila** inicia todo o lote sem manter a requisição web aberta. O worker precisa estar ativo.
- **Processar 1 agora** executa uma inscrição sincronamente, útil para operação pontual sem depender do worker.

Há confirmação antes do lote e proteção contra duplicação e cliques concorrentes.

### Comandos de manutenção

Os comandos continuam disponíveis para automação e contingência, mas não são necessários na rotina normal.

### Inscrições habilitadas sem avaliação

Primeiro, faça uma simulação:

```bash
php artisan ai:evaluate-habilitados
```

Depois, enfileire:

```bash
php artisan ai:evaluate-habilitados --dispatch
```

### Inscrições avaliadas sem seleção

Primeiro, faça uma simulação:

```bash
php artisan ai:select-avaliados
```

Depois, enfileire:

```bash
php artisan ai:select-avaliados --dispatch
```

Esses comandos são idempotentes: execuções já concluídas não geram pareceres duplicados.

## 9. Utilizar documentos institucionais

Quando existirem documentos aprovados, coloque arquivos `.md` ou `.txt` em:

```text
ai-service/app/knowledge/documents
```

Depois, informe uma versão no `ai-service/.env`:

```dotenv
KNOWLEDGE_VERSION=2026-01
```

Reinicie o FastAPI. Sem documentos, mantenha `KNOWLEDGE_VERSION` vazia. A ausência de documentos é aceita e não impede as avaliações.

## 10. Acompanhar o processamento

Durante o funcionamento, acompanhe:

- saída do worker da fila;
- logs em `storage/logs/laravel.log`;
- tabela `jobs`, para itens pendentes;
- tabela `failed_jobs`, para trabalhos definitivamente interrompidos;
- tabela `ai_executions`, para auditoria das avaliações;
- tabelas `opinions`, `scores` e `indications`, para os resultados.

Os estados mais importantes de `ai_executions` são:

| Estado | Significado |
|---|---|
| `pending` | aguardando processamento |
| `processing` | chamada em andamento |
| `completed` | resultado validado e salvo |
| `failed` | processamento não concluído |

## 11. Resolver problemas comuns

### Serviço de IA indisponível

Sintoma: erro de conexão com a porta 8000.

1. Confirme `/health`.
2. Reinicie o FastAPI.
3. Confirme `AI_SERVICE_URL`.
4. Reprocesse pelo comando correspondente.

### Erro 401

Confirme se `AI_SERVICE_TOKEN` é igual nos dois arquivos `.env`. Reinicie o FastAPI e limpe o cache do Laravel.

### Limite do provedor, erro 429

O Laravel realiza retentativas automáticas. Aguarde a liberação da cota. Se todas as tentativas forem consumidas, execute novamente o comando idempotente de seleção ou avaliação.

### Avaliação não foi criada

Confira:

- feature flag ativa;
- ID do avaliador configurado;
- associação do avaliador à categoria;
- critérios existentes na categoria;
- worker da fila ativo;
- logs do Laravel.

### Resultado descartado

O sistema descarta resultados quando a inscrição, os critérios, pareceres ou notas mudam durante a chamada. Isso é uma proteção de consistência. Execute novamente o comando idempotente depois de confirmar os dados.

## 12. Desativar emergencialmente

No painel, desmarque **Avaliação técnica ativa** para interromper novas avaliações e seleções, ou desmarque apenas **Seleção estratégica ativa** para interromper indicações. Salve a configuração; a alteração é imediata.

Essa desativação não apaga resultados já gravados.

## 13. Checklist diário

- [ ] FastAPI responde em `/health`.
- [ ] Worker da fila está ativo.
- [ ] Não existem trabalhos antigos em `failed_jobs`.
- [ ] Execuções recentes chegam a `completed`.
- [ ] Cota do provedor de IA está disponível.
- [ ] Avaliadores continuam vinculados às categorias.
- [ ] Tokens e chaves não aparecem em logs ou no Git.

## 14. Boas práticas

- Revise critérios e rubricas antes de habilitar inscrições.
- Não altere critérios durante uma avaliação em andamento.
- Confira a lista de pendências no painel antes de iniciar um lote.
- Não edite diretamente notas, pareceres ou auditorias no banco.
- Mantenha backup do banco antes de migrations e operações em lote.
- Mude a versão do prompt ou conhecimento quando houver alteração institucional relevante.
