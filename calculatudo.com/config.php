<?php
// Configuracao especifica do site

date_default_timezone_set('America/Sao_Paulo');

define('SITE', $_SERVER['SERVER_NAME']);
define('BD', 'leo19961_calculatudo');
define('BD_LOGIN', 'leo19961_db');
define('BD_SENHA', 'SenhaDoBD');
define('DNS_SITE', 'https://' . SITE);
define('YOUTUBE_CHANNEL', 'calculatudo');
define('SITE_TITULO', 'CalculaTUDO - Calculadoras Gratuitas - Calculadoras Financeiras');
define('SITE_NAME', 'CalculaTUDO');
define('MAX_IN_ROOT', 8);
define('SEARCH','013594555885008672798:pvaspepxciw');
define('AMP', false);
define('DESCRICAO', 'Uma maneira mais simples e precisa de calcular o que você precisa. Calculadora Financeira. Finanças.');
define('LOCALHOST', $_SERVER['SERVER_NAME'] != 'calculatudo.com' ? 1 : 0);

#MUDAR
define('CACHE_ATIVO', false);
define('DEBUG', false);