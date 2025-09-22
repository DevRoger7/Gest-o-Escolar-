<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'escola_merenda');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da Aplicação
define('APP_NAME', 'SIGAE - Sistema Integrado de Gestão Escolar');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// Configurações de Segurança
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);

// Configurações de Ambiente
define('DEBUG_MODE', true); // Mudar para false em produção
define('LOG_ERRORS', true);
define('DISPLAY_ERRORS', true);

// Configurações de Timezone
date_default_timezone_set('America/Fortaleza');

// Configurações de Erro
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mudar para 1 em HTTPS

?>