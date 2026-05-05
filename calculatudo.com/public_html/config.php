<?php
// Configuracao especifica do site

date_default_timezone_set('America/Sao_Paulo');

define('SITE', $_SERVER['SERVER_NAME']);
define('BD', 'u437039667_calculatudo');
define('BD_LOGIN', 'u437039667_leo');
define('BD_SENHA', 'Entryway0-Fox0-Feeble4-Gallstone9');
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