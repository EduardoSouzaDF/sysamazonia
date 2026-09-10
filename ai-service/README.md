# Serviço de IA Sisamazonia

FastAPI + Agno, sem acesso ao banco Laravel. Python 3.11+; CI usa 3.13. Instalação a partir da raiz:

```bash
python3 -m venv ai-service/.venv
ai-service/.venv/bin/python -m pip install -r ai-service/requirements.txt
cp ai-service/.env.example ai-service/.env
```

Ativação opcional: Linux/macOS `source ai-service/.venv/bin/activate`; Windows `ai-service\.venv\Scripts\activate`. No Windows, use `python -m uvicorn app.main:app --host 127.0.0.1 --port 8000` dentro de `ai-service`.

Configure `AI_SERVICE_TOKEN` com o mesmo segredo persistente do Laravel; não muda em restart. Settings sempre lê o `.env` deste diretório por caminho absoluto UTF-8, mas ambiente do processo prevalece. Configuração inválida falha no startup sem imprimir valores.

```bash
bash scripts/ai-service.sh
curl --fail http://127.0.0.1:8000/health
PYTHONPATH=ai-service ai-service/.venv/bin/python -m pytest ai-service/tests -q
python3 scripts/test-ai-restart.py
```

Endpoints:

- `GET /health`: público, somente liveness.
- `GET /v1/diagnostics`: Bearer interno, autenticação sem provider.
- `GET /ready`: contrato legado, configurações de provider do ambiente.
- `POST /v1/configuration/test`: Bearer, acesso a metadados do modelo; sem inferência.
- `POST /v1/configuration/models`: Bearer, lista de sugestões; cache no Laravel.
- `POST /v1/evaluations/technical` e `/selection`: contratos Pydantic existentes, validação estrutural/semântica e prompts versionados.

Providers: OpenAI, Gemini, Anthropic, Mistral, Groq, OpenAI-compatible e Local. Runtime enviado pelo Laravel prevalece sobre defaults; chaves no ambiente Python são fallback legado. Configure preferencialmente pelo painel, sem duplicação de credenciais. Local aceita chave opcional, tipo Ollama (URL sem `/v1`) ou genérico (URL completa). `AI_LOCAL_ALLOWED_URLS` e `AI_CUSTOM_ALLOWED_URLS` são allowlists exatas mantidas pela infraestrutura; custom somente HTTPS/IPs públicos e local somente IP literal privado/loopback. Sem redirects; respostas limitadas. Não é necessário instalar Ollama para usar providers comerciais.

401 interno significa token rejeitado; connection refused significa processo/rede/porta. Provider 401/429/timeout tem código separado em resposta sanitizada. Reinicie pelo script ou Supervisor após mudar ambiente; não regenere token. Detalhes de instalação, Docker/host, SSL, quotas, modelo manual, timeouts e segurança no [README principal](../README.md).

Referências oficiais usadas nos adapters:

- [OpenAI Models](https://developers.openai.com/api/reference/resources/models/methods/list) e [Chat Completions](https://developers.openai.com/api/reference/resources/chat/subresources/completions/methods/create).
- [Gemini Models](https://ai.google.dev/api/models).
- [Anthropic Models](https://platform.claude.com/docs/en/api/models/list) e [Messages](https://platform.claude.com/docs/en/api/messages/create).
- [Mistral Models](https://docs.mistral.ai/api/endpoint/models) e [migração OpenAI](https://docs.mistral.ai/resources/migration-guides).
- [Groq OpenAI compatibility](https://console.groq.com/docs/openai).
- [Ollama OpenAI compatibility](https://docs.ollama.com/api/openai-compatibility).

A compatibilidade do protocolo não garante que todo modelo cumpra o schema institucional; respostas inválidas nunca viram avaliações válidas.
