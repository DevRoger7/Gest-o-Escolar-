<?php
// Configurar headers para AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Iniciar sessão
session_start();

// Verificar estado da sessão
$sessao_info = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'logado' => isset($_SESSION['logado']) ? $_SESSION['logado'] : false,
    'tipo' => isset($_SESSION['tipo']) ? $_SESSION['tipo'] : 'não definido',
    'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'não definido',
    'nome' => isset($_SESSION['nome']) ? $_SESSION['nome'] : 'não definido'
];

echo json_encode($sessao_info, JSON_PRETTY_PRINT);
?>