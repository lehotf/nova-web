<?php
// Configuracao comum a todos os sites

date_default_timezone_set('America/Sao_Paulo');

// Define se o cache deve ser usado por padrao
if (!defined('CACHE_ATIVO')) {
    define('CACHE_ATIVO', true);
}
