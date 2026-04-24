# REGRAS PRINCIPAIS

Abaixo serão listados os "METODOS PADRONIZADOS". Indicam procedimentos a serem adotados de forma padronizada em todos os arquivos.

Desta forma, deveremos:

1 - Verificar se as alternativas abaixo atendem, da melhor forma possível, as necessidades do projeto.

2 - Caso seja identificada uma alternativa mais eficiente, sugerir a alteração. 

3 - Caso seja identificada uma possibilidade de melhoria no código existente nos objetos 'observador', 'controlador' ou 'database',  É MUITO IMPORTANTE sugerir a alteração para que estes objetos tenham seus códigos cada vez mais otimizados. Este é ponto mais importante.

Mas sugira as alterações ao usuário. Só altere após a aprovação.

4 - Não alterar NENHUM dos METODOS PADROZINADOS sem a autorização do usuário.

5 - Estas alterações propostas, após aprovadas pelo usuário, devem ser realizadas no código e depois implementadas neste arquivo regras.md.

6 - A utilização dos métodos abaixo é obrigatória.


# METODOS PADRONIZADOS

## Autoload
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

## Verificação de login
Toda as páginas php que estiverem dentro de /shared/comum/php/path/lb9/php devem verificar se o usuário está logado e com a permissão adequada, utilizando o código abaixo:

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

## Recebimento de informações do font-end 
Utilizaremos o objeto 'observador' (/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/observador.php) para receber as informações enviadas pelo front-end.  

$o = $c->observador;

## Envio de informações ao front-end 

Utilizaremos o mesmo objeto 'observador' para enviar informações ao front-end.  

## Banco de dados 

Utilizaremos o objeto 'database' (/home/leo/Projetos/Web/Htdocs/shared/comum/php/src/db.php) para interagir com o banco de dados.  


## Envio de dados pelo frontend

O envio de dados, pelo frontend, seja feito através da função send, em /home/leo/Projetos/Web/Htdocs/shared/comum/estatico/js/send.js.


