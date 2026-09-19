# Implantação de homologação via SSH — Sisamazonia

Guia para instalar e atualizar **esta versão do repositório**, incluindo Laravel, dashboard, configurações de IA, FastAPI e processamento em fila. Não foi executado no servidor de homologação. Os nomes, domínio e senhas abaixo são exemplos e devem ser substituídos.

O procedimento usa uma única máquina Linux com **systemd, Nginx, PHP-FPM 8.4 e MySQL 8**. O banco pode estar em servidor separado. Os comandos `apt-get` são para Debian/Ubuntu com os repositórios necessários configurados; não devem ser usados em outra distribuição. Se a infraestrutura já usa Apache, mantenha o servidor existente e adapte o VirtualHost conforme a seção 12.

## 1. O que precisa ser transferido

| Item | Como transferir | Observação |
|---|---|---|
| Código, migrations e arquivos do mapa | Git | Branch `feature/production-avaliacaoIA` |
| Dependências PHP | `composer install` | Usar o `composer.lock` versionado |
| Frontend compilado | `npm ci` + `npm run build` | `public/build` não está no Git |
| Dependências Python | Virtualenv + pip | `.venv` não está no Git |
| Configuração Laravel | `.env` criado/copied com segurança | Não versionar credenciais |
| Configuração FastAPI | `ai-service/.env` | Token interno igual ao Laravel |
| Dados existentes | Dump/restauração MySQL | Git e migrations não levam as 255 inscrições |
| Anexos e regulamentos | `storage/app/private` e `storage/app/public` | Copiar separadamente do banco |

**Ao migrar dados existentes, leve a mesma `APP_KEY` da origem.** A chave de API salva em `ai_settings.api_key` é criptografada. Gerar uma nova `APP_KEY` inutiliza a descriptografia desses valores. Use `key:generate` apenas no caminho de banco novo, vazio.

## 2. Requisitos reais desta versão

- **PHP 8.4**, tanto CLI quanto FPM. Embora `composer.json` declare `^8.2`, o `composer.lock` fixa componentes Symfony 8 que exigem `>=8.4`; Larapex também exige PHP `^8.3`. PHP 8.2/8.3 não instala este lock. Não usar `--ignore-platform-reqs`.
- Composer 2, Git, Nginx, MySQL 8 e cliente `mysql`/`mysqldump`.
- Node.js **22.12+ da linha 22 ou 24 LTS**, com npm. O Vite fixado aceita `^20.19.0 || >=22.12.0`; para nova implantação use uma linha LTS mantida.
- Python **3.11+**, com `venv`; CI do projeto usa 3.13.
- PHP: PDO/MySQL, mbstring, XML/DOM, cURL, ZIP, OpenSSL, fileinfo e extensões padrão do Laravel. `pcntl` é necessário para os timeouts do worker CLI.
- Acesso SSH com `sudo`, domínio/DNS, certificado HTTPS, acesso de leitura ao repositório privado e saída HTTPS para provedores de IA.

Consulte `cat /etc/os-release` e `apt-cache policy php8.4-fpm` antes de instalar. Debian 13 oferece PHP 8.4; em sistemas cujo repositório não o oferece, a infraestrutura deve provisioná-lo por fonte aprovada antes de continuar. Não adicione automaticamente repositórios de terceiros nem altere o PHP de outros sites. Se já usa Apache, retire `nginx` da lista de pacotes abaixo e siga a seção 12 para o servidor web.

```bash
ssh usuario@IP_DO_SERVIDOR
cat /etc/os-release
sudo apt-get update
apt-cache policy php8.4-cli php8.4-fpm
sudo apt-get install -y git curl ca-certificates unzip rsync nginx composer \
    php8.4-cli php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-zip php8.4-bcmath python3 python3-venv
```

Instale o **MySQL 8 e seu cliente** conforme o repositório aprovado pela infraestrutura, ou use o banco já fornecido. `default-mysql-server` em Debian pode instalar MariaDB; isso não equivale à instalação MySQL validada neste projeto. Node pode ser fornecido pela infraestrutura ou pelo [distribuidor oficial](https://nodejs.org/en/download); o pacote `nodejs` antigo de algumas distribuições não atende ao Vite.

```bash
php8.4 -v
php8.4 -m
php8.4 -r 'exit(extension_loaded("pcntl") ? 0 : 1);'
composer --version
node --version
npm --version
python3 --version
mysql --version
mysqldump --version
sudo systemctl status php8.4-fpm nginx --no-pager
```

Nos comandos Composer deste guia, `php8.4 "$(command -v composer)"` força o interpretador correto. Se houver falha de certificado, corrija `ca-certificates` ou a CA corporativa; não desative TLS.

## 3. Publicar a branch certa — na máquina de desenvolvimento

Esta etapa ocorre **antes do clone no servidor**. Os commits desta sessão foram locais, sem push automático. No ambiente de desenvolvimento havia dois remotos:

- `origin`: `git@github.com:EduardoSouzaDF/sysamazonia.git`.
- `origin-anterior`: `git@github.com:DouglasMuller/app_premios.git`.

Escolha o destino de homologação. O exemplo abaixo usa o repositório **DouglasMuller** e não altera o `origin` existente:

```bash
git switch feature/production-avaliacaoIA
git status --short
git log -8 --oneline
git remote -v
git push origin-anterior HEAD:refs/heads/feature/production-avaliacaoIA
git ls-remote origin-anterior refs/heads/feature/production-avaliacaoIA
```

Confirme o SHA publicado com `git rev-parse HEAD`. Se o destino aprovado for o repositório EduardoSouzaDF, use `origin` e a URL correspondente em todas as etapas seguintes.

**Alterações não commitadas não serão transferidas.** No momento da elaboração do guia havia remoções locais do dashboard analítico antigo, separadas dos commits novos. Revise esse conjunto antes da publicação; não execute `git add .` nem `git reset --hard` para “resolver” o estado do diretório. Um clone conterá exatamente o que foi commitado, não necessariamente tudo que está visível no ambiente local.

## 4. Usuário de implantação e autenticação SSH do Git — no servidor

Use uma conta dedicada para Git/Composer/npm. Os serviços rodam como `www-data`, sem privilégios administrativos.

```bash
sudo adduser deploy
sudo usermod -aG www-data deploy
sudo install -d -o deploy -g www-data -m 2750 /var/www/sisamazonia
sudo -iu deploy
umask 0027
ssh-keygen -t ed25519 -C 'sisamazonia-homologacao' -f ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub
```

Cadastre **somente a chave pública** como deploy key de leitura no repositório selecionado. A chave privada permanece no servidor. Compare a fingerprint do GitHub com a [documentação oficial](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/githubs-ssh-key-fingerprints) ao aceitar a chave do host pela primeira vez.

```bash
ssh -T git@github.com
git clone --branch feature/production-avaliacaoIA --single-branch \
    git@github.com:DouglasMuller/app_premios.git /var/www/sisamazonia
cd /var/www/sisamazonia
git branch --show-current
git log -1 --oneline
```

O `ssh -T` do GitHub pode retornar código 1 mesmo autenticando, porque não fornece shell. A conta `deploy` deve ter autorização operacional para os comandos `sudo` seguintes; se não tiver, um administrador os executa. Não rode Composer/npm como root.

## 5. Criar o banco de homologação

Use **um banco e credenciais exclusivos de homologação**, nunca o banco de produção. No host do MySQL, como administrador:

```bash
sudo mysql
```

```sql
CREATE DATABASE sisamazonia_hmg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sisamazonia_hmg'@'127.0.0.1' IDENTIFIED BY 'SUBSTITUA_POR_UMA_SENHA_FORTE';
GRANT ALL PRIVILEGES ON sisamazonia_hmg.* TO 'sisamazonia_hmg'@'127.0.0.1';
EXIT;
```

Use outro host permitido na conta se o banco for remoto. Ajuste `DB_HOST` e os comandos MySQL de acordo. Esta conta tem permissões de DDL nesse banco para migrations; nenhuma permissão global é necessária para a aplicação.

```bash
mysql -h 127.0.0.1 -u sisamazonia_hmg -p sisamazonia_hmg -e 'SELECT DATABASE();'
```

### 5A. Instalação nova, sem dados

Não importe um dump. Continue para a configuração e, na seção 8, execute as migrations. O dashboard iniciará vazio. Depois crie o administrador pela seção 9.

### 5B. Transferir os dados existentes

Na origem, faça uma cópia consistente do banco e dos anexos em janela sem gravações. Para copiar também as credenciais criptografadas, obtenha a `APP_KEY` original por canal seguro. Não copie sessões, cache ou jobs para serem executados inadvertidamente: o saneamento do **banco de destino** está na seção 8.

Exemplo de exportação na origem (substitua banco/usuário):

```bash
umask 0077
mysqldump -h 127.0.0.1 -u USUARIO_ORIGEM -p \
    --single-transaction --quick --no-tablespaces BANCO_ORIGEM > sisamazonia-origem.sql
scp sisamazonia-origem.sql deploy@IP_DO_SERVIDOR:/home/deploy/
rsync -av storage/app/private/ deploy@IP_DO_SERVIDOR:/var/www/sisamazonia/storage/app/private/
rsync -av storage/app/public/ deploy@IP_DO_SERVIDOR:/var/www/sisamazonia/storage/app/public/
```

A cópia transacional supõe tabelas InnoDB e ausência de alterações de esquema durante o dump. Preserve eventuais diretórios de arquivos personalizados usados pela instalação. O acesso aos dados de candidatos deve ser autorizado para o ambiente de homologação.

No destino, confirme que o banco de homologação está vazio antes de importar:

```bash
mysql -h 127.0.0.1 -u sisamazonia_hmg -p sisamazonia_hmg -e 'SHOW TABLES;'
mysql -h 127.0.0.1 -u sisamazonia_hmg -p sisamazonia_hmg < /home/deploy/sisamazonia-origem.sql
```

Não execute essas instruções sobre um banco em uso. Ao transferir de outra versão do sistema, o dump deve incluir a tabela `migrations`; depois o Laravel aplicará apenas as migrations pendentes.

## 6. Configurar Laravel e o token da IA

Como `deploy`, em `/var/www/sisamazonia`:

```bash
cp .env.example .env
cp ai-service/.env.example ai-service/.env
chmod 640 .env ai-service/.env
nano .env
```

Use os valores reais da infraestrutura:

```dotenv
APP_NAME="Sisamazonia Homologação"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://homologacao.exemplo.org
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisamazonia_hmg
DB_USERNAME=sisamazonia_hmg
DB_PASSWORD="SUBSTITUIR"

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=null
CACHE_STORE=database
CACHE_PREFIX=sisamazonia_hmg_
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=120
FILESYSTEM_DISK=local
MAIL_MAILER=log
LOG_LEVEL=info
DASHBOARD_CACHE_TTL=30

AI_SERVICE_URL=http://127.0.0.1:8000
AI_SERVICE_TOKEN=
AI_EVALUATION_ENABLED=false
AI_SELECTION_ENABLED=false
AI_TECHNICAL_EVALUATOR_ID=
AI_SELECTION_EVALUATOR_ID=
AI_CONNECT_TIMEOUT=5
AI_EVALUATION_TIMEOUT=60
AI_EVALUATION_TRIES=3
AI_EVALUATION_QUEUE=ai-evaluations
```

`APP_ENV=production` é intencional mesmo em homologação: ativa as verificações de transporte seguro do projeto. O isolamento é dado por URL/banco/credenciais próprios. `MAIL_MAILER=log` evita enviar e-mails reais até configurar SMTP de teste. Não reutilize a credencial SMTP que aparecia no README antigo; se ela era válida, deve ser trocada, pois remover o texto atual não apaga o histórico Git.

**Banco importado:** preencha `APP_KEY` com a chave original, sem regenerá-la. **Banco novo:** deixe vazia por enquanto.

Gere uma vez um token interno e grave o mesmo nos dois `.env`, sem imprimir o valor. O comando preserva um token já configurado no Laravel:

```bash
python3 - <<'PY'
from pathlib import Path
import re
import secrets

laravel = Path('.env')
match = re.search(r'^AI_SERVICE_TOKEN=(.*)$', laravel.read_text(), re.M)
token = match.group(1).strip().strip('"\'') if match else ''
if not token:
    token = secrets.token_hex(32)
if len(token) < 32 or any(ord(c) < 33 or ord(c) > 126 for c in token):
    raise SystemExit('Token existente inválido; ajuste antes de continuar.')
for path in [laravel, Path('ai-service/.env')]:
    text = path.read_text()
    line = f'AI_SERVICE_TOKEN={token}'
    text = re.sub(r'^AI_SERVICE_TOKEN=.*$', lambda _: line, text, flags=re.M) if re.search(r'^AI_SERVICE_TOKEN=', text, re.M) else text + '\n' + line + '\n'
    path.write_text(text)
print('Token interno configurado nos dois arquivos, sem exibição do segredo.')
PY
nano ai-service/.env
```

No Python, configure `LLM_PROVIDER`, modelo/fallback se necessário, e mantenha `LLM_TIMEOUT=45`. Prefira cadastrar modelo e chave de API pelo painel administrativo depois da instalação. O token interno **não** é a chave do provedor de LLM. Para serviços locais/personalizados, configure `AI_LOCAL_ALLOWED_URLS`/`AI_CUSTOM_ALLOWED_URLS` com endereços exatos. Não é necessário instalar Ollama se for usar Gemini/OpenAI/outro provedor remoto.

As configurações persistidas no painel prevalecem sobre os flags de IA do `.env`. Se importar o banco, desabilite as automações também no banco conforme a seção 8, antes de iniciar o worker.

## 7. Dependências e build

```bash
cd /var/www/sisamazonia
php8.4 "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php8.4 "$(command -v composer)" check-platform-reqs --no-dev
npm ci
npm run build
python3 -m venv ai-service/.venv
ai-service/.venv/bin/python -m pip install -r ai-service/requirements.txt
ai-service/.venv/bin/python -m pip check
```

Se a política npm do servidor omitir dependências de desenvolvimento, use `npm ci --include=dev` para disponibilizar o Vite durante o build. Não rode `composer update` nem `npm update` na implantação.

Python ainda usa intervalos de versões, não um lock versionado. Após validar a instalação, registre `pip freeze` em um artefato da release (seção 14); reutilize o conjunto aprovado em novas máquinas. Não copie uma `.venv` de outra máquina ou mova uma virtualenv existente de caminho.

**Somente para banco novo**, gere a chave agora:

```bash
php8.4 artisan key:generate --force
```

Para banco importado, **pule esse comando**. Não envie `.env`, dumps ou chaves ao Git.

## 8. Permissões, migrations e banco importado

O código fica sob controle de `deploy`; `www-data` precisa ler o código e escrever apenas em `storage` e `bootstrap/cache`:

```bash
sudo chown -R deploy:www-data /var/www/sisamazonia
sudo chmod -R g+rX /var/www/sisamazonia
sudo find /var/www/sisamazonia -type d -exec chmod g+s {} +
sudo chmod -R g+rwX storage bootstrap/cache
chmod 640 .env ai-service/.env
umask 0002
php8.4 artisan config:clear
php8.4 artisan migrate:status
php8.4 artisan migrate --force
php8.4 artisan migrate:status
```

Em banco vazio, `migrate:status` pode informar que a tabela de migrations ainda não existe; continue com `migrate --force`. Use `umask 0002` ao executar Artisan para que caches/logs continuem graváveis pelo grupo. Não use `chmod 777`.

Não execute `migrate:fresh`, `migrate:refresh`, `db:wipe` ou `migrate --seed` em dados existentes. Os seeders atuais criam usuários de exemplo e o `UserRoleSeeder` referencia um papel `user` que o `RoleSeeder` não cria; por isso este guia não usa o seeder geral.

A migration `2026_09_03_085511_add_unique_constraints_to_evaluation_tables` interrompe a implantação se encontrar duplicatas em pareceres/notas/indicações. Ela não elimina registros. Se isso ocorrer, mantenha os serviços parados e revise os grupos indicados; não apague avaliações automaticamente.

**Somente no banco recém-importado de origem**, antes de abrir o site ou ligar o worker, confirme `SELECT DATABASE()` e limpe dados operacionais da cópia:

```bash
mysql -h 127.0.0.1 -u sisamazonia_hmg -p sisamazonia_hmg
```

```sql
SELECT DATABASE();
-- Deve mostrar sisamazonia_hmg. Execute somente na copia de homologacao.
DELETE FROM jobs;
DELETE FROM failed_jobs;
DELETE FROM job_batches;
DELETE FROM sessions;
DELETE FROM cache;
DELETE FROM cache_locks;
UPDATE ai_settings SET evaluation_enabled = 0, selection_enabled = 0;
EXIT;
```

Isso preserva inscrições, candidatos, pareceres, indicações e o histórico de `ai_executions`. Execuções importadas em `pending`/`processing` podem não ter job correspondente após a limpeza: não reenvie em massa; revise os IDs antes de um teste. Esses comandos **não fazem parte de atualizações rotineiras**.

Conclua os arquivos públicos e caches:

```bash
php8.4 artisan storage:link
php8.4 artisan cache:clear
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
php8.4 artisan event:cache
```

Se `public/storage` já for um link correto, mantenha-o. Nunca publique `storage/app/private`; somente `storage/app/public` deve estar exposto por esse link.

## 9. Administrador inicial e perfis

Em banco importado, use uma conta administrativa existente. Para **banco novo**, crie uma conta real sem senha fixa no guia. `laravel/tinker` é dependência de execução e está disponível com `--no-dev`:

```bash
php8.4 artisan tinker
```

Cole o bloco inteiro no Tinker, substituindo nome/e-mail. A função evita que o REPL imprima a senha retornada pelo prompt; a senha é solicitada sem eco e não entra no comando:

```php
(function (): void {
    $helper = new Symfony\Component\Console\Helper\QuestionHelper;
    $input = new Symfony\Component\Console\Input\ArgvInput;
    $output = new Symfony\Component\Console\Output\ConsoleOutput;
    $question = (new Symfony\Component\Console\Question\Question('Senha inicial: '))->setHidden(true)->setHiddenFallback(false);
    $plain = $helper->ask($input, $output, $question);
    if (! is_string($plain) || strlen($plain) < 12) { throw new RuntimeException('Use uma senha com pelo menos 12 caracteres.'); }
    Illuminate\Support\Facades\DB::transaction(function () use ($plain) {
        $role = App\Models\Role::firstOrCreate(['name' => 'admin'], ['active' => true]);
        App\Models\Role::firstOrCreate(['name' => 'leitor'], ['active' => true]);
        App\Models\Role::firstOrCreate(['name' => 'comissao'], ['active' => true]);
        $user = App\Models\User::create(['name' => 'Administrador HMG', 'email' => 'admin-hmg@exemplo.org', 'password' => Illuminate\Support\Facades\Hash::make($plain), 'is_judge' => false, 'is_organizer' => false]);
        $user->roles()->attach($role->id);
    });
    unset($plain);
})();
exit
```

Execute uma vez; não é comando de atualização nem de redefinição de senha de conta existente. O dashboard permite `admin` e `leitor`; Configurações de IA exige `admin`.

## 10. Serviços persistentes de IA e filas

Use os modelos **de homologação**, não as unidades em `deploy/systemd/` que apontam para o caminho do ambiente de desenvolvimento. Revise os caminhos se não usar `/var/www/sisamazonia`.

```bash
sudo install -m 644 deploy/homologacao/sisamazonia-ai.service /etc/systemd/system/sisamazonia-ai.service
sudo install -m 644 deploy/homologacao/sisamazonia-queue.service /etc/systemd/system/sisamazonia-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now sisamazonia-ai.service
curl --retry 10 --retry-connrefused --retry-delay 1 --fail http://127.0.0.1:8000/health
sudo systemctl enable --now sisamazonia-queue.service
sudo systemctl status sisamazonia-ai sisamazonia-queue --no-pager
```

O serviço de IA escuta somente em `127.0.0.1:8000`; não exponha essa porta à Internet. Os arquivos `.env` são lidos pela aplicação/Python; não é necessário usar `EnvironmentFile` do systemd. Nunca rode ao mesmo tempo Supervisor e systemd para esses mesmos workers/porta.

O worker consome `ai-evaluations,default`. O timeout do job (70 s) é menor que `DB_QUEUE_RETRY_AFTER=120`, reduzindo reexecuções concorrentes. O systemd aguarda até 90 s ao parar. Mantenha o worker parado até revisar configurações e jobs caso tenha copiado um banco.

Não mantenha `php artisan serve`, `npm run dev` ou `composer run dev` em homologação. PHP-FPM/Nginx servem a aplicação, Vite gera arquivos estáticos e systemd mantém os processos Python/fila.

## 11. Nginx e HTTPS

O DocumentRoot é sempre `/var/www/sisamazonia/public`, nunca a raiz do repositório.

```bash
sudo install -m 644 deploy/homologacao/nginx.conf.example /etc/nginx/sites-available/sisamazonia-hmg
sudo nano /etc/nginx/sites-available/sisamazonia-hmg
sudo ln -s /etc/nginx/sites-available/sisamazonia-hmg /etc/nginx/sites-enabled/sisamazonia-hmg
sudo nginx -t
sudo systemctl reload nginx
```

Troque `server_name` pelo domínio real. Se o link já existir, não repita `ln -s`. O exemplo escuta HTTP inicialmente para permitir emissão do certificado. Não faça login nem libere o ambiente antes de concluir HTTPS, pois `SESSION_SECURE_COOKIE=true` requer HTTPS.

Se a infraestrutura usa Let's Encrypt e o domínio resolve para o servidor com portas 80/443 acessíveis:

```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d homologacao.exemplo.org
sudo certbot renew --dry-run
sudo nginx -t
sudo systemctl reload nginx
```

Se houver certificado institucional ou TLS terminado no proxy, use a configuração da infraestrutura. Nesse caso, confira encaminhamento de HTTPS e proxies confiáveis no Laravel; não confie indiscriminadamente em qualquer proxy.

Configure PHP-FPM para aceitar os uploads esperados. O exemplo Nginx limita a 25 MB; alinhe `upload_max_filesize`, `post_max_size` e as regras de upload da aplicação. Para o processamento síncrono de 60 s, o modelo já usa `fastcgi_read_timeout 90s`; se o pool FPM definir `request_terminate_timeout`, mantenha-o acima desse tempo. Prefira processamento em fila para lotes.

```bash
sudo nano /etc/php/8.4/fpm/php.ini
sudo systemctl restart php8.4-fpm
```

Não apague outros sites do Nginx. Libere SSH/HTTPS no firewall conforme a política da infraestrutura; MySQL e porta 8000 devem permanecer restritos.

## 12. Se o servidor já usa Apache ou frontend separado

No Apache, aponte o VirtualHost HTTPS para `public/`, habilite `rewrite` e PHP-FPM 8.4 (`proxy_fcgi`), e permita as regras do `public/.htaccess`. Não inicie Nginx na mesma porta. O restante do guia (Git, banco, build e systemd) continua aplicável.

Se o formulário público estiver em outro domínio, ajuste **`config/cors.php`** com a origem exata da homologação, sem barra final, e rode `php8.4 artisan config:cache`. Esse arquivo existe no Git, mas também possui entrada no `.gitignore`; trate sua customização explicitamente e não deixe alterações locais impedirem futuras atualizações. Não use `*` como solução para um domínio incorreto.

## 13. Conferência antes de liberar

```bash
cd /var/www/sisamazonia
php8.4 artisan migrate:status
php8.4 artisan route:list --name=dashboard
php8.4 artisan queue:failed
curl --fail https://homologacao.exemplo.org/up
curl --fail http://127.0.0.1:8000/health
sudo systemctl is-active nginx php8.4-fpm sisamazonia-ai sisamazonia-queue
```

- `/up` confirma que o Laravel inicializa; `/health` confirma o processo Python. Nenhum deles garante acesso ao modelo de IA.
- Acesse `/login`, valide o dashboard como admin/leitor, as duas abas, mapa, totais e arquivos públicos/privados autorizados.
- Em **Configurações de IA → Configuração do LLM**, cadastre o provedor, ID do modelo e chave; salve. Depois use **Diagnóstico e processamento → Testar acesso ao modelo**. O teste de metadados não gera avaliações.
- Para testar uma avaliação de fato, associe avaliador/indicador às categorias, confirme critérios e inscrições no status correto, habilite as etapas pelo painel e processe uma inscrição de teste. Essa operação chama o provedor e pode gerar cobrança.
- Valide **Execuções recentes**, HTTP/erro e a gravação do parecer/indicação. Para a seleção, é necessário haver avaliação técnica concluída.

O scheduler atual não declara tarefas periódicas (`routes/console.php` define comandos manuais). Não é necessário cron para as funcionalidades atuais. Não programe `ai:evaluate-habilitados --dispatch` ou `ai:select-avaliados --dispatch` automaticamente: esses comandos iniciam processamento real.

## 14. Atualização de versões — instalação já funcionando

Planeje uma janela curta. Pare novos acessos e o worker antes de substituir código/dependências. Os comandos abaixo são **de atualização**, não devem repetir `key:generate` nem importar novamente o banco de origem.

Como `deploy`, na mesma sessão SSH, ajustando domínio/banco:

```bash
set -euo pipefail
cd /var/www/sisamazonia
umask 0077
release_backup="/home/deploy/backups/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$release_backup"
git status --short
test -z "$(git status --porcelain)"
git fetch origin feature/production-avaliacaoIA
git log --oneline HEAD..origin/feature/production-avaliacaoIA
git rev-parse HEAD > "$release_backup/commit.txt"
cp .env "$release_backup/laravel.env"
cp ai-service/.env "$release_backup/ai.env"
ai-service/.venv/bin/python -m pip freeze > "$release_backup/python-requirements.txt"
cp composer.lock package-lock.json "$release_backup/"
umask 0002
php8.4 artisan down --retry=60
sudo systemctl stop sisamazonia-queue
sudo systemctl stop sisamazonia-ai
umask 0077
mysqldump -h 127.0.0.1 -u sisamazonia_hmg -p \
    --single-transaction --quick --no-tablespaces sisamazonia_hmg > "$release_backup/database.sql"
tar -czf "$release_backup/storage-app.tar.gz" storage/app
tar -czf "$release_backup/public-build.tar.gz" public/build
umask 0002
git merge --ff-only origin/feature/production-avaliacaoIA
php8.4 "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php8.4 "$(command -v composer)" check-platform-reqs --no-dev
npm ci --include=dev
npm run build
ai-service/.venv/bin/python -m pip install -r ai-service/requirements.txt
ai-service/.venv/bin/python -m pip check
php8.4 artisan config:clear
php8.4 artisan migrate --force
php8.4 artisan cache:clear
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
php8.4 artisan event:cache
sudo systemctl restart php8.4-fpm
sudo systemctl start sisamazonia-ai
curl --retry 10 --retry-connrefused --retry-delay 1 --fail http://127.0.0.1:8000/health
sudo systemctl start sisamazonia-queue
php8.4 artisan up
curl --fail https://homologacao.exemplo.org/up
sudo systemctl status sisamazonia-ai sisamazonia-queue --no-pager
git rev-parse HEAD
```

Se houver comandos externos que gravam dados, pause-os na janela de backup. Se alguma etapa falhar, `set -e` interrompe a sequência: **mantenha manutenção/worker parado**, examine logs e corrija ou restaure. Não execute `up` apenas para ocultar uma falha de migration/build. Reinicie a fila somente quando banco, código e IA estiverem coerentes.

O `origin` no servidor será o remoto usado no clone, mesmo que ele se chamasse `origin-anterior` na máquina de desenvolvimento. `git merge --ff-only` após o fetch equivale à atualização linear; `git pull --ff-only origin feature/production-avaliacaoIA` pode ser usado em atualização simples, depois de backup e preparação, mas não elimina migrations/build/restarts.

Se os modelos systemd/Nginx mudarem na release, reinstale os arquivos conforme as seções 10/11 e execute `daemon-reload`/`nginx -t` antes de iniciar os serviços. Se alterar apenas `.env`, refaça `config:cache` e reinicie fila/IA. Não deixe arquivo `public/hot` de desenvolvimento no servidor: se existir, remova somente esse arquivo.

## 15. Retorno à versão anterior

Não use `migrate:rollback` automaticamente: o `down()` de migrations pode apagar tabelas/dados. Identifique o backup correto e mantenha o site em manutenção e a fila parada.

- **Sem mudança incompatível no banco:** retorne ao SHA aprovado com `git switch --detach SHA_ANTERIOR`, reinstale dependências dos locks daquela versão, restaure o conjunto Python aprovado e os assets correspondentes; refaça caches e reinicie serviços. Ao voltar a atualizar, retorne à branch com `git switch feature/production-avaliacaoIA`.
- **Com mudança incompatível de esquema:** restaure o backup em **outro banco vazio**, valide-o e só então aponte o `.env` para esse banco; combine isso com o código, `APP_KEY` e arquivos da mesma release. Não importe um dump parcial em cima do banco atual.

Exemplo de restauração de um banco alternativo, previamente criado pelo DBA:

```bash
mysql -h 127.0.0.1 -u USUARIO_RESTORE -p sisamazonia_hmg_restore < /CAMINHO/DO/BACKUP/database.sql
```

Preserve o banco anterior para diagnóstico. Alterações realizadas depois do backup não estarão na restauração. Para Python, crie uma nova virtualenv no caminho definitivo após guardar a anterior e instale `-r /CAMINHO/DO/BACKUP/python-requirements.txt`; não reutilize um ambiente com pacotes de versão nova assumindo que isso constitui rollback. A retomada usa a mesma sequência de caches, início da IA, teste de saúde, fila e `artisan up` da seção 14.

## 16. Comandos operacionais e solução de problemas

```bash
sudo systemctl restart sisamazonia-ai
sudo systemctl restart sisamazonia-queue
sudo journalctl -u sisamazonia-ai -n 100 --no-pager
sudo journalctl -u sisamazonia-queue -n 100 --no-pager
sudo journalctl -u sisamazonia-ai -f
tail -n 100 /var/www/sisamazonia/storage/logs/laravel.log
sudo tail -n 100 /var/log/nginx/sisamazonia-hmg-error.log
php8.4 artisan queue:failed
```

| Sintoma | Verificar |
|---|---|
| Composer reclama do PHP | CLI e FPM 8.4; executar `check-platform-reqs`; não ignorar requisitos |
| 502 no site | Socket PHP-FPM, serviço e configuração Nginx |
| 500 no Laravel | `storage/logs`, permissões de `storage`/cache, banco e migrations |
| Erro de descriptografia | `APP_KEY` diferente da origem ao importar `ai_settings` |
| 419/login não permanece | HTTPS, domínio/cookie, sessões no banco e `APP_URL` |
| Assets ausentes | `npm ci`, `npm run build`, `public/build/manifest.json`, ausência de `public/hot` |
| IA connection refused | Serviço Python e porta 8000 em loopback |
| HTTP 401 interno | Mesmo `AI_SERVICE_TOKEN` nos dois `.env`, caches Laravel e reinício Python |
| IA pendente indefinidamente | Worker ativo, conexão `database`, fila `ai-evaluations`, jobs importados sem correspondência |
| HTTP 422 na avaliação | Critérios e dados obrigatórios; categoria precisa de critérios cadastrados |
| `PROVIDER_INVALID_RESPONSE` | Detalhes sanitizados no journal Python; versão do código/prompt e schema |
| Model not found / forbidden | Modelo exato, credencial e acesso ao provedor pelo diagnóstico do painel |
| Dashboard aparentemente atrasado | Cache global de até `DASHBOARD_CACHE_TTL`; edição atual recalcula ao recarregar |

Reenvie jobs/avaliações somente após entender o erro. Não rode `queue:retry all` indiscriminadamente em um banco copiado. Logs podem conter dados operacionais; compartilhe apenas o trecho necessário, sem tokens ou dumps.

## 17. Referências e estado do guia

As instruções específicas acima foram conferidas contra `composer.lock`, `package-lock.json`, `.env.example`, `config/queue.php`, models, migrations, seeders e serviço Python desta branch. Modelos incluídos:

- `deploy/homologacao/nginx.conf.example`;
- `deploy/homologacao/sisamazonia-ai.service`;
- `deploy/homologacao/sisamazonia-queue.service`.

As recomendações gerais de DocumentRoot/caches seguem [Laravel 12 — Deployment](https://laravel.com/docs/12.x/deployment); timeouts e reinício de workers seguem [Laravel 12 — Queues](https://laravel.com/docs/12.x/queues). [Debian 13 — PHP 8.4 FPM](https://packages.debian.org/trixie/php8.4-fpm) documenta a disponibilidade do pacote no Debian. O pré-requisito do frontend pode ser conferido no [Vite](https://vite.dev/guide/).

Validação local do guia: sintaxe dos blocos Bash (`bash -n`), PHP (`php -l`) e Python (`compile`), links locais e unidades (`systemd-analyze verify`) aprovados. Nginx não está instalado no ambiente de elaboração; execute `nginx -t` no servidor antes de ativar o site.

A instalação real ainda depende de sistema operacional, domínio, proxy/certificado e credenciais fornecidos pela infraestrutura. Nenhuma conexão SSH, publicação Git, migration ou reinício remoto foi realizado ao criar este documento.
