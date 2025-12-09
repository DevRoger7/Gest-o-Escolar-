<?php
/**
 * Arquivo de Inicialização do Sistema
 * Sistema de Gestão Escolar - Merenda
 */

/**
 * Função helper para iniciar sessão com configurações de 24 horas
 */
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configura o tempo de vida da sessão para 24 horas
        $lifetime = 24 * 60 * 60; // 24 horas em segundos
        session_set_cookie_params($lifetime);
        ini_set('session.gc_maxlifetime', $lifetime);
        session_start();
    }
}

// Iniciar sessão se ainda não foi iniciada
iniciarSessao();

// Incluir configurações (se existir)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Incluir classe de banco de dados
require_once __DIR__ . '/Database.php';

/**
 * Função helper para obter a conexão com o banco
 */
function getDatabase() {
    return Database::getInstance();
}

/**
 * Função helper para executar consultas SELECT
 */
function dbQuery($sql, $params = []) {
    $db = getDatabase();
    return $db->query($sql, $params);
}

/**
 * Função helper para executar INSERT, UPDATE, DELETE
 */
function dbExecute($sql, $params = []) {
    $db = getDatabase();
    return $db->execute($sql, $params);
}

/**
 * Função helper para obter último ID inserido
 */
function dbLastInsertId() {
    $db = getDatabase();
    return $db->lastInsertId();
}

/**
 * Função para sanitizar dados de entrada
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Função para gerar hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Função para verificar senha
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Função para redirecionar
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Função para exibir mensagens de erro/sucesso
 */
function showMessage($message, $type = 'info') {
    $class = '';
    switch ($type) {
        case 'success':
            $class = 'alert-success';
            break;
        case 'error':
            $class = 'alert-danger';
            break;
        case 'warning':
            $class = 'alert-warning';
            break;
        default:
            $class = 'alert-info';
    }
    
    return "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
?>