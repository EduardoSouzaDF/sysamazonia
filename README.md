# 🚀 Sisamazonia Instalação e configuração
--

## 🛠️ Pré-requisitos

Antes de iniciar, certifique-se de ter instalado em sua máquina:
* PHP (versão mínima recomendada para o seu projeto, ex: >= 8.4)
* Banco de Dados (MySQL)
* [Composer](https://getcomposer.org/)
* [Node.js & NPM](https://nodejs.org/)

---

## 📦 Passo a Passo para Instalação

Siga as instruções abaixo para configurar o ambiente de desenvolvimento de produção.
repositório está privado,, informe o email para adicionar e/ou chave para ser adicionada no 
realizar fetch da branch production

### 1. Clonar o Repositório
```bash
git clone https://github.com/EduardoSouzaDF/sysamazonia 
cd seu-repositorio
git pull origin production
```

### 2. Realizar instalação das dependência do PHP com Composer.
Caso composer não tenha sido instalado realizar conforme manual :
https://getcomposer.org/doc/00-intro.md

comandos utilizados em hmg pois dava erro devido ao certificado
```bash
  wget --no-check-certificate https://getcomposer.org/installer -O composer-setup.php
  php composer-setup.php --disable-tls
   php composer.phar install
```
as bibliotescas serão instaladas no diretório : ./vendor

### 3. Instalação de bibliotecas node
Realizar instalação do node via apt-get ou conforme manual
https://nodejs.org/en/download
realizar a instalação dos pacotes 

```bash
node install
```

### 4. Alteração do arquivo de configuração .env
Crie arquivo .env copiando o arquivo .env.example, configure os
seguintes parâmetros:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sisamazonia.ibict.br

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$$$$
DB_USERNAME=$$$$
DB_PASSWORD=$$$$

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=embauba.ibict.br
MAIL_PORT=587
MAIL_USERNAME=amazonia@apps.ibict.br
MAIL_PASSWORD=KeQf!vdV@8
MAIL_FROM_ADDRESS="amazonia@apps.ibict.br"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ENCRYPTION=tls
QUEUE_CONNECTION=sync


### 4. Alteração do arquivo de configuração config/cors.php
Altere conforme a necessidade
    'allowed_origins' => [
        'https://amazonia.ibict.br',
        'https://sisamazonia.ibict.br/',
    ],

### 5. Gere nova chave para a aplicação
```bash
php artisan key:generate
```

### 6. Rodar migrations para criação das tabelas no banco de dados
Obs: Banco de dados deverá ser previamente criado
```bash
 php artisan migrate  --seed
```
### 7. Rodar assets para produção
```bash
npm run build
```

### 8. Limpar as configurações do laravel
```bash
php artisan config:clear
```

 

