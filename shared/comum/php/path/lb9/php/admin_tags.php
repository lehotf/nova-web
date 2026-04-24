<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
$db = $c->db;
$o = $c->observador;

$data = is_array($o->dados) ? $o->dados : [];
$acao = (string) ($data['acao'] ?? '');

try {
    switch ($acao) {
        case 'listar':
            listarTagsAdmin($db, $o);
            break;
        case 'inserir':
            inserirTagAdmin($db, $o, $data);
            break;
        case 'atualizar':
            atualizarTagAdmin($db, $o, $data);
            break;
        case 'excluir':
            excluirTagAdmin($db, $o, $data);
            break;
        default:
            $o->envia('Ação inválida.', 'erro');
            break;
    }
} catch (Throwable $e) {
    $o->r['detalhe'] = $e->getMessage();
    $o->envia('Erro interno no servidor.', 'erro');
}

function listarTagsAdmin(database $db, observador $o): void
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

    $o->r['tags'] = $tags;
    $o->envia('Tags carregadas.');
}

function inserirTagAdmin(database $db, observador $o, array $data): void
{
    $nome = normalizarNomeTagAdmin($data['nome'] ?? '');
    $path = normalizarPathTagAdmin($data['path'] ?? $nome);
    $destaque = (int) ($data['destaque'] ?? 0) ? 1 : 0;

    validarDadosTagAdmin($db, $o, 0, $nome, $path);

    $db->query("INSERT INTO tags (nome, path, destaque) VALUES (?, ?, ?)", 'ssi', [$nome, $path, $destaque]);
    $id = (int) $db->link->insert_id;

    $o->r['tag'] = obterTagAdmin($db, $id);
    $o->envia('Tag inserida.');
}

function atualizarTagAdmin(database $db, observador $o, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $nome = normalizarNomeTagAdmin($data['nome'] ?? '');
    $path = normalizarPathTagAdmin($data['path'] ?? $nome);
    $destaque = (int) ($data['destaque'] ?? 0) ? 1 : 0;

    if ($id <= 0) {
        $o->envia('ID inválido.', 'erro');
    }

    validarDadosTagAdmin($db, $o, $id, $nome, $path);
    $db->query("UPDATE tags SET nome = ?, path = ?, destaque = ? WHERE id = ?", 'ssii', [$nome, $path, $destaque, $id]);

    $o->r['tag'] = obterTagAdmin($db, $id);
    $o->envia('Tag atualizada.');
}

function excluirTagAdmin(database $db, observador $o, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0) {
        $o->envia('ID inválido.', 'erro');
    }

    $res = $db->query("SELECT id FROM tags WHERE id = ? LIMIT 1", 'i', [$id]);
    if (!($res instanceof mysqli_result) || !($res->fetch_assoc())) {
        $o->envia('Tag não encontrada.', 'erro');
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

    $o->envia('Tag excluída.');
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

function validarDadosTagAdmin(database $db, observador $o, int $idAtual, string $nome, string $path): void
{
    if ($nome === '') {
        $o->envia('Informe o nome da tag.', 'erro');
    }

    if ($path === '') {
        $o->envia('Informe o path da tag.', 'erro');
    }

    $res = $db->query("SELECT id FROM tags WHERE path = ? LIMIT 1", 's', [$path]);
    if ($res instanceof mysqli_result && ($row = $res->fetch_assoc())) {
        $idEncontrado = (int) ($row['id'] ?? 0);
        if ($idEncontrado !== $idAtual) {
            $o->envia('Já existe uma tag com esse path.', 'erro');
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
