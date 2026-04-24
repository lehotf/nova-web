# Relatório de Auditoria

## Escopo

Auditoria dos arquivos PHP em:

- `/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php`

E revisão dos objetos centrais:

- `observador`
- `controlador`
- `database`

Base de comparação:

- [`regras.md`](/home/leo/Projetos/Web/Htdocs/regras.md)

## Critérios Verificados

### Regras obrigatórias

1. Uso de `autoload`:

```php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
```

2. Verificação de login nas páginas PHP do diretório:

```php
$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
```

3. Recebimento de informações do front-end via `observador`:

```php
$o = $c->observador;
```

4. Envio de informações ao front-end via `observador`

5. Interação com banco via objeto `database`

## Resumo Executivo

- Total de arquivos PHP auditados: `18`
- Endpoints aderentes ao padrão principal: `8`
- Endpoints com desvio de padronização: `4`
- Arquivos com exceção ou regra ambígua: `2`
- Helpers internos incluídos por outros scripts: `4`

### Conclusão geral

O diretório está parcialmente aderente às regras.

Os principais desvios estão concentrados em endpoints mais novos que:

- leem `php://input` manualmente
- respondem com `echo json_encode(...)`
- instanciam `database` diretamente em vez de reutilizar `$c->db`

Além disso, os objetos `observador`, `controlador` e `database` funcionam, mas ainda não representam a melhor solução possível na forma atual.

## Achados Principais

### 1. Erro funcional grave em `autenticacao/login.php`

Arquivo:

- [autenticacao/login.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/login.php#L1)

Problemas identificados:

- [autenticacao/login.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/login.php#L6) usa `$c` dentro da função `lockout()` sem recebê-lo por parâmetro e sem declarar `global`.
- [autenticacao/login.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/login.php#L25) usa `$tempo` fora do escopo da função.

Impacto:

- O fluxo de lockout pode falhar justamente no momento em que deveria bloquear o acesso.

### 2. `observador` altera os dados cedo demais

Arquivo:

- [observador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/observador.php#L1)

Problema identificado:

- [observador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/observador.php#L30) aplica `db->protege()` em toda string recebida.
- [observador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/observador.php#L66) depois devolve esse valor como dado normal de negócio.
- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L68) e [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L150) já usam prepared statements.

Impacto:

- O dado de entrada pode chegar mutado ao domínio da aplicação antes de qualquer regra de negócio.
- Há conflito conceitual entre escape manual e prepared statements.

### 3. Endpoints fora do padrão obrigatório de entrada e saída

Arquivos afetados:

- [admin_tags.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_tags.php#L1)
- [artigos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/artigos.php#L1)
- [admin_acessos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_acessos.php#L1)
- [admin_clear_acessos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_clear_acessos.php#L1)

Desvios observados:

- leitura manual de `php://input`
- uso direto de `$_GET`, `$_POST` e `$_FILES` sem centralização no `observador`
- resposta com `echo json_encode(...)` em vez de `observador->envia()` / `observador->erro()`
- criação direta de `new database(...)`

Exemplos:

- [admin_tags.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_tags.php#L11)
- [admin_tags.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_tags.php#L17)
- [admin_tags.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_tags.php#L38)
- [artigos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/artigos.php#L27)
- [artigos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/artigos.php#L33)
- [artigos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/artigos.php#L76)
- [admin_acessos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_acessos.php#L29)
- [admin_clear_acessos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_clear_acessos.php#L33)

Impacto:

- Quebra do padrão obrigatório
- Duplicação de protocolo de resposta
- Mais custo de manutenção
- Menor consistência entre endpoints

### 4. `controlador` não está conseguindo impor reutilização uniforme do `database`

Arquivo:

- [controlador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/controlador.php#L1)

Pontos observados:

- [controlador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/controlador.php#L119) cria `database` sempre que `observador` ou `autenticador` são ativados.
- [controlador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/controlador.php#L121) injeta esse `database` em `observador`.

Mesmo assim, estes arquivos abrem outra conexão:

- [admin_tags.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_tags.php#L11)
- [artigos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/artigos.php#L27)
- [cacheTemplatesCore.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/gerador/cacheTemplatesCore.php#L266)

Impacto:

- Conexões duplicadas
- API aplicada de forma inconsistente
- Mais acoplamento aos detalhes de infraestrutura

### 5. `database` encerra execução com `die()`

Arquivo:

- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L1)

Ocorrências:

- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L22)
- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L99)
- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L116)
- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L138)
- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L153)
- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php#L177)

Impacto:

- O endpoint perde a oportunidade de tratar erro de forma padronizada
- Dificulta logging contextual
- Dificulta rollback e resposta uniforme via `observador`

### 6. A regra de login em `regras.md` está ampla demais para a estrutura real da pasta

Arquivos sem `acesso(2)`:

- [autenticacao/login.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/login.php)
- [autenticacao/get_token.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/get_token.php)
- [compactaJS.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/compactaJS.php)
- [compactaCSS.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/compactaCSS.php)
- [conversorDePath.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/conversorDePath.php)
- [cacheTemplatesCore.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/gerador/cacheTemplatesCore.php)

Leitura correta desses casos:

- `login.php` e `get_token.php` são endpoints de autenticação e precisam ser tratados como exceção formal.
- `compactaJS.php`, `compactaCSS.php`, `conversorDePath.php` e `cacheTemplatesCore.php` são helpers incluídos por outros scripts, não endpoints autônomos.

Impacto:

- A redação atual da regra gera falso positivo na auditoria.

## Conformidade por Arquivo

### Endpoints conformes ao padrão principal

- [admin_toggle_cache.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_toggle_cache.php)
- [ultimos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/ultimos.php)
- [clear_cache.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/clear_cache.php)
- [autenticacao/cookie.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/cookie.php)
- [compactador/compacta.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/compacta.php)
- [admin_cache_status.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_cache_status.php)
- [gerador/cacheTemplates.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/gerador/cacheTemplates.php)
- [sitemap.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/sitemap.php)

### Endpoints com desvio de padronização

- [admin_tags.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_tags.php)
  - usa `new database(...)`
  - lê `php://input` manualmente
  - responde com `echo json_encode(...)`

- [artigos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/artigos.php)
  - usa `new database(...)`
  - lê entrada manualmente
  - responde fora do `observador`

- [admin_acessos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_acessos.php)
  - saída fora do `observador`

- [admin_clear_acessos.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/admin_clear_acessos.php)
  - saída fora do `observador`

### Exceções ou casos ambíguos

- [autenticacao/login.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/login.php)
  - sem `acesso(2)` por natureza do fluxo
  - contém bug funcional no lockout

- [autenticacao/get_token.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/autenticacao/get_token.php)
  - sem `acesso(2)`
  - precisa ser classificado como endpoint público ou protegido por outra regra

### Helpers internos incluídos por outros scripts

- [compactador/compactaJS.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/compactaJS.php)
- [compactador/compactaCSS.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/compactaCSS.php)
- [compactador/conversorDePath.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/compactador/conversorDePath.php)
- [gerador/cacheTemplatesCore.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/path/lb9/php/gerador/cacheTemplatesCore.php)

Observação:

Esses arquivos não devem ser avaliados pelo mesmo critério de endpoint HTTP autônomo.

## Avaliação dos Objetos Centrais

### `observador`

Arquivo:

- [observador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/observador.php)

Situação atual:

- Centraliza entrada JSON
- Oferece tipagem básica (`texto`, `numero`, `vetor`, `valida`)
- Centraliza envio de resposta JSON

Pontos positivos:

- Padroniza resposta para front-end
- Reduz repetição em endpoints simples
- Já possui integração com `database`

Pontos fracos:

- mistura leitura de input com escaping SQL
- não cobre naturalmente `GET`, `POST multipart` e arquivos enviados
- acopla validação de entrada com persistência

Conclusão:

- Entrega valor, mas não está na melhor forma possível.

### `controlador`

Arquivo:

- [controlador.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/controlador.php)

Situação atual:

- Centraliza criação de `logger`, `guardiao`, `database`, `observador` e `autenticador`

Pontos positivos:

- reduz bootstrap repetido
- facilita padronização dos endpoints

Pontos fracos:

- não impede que endpoints criem nova conexão manualmente
- cria dependências por flags, o que gera variações de estado
- não deixa explícito quais objetos devem sempre ser reutilizados

Conclusão:

- Funciona, mas ainda não impõe o padrão com rigidez suficiente.

### `database`

Arquivo:

- [db.php](/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php)

Situação atual:

- usa `mysqli`
- usa prepared statements
- fornece `select`, `v_select`, `f_select`, `query` e `queryXHR`

Pontos positivos:

- prepared statements já reduzem risco de SQL injection
- normalização de parâmetros está melhor do que interpolação manual

Pontos fracos:

- uso de `die()` como tratamento de erro
- charset configurado como `utf8` em vez de `utf8mb4`
- API mistura retorno de `mysqli_result`, `true`, `false` e encerramento abrupto

Conclusão:

- Está funcional, mas ainda não representa a melhor solução possível para manutenção e robustez.

## Recomendações

### Prioridade alta

1. Corrigir o bug de escopo em `autenticacao/login.php`
2. Parar de escapar input no construtor de `observador`
3. Padronizar `admin_tags.php` e `artigos.php` para uso de `$c->db` e `observador`

### Prioridade média

1. Padronizar saída de `admin_acessos.php` e `admin_clear_acessos.php` via `observador`
2. Revisar `get_token.php` para definir se é endpoint público ou protegido
3. Redefinir em `regras.md` a diferença entre endpoint e helper interno

### Prioridade estrutural

1. Fazer `database` lançar exceções ou retornar erro tratável em vez de `die()`
2. Migrar charset para `utf8mb4`
3. Evoluir `observador` para suportar de forma clara JSON, query string e multipart

## Pontos que Precisam de Decisão

1. `login.php` e `get_token.php` devem ser exceções formais em `regras.md`?
2. Helpers internos nessa pasta continuam no mesmo diretório ou devem ser movidos?
3. A padronização do `observador` deve passar a cobrir também `GET`, `POST multipart` e upload?

## Encerramento

Situação final da auditoria:

- Há boa base de padronização já implantada
- Existem desvios concretos em endpoints importantes
- Há melhorias estruturais relevantes em `observador`, `controlador` e `database`
- Nenhuma alteração de código foi aplicada nesta etapa

