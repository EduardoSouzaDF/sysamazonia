# Relatório de implantação e guia para evolução

**Projeto:** App Prêmios — Sisamazonia

**Data do levantamento:** 04/09/2026

**Branch publicada:** `main`

**Repositório:** `git@github.com:DouglasMuller/app_premios.git`

**Commit funcional:** `a65b397` (`feat: adiciona fluxo de avaliações por IA`)

**Commit de integração com o remoto:** `6e290ac`

## 1. Resumo executivo

Foi implantada uma aplicação Laravel para gestão de prêmios, com cadastro de edições, modalidades, categorias, critérios, usuários, candidatos, inscrições e indicados. O sistema possui autenticação, perfis de acesso, painel estatístico, habilitação/rejeição de inscrições, pareceres humanos e um novo fluxo assíncrono de avaliação por IA.

A solução de IA é dividida em dois serviços: Laravel mantém as regras e os dados; um serviço Python/FastAPI executa inferências estruturadas por OpenAI ou Gemini. A integração possui autenticação por token, fila, retentativas, validação de respostas, transações, idempotência e trilha de auditoria.

No teste operacional desta implantação, quatro inscrições habilitadas foram avaliadas pela IA, receberam parecer e passaram para `Avaliado`. As médias geradas foram 50, 45, 93 e 55. Ao final, não havia registros habilitados nem trabalhos restantes na fila de avaliação.

## 2. Tecnologias e componentes

| Camada | Tecnologia | Responsabilidade |
|---|---|---|
| Aplicação principal | PHP 8.2+ / Laravel 12 | Interface, autenticação, regras, persistência e integração |
| Interface | Blade, JavaScript, CSS, Vite 7, Tailwind 4 | Telas administrativas e formulários |
| Banco | MySQL | Dados de negócio, sessões, cache, filas e auditoria |
| Fila | Laravel Queue com driver `database` | Avaliações assíncronas e retentativas |
| Serviço de IA | Python / FastAPI / Agno | Avaliação técnica e seleção estratégica |
| Provedores de LLM | OpenAI ou Gemini | Inferência com saída estruturada |
| Testes | PHPUnit 11 e pytest | Testes do Laravel e do serviço Python |
| Versionamento | Git / GitHub | Branch principal `main` |

## 3. Funcionalidades implantadas

### 3.1 Acesso e usuários

- Login, logout, recuperação e redefinição de senha.
- Cadastro e administração de usuários.
- Perfis ativos `admin` e `comission`, armazenados em `roles` e `user_role`. O enum também prevê `LEITOR`, mas o seeder atual não cria esse perfil.
- Sinalizadores de jurado e organizador no usuário.
- Associação de avaliadores e indicadores às categorias.
- Recurso administrativo de entrar como outro usuário e retornar ao administrador.
- Middleware para restringir operações administrativas por perfil.

### 3.2 Estrutura dos prêmios

- Edições, incluindo regulamento.
- Modalidades vinculadas a edições.
- Categorias vinculadas a modalidades.
- Comissões, avaliadores e indicadores vinculados ao contexto da premiação.
- Critérios de avaliação por categoria, com peso, escala, descrição, rubrica JSON e versão da rubrica.
- Quantidade de pareceres exigida por categoria (`evaluations_count`).

### 3.3 Inscrições e indicações

- Cadastro público de candidatos, inscrições e indicados.
- Consulta de candidato por CPF na API.
- Upload e consulta de arquivos das inscrições/indicados.
- Protocolo e tokens de ação para fluxos públicos.
- Estados de inscrição: `Inscrito`, `Rejeitado`, `Habilitado`, `Avaliado` e `Agraciado`.
- Ações administrativas de habilitar, rejeitar e indicar.
- Listagem contextual conforme perfil e categoria do avaliador.

### 3.4 Avaliação humana

- Emissão de parecer por avaliador autorizado.
- Uma nota e uma justificativa para cada critério.
- Validação de presença, escala e integridade dos critérios.
- Bloqueio de parecer duplicado para a mesma inscrição e avaliador.
- Cálculo da pontuação ponderada pelo Laravel.
- Conclusão apenas depois de alcançado o quórum configurado de pareceres completos.

### 3.5 Painel

- Gráficos de registros por edição.
- Gráficos por modalidade.
- Distribuição geográfica por estado.
- Distribuição por sexo.

## 4. Fluxo implantado de avaliação por IA

```text
Inscrição muda para Habilitado
  → evento RegistrationStatusChanged
  → cria AiExecution de avaliação técnica
  → fila ai-evaluations
  → FastAPI /v1/evaluations/technical
  → valida notas e justificativas
  → grava Opinion + Scores em transação
  → recalcula média e verifica quórum
  → inscrição muda para Avaliado
  → opcionalmente dispara seleção estratégica
  → FastAPI /v1/evaluations/selection
  → grava Indication com decisão e justificativa
```

### Controles existentes

- Feature flags independentes para avaliação técnica e seleção estratégica.
- Avaliadores técnico e estratégico configurados por ID.
- Verificação de que o usuário existe e está associado à categoria.
- Bearer token entre Laravel e FastAPI.
- HTTPS obrigatório para destinos remotos; HTTP permitido apenas localmente.
- Payload técnico sem dados pessoais do candidato e sem pesos dos critérios.
- Resposta estruturada e validação completa antes de gravar.
- Gravação transacional, sem parecer parcial.
- Restrições únicas no banco para impedir duplicidade.
- Fingerprint SHA-256 da configuração de critérios.
- Descarte do resultado se o status ou os critérios mudarem durante a execução.
- Retentativas com backoff de 15, 60 e 180 segundos para falhas transitórias.
- Falhas de autenticação, configuração, schema ou regra não são repetidas.
- Lock contra processamento concorrente da mesma execução.
- Registro de status, tentativas, duração, provedor, modelo, versões e códigos de erro em `ai_executions`.
- Logs correlacionados sem registrar token ou texto integral da proposta.

### Configuração necessária

Laravel utiliza `AI_EVALUATION_ENABLED`, `AI_SELECTION_ENABLED`, `AI_SERVICE_URL`, `AI_SERVICE_TOKEN`, IDs dos avaliadores, timeouts, número de tentativas, fila e versão do conhecimento. O FastAPI utiliza o mesmo token, provedor/modelo de LLM, credenciais do provedor e timeout próprio.

As configurações reais ficam somente nos arquivos locais de ambiente. `.env`, `.env.local`, caches, configurações de IDE e arquivos `Zone.Identifier` estão excluídos do Git.

## 5. Modelo de dados

Principais grupos de tabelas:

- **Infraestrutura Laravel:** `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.
- **Acesso:** `roles`, `user_role`, `user_extra_data`.
- **Estrutura do prêmio:** `editions`, `modalities`, `categories`, `commissions`, `evaluation_criteria`, `evaluators`, `indicators`.
- **Participação:** `candidates`, `registrations`, `registrations_files`, `nominees`, `action_tokens`.
- **Avaliação:** `opinions`, `scores`, `indications`, `ai_executions`.

Relação central:

```text
Edition 1─N Modality 1─N Category
                           ├─N EvaluationCriterion
                           ├─N Registration N─1 Candidate
                           ├─N Nominee
                           ├─N Evaluator (User, N─N)
                           └─N Indicator (User, N─N)

Registration 1─N Opinion 1─N Score N─1 EvaluationCriterion
Registration 1─N Indication
Registration 1─N AiExecution
```

Exclusões em cascata existem em partes do domínio. Em especial, apagar inscrições ou critérios pode apagar auditorias ou notas relacionadas. Mudanças nas migrations devem ser planejadas com backup e teste de rollback.

## 6. Rotas e superfícies de integração

### Web

- Regulamentos e formulário público de edição.
- Autenticação e recuperação de senha.
- Dashboard autenticado.
- CRUD administrativo de usuários, edições, modalidades, categorias e critérios.
- Consulta e operações sobre inscrições.
- Envio de parecer, indicação e acesso a arquivos.

### API Laravel

- `GET /api/has-registrations`
- `POST /api/registration`
- `GET /api/candidato/{cpf}`
- `GET /api/requestToken/{protocol}/{actionType}`
- `GET|POST /api/verifyToken/{token}`

### API interna de IA

- `GET /health`
- `POST /v1/evaluations/technical`
- `POST /v1/evaluations/selection`

## 7. Validação realizada

| Verificação | Resultado |
|---|---|
| PHPUnit/Laravel | 24 testes aprovados, 63 asserções |
| Build Vite de produção | Concluído |
| Teste operacional da fila/IA | 4 de 4 avaliações concluídas |
| Fila ao fim do processamento | 0 itens |
| Habilitados sem avaliação ao fim | 0 |
| pytest/FastAPI | 18 testes aprovados em ambiente virtual isolado |

O build emite alertas sobre uso de `eval` em um bundle legado e chunks maiores que 500 kB. Ele termina com sucesso, mas esses pontos devem entrar no backlog de front-end e segurança.

## 8. Pontos ainda não implantados ou incompletos

- A carga controlada de conhecimento está implementada, mas ainda depende do fornecimento e aprovação dos documentos institucionais.
- Não há interface administrativa específica para acompanhar/reprocessar `ai_executions`; o backfill seguro está disponível pelo comando `ai:evaluate-habilitados`.
- Há um modelo de Supervisor em `deploy/supervisor`, mas sua instalação no servidor continua sendo uma etapa operacional.
- A cobertura de testes fora do fluxo de IA é pequena.
- A execução automatizada de testes e build foi configurada em `.github/workflows/ci.yml`.
- O README ainda possui instruções antigas e trechos que merecem revisão editorial.

## 9. Riscos e dívida técnica

### Prioridade alta

1. **Proteção das APIs públicas:** revisar autenticação, autorização, rate limiting, validação de CPF/protocolo e exposição de dados.
2. **Operação da fila e FastAPI:** configurar Supervisor/systemd, reinício automático, logs, health checks e alertas.
3. **Backup e recuperação:** definir backup do MySQL, retenção e teste de restauração antes de migrations destrutivas.
4. **Segredos:** manter tokens e chaves fora do Git, com rotação e permissão mínima.
5. **Dados pessoais/LGPD:** documentar base legal, retenção, acesso e exclusão de CPF, endereço e documentos.

### Prioridade média

1. Adicionar CI para PHP, Python e build do front-end.
2. Criar testes para autenticação, permissões, CRUDs, uploads e APIs públicas.
3. Criar painel operacional da IA com filtro por status/erro e reprocessamento autorizado.
4. Criar comando seguro e idempotente de backfill de habilitados.
5. Padronizar nomes em português/inglês e corrigir migrations/arquivos com nomes inconsistentes.
6. Revisar SQL manual do dashboard e adicionar testes sobre os indicadores.
7. Remover ou substituir bundles que usam `eval` e dividir arquivos front-end grandes.

### Prioridade baixa

1. Revisar README e documentação de onboarding.
2. Padronizar estilo do código com Pint e ferramentas Python.
3. Expandir indicadores e relatórios gerenciais.

## 10. Plano recomendado para executar alterações

### Fase 1 — estabelecer uma base segura

1. Criar uma branch a partir de `main`: `git switch -c tipo/nome-da-alteracao`.
2. Registrar objetivo, regras afetadas e critérios de aceite.
3. Fazer backup quando a mudança envolver banco ou dados reais.
4. Mapear controller, service, model, migration, job e testes afetados.
5. Usar flags para mudanças operacionais de alto risco.

### Fase 2 — implementar

1. Criar migrations aditivas e reversíveis; evitar editar migrations já aplicadas.
2. Colocar regras de negócio em services, mantendo controllers pequenos.
3. Usar Form Requests/policies ou middleware para validação e autorização.
4. Preservar idempotência e transações nos fluxos de fila/IA.
5. Ao mudar prompts, criar nova versão; não alterar silenciosamente uma versão existente.
6. Ao mudar critérios, considerar o fingerprint e execuções em andamento.

### Fase 3 — validar

1. Executar `composer test`.
2. No ambiente Python correto, executar `cd ai-service && PYTHONPATH=. pytest`.
3. Executar `npm run build`.
4. Testar manualmente permissões e o caminho principal alterado.
5. Para IA, testar sucesso, timeout, resposta inválida, reenvio e mudança concorrente de status/configuração.

### Fase 4 — publicar e operar

1. Revisar o diff e procurar segredos/arquivos gerados.
2. Criar commit descritivo e abrir PR ou integrar conforme a política do projeto.
3. Publicar código, instalar dependências e executar migrations com backup.
4. Limpar cache de configuração e reiniciar workers/FastAPI quando necessário.
5. Executar smoke test e monitorar logs, jobs com falha e `ai_executions`.
6. Manter um procedimento de rollback de código, configuração e banco.

## 11. Ordem sugerida para o próximo ciclo

1. Configurar CI no GitHub.
2. Implantar gestão de processos e monitoramento da fila/serviço de IA.
3. Criar painel e comando de reprocessamento/backfill da IA.
4. Reforçar segurança e testes das APIs públicas.
5. Formalizar privacidade/LGPD e retenção de dados.
6. Ativar Knowledge/RAG somente depois de definir documentos aprovados, governança e testes de qualidade.
7. Tratar bundles legados e desempenho do front-end.

## 12. Checklist de impacto para cada solicitação futura

- Qual problema e qual usuário a mudança atende?
- Quais perfis podem ver ou executar a nova função?
- Há mudança de schema, dados existentes ou necessidade de backfill?
- Há dados pessoais ou segredos envolvidos?
- A mudança afeta status, quórum, média, parecer ou indicação?
- A mudança afeta payload, schema, prompt ou versão da IA?
- O fluxo é idempotente e seguro para retentativa?
- Quais testes automatizados e manuais comprovam o aceite?
- Como implantar, observar e reverter?

Este documento deve ser atualizado sempre que houver alteração relevante de arquitetura, banco, integração, operação ou regra de avaliação.
