<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 6);
$autoload = $docRoot . '/comum/php/autoload.php';
if (!file_exists($autoload)) {
    $autoload = dirname(__DIR__, 4) . '/php/autoload.php';
}
require_once $autoload;

$db = new database('localhost', BD_LOGIN, BD_SENHA, BD);

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$acao = $_GET['acao'] ?? '';
$data = [];

if ($metodo === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $acao = $data['acao'] ?? $acao;
}

try {
    switch ($acao) {
        case 'listar':
            listarTagsAdmin($db);
            break;
        case 'inserir':
            inserirTagAdmin($db, $data);
            break;
        case 'atualizar':
            atualizarTagAdmin($db, $data);
            break;
        case 'excluir':
            excluirTagAdmin($db, $data);
            break;
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

function listarTagsAdmin(database $db): void
{
    $sql = "SELECT tags.id, tags.nome, tags.path, tags.destaque, COUNT(links_tags.linkID) AS total_links
            FROM tags
            LEFT JOIN links_tags ON links_tags.tagID = tags.id
            GROUP BY tags.id, tags.nome, tags.path, tags.destaque
            ORDER BY tags.nome ASC";
    $res = $db->query($sql);
    $tags = [];

    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $tags[] = $row;
        }
    }

    echo json_encode(['sucesso' => true, 'tags' => $tags]);
}

function inserirTagAdmin(database $db, array $data): void
{
    $nome = normalizarNomeTagAdmin($data['nome'] ?? '');
    $path = normalizarPathTagAdmin($data['path'] ?? $nome);
    $destaque = (int) ($data['destaque'] ?? 0) ? 1 : 0;

    validarDadosTagAdmin($db, 0, $nome, $path);

    $db->query("INSERT INTO tags (nome, path, destaque) VALUES (?, ?, ?)", 'ssi', [$nome, $path, $destaque]);
    $id = (int) $db->link->insert_id;

    echo json_encode(['sucesso' => true, 'tag' => obterTagAdmin($db, $id)]);
}

function atualizarTagAdmin(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $nome = normalizarNomeTagAdmin($data['nome'] ?? '');
    $path = normalizarPathTagAdmin($data['path'] ?? $nome);
    $destaque = (int) ($data['destaque'] ?? 0) ? 1 : 0;

    if ($id <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        return;
    }

    validarDadosTagAdmin($db, $id, $nome, $path);
    $db->query("UPDATE tags SET nome = ?, path = ?, destaque = ? WHERE id = ?", 'ssii', [$nome, $path, $destaque, $id]);

    echo json_encode(['sucesso' => true, 'tag' => obterTagAdmin($db, $id)]);
}

function excluirTagAdmin(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        return;
    }

    $res = $db->query("SELECT id FROM tags WHERE id = ? LIMIT 1", 'i', [$id]);
    if (!($res instanceof mysqli_result) || !($res->fetch_assoc())) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tag não encontrada.']);
        return;
    }

    $db->link->begin_transaction();

    try {
        $db->query("DELETE FROM links_tags WHERE tagID = ?", 'i', [$id]);
        $db->query("DELETE FROM tags WHERE id = ?", 'i', [$id]);
        $resConfirmacao = $db->query("SELECT id FROM tags WHERE id = ? LIMIT 1", 'i', [$id]);
        if ($resConfirmacao instanceof mysqli_result && $resConfirmacao->fetch_assoc()) {
            throw new RuntimeException('A exclusão da tag não foi confirmada no banco de dados.');
        }
        $db->link->commit();
    } catch (Throwable $e) {
        $db->link->rollback();
        throw $e;
    }

    echo json_encode(['sucesso' => true]);
}

function obterTagAdmin(database $db, int $id): array
{
    $res = $db->query(
        "SELECT tags.id, tags.nome, tags.path, tags.destaque, COUNT(links_tags.linkID) AS total_links
         FROM tags
         LEFT JOIN links_tags ON links_tags.tagID = tags.id
         WHERE tags.id = ?
         GROUP BY tags.id, tags.nome, tags.path, tags.destaque
         LIMIT 1",
        'i',
        [$id]
    );

    if ($res instanceof mysqli_result && ($tag = $res->fetch_assoc())) {
        return $tag;
    }

    throw new RuntimeException('Tag não encontrada após a operação.');
}

function validarDadosTagAdmin(database $db, int $idAtual, string $nome, string $path): void
{
    if ($nome === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Informe o nome da tag.']);
        exit;
    }

    if ($path === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Informe o path da tag.']);
        exit;
    }

    $res = $db->query("SELECT id FROM tags WHERE path = ? LIMIT 1", 's', [$path]);
    if ($res instanceof mysqli_result && ($row = $res->fetch_assoc())) {
        $idEncontrado = (int) ($row['id'] ?? 0);
        if ($idEncontrado !== $idAtual) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Já existe uma tag com esse path.']);
            exit;
        }
    }
}

function normalizarNomeTagAdmin($valor): string
{
    return mb_substr(trim((string) $valor), 0, 25);
}

function normalizarPathTagAdmin($valor): string
{
    $path = trim((string) $valor);
    $path = strtolower($path);
    $path = preg_replace('/[^a-z0-9\-]+/', '-', $path) ?? '';
    $path = preg_replace('/-+/', '-', $path) ?? '';
    $path = trim($path, '-');

    return substr($path, 0, 25);
}
