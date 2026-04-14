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

    $sql = "SELECT id, titulo, subtitulo,
                   CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
                   keywords,
                   artigo AS conteudo,
                   thumb, duracao,
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

function inserir(database $db, array $data): void
{
    $titulo = trim($data['titulo'] ?? '');
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
                 ?, ?, ?, NOW(), 0, ?,
                 ?, ?, ?, ?, ?)";

    $db->query($sql, 'sssssssssiiiii', [
        $titulo, $titulo, $titulo,
        $subtitulo, $path,
        $keywords, $conteudo, $dataPub, $duracao,
        $publicado, $ultimos, $root, $search, $amp
    ]);

    $novoId = $db->link->insert_id;
    echo json_encode(['sucesso' => true, 'id' => $novoId]);
}

function atualizar(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $titulo = trim($data['titulo'] ?? '');
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
                duracao            = ?,
                artigo             = ?,
                datePublished      = ?,
                dateModified       = NOW(),
                publicado          = ?,
                ultimos            = ?,
                root               = ?,
                search             = ?,
                amp                = ?
            WHERE id = ?";

    $db->query($sql, 'sssssssssiiiiii', [
        $titulo, $titulo, $titulo,
        $subtitulo, $path,
        $keywords, $duracao, $conteudo, $dataPub,
        $publicado, $ultimos, $root, $search, $amp,
        $id
    ]);

    echo json_encode(['sucesso' => true, 'id' => $id]);
}

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

function uploadThumb(database $db, array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $width = (int) ($data['width'] ?? 0);
    $bx = (int) ($data['bx'] ?? 0);
    $by = (int) ($data['by'] ?? 0);
    $qualidadeP = limitarQualidade((int) ($data['qualidade_p'] ?? 0));
    $qualidadeG = limitarQualidade((int) ($data['qualidade_g'] ?? 0));

    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido para upload da thumb.']);
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

    $ampFile = $uploadDirectory . $id . 'amp.jpg';
    $smallFile = $uploadDirectory . $id . '.jpg';
    $largeFile = $uploadDirectory . $id . 'g.jpg';

    if (!imagejpeg($crop, $ampFile, 60)) {
        imagedestroy($crop);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar a thumb AMP.']);
        return;
    }

    if (!salvarResizeJpeg($crop, 272, $smallFile, $qualidadeP) || !salvarResizeJpeg($crop, 559, $largeFile, $qualidadeG)) {
        imagedestroy($crop);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao gerar os tamanhos da thumb.']);
        return;
    }

    imagedestroy($crop);

    $db->query("UPDATE links SET thumb = ?, dateModified = NOW() WHERE id = ?", 'ii', [$id, $id]);

    echo json_encode([
        'sucesso' => true,
        'id' => $id,
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
