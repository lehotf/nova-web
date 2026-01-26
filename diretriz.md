# Diretrizes de Codificacao (Htdocs)

## Escopo e organizacao
- Trabalhar apenas dentro de `Htdocs`.
- `Htdocs2` serve apenas para consulta e comparacao, é a versão antiga do site.
- Se um arquivo nao existir em `Htdocs`, procurar o equivalente em `Htdocs2` para entender a logica anterior.
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
- As configuracoes comuns a todos os sites ficam em `shared/comum/config/` (na estrutura antiga havia `config.php`; atualmente existe `shared/comum/config/ad.php`).
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
- Em falha de login, o `autenticador` deve usar o `Guardiao` para adicionar o IP na lista negra.
- A lista negra deve usar TTL fixo de 30s.
- Lista negra e lista branca devem ser persistidas via arquivos em `$_SERVER['DOCUMENT_ROOT']/log/lista_negra` e `$_SERVER['DOCUMENT_ROOT']/log/lista_branca` usando apenas `touch`.
- Ao verificar, usar apenas `mtime` para expirar (se passou do TTL, apagar) e renovar (se dentro do TTL, dar `touch`).

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
- Em `cacheTemplates.php`, manter o arquivo minimalista: sem fallback por `$_GET` e sem criar variaveis para `DEBUG` (usar a constante diretamente).
- No Observador, o Guardiao deve ser instanciado no construtor para mitigar abusos por repetidas requisicoes em endpoints XHR.
- No Observador, `valida()` define tipos e `salva()` deve respeitar a instrucao (`tipo` e `salva`) para manter o comportamento da versao antiga.
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

# Templates e cache de HTML

- Templates fonte ficam em `shared/comum/php/template` (ex.: `index.html`, `artigo.html`, `_artigo.html`, `_adm.html`).
- Os templates sao pre-processados e gerados em `cache/template`.
- Prefixo `_` no template indica instrucao de cabeçalho (ex.: `_noRoot`) e define se o template entra ou nao no root.
- `Carregador::prepara()` carrega o HTML ja gerado em `cache/template` e faz substituicoes de tokens do tipo `[chave]`.
- A pagina root (casca) era `root.html`/`amp.html` na versao antiga; na versao nova, ainda precisamos definir/portar esses arquivos.

# Gerador de templates (cacheTemplates)

- Ponto de entrada: `shared/comum/php/sistema/gerador/cacheTemplates.php`.
- Core: `shared/comum/php/sistema/gerador/cacheTemplatesCore.php`.
- Funcao: gerar arquivos em `cache/template` a partir dos templates de `shared/comum/php/template` e, se existir, `site/php/template`.
- Remove bloco `<!--start-->...<!--end-->` (analytics) durante a geracao.
- Em modo `DEBUG` falso, substitui referencias `comum/site/estatico` por `cache`.
- Gera versao AMP adicionando sufixo `_amp.html`.
- Regra atual: manter `cacheTemplates.php` minimalista (sem GET e sem variavel local para DEBUG).


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
│   │   │   └── ad.php      # Configuracoes comuns (atual)
│   │   ├── estatico/       # Arquivos estáticos compartilhados
│   │   │   ├── css/        # Arquivos CSS
│   │   │   ├── fonts/      # Arquivos de fontes
│   │   │   ├── html/       # Arquivos HTML
│   │   │   └── js/         # Arquivos JS
│   │   └── php/            # Classes e scripts PHP comuns
│   │       ├── controlador.php (classes: Cache, Logger, Controlador)
│   │       ├── guardiao.php (classes: Guardiao)
│   │       ├── montador/   # Montagem de conteudo (ex.: MontaArtigo)
│   │       ├── sistema/    # Ferramentas internas (ex.: gerador de templates)
│   │       └── template/   # Templates fonte (HTML)
│   └── sites/              # Código específico por tipo de site
│       └── [tipo]/
```

Leia os arquivos `shared/comum/php/controlador.php`, `shared/comum/php/guardiao.php` e `shared/comum/php/carregador.php` para entender o fluxo do sistema.

# Arquivos-chave (localizacao e papel)

- `shared/comum/php/controlador.php`: Cache (HTML), Logger e orquestracao geral.
- `shared/comum/php/guardiao.php`: IP/URL, listas negra/branca e PNF.
- `shared/comum/php/carregador.php`: AMP, cache de template e roteamento para `site/php/path/*`.
- `shared/sites/artigos/php/path/artigo.php`: consulta artigo no BD e delega para `MontaArtigo`.
- `shared/comum/php/montador/montaArtigo.php`: monta dados do artigo e escolhe template `artigo` ou `_artigo`.
- `shared/comum/php/montador/pesquisa.php`: auxiliares de links/tags, usados por artigos/root.
- `shared/comum/php/sistema/gerador/cacheTemplates.php`: executa geracao do cache de templates.
- `shared/comum/php/sistema/gerador/cacheTemplatesCore.php`: logica de compactacao, root e geracao AMP.

# Progresso - Arquivos modificados

Htdocs
  ├── shared/comum/php/carregador.php (objetos: Carregador)
  ├── shared/comum/php/controlador.php (objetos: Cache, Logger, Controlador)
  ├── shared/comum/php/guardiao.php (objetos: Guardiao)
  ├── shared/sites/artigos/php/path/artigo.php
  ├── shared/comum/php/sistema/gerador/cacheTemplates.php (atualizado para nova estrutura)
  └── shared/comum/php/sistema/gerador/cacheTemplatesCore.php (atualizado para nova estrutura)

# Em execucao

Sessao #Em Execucao:
- Objetivo: revisar e simplificar o gerador de templates para a nova estrutura (fonte em `shared/comum/php/template`, cache em `cache/template`).
- Ajustes feitos: reescrita de `cacheTemplates.php` e `cacheTemplatesCore.php` para o novo layout (sem `config_externo.php`, sem `core/template`, sem `root_config.php`/`root_script.php`).
- Observacao: `root.html` e `amp.html` ainda nao existem em `Htdocs`. Na geracao atual, o root usa fallback para `index.html` (que hoje e apenas `[conteudo]`). Precisamos decidir se vamos portar `root.html`/`amp.html` da versao antiga (`Htdocs2/shared/comum/config/`) ou criar novos.
- O template de artigo atual usa `MontaArtigo` e carrega `artigo.html`/`_artigo.html` via `Carregador::prepara()`.

Notas tecnicas do fluxo atual:
- `Carregador::executaPadrao()` chama `site/php/path/PADRAO.php`.
- Para artigos, `PADRAO = 'artigo'` e o arquivo fica em `shared/sites/artigos/php/path/artigo.php`.
- Esse arquivo monta o artigo usando `MontaArtigo` e injeta no template via `prepara()`.


# Sugestões de Mudanças

1 - Alterar os objetos/funções de artigo.php para a nova formatação (com os novos objetos que são diferentes dos objetos da versão anterior)
2 - Criar a classe Montador para centralizar a lógica de montagem de artigos. Ele terá funções para montar o artigo, o sidebar, o modulos, etc.
3 - Reduzir uso de variávies globais no montador. Passar variáveis por parâmetro no executaPadrao.
4 - Verificar se /comum/php/core/montador/pesquisa.php apenas contém arquivos necessários para montagem de artigo. Caso contrário, mover o conteúdo de pesquisa.php para pesquisa_artigo.php
