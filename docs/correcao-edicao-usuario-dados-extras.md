# Correção do erro ao atualizar usuário

## Erro e causa

Ao salvar a edição de um usuário, a aplicação pode retornar erro HTTP 500:

```text
Call to a member function updateOrCreate() on null
app/Http/Controllers/UserController.php:253
```

Isso ocorre quando o usuário ainda não possui registro na tabela `user_extra_data`. A propriedade `$user->extraData` retorna `null`, e o controller tenta executar `updateOrCreate()` sobre esse valor.

## Alteração no servidor de produção

No diretório da aplicação, faça uma cópia de segurança de `app/Http/Controllers/UserController.php` fora da pasta pública do servidor.

Abra o arquivo e localize a chamada no método `update()`, abaixo do comentário `// Atualiza dados extras`. O número da linha pode variar entre versões.

Substitua:

```php
$user->extraData->updateOrCreate(
```

Por:

```php
$user->extraData()->updateOrCreate(
```

Mantenha os argumentos e o restante do método como estão:

```php
// Atualiza dados extras
$user->extraData()->updateOrCreate(
    ['user_id' => $user->id],
    [
        // Manter aqui todos os campos existentes e seus valores.
    ],
);
```

O trecho acima ilustra a chamada: não substitua a lista real de campos pelo comentário. A alteração necessária é somente acrescentar `()` após `extraData`.

A chamada passa a usar a relação Eloquent, que cria os dados extras quando não existem ou atualiza o registro existente do usuário.

## Validação após a alteração

Execute na raiz da aplicação:

```bash
php -l app/Http/Controllers/UserController.php
```

O resultado esperado é `No syntax errors detected`.

Teste pela interface:

1. Edite um usuário sem dados extras cadastrados e salve. A aplicação deve retornar à lista de usuários com a mensagem de sucesso.
2. Confira se nome e permissões foram salvos corretamente.
3. Preencha um campo extra, como empresa, e salve novamente. Confira se o valor foi atualizado.
4. Confirme que a atualização não cria registros extras duplicados nem altera dados de outro usuário.

Se o servidor utiliza OPcache com verificação de arquivos desativada (`opcache.validate_timestamps=0`) ou Laravel Octane, recarregue o serviço correspondente pelo procedimento de implantação do servidor para que ele carregue o código atualizado. Reinicie/recarregue a versão de PHP realmente utilizada pelo site; não presuma o nome do serviço.

## Banco, dependências e caches

Esta correção não exige migration, alteração de banco, atualização de dependências, regeneração de autoload ou limpeza de caches de configuração, rotas e views. O arquivo modificado é um controller já existente.

O erro anterior pode ter ocorrido depois de salvar os dados básicos e permissões, pois essas operações precedem a chamada que falhou. Confira os valores atuais do usuário antes de repetir a edição.

## Teste automatizado

A correção local foi validada com `tests/Feature/UserUpdateTest.php`: um teste com 14 verificações cobre a criação dos dados extras ausentes, a atualização do registro existente sem duplicação, a persistência das permissões e a preservação dos dados de outro usuário.

Caso esse teste também esteja disponível no ambiente de testes do servidor, execute nele:

```bash
php artisan test --compact --filter=UserUpdateTest
```

Esse teste usa `RefreshDatabase`. Execute-o apenas com configuração de banco de testes isolado, nunca apontando para o banco de produção. Para aplicar a correção no servidor, basta a alteração de uma linha e a validação de sintaxe e funcional descritas acima.

## Reversão

Se necessário, restaure a cópia de segurança do controller e recarregue os serviços que mantêm código em memória, conforme o procedimento do servidor. A reversão do arquivo não exige desfazer alterações de estrutura no banco; ela também reintroduz o erro para usuários sem dados extras.

## Situação desta entrega

A correção do controller e o teste integram a branch `production-evolucao`. Ao publicar a branch, não é necessário editar o controller manualmente. Consulte `docs/implantacao-production-evolucao.md` para os comandos completos. Este documento não executa mudanças no servidor.
