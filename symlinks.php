<?php
/**
 * Script para criar symlinks automaticamente ao migrar de servidor
 * Coloque este arquivo na raiz do public_html e execute via CLI ou navegador
 */

// Configurações
$BASE_DIR = dirname(__DIR__);
$SHARED_DIR = $BASE_DIR . '/shared';

// Sites que usam estrutura completa de artigos
$SITES_ARTIGOS = ['eupenso.com'];

// Symlinks para sites de artigos
$SYMLINKS_ARTIGOS = [
    'comum' => '/shared/comum',
    'log' => '/shared/logs',
    'site' => '/shared/sites/artigos',
    'cache/css' => '/shared/cache/css',
    'cache/js' => '/shared/cache/js',
];

// Symlinks para calculatudo (apenas comum e log)
$SYMLINKS_CALCULATUDO = [
    'comum' => '/shared/comum',
    'log' => '/shared/logs',
];

// Cores para output CLI
$isCliMode = php_sapi_name() === 'cli';

function output($msg, $type = 'info') {
    global $isCliMode;

    $colors = [
        'success' => $isCliMode ? "\033[32m✓\033[0m" : "✓",
        'error' => $isCliMode ? "\033[31m✗\033[0m" : "✗",
        'warning' => $isCliMode ? "\033[33m⚠\033[0m" : "⚠",
        'info' => $isCliMode ? "\033[36mℹ\033[0m" : "ℹ",
    ];

    $prefix = $colors[$type] ?? $colors['info'];

    if ($isCliMode) {
        echo "$prefix $msg\n";
    } else {
        echo "<p style='margin: 5px 0;'>$prefix $msg</p>";
    }
}

function createSymlink($target, $link) {
    global $BASE_DIR;

    $fullLink = $BASE_DIR . '/' . $link;
    $fullTarget = $BASE_DIR . $target;

    // Verifica se o link já existe
    if (file_exists($fullLink) || is_link($fullLink)) {
        if (is_link($fullLink)) {
            $currentTarget = readlink($fullLink);
            if ($currentTarget === $fullTarget) {
                output("Symlink já existe: $link → $target", 'info');
                return true;
            } else {
                output("Symlink existe mas aponta para local diferente: $link → $currentTarget", 'warning');
                return false;
            }
        } else {
            output("Arquivo/diretório já existe (não é symlink): $link", 'warning');
            return false;
        }
    }

    // Verifica se o target existe
    if (!file_exists($fullTarget)) {
        output("Target não existe: $fullTarget", 'error');
        return false;
    }

    // Cria diretório pai se não existir
    $linkDir = dirname($fullLink);
    if (!is_dir($linkDir)) {
        mkdir($linkDir, 0755, true);
        output("Criado diretório: " . basename(dirname($link)), 'success');
    }

    // Cria o symlink
    if (symlink($fullTarget, $fullLink)) {
        output("Symlink criado: $link → $target", 'success');
        return true;
    } else {
        output("Erro ao criar symlink: $link", 'error');
        return false;
    }
}

// Header
if (!$isCliMode) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Symlinks</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}</style></head><body>";
}

output("=== Iniciando configuração de symlinks ===", 'info');
output("Base: $BASE_DIR", 'info');
echo $isCliMode ? "\n" : "<br>";

// Verifica se diretório shared existe
if (!is_dir($SHARED_DIR)) {
    output("ERRO: Diretório shared não encontrado em $SHARED_DIR", 'error');
    exit(1);
}

// Processa sites de artigos
output("--- Sites de Artigos ---", 'info');
foreach ($SITES_ARTIGOS as $site) {
    $siteDir = $BASE_DIR . '/' . $site;

    if (!is_dir($siteDir)) {
        output("Site não encontrado, pulando: $site", 'warning');
        continue;
    }

    output("Processando: $site", 'info');

    foreach ($SYMLINKS_ARTIGOS as $link => $target) {
        $fullLink = $site . '/' . $link;
        createSymlink($target, $fullLink);
    }

    echo $isCliMode ? "\n" : "<br>";
}

// Processa calculatudo
output("--- Calculatudo ---", 'info');
$calculatudoDir = $BASE_DIR . '/calculatudo.com';

if (is_dir($calculatudoDir)) {
    output("Processando: calculatudo.com", 'info');

    foreach ($SYMLINKS_CALCULATUDO as $link => $target) {
        $fullLink = 'calculatudo.com/' . $link;
        createSymlink($target, $fullLink);
    }
} else {
    output("Site não encontrado, pulando: calculatudo.com", 'warning');
}

echo $isCliMode ? "\n" : "<br>";
output("=== Configuração concluída ===", 'success');

if (!$isCliMode) {
    echo "</body></html>";
}
