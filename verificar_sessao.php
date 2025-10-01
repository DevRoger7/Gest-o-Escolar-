<?php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'is_admin' => isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'ADM',
    'user_type' => $_SESSION['tipo'] ?? 'não definido',
    'user_id' => $_SESSION['usuario_id'] ?? 'não definido'
]);
?>