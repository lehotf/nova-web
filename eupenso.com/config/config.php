<?php
// Configuracao especifica do site

define('NOME_SITE', 'Eu Penso');
define('CAMINHO', $_SERVER['DOCUMENT_ROOT']);
define('SITE', $_SERVER['SERVER_NAME']);
define('DESCRICAO_SITE', 'Uma conversa sobre os fatos sociais e politicos.');
define('PADRAO', 'artigo');
define('BD', 'eupens16_eupenso');
define('BD_LOGIN', 'eupens16_site');
define('DNS_SITE', 'https://' . SITE);
date_default_timezone_set('America/Sao_Paulo');
define('BD_SENHA', 'segredo');
define('ANALYTICS', 'UA-78519801-1');
define('FACEBOOK_ID', '638596883012672');
define('YOUTUBE_CHANNEL', 'eupenso');
define('SITE_TITULO', 'Eu Penso');
define('SITE_NAME', 'Eu Penso');
define('DESCRICAO', 'Uma conversa sobre os fatos sociais e políticos.');
define('MAX_IN_ROOT', 8);
define('SEARCH','013594555885008672798:pvaspepxciw');
define('AMP', true);

#MUDAR
define('CACHE_ATIVO', false);

#APAGAR
define('LOCALHOST',true);