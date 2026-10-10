# Guias interativos das comissões

## Comportamento

O ambiente oferece boas-vindas no primeiro acesso de avaliadores, indicadores e julgadores. Ao abrir uma funcionalidade pela primeira vez, oferece as orientações correspondentes em balões sobre a própria página.

Cada trecho do guia pode ser iniciado, adiado, retomado ou revisto. “Rever orientações” fica no cabeçalho e também no rodapé da modal de julgamento. Os balões usam navegação por botões, foco por teclado e fechamento pela tecla Escape; não bloqueiam a operação da página.

Os guias incluem:

- Avaliador: pesquisa, abertura da inscrição, conteúdo, anexos, critérios, pontuação, justificativa e botão Avaliar.
- Indicador: pesquisa, abertura da inscrição, leitura do conteúdo e anexos. Não foi criado um novo formulário de indicação. As etapas de envio que estavam sinalizadas para confirmação no documento editorial permanecem fora do guia.
- Julgador: categoria, cota, cartões, marcadores, limpar, confirmação da categoria, detalhes da inscrição, acompanhamento e conclusão. Os guias de detalhes e acompanhamento aparecem quando suas respectivas áreas estão disponíveis.
- Situações sem inscrições ou sem categorias disponíveis, e omissão de anexos quando não existem.

A modal de detalhes é centralizada. O botão Escolher esta inclui a proposta na seleção; Confirmar votos, no painel, grava as escolhas da categoria. As duas ações são explicadas separadamente. Nenhum passo envia formulários ou registra escolhas automaticamente. A conclusão do guia não exige concluir uma avaliação ou julgamento real.

## Progresso e permissões

A tabela `user_guide_progress` guarda usuário, identificador do guia, versão, etapa, situação e datas. A restrição única por usuário/guia/versão evita duplicações. O progresso acompanha o usuário entre computadores e não depende de armazenamento no navegador.

A versão inicial é 1. Alterações futuras que exijam nova apresentação devem atualizar `CommissionGuides::VERSION`. A versão enviada pelo navegador é validada contra a versão do servidor.

Os endpoints exigem autenticação. O servidor obtém o usuário da sessão, valida o perfil e permite salvar somente os guias correspondentes às funções atuais. A revogação de uma função impede novas gravações daquele perfil. A função de avaliador ou indicador exige também o papel ativo `comissao`; o julgador é identificado por `is_judge`.

Se houver falha de conexão ao salvar, o guia informa o erro e permite tentar novamente ou fechar sem salvar. Nessa situação, permanece no servidor a última etapa salva com sucesso.

## Arquivos principais

- Textos dos balões: `resources/js/commission-guide-content.js`.
- Interface e navegação: `resources/js/commission-guides.js`.
- Estilos: `resources/css/commission-guides.css`.
- Perfis, versão e leitura do progresso: `app/Services/CommissionGuides.php`.
- Validação e gravação: `app/Http/Controllers/CommissionGuideController.php`.
- Inclusão no cabeçalho: `resources/views/admin/partials/commission-guides.blade.php`.
- Migration: `database/migrations/2026_10_09_200000_create_user_guide_progress_table.php`.

O JavaScript é incorporado ao `resources/js/app.js` existente pelo Vite. Não foram adicionadas dependências ao projeto. Os controles existentes receberam atributos para identificar as etapas sem depender de seus textos ou de posição na página.

## Implantação

Aplicar a migration antes de liberar o código atualizado aos usuários de comissão, pois o cabeçalho passa a consultar a tabela de progresso. Executar o procedimento habitual de publicação do servidor, incluindo:

```bash
php artisan migrate --force
npm ci
npm run build
php artisan route:cache
php artisan view:cache
```

A geração de arquivos deve ocorrer no ambiente de build ou no servidor, conforme o procedimento existente. O diretório `public/build`, incluindo o manifest, precisa estar presente na implantação.

Não há novas variáveis de ambiente ou pacotes Composer. Caso o procedimento de produção use autoload autoritativo, regenerar o autoload para incluir as novas classes:

```bash
composer dump-autoload --optimize
```

Recarregar processos que mantenham o código em memória, como Octane ou PHP com OPcache sem verificação de alterações, conforme a configuração do servidor. O recurso não altera os jobs de IA nem exige reinício específico de filas.

A migration foi aplicada somente ao banco local durante o desenvolvimento. Isso não representa aplicação ao banco do servidor de produção.

## Verificação

Em ambiente de testes isolado:

```bash
php artisan test --compact --filter=CommissionGuideTest
node --test tests/js/commission-guides.test.mjs
```

Os testes PHP usam `RefreshDatabase`; não executá-los apontando para o banco real de produção.

Na interface, verificar:

1. Usuário sem perfil de comissão não recebe o botão nem o guia.
2. Avaliador, indicador e julgador recebem somente os guias permitidos.
3. Ver depois permite retomar no próximo acesso.
4. Guia concluído não reaparece automaticamente, mas pode ser revisto.
5. Usuário com várias funções pode escolher os guias disponíveis na tela.
6. A modal permite usar o guia por teclado e fechar o balão com Escape sem fechar a modal.
7. Em celular, os balões permanecem dentro da tela.
8. Avançar ou concluir orientações não cria opiniões, indicações ou votos.

## Relação com a proposta editorial

O documento `docs/guia-interativo-comissoes-textos-para-revisao.md` foi usado como base. As orientações foram separadas por tela para permitir trabalhar sem terminar uma operação real apenas para acompanhar o guia. As etapas de envio da indicação IN-06 e IN-07 não foram implementadas, conforme a condição registrada naquele documento.

## Validação realizada no desenvolvimento

- Suíte PHP: 168 testes aprovados, com 1.096 verificações.
- Três arquivos de testes JavaScript aprovados.
- Compilação Vite, cache de rotas, compilação das views e verificação de estilo PHP aprovados.
- Navegador Chromium, com banco SQLite temporário e dados fictícios: primeiro acesso, retomada após recarregar, navegação dentro da modal Shoelace, Escape sem fechar a modal, botão de revisão na modal, posicionamento em tela de 390 × 844, usuário com múltiplas funções, erro de gravação com nova tentativa e guia da avaliação.
- Após percorrer os guias, o banco de teste continuou com zero opiniões, indicações e votos; somente o progresso dos guias foi gravado.

Esta implementação integra a entrega `production-evolucao`. Consulte `docs/implantacao-production-evolucao.md` para o procedimento completo de publicação.
