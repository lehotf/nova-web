# Diretrizes de Codificacao (Htdocs)

## Escopo e organizacao
- Trabalhar apenas dentro de `Htdocs`.
- `Htdocs2` serve apenas para consulta e comparacao, é a versão antiga do site.
- Vários sites serão publicados em um mesmo servidor compartilhado com diretórios diferentes. Exemplo: `eupenso.com`, `calculatudo.com`, `quemoleza.com`.
- Codigo compartilhado entre todos os sites fica em `shared/comum`.
- Codigo compartilhado por tipo de site fica em `shared/sites/<tipo>`.
- <tipo> é o tipo de site. O site eupenso.com é do tipo "publicação de artigos". Então, a estrutura comum para esse tipo de site fica em `shared/sites/artigos`.
- Cache compartilhado de assets (css/js) fica em `shared/cache`. São os arquivos css e js que sao usados por todos os sites.
- O arquivo `index.php` fica na raiz de cada site e serve como ponto de entrada desse site. 
- .htaccess é configurado para remanejar todos os acessos para o arquivo index.php. (Não precisamos fazer o arquivo.htaccess)

RewriteBase / 
RewriteCond %{REQUEST_FILENAME} !-f     
RewriteRule (.*) index.php

- Não incluiremos nos arquivos index.php nenhum valor hardcoded específico do site atual. Ao invés disso, usaremos o arquivo config.php do site para carregar as configuracoes particulares do site.
- Cada site possui um link simbolico `comum` apontando para `shared/comum` para facilitar os includes; portanto, `site.com/comum/...` e `shared/comum/...` apontam para o mesmo arquivo (nao ha duplicacao).
- Assuma que modulos, recursos e diretorios sempre existem; nao faça verificacoes de existencia para eles.

## Configuracao
- Cada site terá um unico arquivo `config.php` dentro do seu diretorio `config`(ex.: `eupenso.com/config/config.php`).
- O `config.php` do site deve conter apenas dados especificos daquele site (ex.: IDs, titulo, descricao, flags).
- As configuracoes comuns a todos os sites ficam em `shared/comum/config/config.php`.
- O carregamento de configuracao acontece no `index.php` do site, incluindo o config comum e o config do site via `comum/`.
- O `index.php` nao deve ter valores hardcoded por dominio; toda especificidade vem do `config.php` do site.
- Configuracoes que podem variar por site (ex.: `CACHE_ATIVO`) devem ficar no `config.php` do site, nao no comum.

## Cache
- O diretorio de cache HTML e montado diretamente no `Cache` como `cache/html` (caminho relativo ao `index.php`).
- O `Cache` recebe o `Guardiao` existente (injeção) para evitar criar outro e repetir resolucoes de IP/URL.
- O `Cache` nao deve criar um `Guardiao`; o `Guardiao` e criado uma unica vez no inicio e compartilhado.
- O `Cache` deve ser simples: `buscar()` e `salvar()` e nada de verificacoes extras.
- O `Verificador` recebe o `Guardiao` existente e usa o `Cache` antes de chamar o construtor de conteudo.
- O `Cache` deve ser sempre instanciado com `Guardiao` (sem default `null`).

## Bots e bloqueio
- A primeira verificacao deve ser a lista negra; se o IP estiver nela, retornar 404 vazio e renovar o TTL.
- A lista branca so deve ser consultada imediatamente antes de um lookup reverso (para evitar lookup desnecessario).
- O arquivo `controlador.php` possui um objeto `Guardiao`, responsavel por limpar URL, gerenciar listas branca/negra e realizar lookup reverso.
- O TTL das listas branca e negra e fixo em 30 segundos, sem parametros.
- O `Guardiao` e o proprietario do IP e da URL tratada; outras funcoes consultam essas informacoes nele.
- O `Guardiao` responde `PNF` (404) e decide blacklist/whitelist: primeiro verifica lista negra, depois lista branca, depois valida Googlebot, e so entao adiciona na lista negra.
- O `Guardiao` deve ser criado e checado antes de carregar configs e outros arquivos do site.
- Nao repetir verificacoes de blacklist em outras classes; o `Guardiao` faz isso uma unica vez no inicio.
- O `Guardiao` registra acessos (TTL 5s) e, ao atingir o limite (5 acessos), chama `PNF` para aplicar a logica completa de bloqueio.

## Linguagem e nomes
- Nomes de classes, variaveis e funcoes em portugues.
- Usar `set` e `get` nos metodos de atribuicao e leitura (ex.: `setCache`, `getCache`).
- Preferir nomes simples e logica direta (evitar logica negativa em flags e funcoes).

## Logica
- Evitar duplicar logicas antigas quando estiverem confusas; ajustar para clareza.
- Manter a funcionalidade equivalente, mas com estrutura mais clara e segura.
- Evitar variaveis e constantes redundantes; preferir caminhos relativos ao `index.php` (sem `__DIR__`) conforme padrão atual.
- Nao inventar APIs ou callbacks sem combinacao previa; manter o fluxo direto e acordado.

## Constantes x variaveis
- Usar `define()` (ou `const`) apenas para valores realmente globais e imutaveis.
- Preferir variaveis locais para caminhos e valores usados apenas no arquivo atual.

## Processo
- Evoluir passo a passo, com ajustes pequenos e validaveis.
- Sempre revisar o que foi feito e adequar antes de seguir.
- IMPORTANTE: Sempre que surgir uma nova regra durante a conversa, que não esteja contida neste arquivo, adicionar o topico correspondente em `diretriz.md`.
- Nao e necessario verificar a existencia de uma constante global.
- Estamos implementando a migração da versão antiga em `Htdocs2` para a versão nova em `Htdocs`.
  - `Htdocs2` é a referência para o trabalho realizado em `Htdocs`.
  - Os diretórios em `Htdocs` estão sendo preenchidos gradativamente conforme os arquivos são adaptados.
  - A ausência de um arquivo em `Htdocs` não significa que ele foi eliminado; pode indicar apenas que ainda não foi adaptado.
  - À medida que houver necessidade de adaptar um arquivo, ele será inserido em `Htdocs` com sua versão atualizada.

# As classes Principais

## Cache

Responsável por:

1 - verificar se existe um cache para a URL atual e retornar o conteúdo do cache ao usuário.
2 - salvar o conteúdo do cache para a URL atual.

## Guardiao

Responsável por;
1 - verificar se o IP e URL atual estão na lista negra e lista branca.
2 - Um IP é cadastrado na lista negra em caso de acessar uma página inexistente e não ser um googlebot. 
3 - A verificação se um IP é um googlebot é feita através de Lookup Reverso. Quando um IP acessa uma página inexistente, verificamos se ele é um googlebot ou não. Se for, ele é cadastrado na lista branca. Se não for, é cadastrado na lista negra.
4 - caso o IP esteja na lista negra, retornar 404 vazio e renovar o TTL de 30 segundos daquele IP na lista negra.
5 - caso o IP esteja na lista branca, impede que ele seja cadastrado na lista negra em caso de acessar uma página inexistente.
6 - Sanitizar e armazenar URL e IP.

## Logger

Responsável por;

1 - Registrar acessos e acessos negados.
2 - Registrar acessos subsequentes.

## Controlador

É o componente que coordena as operações do sistema. Ele aciona o guardião, logger e cache para a tomada de decisão sobre prosseguir com o carregamento da página ou interromper o processo retornando 404.

## BD

Responsável por;
1 - Acesso ao banco de dados

## Carregador

Responsável por;
1 - Carregar o conteúdo do arquivo solicitado, caso ele não esteja em cache.
  - Este carregamento observará o TIPO de site. Sendo um site de artigos, o carregamento acontecerá através do carregamento da página artigo.php do diretório compartilhado comum/site/artigos/php/path.
2 - Encaminhar o conteúdo para o objeto cache para geração do cache.
3 - Encaminhar para o Guardião, caso não localize o conteúdo solicitado no banco de dados. Assim, o guardião retornará 404 e decidirá se adiciona o IP em lista negra ou branca.


# Estrutura de Diretorios

```
Htdocs/
├── [site].com/
│   ├── config/
│   │   └── config.php      # Config específica do site
│   ├── comum -> ../shared/comum  # Symlink para shared/comum
│   └── index.php           # Ponto de entrada
├── shared/
│   ├── comum/              # Código comum a todos os sites
│   │   ├── config/
│   │   │   └── config.php  # Configurações globais
│   │   ├── estatico/       # Arquivos estáticos compartilhados
│   │   │   ├── css/        # Arquivos CSS
│   │   │   ├── fonts/      # Arquivos de fontes
│   │   │   ├── html/       # Arquivos HTML
│   │   │   └── js/         # Arquivos JS
│   │   └── php/            # Classes e scripts PHP comuns
│   │       ├── controlador.php (classes: Cache, Logger, Controlador)
│   │       ├── guardiao.php (classes: Guardiao)
│   │       ├── path/       # Caminhos do site
│   │       ├── xhr/        # Arquivos que respondem solicitações XHR
│   │       └── template/   # Arquivos do template para respostas HTML
│   └── sites/              # Código específico por tipo de site
│       └── [tipo]/
```


# Progresso - Arquivos modificados

Htdocs
  ├── shared/comum/php/carregador.php (objetos: Carregador)
  ├── shared/comum/php/controlador.php (objetos: Cache, Logger, Controlador)
  ├── shared/comum/php/guardiao.php (objetos: Guardiao)
  └── shared/sites/artigos/php/path/artigo.php



# Próximo passo:

Vamos analisar a possibilidade de aprimorar a estrutura de templates.

O diretório cache/template contém os templates de arquivos que podem ser utilizados para responder solicitações HTML. O conteúdo gerado através dos arquivos PHP são colocados dentro destes templates para evitar a necessidade de repetir código em todos os arquivos PHP. Além de facilitar a mudança de estilos e estrutura de arquivos HTML.

A linha 257 do arquivo carregador.php é a seguinte:

    require 'site/php/path/' . PADRAO . '.php';

A constante PADRAO é preenchida  na linha 7 do aruivo config/config.php:

    define('PADRAO', 'artigo');

Ou seja, quando o sistema recebe uma requisição e não identifica existencia de cache para esta requisição, será executado o arquivo PADRAO para aquele site. O arquivo padrão para sites de publicações de artigos é artigo.php.

Portanto, será executado 'site/php/path/artigo.php'

lembrando que "site" é um symlink para shared/sites/artigos/


O arquivo artigo.php é executado DENTRO de private function executaPadrao($comando), do objeto carregador. 

Mas esta versão do artigo.php corresponde a versão utilizada antes da modificação que estamos fazendo. Portanto, certamente haverá necessidade de ajustes.

Nosso papel, hoje, é:

1 - Ajustar artigo.php para funcionar com a nova estrutura do site
2 - Verificar se a lógica de template pode ser melhorada para ficar mais eficiente e minimalista

# Sugestões de Mudanças

1 - Alterar os objetos/funções de artigo.php para a nova formatação (com os novos objetos que são diferentes dos objetos da versão anterior)
2 - Criar a classe Montador para centralizar a lógica de montagem de artigos. Ele terá funções para montar o artigo, o sidebar, o modulos, etc.
3 - Reduzir uso de variávies globais no montador. Passar variáveis por parâmetro no executaPadrao.
4 - Verificar se /comum/php/core/montador/pesquisa.php apenas contém arquivos necessários para montagem de artigo. Caso contrário, mover o conteúdo de pesquisa.php para pesquisa_artigo.php