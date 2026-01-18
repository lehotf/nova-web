# Diretrizes de Codificacao (Htdocs)

## Escopo e organizacao
- Trabalhar apenas dentro de `Htdocs`.
- `Htdocs2` serve apenas para consulta e comparacao.
- Codigo compartilhado entre todos os sites fica em `shared/comum`.
- Codigo compartilhado por tipo de site fica em `shared/sites/<tipo>`.
- Cache compartilhado de assets (css/js) fica em `shared/cache`.
- O arquivo `index.php` fica na raiz de cada site e serve como ponto de entrada desse site.
- O `index.php` usa apenas componentes comuns em `shared`, sem dados especificos hardcoded.
- Cada site possui um link simbolico `comum` apontando para `shared/comum` para facilitar os includes.
- Nao e necessario verificar existencia de modulos, recursos ou diretorios base; eles sempre existem.

## Configuracao
- Cada site tera um unico arquivo `config.php` dentro do seu diretorio (ex.: `eupenso.com/config/config.php`).
- O `config.php` do site deve conter apenas dados especificos daquele site (ex.: IDs, titulo, descricao, flags).
- As configuracoes comuns a todos os sites ficam em `shared/comum/config/config.php`.
- O carregamento de configuracao acontece no `index.php` do site, incluindo o config comum e o config do site via `comum/`.
- O `index.php` nao deve ter valores hardcoded por dominio; toda especificidade vem do `config.php` do site.
- Configuracoes que podem variar por site (ex.: `CACHE_ATIVO`) devem ficar no `config.php` do site, nao no comum.

## Cache
- O diretorio de cache HTML e montado diretamente no `GerenciadorCache` como `cache/html` (caminho relativo ao `index.php`).
- O `GerenciadorCache` recebe o `Guardiao` existente (injeção) para evitar criar outro e repetir resolucoes de IP/URL.
- O `GerenciadorCache` nao deve criar um `Guardiao`; o `Guardiao` e criado uma unica vez no inicio e compartilhado.
- O `GerenciadorCache` deve ser simples: `buscar()` e `salvar()` e nada de verificacoes extras.
- O `Verificador` recebe o `Guardiao` existente e usa o `GerenciadorCache` antes de chamar o construtor de conteudo.

## Bots e bloqueio
- A primeira verificacao deve ser a lista negra; se o IP estiver nela, retornar 404 vazio e renovar o TTL.
- A lista branca so deve ser consultada imediatamente antes de um lookup reverso (para evitar lookup desnecessario).
- O arquivo `controlador.php` possui um objeto `Guardiao`, responsavel por limpar URL, gerenciar listas branca/negra e realizar lookup reverso.
- O TTL das listas branca e negra e fixo em 30 segundos, sem parametros.
- O `Guardiao` e o proprietario do IP e da URL tratada; outras funcoes consultam essas informacoes nele.
- O `Guardiao` responde `PNF` (404) e decide blacklist/whitelist: primeiro verifica lista negra, depois lista branca, depois valida Googlebot, e so entao adiciona na lista negra.
- O `Guardiao` deve ser criado e checado antes de carregar configs e outros arquivos do site.
- Nao repetir verificacoes de blacklist em outras classes; o `Guardiao` faz isso uma unica vez no inicio.

## Linguagem e nomes
- Nomes de classes, variaveis e funcoes em portugues.
- Usar `set` e `get` nos metodos de atribuicao e leitura (ex.: `setCache`, `getCache`).
- Preferir nomes simples e logica direta (evitar logica negativa em flags e funcoes).

## Logica
- Evitar duplicar logicas antigas quando estiverem confusas; ajustar para clareza.
- Manter a funcionalidade equivalente, mas com estrutura mais clara e segura.
- Evitar variaveis e constantes redundantes; usar `__DIR__` quando o caminho do arquivo atual for suficiente.
- Nao inventar APIs ou callbacks sem combinacao previa; manter o fluxo direto e acordado.

## Constantes x variaveis
- Usar `define()` (ou `const`) apenas para valores realmente globais e imutaveis.
- Preferir variaveis locais para caminhos e valores usados apenas no arquivo atual.

## Processo
- Evoluir passo a passo, com ajustes pequenos e validaveis.
- Sempre revisar o que foi feito e adequar antes de seguir.
- Sempre que surgir uma nova regra durante a conversa, adicionar o topico correspondente em `diretriz.md`.
