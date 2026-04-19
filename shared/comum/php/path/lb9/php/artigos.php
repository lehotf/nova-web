<?php
/**
 * Endpoint AJAX — Módulo de Artigos
 *
 * GET  ?acao=listar[&termo=busca]  → { sucesso, artigos[] }
 * GET  ?acao=obter&id=X            → { sucesso, artigo{} }
 * POST (JSON) acao=inserir         → { sucesso, id }
 * POST (JSON) acao=atualizar       → { sucesso, id }
 * POST (JSON) acao=excluir         → { sucesso }
 * POST (multipart) acao=upload_thumb → { sucesso, id, timestamp }
 * POST (multipart) acao=upload_imagem_artigo → { sucesso, name }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Surrogate-Control: no-store');
header('Vary: Accept');

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
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;
        $acao = $data['acao'] ?? $acao;
    } else {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $acao = $data['acao'] ?? $acao;
    }
}

try {
    switch ($acao) {
        case 'listar':
            listar($db);
            break;
        case 'obter':
            obter($db);
            break;
        case 'listar_tags':
            listarTags($db);
            break;
        case 'inserir_tag':
            inserirTag($db, $data);
            break;
        case 'inserir':
            inserir($db, $data);
            break;
        case 'atualizar':
            atualizar($db, $data);
            break;
        case 'excluir':
            excluir($db, $data);
            break;
        case 'upload_thumb':
            uploadThumb($db, $data);
            break;
        case 'upload_imagem_artigo':
            uploadImagemArtigo($data);
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

function listar(database $db): void
{
    $termo = trim($_GET['termo'] ?? '');
    $limit = 20;

    if ($termo !== '') {
        $like = '%' . $termo . '%';
        $sql = "SELECT id, titulo, subtitulo,
                       CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                       keywords, thumb, duracao,
                       publicado, ultimos, root, search, amp, datePublished
                FROM links
                WHERE titulo LIKE ? OR subtitulo LIKE ?
                ORDER BY datePublished DESC
                LIMIT {$limit}";
        $resultado = $db->query($sql, 'ss', [$like, $like]);
    } else {
        $sql = "SELECT id, titulo, subtitulo,
                       CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                       keywords, thumb, duracao,
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

function obter(database $db): void
{
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        return;
    }

    $sql = "SELECT links.id, links.titulo, links.thumb_titulo, links.subtitulo,
                   CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                   links.keywords,
                   links.artigo AS conteudo,
                   links.thumb, links.duracao,
                   links.publicado, links.ultimos, links.root, links.search, links.amp, links.datePublished,
                   MAX(links_destaques.id) AS destaque_id
            FROM links
            LEFT JOIN links_destaques ON links_destaques.linkID = links.id
            WHERE links.id = ?
            GROUP BY links.id, links.titulo, links.thumb_titulo, links.subtitulo, links.path,
                     links.keywords, links.artigo, links.thumb, links.duracao,
                     links.publicado, links.ultimos, links.root, links.search, links.amp, links.datePublished
            LIMIT 1";
    $res = $db->query($sql, 'i', [$id]);

    if (!($res instanceof mysqli_result) || !($artigo = $res->fetch_assoc())) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Artigo não encontrado.']);
        return;
    }

    $artigo['tag_ids'] = obterTagIdsArtigo($db, (int) $artigo['id']);

    echo json_encode(['sucesso' => true, 'artigo' => $artigo]);
}

function listarTags(database $db): void
{
    $res = $db->query("SELECT id, nome, path, destaque FROM tags ORDER BY nome ASC");
    $tags = [];

    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $tags[] = $row;
        }
    }

    echo json_encode(['sucesso' => true, 'tags' => $tags]);
}

function inserirTag(database $db, array $data): void
{
    $nome = trim((string) ($data['nome'] ?? ''));
    if ($nome === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Informe o nome da tag.']);
        return;
    }

    $nome = mb_substr($nome, 0, 25);
    $path = mb_substr(url_amigavel($nome), 0, 25);

    if ($path === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível gerar o path da tag.']);
        return;
    }

    $resExistente = $db->query("SELECT id, nome, path, destaque FROM tags WHERE path = ? LIMIT 1", 's', [$path]);
    if ($resExistente instanceof mysqli_result && ($tag = $resExistente->fetch_assoc())) {
        echo json_encode([
            'sucesso' => true,
            'existente' => true,
            'tag' => $tag
        ]);
        return;
    }

    $db->query("INSERT INTO tags (nome, path, destaque) VALUES (?, ?, 0)", 'ss', [$nome, $path]);
    $id = (int) $db->link->insert_id;

    echo json_encode([
        'sucesso' => true,
        'existente' => false,
        'tag' => [
            'id' => $id,
            'nome' => $nome,
            'path' => $path,
            'destaque' => 0
        ]
    ]);
}

function inserir(database $db, array $data): void
{
    $titulo = trim($data['titulo'] ?? '');
    $thumb = normalizarThumb($data['thumb'] ?? null);
    $thumbTitulo = trim($data['thumb_titulo'] ?? '');
    $subtitulo = trim($data['subtitulo'] ?? '');
    $path = normalizePath($data['path'] ?? '');
    $keywords = trim($data['keywords'] ?? '');
    $duracao = validarDuracao($data['duracao'] ?? '');
    $conteudo = $data['conteudo'] ?? '';
    $dataPub = validarData($data['data'] ?? '');
    $publicado = (int) ($data['publicado'] ?? 0);
    $ultimos = (int) ($data['ultimos'] ?? 0);
    $root = (int) ($data['root'] ?? 0);
    $search = (int) ($data['search'] ?? 0);
    $amp = (int) ($data['amp'] ?? 0);
    $tagsCadastrar = normalizarIdsTag($data['tags_a_cadastrar'] ?? []);
    $tagsExcluir = normalizarIdsTag($data['tags_a_excluir'] ?? []);
    $destaque = normalizarDestaque($data['destaque'] ?? null);

    if ($titulo === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Título obrigatório.']);
        return;
    }
    $sql = "INSERT INTO links
                (titulo, thumb_titulo, subtitulo, path,
                 keywords, artigo, datePublished, dateModified, thumb, duracao,
                 publicado, ultimos, root, search, amp)
            VALUES
                (?, ?, ?, ?,
                 ?, ?, ?, NOW(), ?, ?,
                 ?, ?, ?, ?, ?)";

    $db->link->begin_transaction();

    try {
        $db->query($sql, 'sssssssisiiiii', [
            $titulo, $thumbTitulo,
            $subtitulo, $path,
            $keywords, $conteudo, $dataPub, $thumb, $duracao,
            $publicado, $ultimos, $root, $search, $amp
        ]);

        $novoId = $db->link->insert_id;
        aplicarMudancasTagsArtigo($db, $novoId, $tagsCadastrar, $tagsExcluir);
        aplicarDestaqueArtigo($db, $novoId, $destaque);
        $db->link->commit();
    } catch (Throwable $e) {
        $db->link->rollback();
        throw $e;
    }

    echo json_encode(['sucesso' => true, 'id' => $novoId, 'thumb' => $thumb]);
}

function atualizar(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $tagsCadastrar = normalizarIdsTag($data['tags_a_cadastrar'] ?? []);
    $tagsExcluir = normalizarIdsTag($data['tags_a_excluir'] ?? []);
    $destaque = normalizarDestaque($data['destaque'] ?? null);

    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID obrigatório.']);
        return;
    }

    try {
        [$set, $types, $params] = montarCamposAtualizacaoArtigo($data);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
        return;
    }

    $db->link->begin_transaction();

    try {
        if ($set) {
            $sql = "UPDATE links SET " . implode(', ', $set) . ", dateModified = NOW() WHERE id = ?";
            $db->query($sql, $types . 'i', [...$params, $id]);
        }

        aplicarMudancasTagsArtigo($db, $id, $tagsCadastrar, $tagsExcluir);
        aplicarDestaqueArtigo($db, $id, $destaque);
        $db->link->commit();
    } catch (Throwable $e) {
        $db->link->rollback();
        throw $e;
    }

    echo json_encode(['sucesso' => true, 'id' => $id]);
}

function excluir(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID obrigatório.']);
        return;
    }

    $db->link->begin_transaction();

    try {
        $db->query("DELETE FROM links_tags WHERE linkID = ?", 'i', [$id]);
        $db->query("DELETE FROM links WHERE id = ?", 'i', [$id]);
        $db->link->commit();
    } catch (Throwable $e) {
        $db->link->rollback();
        throw $e;
    }

    echo json_encode(['sucesso' => true]);
}

function uploadThumb(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $thumbNome = normalizarThumb($data['thumb_nome'] ?? null);
    $width = (int) ($data['width'] ?? 0);
    $bx = (int) ($data['bx'] ?? 0);
    $by = (int) ($data['by'] ?? 0);
    $qualidadeP = limitarQualidade((int) ($data['qualidade_p'] ?? 0));
    $qualidadeG = limitarQualidade((int) ($data['qualidade_g'] ?? 0));

    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido para upload da thumb.']);
        return;
    }

    if ($thumbNome <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Número da thumb inválido.']);
        return;
    }

    if (!isset($_FILES['thumb']) || !is_uploaded_file($_FILES['thumb']['tmp_name'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione uma imagem para a thumb.']);
        return;
    }

    $arquivo = $_FILES['thumb'];
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha no upload da imagem.']);
        return;
    }

    if (($arquivo['size'] ?? 0) > 2_000_000) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tamanho máximo de arquivo é 2MB.']);
        return;
    }

    if ($width < 1280) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Largura de corte inválida.']);
        return;
    }

    $extensao = strtolower(pathinfo($arquivo['name'] ?? '', PATHINFO_EXTENSION));
    $mime = mime_content_type($arquivo['tmp_name']) ?: '';
    $permitidos = ['jpg', 'jpeg', 'png'];
    $mimesPermitidos = ['image/jpeg', 'image/png'];

    if (!in_array($extensao, $permitidos, true) || !in_array($mime, $mimesPermitidos, true)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Extensão não permitida. Utilize JPEG ou PNG.']);
        return;
    }

    $imagemOrigem = criarImagemOrigem($arquivo['tmp_name'], $mime);
    if (!$imagemOrigem) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível processar a imagem enviada.']);
        return;
    }

    $dimensoes = getimagesize($arquivo['tmp_name']);
    if (!$dimensoes) {
        imagedestroy($imagemOrigem);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Imagem inválida.']);
        return;
    }

    [$origWidth, $origHeight] = $dimensoes;
    $newHeight = (int) round(($origHeight / $origWidth) * $width);

    $tmp = imagecreatetruecolor($width, $newHeight);
    imagecopyresampled($tmp, $imagemOrigem, $bx, $by, 0, 0, $width, $newHeight, $origWidth, $origHeight);

    $crop = imagecrop($tmp, ['x' => 0, 'y' => 0, 'width' => 1280, 'height' => 720]);
    imagedestroy($tmp);
    imagedestroy($imagemOrigem);

    if (!$crop) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível gerar o corte da thumb.']);
        return;
    }

    $uploadDirectory = $GLOBALS['docRoot'] . '/cache/img/upload/t/';
    ensureDirectory($uploadDirectory);

    $ampFile = $uploadDirectory . $thumbNome . 'amp.jpg';
    $smallFile = $uploadDirectory . $thumbNome . '.jpg';
    $largeFile = $uploadDirectory . $thumbNome . 'g.jpg';

    if (!imagejpeg($crop, $ampFile, 60)) {
        imagedestroy($crop);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar a thumb AMP.']);
        return;
    }

    if (!salvarResizeJpeg($crop, 283, $smallFile, $qualidadeP) || !salvarResizeJpeg($crop, 586, $largeFile, $qualidadeG)) {
        imagedestroy($crop);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao gerar os tamanhos da thumb.']);
        return;
    }

    imagedestroy($crop);

    $db->query("UPDATE links SET thumb = ?, dateModified = NOW() WHERE id = ?", 'ii', [$thumbNome, $id]);

    echo json_encode([
        'sucesso' => true,
        'id' => $id,
        'thumb' => $thumbNome,
        'timestamp' => time()
    ]);
}

function uploadImagemArtigo(array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você deve salvar o artigo antes de fazer upload de imagem.']);
        return;
    }

    if (!isset($_FILES['imagem']) || !is_uploaded_file($_FILES['imagem']['tmp_name'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione uma imagem para upload.']);
        return;
    }

    $arquivo = $_FILES['imagem'];
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha no upload da imagem.']);
        return;
    }

    if (($arquivo['size'] ?? 0) > 2_000_000) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tamanho máximo de arquivo é 2MB.']);
        return;
    }

    $extensao = strtolower(pathinfo($arquivo['name'] ?? '', PATHINFO_EXTENSION));
    $mime = mime_content_type($arquivo['tmp_name']) ?: '';
    $permitidos = ['jpg', 'jpeg', 'png'];
    $mimesPermitidos = ['image/jpeg', 'image/png'];

    if (!in_array($extensao, $permitidos, true) || !in_array($mime, $mimesPermitidos, true)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Extensão não permitida. Utilize JPEG ou PNG.']);
        return;
    }

    $uploadDirectory = $GLOBALS['docRoot'] . '/cache/img/upload/a/';
    ensureDirectory($uploadDirectory);

    $nome = proximoNomeImagemArtigo($uploadDirectory, $id);
    $destino = $uploadDirectory . $nome . '.jpg';

    $imagem = criarImagemOrigem($arquivo['tmp_name'], $mime);
    if (!$imagem) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível processar a imagem enviada.']);
        return;
    }

    $ok = imagejpeg($imagem, $destino, 70);
    imagedestroy($imagem);

    if (!$ok) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar a imagem enviada.']);
        return;
    }

    echo json_encode([
        'sucesso' => true,
        'name' => $nome
    ]);
}

function validarData(string $v): ?string
{
    $v = trim($v);
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) {
        [$dia, $mes, $ano] = explode('/', $v);
        $v = $ano . '-' . $mes . '-' . $dia;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if ($d && $d->format('Y-m-d') === $v) {
            return $v;
        }
    }
    return null;
}

function validarDuracao(string $v): string
{
    $v = trim($v);
    if ($v === '') {
        return '00:00:00';
    }

    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $v)) {
        [$horas, $minutos, $segundos] = array_map('intval', explode(':', $v));
        if ($minutos <= 59 && $segundos <= 59) {
            return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
        }
    }

    return '00:00:00';
}

function normalizePath(string $path): string
{
    return ltrim(trim($path), '/');
}

function limitarQualidade(int $valor): int
{
    if ($valor === 0) {
        return 60;
    }
    if ($valor > 100) {
        return 100;
    }
    if ($valor < 20) {
        return 20;
    }
    return $valor;
}

function normalizarIdsTag($valor): array
{
    if (!is_array($valor)) {
        return [];
    }

    $ids = array_map('intval', $valor);
    $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
    sort($ids);

    return $ids;
}

function normalizarDestaque($valor): ?int
{
    $id = (int) $valor;
    return in_array($id, [100, 200], true) ? $id : null;
}

function normalizarThumb($valor): int
{
    $thumb = preg_replace('/\D+/', '', (string) ($valor ?? ''));
    if ($thumb === '') {
        return 0;
    }

    return (int) $thumb;
}

function montarCamposAtualizacaoArtigo(array $data): array
{
    $set = [];
    $types = '';
    $params = [];
    $campos = [
        'titulo' => ['coluna' => 'titulo', 'tipo' => 's', 'transform' => static fn ($valor) => trim((string) $valor), 'required' => true],
        'thumb' => ['coluna' => 'thumb', 'tipo' => 'i', 'transform' => static fn ($valor) => normalizarThumb($valor)],
        'thumb_titulo' => ['coluna' => 'thumb_titulo', 'tipo' => 's', 'transform' => static fn ($valor) => trim((string) $valor)],
        'subtitulo' => ['coluna' => 'subtitulo', 'tipo' => 's', 'transform' => static fn ($valor) => trim((string) $valor)],
        'path' => ['coluna' => 'path', 'tipo' => 's', 'transform' => static fn ($valor) => normalizePath((string) $valor)],
        'keywords' => ['coluna' => 'keywords', 'tipo' => 's', 'transform' => static fn ($valor) => trim((string) $valor)],
        'duracao' => ['coluna' => 'duracao', 'tipo' => 's', 'transform' => static fn ($valor) => validarDuracao((string) $valor)],
        'conteudo' => ['coluna' => 'artigo', 'tipo' => 's', 'transform' => static fn ($valor) => (string) $valor],
        'data' => ['coluna' => 'datePublished', 'tipo' => 's', 'transform' => static fn ($valor) => validarData((string) $valor), 'valid' => static fn ($valor) => $valor !== null],
        'publicado' => ['coluna' => 'publicado', 'tipo' => 'i', 'transform' => static fn ($valor) => (int) $valor],
        'ultimos' => ['coluna' => 'ultimos', 'tipo' => 'i', 'transform' => static fn ($valor) => (int) $valor],
        'root' => ['coluna' => 'root', 'tipo' => 'i', 'transform' => static fn ($valor) => (int) $valor],
        'search' => ['coluna' => 'search', 'tipo' => 'i', 'transform' => static fn ($valor) => (int) $valor],
        'amp' => ['coluna' => 'amp', 'tipo' => 'i', 'transform' => static fn ($valor) => (int) $valor],
    ];

    foreach ($campos as $chave => $config) {
        if (!array_key_exists($chave, $data)) {
            continue;
        }

        $valor = $config['transform']($data[$chave]);

        if (($config['required'] ?? false) && $valor === '') {
            throw new InvalidArgumentException('Título obrigatório.');
        }

        if (isset($config['valid']) && !$config['valid']($valor)) {
            throw new InvalidArgumentException('Valor inválido para o campo ' . $chave . '.');
        }

        $set[] = $config['coluna'] . ' = ?';
        $types .= $config['tipo'];
        $params[] = $valor;
    }

    return [$set, $types, $params];
}

function obterTagIdsArtigo(database $db, int $artigoId): array
{
    if ($artigoId <= 0) {
        return [];
    }

    $res = $db->query("SELECT tagID FROM links_tags WHERE linkID = ? ORDER BY tagID ASC", 'i', [$artigoId]);
    $ids = [];

    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['tagID'];
        }
    }

    return $ids;
}

function aplicarMudancasTagsArtigo(database $db, int $artigoId, array $tagsCadastrar, array $tagsExcluir): void
{
    foreach ($tagsExcluir as $tagId) {
        $db->query("DELETE FROM links_tags WHERE linkID = ? AND tagID = ?", 'ii', [$artigoId, $tagId]);
    }

    foreach ($tagsCadastrar as $tagId) {
        $db->query("INSERT INTO links_tags (linkID, tagID) VALUES (?, ?)", 'ii', [$artigoId, $tagId]);
    }
}

function aplicarDestaqueArtigo(database $db, int $artigoId, ?int $destaqueId): void
{
    if ($artigoId <= 0 || $destaqueId === null) {
        return;
    }

    $db->query("UPDATE links_destaques SET linkID = ? WHERE id = ?", 'ii', [$artigoId, $destaqueId]);
}

function criarImagemOrigem(string $arquivo, string $mime)
{
    if ($mime === 'image/jpeg') {
        return imagecreatefromjpeg($arquivo);
    }
    if ($mime === 'image/png') {
        $img = imagecreatefrompng($arquivo);
        if (!$img) {
            return false;
        }

        $jpg = imagecreatetruecolor(imagesx($img), imagesy($img));
        $background = imagecolorallocate($jpg, 255, 255, 255);
        imagefill($jpg, 0, 0, $background);
        imagecopy($jpg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imagedestroy($img);
        return $jpg;
    }
    return false;
}

function salvarResizeJpeg($source, int $targetWidth, string $outputPath, int $quality): bool
{
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $targetHeight = (int) round(($sourceHeight / $sourceWidth) * $targetWidth);

    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
    $ok = imagejpeg($target, $outputPath, $quality);
    imagedestroy($target);

    return $ok;
}

function ensureDirectory(string $dir): void
{
    if (is_dir($dir)) {
        return;
    }

    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar o diretório de upload.');
    }
}

function proximoNomeImagemArtigo(string $diretorio, int $id): string
{
    $contador = 1;
    do {
        $nome = $id . '-' . $contador;
        $contador++;
    } while (file_exists($diretorio . $nome . '.jpg'));

    return $nome;
}
