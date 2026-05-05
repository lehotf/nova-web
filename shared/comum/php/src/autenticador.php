<?php
class autenticador
{
    private const REMEMBER_COOKIE = 'lb9_remember';
    private const REMEMBER_DAYS = 30;

    private $observador;
    private $db;
    private $guardiao;

    public function __construct(database $db, observador $observador = null, guardiao $guardiao = null)
    {
        $this->db = $db;
        $this->observador = $observador;
        $this->guardiao = $guardiao;
    }

    public function acesso($acesso)
    {
        if ((session_status() !== PHP_SESSION_ACTIVE) || !isset($_SESSION['autorizacao'])) {
            $this->cookie();
        }

        if ($_SESSION['autorizacao'] < $acesso) {
            $this->acesso_negado();
        }
    }

    public function login($login, $senha)
    {
        $login = is_string($login) ? trim($login) : '';
        $senha = is_string($senha) ? $senha : '';

        if ($login === '' || $senha === '') {
            return false;
        }

        $usuario = $this->buscarUsuarioPorLogin($login);
        if (!$usuario || !$this->senhaValida($senha, $usuario['senha'])) {
            $this->limparAutenticacaoLocal();
            if ($this->guardiao) {
                $this->guardiao->adicionarListaNegra();
            }
            return false;
        }

        $this->iniciarSessao();
        session_regenerate_id(true);
        $this->registrarSessao($usuario);
        $this->rotacionarLembrarDeMim((int) $usuario['id']);

        if (password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT)) {
            $novoHash = password_hash($senha, PASSWORD_DEFAULT);
            $this->db->query(
                'UPDATE usuario SET senha = ? WHERE id = ?',
                'si',
                [$novoHash, (int) $usuario['id']]
            );
        }

        return true;
    }

    public function cookie()
    {
        $this->iniciarSessao();

        if ($this->sessaoAutenticada()) {
            return;
        }

        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        $usuario = $this->validarCookiePersistente($cookie);

        if (!$usuario) {
            $this->limparAutenticacaoLocal();
            $this->acesso_negado();
        }

        session_regenerate_id(true);
        $this->registrarSessao($usuario);
    }

    public function acesso_negado()
    {
        if (!$this->observador) {
            http_response_code(404);
            die();
        }

        $this->observador->erro('Você não está autenticado');
    }

    private function buscarUsuarioPorLogin($login)
    {
        return $this->db->select(
            'id, login, senha, autorizacao FROM usuario WHERE login = ? LIMIT 1',
            's',
            [$login]
        );
    }

    private function senhaValida($senhaRecebida, $senhaSalva)
    {
        return is_string($senhaSalva)
            && $senhaSalva !== ''
            && password_verify($senhaRecebida, $senhaSalva);
    }

    private function registrarSessao(array $usuario)
    {
        $_SESSION['id'] = (int) $usuario['id'];
        $_SESSION['login'] = (string) $usuario['login'];
        $_SESSION['autorizacao'] = (int) $usuario['autorizacao'];
    }

    private function iniciarSessao()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    private function sessaoAutenticada()
    {
        return session_status() === PHP_SESSION_ACTIVE
            && isset($_SESSION['id'])
            && isset($_SESSION['autorizacao']);
    }

    private function rotacionarLembrarDeMim($usuarioId)
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $validatorHash = hash('sha256', $validator);
        $expiracao = date('Y-m-d H:i:s', time() + (86400 * self::REMEMBER_DAYS));

        $this->db->query(
            'UPDATE usuario
             SET remember_selector = ?, remember_validator_hash = ?, remember_expires_at = ?
             WHERE id = ?',
            'sssi',
            [$selector, $validatorHash, $expiracao, $usuarioId]
        );

        $this->definirCookiePersistente($selector . ':' . $validator);
    }

    private function validarCookiePersistente($cookie)
    {
        if (!is_string($cookie) || strpos($cookie, ':') === false) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);
        if (!preg_match('/^[a-f0-9]{24}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
            return false;
        }

        $usuario = $this->db->select(
            'id, login, senha, autorizacao, remember_selector, remember_validator_hash, remember_expires_at
             FROM usuario
             WHERE remember_selector = ?
             LIMIT 1',
            's',
            [$selector]
        );

        if (!$usuario) {
            return false;
        }

        $expiraEm = strtotime((string) ($usuario['remember_expires_at'] ?? ''));
        if (!$expiraEm || $expiraEm <= time()) {
            $this->revogarLembrarDeMim((int) $usuario['id']);
            return false;
        }

        $validatorHash = hash('sha256', $validator);
        if (!hash_equals((string) $usuario['remember_validator_hash'], $validatorHash)) {
            $this->revogarLembrarDeMim((int) $usuario['id']);
            return false;
        }

        $this->rotacionarLembrarDeMim((int) $usuario['id']);
        return $usuario;
    }

    private function revogarLembrarDeMim($usuarioId)
    {
        $this->db->query(
            'UPDATE usuario
             SET remember_selector = NULL, remember_validator_hash = NULL, remember_expires_at = NULL
             WHERE id = ?',
            'i',
            [$usuarioId]
        );
    }

    private function limparAutenticacaoLocal()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }

        $this->apagarCookiePersistente();
    }

    private function definirCookiePersistente($value)
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => time() + (86400 * self::REMEMBER_DAYS),
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function apagarCookiePersistente()
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function isHttps()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return (($_SERVER['SERVER_PORT'] ?? null) == 443);
    }
}
