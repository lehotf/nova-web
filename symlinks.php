<?php
/**
 * Script para criar os symlinks do site atual.
 * Coloque este arquivo na raiz do site e execute via CLI ou navegador.
 */

// Configurações
$SITE_DIR = __DIR__;
$SITE_ROOT_DIR = $SITE_DIR;

// Em hospedagens como a Hostinger, o script pode ficar em
// .../domains/<site>/public_html, enquanto o shared fica em .../domains/shared.
if (basename($SITE_DIR) === 'public_html') {
    $SITE_ROOT_DIR = dirname($SITE_DIR);
}

$SITE_NAME = basename($SITE_ROOT_DIR);
$DOMAINS_DIR = dirname($SITE_ROOT_DIR);
$SHARED_DIR = $DOMAINS_DIR . '/shared';

// Symlinks para sites de artigos
$SYMLINKS_ARTIGOS = [
    'comum' => '/shared/comum',
    'log' => '/shared/logs',
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

    if (function_exists('flush')) {
        flush();
    }
}

function createSymlink($target, $link) {
    global $SITE_DIR, $DOMAINS_DIR;

    $fullLink = $SITE_DIR . '/' . $link;
    $fullTarget = $DOMAINS_DIR . '/' . ltrim($target, '/');

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

    output("Target resolvido: $fullTarget", 'info');

    // Cria diretório pai se não existir
    $linkDir = dirname($fullLink);
    if (!is_dir($linkDir)) {
        if (!mkdir($linkDir, 0755, true) && !is_dir($linkDir)) {
            output("Falha ao criar diretório pai: $linkDir", 'error');
            return false;
        }
        output("Criado diretório: " . basename(dirname($link)), 'success');
    }

    if (!is_writable($linkDir)) {
        output("Diretório sem permissão de escrita: $linkDir", 'error');
        return false;
    }

    if (!function_exists('symlink')) {
        output("Função symlink() não está disponível nesta hospedagem", 'error');
        return false;
    }

    // Cria o symlink
    output("Tentando criar symlink em: $fullLink", 'info');

    $lastError = null;
    set_error_handler(function ($severity, $message) use (&$lastError) {
        $lastError = $message;
        return true;
    });

    $created = symlink($fullTarget, $fullLink);
    restore_error_handler();

    if ($created) {
        output("Symlink criado: $link → $target", 'success');
        return true;
    } else {
        $details = $lastError ? " ($lastError)" : '';
        output("Erro ao criar symlink: $link$details", 'error');
        return false;
    }
}

// Header
if (!$isCliMode) {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(true);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Symlinks</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}</style></head><body>";
}

output("=== Iniciando configuração de symlinks ===", 'info');
output("Site: $SITE_NAME", 'info');
output("Base do site: $SITE_DIR", 'info');
output("Shared: $SHARED_DIR", 'info');
output("Diretório domains: $DOMAINS_DIR", 'info');
echo $isCliMode ? "\n" : "<br>";

// Verifica se diretório shared existe
if (!is_dir($SHARED_DIR)) {
    output("ERRO: Diretório shared não encontrado em $SHARED_DIR", 'error');
    exit(1);
}

foreach ($SYMLINKS_ARTIGOS as $link => $target) {
    output("Criando symlink: $link → $target", 'info');
    createSymlink($target, $link);
}


echo $isCliMode ? "\n" : "<br>";

output("=== Finalizado ===", 'success');

if (!$isCliMode) {
    echo "</body></html>";
}
