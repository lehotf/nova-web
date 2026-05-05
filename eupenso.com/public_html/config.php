<?php
// Configuracao especifica do site

date_default_timezone_set('America/Sao_Paulo');

define('SITE', $_SERVER['SERVER_NAME']);
define('BD', 'leo19961_eupenso');
define('BD_LOGIN', 'leo19961_db');
define('BD_SENHA', 'SenhaDoBD');
define('DNS_SITE', 'https://' . SITE);
define('YOUTUBE_CHANNEL', 'eupenso');
define('SITE_TITULO', 'Eu Penso');
define('SITE_NAME', 'Eu Penso');
define('MAX_IN_ROOT', 8);
define('SEARCH','013594555885008672798:pvaspepxciw');
define('AMP', true);
define('DESCRICAO', 'Uma conversa sobre os fatos sociais e políticos.');
define('LOCALHOST', $_SERVER['SERVER_NAME'] != 'eupenso.com' ? 1 : 0);

#MUDAR
define('CACHE_ATIVO', false);
define('DEBUG', false);

#APAGAR
