<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_vendas');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações Gerais
define('SITE_NAME', 'Sistema de Vendas');
define('SITE_URL', 'http://localhost/sistema_vendas');

// Configurações de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Conexão com o Banco de Dados usando PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Funções Helpers
function clean($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Função para verificar o nível de acesso do usuário
function hasPermission($requiredLevel) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $roles = array(
        'Administrador' => 3,
        'Superusuário' => 2,
        'Usuário' => 1
    );
    
    $userLevel = $roles[$_SESSION['user_role']];
    $requiredLevel = $roles[$requiredLevel];
    
    return $userLevel >= $requiredLevel;
}
