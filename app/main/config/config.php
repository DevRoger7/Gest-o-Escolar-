<?php
/**
 * Arquivo de Configuração do Sistema
 * Configurações gerais da aplicação
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestao_escolar');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da Aplicação
define('APP_NAME', 'Sistema de Gestão Escolar');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Gest-o-Escolar-');
define('APP_TIMEZONE', 'America/Sao_Paulo');

// Configurações de Sessão
define('SESSION_NAME', 'gestao_escolar_session');
define('SESSION_LIFETIME', 3600); // 1 hora

// Configurações de Upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Configurações de Segurança
define('ENCRYPTION_KEY', 'sua_chave_secreta_aqui');
define('PASSWORD_MIN_LENGTH', 6);

// Configurações de Email (se necessário)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', '');
define('MAIL_FROM_NAME', APP_NAME);

// Definir timezone
date_default_timezone_set(APP_TIMEZONE);

// Configurações de erro (desenvolvimento/produção)
if (APP_URL === 'http://localhost/Gest-o-Escolar-') {
    // Ambiente de desenvolvimento
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    // Ambiente de produção
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}
?>