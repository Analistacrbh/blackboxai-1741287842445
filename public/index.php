<?php
require_once '../config/config.php';

// Definir rota padrão
$controller = isset($_GET['controller']) ? clean($_GET['controller']) : 'auth';
$action = isset($_GET['action']) ? clean($_GET['action']) : 'login';

// Verificar autenticação
$public_routes = ['auth/login', 'auth/authenticate'];
$current_route = $controller . '/' . $action;

if (!in_array($current_route, $public_routes) && !isLoggedIn()) {
    redirect(SITE_URL . '/public/index.php?controller=auth&action=login');
}

// Mapear controllers
$controllers = [
    'auth' => '../src/controllers/AuthController.php',
    'dashboard' => '../src/controllers/DashboardController.php',
    'sales' => '../src/controllers/SalesController.php',
    'customers' => '../src/controllers/CustomerController.php',
    'products' => '../src/controllers/ProductController.php',
    'settings' => '../src/controllers/SettingsController.php'
];

// Verificar se o controller existe
if (!array_key_exists($controller, $controllers)) {
    die('Controller não encontrado');
}

// Incluir o controller apropriado
require_once $controllers[$controller];

// Instanciar o controller
$controllerClass = ucfirst($controller) . 'Controller';
$controllerInstance = new $controllerClass();

// Verificar se o método existe
if (!method_exists($controllerInstance, $action)) {
    die('Ação não encontrada');
}

// Executar a ação
$controllerInstance->$action();
