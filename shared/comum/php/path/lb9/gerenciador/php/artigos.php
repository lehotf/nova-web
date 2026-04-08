<?php
/**
 * Endpoint AJAX — Módulo de Artigos
 *
 * GET  ?acao=listar[&termo=busca]  → { sucesso, artigos[] }
 * GET  ?acao=obter&id=X            → { sucesso, artigo{} }
 * POST (JSON) acao=inserir          → { sucesso, id }
 * POST (JSON) acao=atualizar        → { sucesso, id }
 *
 * Tabela: links
 * Campos: id, thumb_titulo, thumb_titulo_html, path, titulo, subtitulo,
 *         datePublished, dateModified, thumb, publicado, root, ultimos, search,
 *         amp, duracao, keywords, artigo
 */

header('Content-Type: application/json; charset=utf-8');

$docRoot  = $_SERVER['DOCUMENT_ROOT'];
$autoload = $docRoot . '/comum/php/autoload.php';
if (!file_exists($autoload)) {
    $autoload = dirname(__DIR__, 5) . '/php/autoload.php';
}
require_once $autoload;

$db = new database('localhost', BD_LOGIN, BD_SENHA, BD);

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$acao   = $_GET['acao'] ?? '';

if ($metodo === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $acao = $data['acao'] ?? $acao;
} else {
    $data = [];
}

try {
    switch ($acao) {
        case 'listar':    listar($db);             break;
        case 'obter':     obter($db);              break;
        case 'inserir':   inserir($db, $data);     break;
        case 'atualizar': atualizar($db, $data);   break;
        case 'excluir':   excluir($db, $data);     break;
        default:
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno no servidor.',
        'detalhe' => $e->getMessage()
    ]);
}

/* ═══════════════════════════════════════════════════════════
 *  LISTAR — últimos 20 artigos (busca opcional)
 * ═══════════════════════════════════════════════════════════ */
function listar(database $db): void
{
    $termo = trim($_GET['termo'] ?? '');
    $limit = 20;

    if ($termo !== '') {
        $like = '%' . $termo . '%';
        $sql  = "SELECT id, titulo, subtitulo,
                        CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                        keywords,
                        publicado, ultimos, root, search, amp, datePublished
                 FROM links
                 WHERE titulo LIKE ? OR subtitulo LIKE ?
                 ORDER BY datePublished DESC
                 LIMIT {$limit}";
        $resultado = $db->query($sql, 'ss', [$like, $like]);
    } else {
        $sql = "SELECT id, titulo, subtitulo,
                       CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                       keywords,
                       publicado, ultimos, root, search, amp, datePublished
                FROM links
                ORDER BY datePublished DESC
                LIMIT {$limit}";
        $resultado = $db->query($sql);
    }

    $artigos = [];
    if ($resultado instanceof mysqli_result) {
        while ($row = $resultado->fetch_assoc()) {
            $artigos[] = $row;
        }
    }

    echo json_encode(['sucesso' => true, 'artigos' => $artigos]);
}

/* ═══════════════════════════════════════════════════════════
 *  OBTER — artigo completo pelo ID
 * ═══════════════════════════════════════════════════════════ */
function obter(database $db): void
{
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        return;
    }

    $sql = "SELECT id, titulo, subtitulo,
                   CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                   keywords,
                   artigo AS conteudo,
                   publicado, ultimos, root, search, amp, datePublished
            FROM links
            WHERE id = ?
            LIMIT 1";
    $res = $db->query($sql, 'i', [$id]);

    if (!($res instanceof mysqli_result) || !($artigo = $res->fetch_assoc())) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Artigo não encontrado.']);
        return;
    }

    echo json_encode(['sucesso' => true, 'artigo' => $artigo]);
}

/* ═══════════════════════════════════════════════════════════
 *  INSERIR — novo artigo
 * ═══════════════════════════════════════════════════════════ */
function inserir(database $db, array $data): void
{
    $titulo    = trim($data['titulo']    ?? '');
    $subtitulo = trim($data['subtitulo'] ?? '');
    $path      = normalizePath($data['path'] ?? '');
    $keywords  = trim($data['keywords']  ?? '');
    $conteudo  = $data['conteudo']       ?? '';
    $dataPub   = validarData($data['data'] ?? '');
    $publicado = (int) ($data['publicado'] ?? 0);
    $ultimos   = (int) ($data['ultimos']   ?? 0);
    $root      = (int) ($data['root']      ?? 0);
    $search    = (int) ($data['search']    ?? 0);
    $amp       = (int) ($data['amp']       ?? 0);

    if ($titulo === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Título obrigatório.']);
        return;
    }

    $sql = "INSERT INTO links
                (titulo, thumb_titulo, thumb_titulo_html, subtitulo, path,
                 keywords, artigo, datePublished, dateModified, thumb, duracao,
                 publicado, ultimos, root, search, amp)
            VALUES
                (?, ?, ?, ?, ?,
                 ?, ?, ?, NOW(), 0, '00:00:00',
                 ?, ?, ?, ?, ?)";

    $db->query($sql, 'ssssssssiiiii', [
        $titulo, $titulo, $titulo,
        $subtitulo, $path,
        $keywords, $conteudo, $dataPub,
        $publicado, $ultimos, $root, $search, $amp
    ]);

    $novoId = $db->link->insert_id;
    echo json_encode(['sucesso' => true, 'id' => $novoId]);
}

/* ═══════════════════════════════════════════════════════════
 *  ATUALIZAR — artigo existente
 * ═══════════════════════════════════════════════════════════ */
function atualizar(database $db, array $data): void
{
    $id        = (int) ($data['id']        ?? 0);
    $titulo    = trim($data['titulo']    ?? '');
    $subtitulo = trim($data['subtitulo'] ?? '');
    $path      = normalizePath($data['path'] ?? '');
    $keywords  = trim($data['keywords']  ?? '');
    $conteudo  = $data['conteudo']       ?? '';
    $dataPub   = validarData($data['data'] ?? '');
    $publicado = (int) ($data['publicado'] ?? 0);
    $ultimos   = (int) ($data['ultimos']   ?? 0);
    $root      = (int) ($data['root']      ?? 0);
    $search    = (int) ($data['search']    ?? 0);
    $amp       = (int) ($data['amp']       ?? 0);

    if (!$id || $titulo === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID e título são obrigatórios.']);
        return;
    }

    $sql = "UPDATE links SET
                titulo             = ?,
                thumb_titulo       = ?,
                thumb_titulo_html  = ?,
                subtitulo          = ?,
                path               = ?,
                keywords           = ?,
                artigo             = ?,
                datePublished      = ?,
                dateModified       = NOW(),
                publicado          = ?,
                ultimos            = ?,
                root               = ?,
                search             = ?,
                amp                = ?
            WHERE id = ?";

    $db->query($sql, 'ssssssssiiiiii', [
        $titulo, $titulo, $titulo,
        $subtitulo, $path,
        $keywords, $conteudo, $dataPub,
        $publicado, $ultimos, $root, $search, $amp,
        $id
    ]);

    echo json_encode(['sucesso' => true, 'id' => $id]);
}

/* ═══════════════════════════════════════════════════════════
 *  EXCLUIR — remove artigo
 * ═══════════════════════════════════════════════════════════ */
function excluir(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID obrigatório.']);
        return;
    }

    $sql = "DELETE FROM links WHERE id = ?";
    $db->query($sql, 'i', [$id]);

    echo json_encode(['sucesso' => true]);
}

/* ── Helper ──────────────────────────────────────────────── */
function validarData(string $v): ?string
{
    $v = trim($v);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if ($d && $d->format('Y-m-d') === $v) return $v;
    }
    return null;
}

function normalizePath(string $path): string
{
    return ltrim(trim($path), '/');
}
