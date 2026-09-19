# Dashboard estatístico

A rota `dashboard` (`GET /dashboard`) mantém o menu existente e exige autenticação e papel ativo `admin` ou `leitor`. O `DashboardController` delega as consultas ao `DashboardStatisticsService`. O método sem uso do `AuthController` foi removido. Nenhuma tabela ou migration foi criada.

## Indicadores e regras

- **Inscrição:** cada linha de `registrations` ou `nominees`, combinadas com `UNION ALL`. Inclui todos os status, sem deduplicar candidatos. Não equivale ao total de pessoas.
- **Total geral:** número de linhas das duas origens no escopo. **Regulares:** somente `registrations`. **Honoríficas:** somente `nominees`. A soma das duas origens é o total geral.
- **Global:** todas as edições. **Edição Atual:** apenas a edição selecionada pela regra abaixo.
- **Edição, modalidade e categoria:** obtidas pelo caminho inscrição → categoria → modalidade → edição; agrupadas por ID, não pelo título. Catálogos sem inscrições aparecem com zero. Categorias são contextualizadas pela modalidade; no global, modalidade e categoria também pela edição. Eventuais vínculos ausentes no global são contabilizados como “Não informado”.
- **UF:** `UPPER(TRIM(candidates.ufendereco))`, validada contra as 27 UFs; vazios/nulos são “Não informado”, outros valores são “UF inválida”. Não se usa `rg_uf` nem `nominees.state`.
- **Região:** soma das UFs segundo `BrazilStates::REGIONS` e `STATES`. Registros sem UF válida aparecem em uma linha adicional, mantendo a conciliação com o total da edição.
- **Sexo e escolaridade:** respectivamente `candidates.sexo` e `candidates.escolaridade`; opções existentes são preservadas, removendo apenas espaços nas extremidades. Nulos/vazios são “Não informado”.
- **Idade:** anos completos na data civil de `created_at` da própria inscrição. Aniversário ainda não ocorrido subtrai um ano. Faixas: Até 17 (apenas quando houver), 18 a 30, 31 a 40, 41 a 59, 60 ou mais e Não informado. Datas ausentes, impossíveis, com ano zero ou nascimento posterior à inscrição são “Não informado”. O cálculo valida dias do mês e anos bissextos no SQL, tanto no MySQL quanto no SQLite.
- **Percentual:** quantidade do grupo ÷ total de inscrições do escopo × 100, com duas casas decimais. Para total zero, 0%. UFs inválidas/ausentes permanecem no denominador do mapa. Arredondamentos podem fazer a soma dos percentuais diferir ligeiramente de 100%; as quantidades conciliam exatamente.

## Seleção da edição e atualização

Seleciona a edição com `is_registration_active = true` mais recente por `registration_start`, desempatando pelo maior ID. Se houver várias ativas, registra aviso no log com o ID escolhido e a quantidade de ativas. Na ausência de ativa, usa a mais recente e informa o fallback na interface. Sem edições, a segunda aba exibe estado vazio.

O cache global usa a chave `dashboard.statistics.v1.{editionId}` e `DASHBOARD_CACHE_TTL` (segundos, padrão 30, limitado a 300; zero desativa). A seleção da edição e os dados da edição atual são consultados a cada requisição. Por isso, após uma nova inscrição, o global pode levar até o TTL para refletir a alteração, enquanto a edição atual a reflete na próxima carga. Não há polling: recarregue a página. As datas de geração são exibidas nas abas.

As consultas usam agregações SQL e consultas de catálogos, sem carregar inscrições/candidatos em memória. Com uma edição e sem cache são 19 consultas do serviço, independentemente do volume de inscrições. Um cache global válido reduz isso para 10. Não há consulta por UF, modalidade ou categoria. Não foram adicionados índices sem evidência de necessidade.

## Interface e privacidade

Utiliza `admin.content`, o componente existente `x-elements.tabs`, classes KTUI, cores do tema e ApexCharts local. O JavaScript e o CSS têm entradas próprias no Vite. Gráficos são criados apenas quando visíveis e ajustam sua largura ao trocar de aba ou redimensionar a tela. Categorias extensas têm área de rolagem e tooltip com o nome completo. Cada gráfico tem tabela acessível. Sem JavaScript, as duas abas e todas as tabelas continuam disponíveis.

O payload usa `Js::from` e contém apenas agregados e títulos de catálogos. Não são consultados nem enviados nomes, CPF, RG, contato ou conteúdo das propostas. Tooltips escapam os rótulos. A demografia é a do candidato atualmente vinculado: alterações posteriores em seu cadastro podem alterar estatísticas históricas; não existe snapshot demográfico no esquema atual.

O mapa tem 26 estados e DF, teclado (Tab/Enter/Espaço), seleção persistente da UF, tooltip nativo com nome/sigla/região/total/percentual e tabela das 27 UFs. Zero tem cor própria e os valores positivos são divididos em até quatro intervalos iguais, com faixas vazias omitidas. A seleção destaca dados; não filtra os demais gráficos. O DF também pode ser escolhido na tabela, evitando depender de sua pequena área cartográfica.

Fonte do mapa: [IBGE — API de Malhas Geográficas](https://servicodados.ibge.gov.br/api/docs/malhas?versao=3). A geometria está em `resources/maps/brazil-states.json`; procedência e transformação em `resources/maps/README.md`. Nenhuma dependência cartográfica remota é usada em execução. O layout compartilhado existente ainda contém recursos externos do tema, que não foram alterados.

## Arquivos

- Controller: `app/Http/Controllers/DashboardController.php`.
- Agregações: `app/Services/DashboardStatisticsService.php`.
- Geografia: `app/Support/BrazilStates.php`, `resources/maps/`.
- Cache: `config/dashboard.php`.
- Interface: `resources/views/dashboard.blade.php` e `resources/views/dashboard/{global,current,indicators,chart,map}.blade.php`.
- Frontend: `resources/js/dashboard.js`, `resources/js/dashboard-format.js`, `resources/css/dashboard.css` e entradas em `vite.config.js`.
- Integração: `routes/web.php`, remoção do método legado em `AuthController`.
- Testes: `tests/Feature/DashboardStatisticsTest.php`, `tests/js/dashboard-format.test.mjs`.

## Validação

```sh
vendor/bin/pint app/Http/Controllers/DashboardController.php app/Http/Controllers/AuthController.php app/Services/DashboardStatisticsService.php app/Support/BrazilStates.php config/dashboard.php tests/Feature/DashboardStatisticsTest.php
php artisan test --compact
node --test tests/js/dashboard-format.test.mjs
php artisan view:cache
npm run build
git diff --check
```

Também foi realizada consulta somente de leitura no MySQL local para conciliar totais gerais, faixas etárias e categorias. Os testes automatizados usam SQLite em memória. A versão de PHP disponível para validação neste ambiente é 8.4; o código usa recursos compatíveis com PHP 8.2.
