# Manual do usuário — Ambiente de Avaliação por IA

## 1. Objetivo

Este manual apresenta a operação diária do ambiente de avaliação por inteligência artificial. O sistema possui duas etapas:

1. **Avaliação técnica:** analisa a inscrição conforme os critérios da categoria, atribui notas e registra justificativas.
2. **Seleção estratégica:** após a conclusão técnica, registra a decisão `INDICADA` ou `NAO_INDICADA` com justificativa.

A IA auxilia o processo, mas o sistema continua controlando permissões, critérios, escalas, quórum, status e prevenção de duplicidades. Resultados importantes devem ser acompanhados pela equipe responsável.

## 2. Perfis envolvidos

| Perfil | Responsabilidade |
|---|---|
| Administrador | Configurar a IA, testar a conexão, ativar as etapas e iniciar o processamento de pendências. |
| Comissão | Conferir inscrições e realizar habilitação ou rejeição conforme as regras do processo. |
| Avaliador técnico | Perfil usado para registrar pareceres e notas produzidos na avaliação técnica. |
| Indicador estratégico | Perfil usado para registrar a indicação produzida na seleção estratégica. |

Apenas administradores têm acesso às configurações da IA.

## 3. Visão geral do fluxo

```text
Inscrição recebida
        ↓
Conferência pela comissão
        ↓
Inscrição habilitada
        ↓
Avaliação técnica por IA
        ↓
Quórum técnico atingido
        ↓
Inscrição avaliada
        ↓
Seleção estratégica por IA
        ↓
Indicada ou não indicada
```

## 4. Acessar o ambiente

1. Entre no sistema com seu usuário e senha.
2. No menu administrativo, abra **Agente IA**.
3. Será exibida a página **Configurações de IA**, dividida em configuração, diagnóstico, operações pendentes e execuções recentes.

Se o item **Agente IA** não aparecer, solicite a um administrador a revisão do seu perfil.

## 5. Configurar a IA

### 5.1 Provider e modelo

Na seção **Configuração do LLM**:

1. escolha o provider: **Gemini** ou **OpenAI**;
2. informe o nome exato do modelo disponibilizado pelo provider;
3. informe a API Key na primeira configuração;
4. clique em **Salvar configurações**.

Depois de salva, a chave aparece somente de forma mascarada, por exemplo `Configurada ••••AB12`. Ao editar outras opções, deixe o campo da API Key vazio para manter a chave atual.

### 5.2 Avaliadores

Selecione:

- **Avaliador técnico:** usuário que registrará os pareceres e notas gerados pela IA;
- **Indicador estratégico:** usuário que registrará a decisão da etapa estratégica.

Esses usuários também precisam estar vinculados às categorias correspondentes. Sem esse vínculo, a inscrição não será apresentada como pendente.

### 5.3 Tempo e tentativas

- **Timeout:** tempo máximo de espera pela resposta da IA.
- **Connect timeout:** tempo máximo para estabelecer conexão com o serviço.
- **Tentativas:** quantidade máxima de novas tentativas em falhas temporárias.

Mantenha os valores definidos pela equipe técnica, salvo orientação específica.

### 5.4 Prompts

O **Prompt técnico** orienta notas e justificativas. O **Prompt estratégico** orienta a indicação final.

Antes de salvar uma alteração:

- descreva claramente o objetivo da análise;
- mantenha as regras institucionais e os critérios do edital;
- evite instruções contraditórias;
- não inclua senhas, tokens ou informações sigilosas;
- registre internamente o motivo da mudança.

Cada alteração gera automaticamente uma nova versão. Uma execução já iniciada permanece associada à versão usada naquele momento.

### 5.5 Base de conhecimento

O campo **Knowledge version** identifica a versão dos documentos institucionais disponíveis para consulta da IA. Ele não envia documentos pelo painel.

Enquanto não houver documentos aprovados, deixe esse campo vazio. A inclusão dos arquivos é uma atividade da equipe técnica.

## 6. Testar antes de ativar

Após salvar provider, modelo e API Key:

1. localize a seção **Diagnóstico**;
2. clique em **Testar conexão**;
3. aguarde a mensagem de resultado.

Uma resposta positiva confirma que o Laravel alcança o FastAPI e que a configuração do provider pode ser carregada. Esse teste não cria parecer, nota, indicação nem altera a inscrição.

Não ative o processamento se o diagnóstico informar que o FastAPI está indisponível ou que o provider não está pronto.

## 7. Ativar ou interromper o processamento

Na configuração existem duas opções:

- **Avaliação técnica ativa:** permite criar e executar avaliações técnicas;
- **Seleção estratégica ativa:** permite executar a seleção depois da avaliação.

Para iniciar a rotina normal, marque as opções necessárias e salve. Para uma interrupção emergencial, desmarque a etapa correspondente e salve novamente. A desativação não apaga resultados existentes.

## 8. Avaliar novas inscrições

### 8.1 Conferir e habilitar

1. Abra a lista de inscrições.
2. Acesse a inscrição desejada.
3. Confira os dados e documentos disponíveis.
4. Se estiver regular, use a ação **Habilitar**.
5. Se não estiver regular, siga o procedimento institucional de rejeição.

A habilitação dispara automaticamente a avaliação técnica quando ela estiver ativa. Não é necessário executar comandos.

### 8.2 Resultado técnico

A IA gera uma justificativa e uma nota para cada critério. O sistema valida:

- identificação da inscrição e do avaliador;
- critérios esperados;
- limites mínimo e máximo das notas;
- versão do prompt;
- integridade dos dados usados na análise.

Quando o quórum exigido é alcançado, a inscrição muda para **Avaliado**. Se dados relevantes forem alterados durante a chamada, o resultado é descartado por segurança e pode ser processado novamente.

### 8.3 Seleção estratégica

Quando a inscrição chega ao status **Avaliado**, a seleção estratégica é iniciada automaticamente se estiver ativa. Ela considera a proposta e os pareceres técnicos e registra:

- `INDICADA`; ou
- `NAO_INDICADA`.

A seleção estratégica não altera notas ou pareceres técnicos.

## 9. Processar pendências pelo painel

A página **Agente IA** mostra duas listas:

- **Avaliações técnicas:** inscrições habilitadas que ainda precisam da avaliação configurada;
- **Seleções estratégicas:** inscrições avaliadas que ainda precisam da indicação configurada.

Antes de processar, confira a quantidade e os registros exibidos.

### Processar em fila

Use **Processar em fila** para iniciar todas as pendências da seção. Confirme a operação quando solicitado. Essa opção é indicada para lotes e depende do serviço de fila mantido pela infraestrutura.

### Processar 1 agora

Use **Processar 1 agora** para executar imediatamente uma única inscrição. Essa opção é útil para teste operacional ou processamento pontual e pode levar alguns segundos.

O sistema utiliza identificação única e bloqueios para evitar duplicidade em cliques repetidos ou solicitações simultâneas.

## 10. Acompanhar as execuções

Na seção **Execuções recentes**, observe:

| Campo | Significado |
|---|---|
| ID | Identificador da execução para suporte e auditoria. |
| Inscrição | Registro avaliado. |
| Tipo | Avaliação técnica ou seleção estratégica. |
| Status | Situação atual do processamento. |
| HTTP | Código retornado pelo serviço de IA, quando disponível. |
| Atualização | Data e hora da última movimentação. |

Os status são:

| Status | Significado | Ação recomendada |
|---|---|---|
| `pending` | Aguardando processamento. | Aguarde; se permanecer assim, acione o suporte. |
| `processing` | Avaliação em andamento. | Aguarde a conclusão. |
| `completed` | Resultado validado e salvo. | Nenhuma ação necessária. |
| `failed` | A execução não foi concluída. | Verifique o diagnóstico e tente novamente após corrigir a causa. |

## 11. Problemas comuns

### FastAPI indisponível

- não inicie novos lotes;
- aguarde alguns minutos e atualize a página;
- se persistir, informe à equipe técnica que o diagnóstico mostra **FastAPI indisponível**.

### Provider não está pronto

Confira provider, nome do modelo e existência da API Key. Salve e execute **Testar conexão** novamente. Nunca envie a chave em mensagens, chamados ou capturas de tela.

### A inscrição não aparece como pendente

Confira se:

- ela está em **Habilitado** para avaliação técnica ou **Avaliado** para seleção;
- a etapa está ativa;
- o avaliador selecionado está associado à categoria;
- já existe uma execução concluída para a mesma configuração.

### Execução permanece em `pending`

O processamento em fila depende do worker mantido pela infraestrutura. Informe à equipe técnica o ID da execução.

### Execução com `failed`

Teste a conexão e verifique se a falha é geral. Depois da correção, processe novamente pela seção adequada. A proteção de idempotência impede a criação de resultado duplicado.

### Resultado foi descartado

Isso ocorre quando a inscrição, critérios, escalas, pareceres ou notas mudam durante a chamada. Confira os dados e processe novamente.

## 12. Segurança e boas práticas

- Nunca compartilhe API Keys, tokens ou senhas.
- Não coloque credenciais nos prompts.
- Não altere critérios ou escalas enquanto existirem avaliações em andamento.
- Revise cuidadosamente qualquer mudança de prompt antes de ativá-la.
- Confira as pendências antes de confirmar um lote.
- Não edite notas, pareceres, indicações ou registros de auditoria diretamente no banco.
- Não trate a IA como única responsável pela decisão institucional.

## 13. Checklist do administrador

### Antes de ativar

- [ ] Provider e modelo conferidos.
- [ ] API Key indicada como configurada.
- [ ] Avaliador técnico associado às categorias.
- [ ] Indicador estratégico associado às categorias.
- [ ] Critérios e escalas revisados.
- [ ] Prompts revisados.
- [ ] Teste de conexão aprovado.

### Durante a operação

- [ ] FastAPI aparece como disponível.
- [ ] Pendências estão diminuindo.
- [ ] Execuções recentes chegam a `completed`.
- [ ] Não há aumento contínuo de registros `failed`.

### Ao alterar um prompt

- [ ] Motivo da alteração registrado.
- [ ] Texto revisado por responsável institucional.
- [ ] Nova versão exibida após salvar.
- [ ] Primeira execução da nova versão acompanhada.

## 14. Informações para solicitar suporte

Ao abrir um chamado, informe:

- ID da execução;
- ID da inscrição;
- tipo da operação;
- status apresentado;
- data e hora do problema;
- mensagem exibida na tela;
- se o teste de conexão foi aprovado.

Não informe a API Key nem o token do serviço.

## 15. Nota para a equipe técnica

O uso cotidiano deve ser realizado integralmente pelo painel. O FastAPI e o worker da fila precisam permanecer ativos pela infraestrutura. Os comandos Artisan continuam disponíveis apenas para manutenção e automação. Os documentos aprovados devem ser colocados em `ai-service/app/knowledge/documents`; o painel administra somente a versão de conhecimento.
