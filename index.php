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

// Incluir classes principais do Core na ordem correta
require_once APP_PATH . '/Core/Database.php';
require_once APP_PATH . '/Core/Model.php';
require_once APP_PATH . '/Core/Controller.php';
require_once APP_PATH . '/Core/Router.php';

// Incluir HomeController
require_once APP_PATH . '/Controllers/HomeController.php';

// Verificar se todas as classes foram carregadas
if (!class_exists('Router') || !class_exists('Database') || !class_exists('Model') || !class_exists('Controller')) {
    die('Erro: Classes essenciais não foram carregadas corretamente');
}

// Inicializar sessão
session_start();

// Inicializar roteamento
$router = new Router();

// Definir rotas básicas
$router->get('/', 'HomeController@index');

// Executar roteamento
$router->run();