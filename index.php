<?php
/**
 * Sistema de Gestão Escolar
 * Arquivo principal de entrada da aplicação
 */


// Definir constantes do sistema
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Incluir arquivo de configuração
require_once CONFIG_PATH . '/config.php';

// Incluir autoloader (se usando Composer)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Incluir Controllers
require_once APP_PATH . '/main/Controllers/HomeController.php';
require_once APP_PATH . '/main/Controllers/AuthController.php';
require_once APP_PATH . '/main/Controllers/HubController.php';

// Inicializar sessão
session_start();

// Sistema de roteamento simples
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remover query string da URI
$uri = parse_url($request_uri, PHP_URL_PATH);

// Remover a base do projeto da URI
$base_path = '/GitHub/Gest-o-Escolar-';
if (strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}


// Roteamento
switch ($uri) {
    case '/':
    case '':
        $controller = new HomeController();
        $controller->index();
        break;
        
    case '/login':
        if ($request_method === 'GET') {
            $controller = new AuthController();
            $controller->login();
        } elseif ($request_method === 'POST') {
            $controller = new AuthController();
            $controller->authenticate();
        }
        break;
        
    case '/logout':
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/hub':
        $controller = new HubController();
        $controller->index();
        break;
        
    case '/hub/select':
        if ($request_method === 'POST') {
            $controller = new HubController();
            $controller->selectSchool();
        } else {
            header('Location: /hub');
        }
        break;
        
    default:
        http_response_code(404);
        echo "Página não encontrada";
        break;
}