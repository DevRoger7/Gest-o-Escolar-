<?php

/**
 * Configurações do Sistema de Gestão Escolar BANCO DE DADOS
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestao_escolar');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('APP_NAME', 'Sistema de Gestão Escolar');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, production

// Configurações de sessão
define('SESSION_LIFETIME', 7200); // 2 horas
define('SESSION_NAME', 'GESTAO_ESCOLAR_SESSION');

// Configurações de segurança
define('HASH_ALGORITHM', 'sha256');
define('ENCRYPTION_KEY', 'sua_chave_secreta_aqui_2024');

// URLs base
define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
define('ASSETS_URL', BASE_URL . '/assets');

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Configurações de email (para futuras implementações)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', 'noreply@gestaoescolar.com');
define('MAIL_FROM_NAME', 'Sistema de Gestão Escolar');

// Configurações de timezone
date_default_timezone_set('America/Fortaleza');

// Configurações de erro (apenas em desenvolvimento)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurações de sessão personalizadas
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.name', SESSION_NAME);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em produção com HTTPS
