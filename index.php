<?php
require __DIR__ . '/shared/comum/php/cache.php';

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$host = strtolower($host);
$host = preg_replace('#[^a-z0-9\.\-]#', '', $host);

if ($host === '') {
    http_response_code(404);
    echo 'Site nao identificado';
    exit;
}

$diretorioBase = __DIR__;
$diretorioSite = $diretorioBase . '/' . $host;

if (!is_dir($diretorioSite)) {
    http_response_code(404);
    echo 'Site inexistente';
    exit;
}

define('DIRETORIO_BASE', $diretorioBase);
define('DIRETORIO_SITE', $diretorioSite);
define('DIRETORIO_SHARED', $diretorioBase . '/shared');
define('SITE_HOST', $host);

$arquivoConfigComum = DIRETORIO_SHARED . '/comum/config/config.php';
$arquivoConfigSite = DIRETORIO_SITE . '/config/config.php';

if (is_file($arquivoConfigComum)) {
    require $arquivoConfigComum;
}

if (is_file($arquivoConfigSite)) {
    require $arquivoConfigSite;
} else {
    http_response_code(500);
    echo 'Configuracao do site ausente';
    exit;
}

$diretorioCacheHtml = DIRETORIO_SITE . '/cache/html';
$cache = new GerenciadorCache($diretorioCacheHtml, CACHE_ATIVO);

$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$conteudoCache = $cache->buscar($url);

if ($conteudoCache !== false) {
    echo $conteudoCache;
    exit;
}

echo 'Sem cache';
