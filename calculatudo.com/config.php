<?php
// Configuracao especifica do site

date_default_timezone_set('America/Sao_Paulo');

define('SITE', $_SERVER['SERVER_NAME']);
define('BD', 'eupens16_calculatudo');
define('BD_LOGIN', 'eupens16_site');
define('DNS_SITE', 'https://' . SITE);
define('BD_SENHA', 'segredo');
define('ANALYTICS', 'UA-78519801-3');
define('YOUTUBE_CHANNEL', 'calculatudo');
define('SITE_TITULO', 'CalculaTUDO - Calculadoras Gratuitas - Calculadoras Financeiras');
define('SITE_NAME', 'CalculaTUDO');
define('DESCRICAO', 'Uma maneira mais simples e precisa de calcular o que você precisa.');
define('MAX_IN_ROOT', 8);
define('SEARCH','013594555885008672798:pvaspepxciw');
define('AMP', true);

#MUDAR
define('CACHE_ATIVO', false);
define('DEBUG', false);
define('LOCALHOST',true);