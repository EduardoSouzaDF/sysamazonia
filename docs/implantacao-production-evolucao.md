# Implantação de production-evolucao

Esta branch reúne os ajustes do julgamento e das boas-vindas, os guias interativos das comissões, a exportação do dashboard em PDF e as correções de recuperação de senha e edição de usuários.

## Requisitos

- Manter a configuração `.env` e a `APP_KEY` existentes do servidor.
- PHP e extensões compatíveis com `composer.lock`, Composer e Node compatível com o Vite 7 (Node 20.19+ ou 22.12+).
- Executar a implantação na raiz da aplicação, usando o remoto correspondente ao servidor: `origin` neste workspace identifica sysamazonia e `origin-anterior` identifica app_premios. O nome do remoto no servidor pode ser diferente.
- Fazer backup do banco antes da migration e registrar o commit atualmente instalado.
- Não executar a suíte de testes contra o banco de produção.

## Publicação

Confira que o checkout do servidor não contém alterações locais antes de atualizar. No exemplo abaixo, `origin` é o remoto escolhido no servidor:

```bash
umask 0002
git fetch origin
git switch production-evolucao
git pull --ff-only origin production-evolucao
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Na primeira implantação, se a branch não existir localmente, use `git switch --track origin/production-evolucao` no lugar de `git switch production-evolucao`.

Execute `php artisan up` somente depois que todos os passos terminarem com sucesso. A migration deve ser aplicada antes de liberar o acesso: o cabeçalho das comissões consulta a nova tabela `user_guide_progress`. `migrate --force` também aplica outras migrations pendentes; confira `php artisan migrate:status` antes da publicação.

O build pode ser realizado no pipeline e enviado ao servidor. Nesse caso, substitua os comandos npm pela publicação de `public/build` completo, incluindo o manifest. Esse diretório não é versionado. As novas dependências JavaScript `jspdf` e `jspdf-autotable` estão fixadas em `package-lock.json`; use `npm ci`.

Não há novas variáveis de ambiente ou dependências Composer nesta entrega. O `composer install` regenera o autoload das novas classes. A recuperação de senha continua usando a configuração de e-mail existente.

## Permissões de cache

O usuário de implantação e o processo PHP precisam conseguir escrever em `storage` e `bootstrap/cache`. Eles devem usar um grupo compartilhado com escrita nos arquivos e diretórios. Gere caches com `umask 0002` ou execute Artisan com o usuário do serviço PHP.

Foi identificado no ambiente local um erro `Permission denied` em `storage/framework/views`: uma view compilada pelo usuário do terminal tinha modo `0644`, impedindo o servidor de sobrescrevê-la. Não publique arquivos dessa pasta. Para corrigir um ambiente com esse problema, um administrador pode ajustar o grupo compartilhado real e as permissões, restritas a esses diretórios:

```bash
# Substitua GRUPO_COMPARTILHADO pelo grupo real de implantação/PHP.
sudo chgrp -R GRUPO_COMPARTILHADO storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} +
sudo find storage bootstrap/cache -type f -exec chmod 0664 {} +
php artisan view:clear
umask 0002
php artisan view:cache
```

Recarregue PHP/OPcache ou Octane quando a configuração do servidor mantiver código antigo em memória. Não há alteração específica de jobs de IA nesta entrega.

## Conferência após publicar

- Entre como julgador e confira as boas-vindas, o menu Iniciar julgamento e a descrição da categoria.
- Confira a conclusão: novo título e agradecimento, botão verde de saída à direita e ausência do título Iniciar julgamento nessa tela.
- Abra Rever orientações e confirme que o progresso é salvo sem registrar votos ou avaliações automaticamente.
- Gere o PDF do dashboard com um usuário autorizado.
- Valide recuperação de senha e edição de um usuário sem dados extras.

O envio ao Git não executa essas etapas no servidor. Para reverter o código, restaure o commit anterior e refaça autoload, build e caches. A tabela de progresso pode permanecer no banco; não remova dados nem execute rollback indiscriminadamente.
