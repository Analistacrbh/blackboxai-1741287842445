<?php
class AuthController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function login() {
        // Se já estiver logado, redireciona para o dashboard
        if (isLoggedIn()) {
            redirect(SITE_URL . '/public/index.php?controller=dashboard&action=index');
        }

        // Verificar se há mensagem de erro
        $error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
        unset($_SESSION['login_error']);

        // Incluir a view de login
        require_once '../src/views/auth/login.php';
    }

    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(SITE_URL . '/public/index.php?controller=auth&action=login');
        }

        $username = isset($_POST['username']) ? clean($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND status = TRUE LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Iniciar sessão
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_role'] = $user['role'];
                
                // Registrar último login
                $stmt = $this->pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                redirect(SITE_URL . '/public/index.php?controller=dashboard&action=index');
            } else {
                $_SESSION['login_error'] = 'Usuário ou senha inválidos';
                redirect(SITE_URL . '/public/index.php?controller=auth&action=login');
            }
        } catch (PDOException $e) {
            $_SESSION['login_error'] = 'Erro ao realizar login. Tente novamente.';
            redirect(SITE_URL . '/public/index.php?controller=auth&action=login');
        }
    }

    public function logout() {
        // Destruir todas as variáveis de sessão
        $_SESSION = array();

        // Destruir a sessão
        session_destroy();

        // Redirecionar para a página de login
        redirect(SITE_URL . '/public/index.php?controller=auth&action=login');
    }
}
