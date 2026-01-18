# Diretrizes de Codificacao (Htdocs2)

## Escopo e organizacao
- Trabalhar apenas dentro de `Htdocs`.
- `Htdocs2` serve apenas para consulta e comparacao.
- Codigo compartilhado entre todos os sites fica em `shared/comum`.
- Codigo compartilhado por tipo de site fica em `shared/sites/<tipo>`.
- Cache compartilhado de assets (css/js) fica em `shared/cache`.
- Arquivos compartilhados (ex.: `index.php`) nao podem conter dados especificos de um site.

## Configuracao
- Cada site tera um unico arquivo `config.php` dentro do seu diretorio (ex.: `eupenso.com/config/config.php`).
- O `config.php` do site deve conter apenas dados especificos daquele site (ex.: IDs, titulo, descricao, flags).
- As configuracoes comuns a todos os sites ficam em `shared/comum/config/config.php`.
- O carregamento de configuracao deve acontecer no `index.php` compartilhado, identificando o site pelo `HTTP_HOST` e incluindo o `config.php` do site e o config comum.
- O `index.php` nao deve ter valores hardcoded por dominio; toda especificidade vem do `config.php` do site.

## Linguagem e nomes
- Nomes de classes, variaveis e funcoes em portugues.
- Usar `set` e `get` nos metodos de atribuicao e leitura (ex.: `setCache`, `getCache`).
- Preferir nomes simples e logica direta (evitar logica negativa em flags e funcoes).

## Logica
- Evitar duplicar logicas antigas quando estiverem confusas; ajustar para clareza.
- Manter a funcionalidade equivalente, mas com estrutura mais clara e segura.

## Processo
- Evoluir passo a passo, com ajustes pequenos e validaveis.
- Sempre revisar o que foi feito e adequar antes de seguir.
- Sempre que surgir uma nova regra durante a conversa, adicionar o topico correspondente em `diretriz.md`.
