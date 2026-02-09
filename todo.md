# TODO - Sugestoes de Melhoramento (src)

## Criticos

1. Corrigir `pnf()` que hoje interrompe antes da logica de bloqueio.
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/guardiao.php:120`
- Problema: existe `echo $this->url; die();` no inicio do metodo, tornando o restante do fluxo inacessivel.
- Ajuste: remover esse retorno antecipado e manter o fluxo completo:
  lista negra -> lista branca -> validacao googlebot -> adicionar na lista negra.
# FEITO

2. Padronizar TTL da lista branca para `30s` (igual lista negra).
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/guardiao.php:82`
- Problema: lista branca usa `300` segundos, mas a diretriz define TTL fixo de `30` segundos.
- Ajuste: trocar `300` por `self::TTL`.
# RECUSADO - A lista branca pode ter duração maior que a lista negra

3. Blindar SQL no `observador` em update por ID.
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/observador.php:200`
- Problema: `$id` entra na query sem cast/validacao forte no `WHERE`.
- Ajuste: forcar inteiro (`(int)$id`) antes de montar SQL ou migrar para prepared statement.
# RECUSADO - ID é validado sempre como número pela função valida().

4. Validar/whitelist de `order` nas queries do montador de artigo.
- Arquivos:
  `hostgator/Htdocs/shared/comum/php/src/monta_artigo.php:203`
  `hostgator/Htdocs/shared/comum/php/src/monta_artigo.php:222`
- Problema: `order` e concatenado em SQL, abrindo risco de injecao se vier de entrada externa.
- Ajuste: aceitar apenas colunas/direcoes predefinidas.
# RECUSADO - Não há possibilidade de entrada externa.


## Altos

1. Evitar fatal no `autenticador` quando `observador` for `null`.
- Arquivos:
  `hostgator/Htdocs/shared/comum/php/src/autenticador.php:47`
  `hostgator/Htdocs/shared/comum/php/src/autenticador.php:80`
- Problema: acesso direto a `$this->observador->guardiao` sem checagem.
- Ajuste: proteger com condicao, ou injetar `guardiao` diretamente no `autenticador`.
# FEITO

2. Instanciar `guardiao` no construtor do `observador`.
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/observador.php:11`
- Problema: diretriz atual pede guardiao no construtor do observador para mitigar abuso em XHR.
- Ajuste: criar/injetar `guardiao` logo no `__construct`.
# RECUSADO - O observador não utiliza o guardião

3. Remover dependencia de `global $amp`.
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/carregador.php:67`
- Problema: estado global aumenta acoplamento e risco de efeito colateral.
- Ajuste: manter estado AMP apenas no objeto (`$this->amp`) e repassar por parametro onde necessario.
# FEITO

## Medios

1. Simplificar fluxo do `carregador` (metodos nao usados).
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/carregador.php:193`
- Problema: `localizaPath()` e `localizaPathComum()` estao definidos, mas o fluxo atual usa `executaPadrao()` com include direto.
- Ajuste: integrar esses metodos ao fluxo atual ou remover para reduzir complexidade.
# RECUSADO - Os métodos são usados em outros arquivos.

2. Endurecer origem de IP no `guardiao`.
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/guardiao.php:154`
- Problema: `HTTP_CF_CONNECTING_IP` e aceito sem validar contexto de proxy confiavel.
- Ajuste: usar esse header apenas quando a requisicao vier de proxy conhecido.
# RECUSADO - O header é usado apenas quando a requisição vem de proxy conhecido.

3. Avancar para prepared statements no restante do `database`.
- Arquivo: `hostgator/Htdocs/shared/comum/php/src/db.php:72`
- Problema: ainda ha varios metodos com SQL concatenado (`select/v_select/f_select/query`).
- Ajuste: criar variantes preparadas para chamadas com parametros externos.
