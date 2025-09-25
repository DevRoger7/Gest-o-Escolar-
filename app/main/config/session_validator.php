<?php
/**
 * Validador de Sessão Centralizado
 * 
 * Este arquivo fornece funções para validação de sessão
 * e controle de acesso de forma centralizada.
 */

/**
 * Valida se o usuário está logado e tem permissão adequada
 * 
 * @param string|array $tiposPermitidos Tipos de usuário permitidos
 * @param bool $isAjax Se é uma requisição AJAX (retorna JSON)
 * @return bool True se válido, false caso contrário (ou exit se AJAX)
 */
function validarSessao($tiposPermitidos = ['ADM'], $isAjax = false) {
    // Iniciar sessão se não estiver iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Log de debug
    error_log("Validação de sessão - Session ID: " . session_id());
    error_log("Validação de sessão - Dados da sessão: " . print_r($_SESSION, true));
    error_log("Validação de sessão - Tipos permitidos: " . print_r($tiposPermitidos, true));
    
    // Verificar se a sessão existe
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo'])) {
        error_log("Validação de sessão - Sessão inválida ou não encontrada");
        
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'status' => false, 
                'mensagem' => 'Sessão inválida. Faça login novamente.',
                'redirect' => '../auth/login.php'
            ]);
            exit;
        }
        
        return false;
    }
    
    // Converter para array se for string
    if (is_string($tiposPermitidos)) {
        $tiposPermitidos = [$tiposPermitidos];
    }
    
    // Verificar se o tipo do usuário está permitido
    if (!in_array($_SESSION['tipo'], $tiposPermitidos)) {
        error_log("Validação de sessão - Acesso negado. Tipo do usuário: " . $_SESSION['tipo']);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'status' => false, 
                'mensagem' => 'Acesso não autorizado para este recurso.',
                'redirect' => '../auth/login.php'
            ]);
            exit;
        }
        
        return false;
    }
    
    // Verificar se a sessão não expirou (opcional - 2 horas)
    if (isset($_SESSION['ultimo_acesso'])) {
        $tempoLimite = 2 * 60 * 60; // 2 horas em segundos
        if (time() - $_SESSION['ultimo_acesso'] > $tempoLimite) {
            error_log("Validação de sessão - Sessão expirada");
            
            // Destruir sessão expirada
            session_destroy();
            
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode([
                    'status' => false, 
                    'mensagem' => 'Sessão expirada. Faça login novamente.',
                    'redirect' => '../auth/login.php'
                ]);
                exit;
            }
            
            return false;
        }
    }
    
    // Atualizar último acesso
    $_SESSION['ultimo_acesso'] = time();
    
    error_log("Validação de sessão - Sucesso para usuário: " . $_SESSION['nome']);
    return true;
}

/**
 * Redireciona para login se a sessão for inválida
 * 
 * @param string|array $tiposPermitidos Tipos de usuário permitidos
 */
function requireLogin($tiposPermitidos = ['ADM']) {
    if (!validarSessao($tiposPermitidos, false)) {
        header('Location: ../auth/login.php?erro=acesso_negado');
        exit;
    }
}

/**
 * Valida sessão para requisições AJAX
 * 
 * @param string|array $tiposPermitidos Tipos de usuário permitidos
 */
function requireAjaxLogin($tiposPermitidos = ['ADM']) {
    validarSessao($tiposPermitidos, true);
}

/**
 * Obtém informações do usuário logado
 * 
 * @return array|null Dados do usuário ou null se não logado
 */
function getUsuarioLogado() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['usuario_id'],
        'nome' => $_SESSION['nome'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'tipo' => $_SESSION['tipo'] ?? '',
        'ultimo_acesso' => $_SESSION['ultimo_acesso'] ?? null
    ];
}
?>