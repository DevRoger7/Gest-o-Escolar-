<?php
// Configurar headers para AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Configurar parâmetros de cookie para melhor compatibilidade com AJAX
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Incluir validador de sessão
require_once('../../config/session_validator.php');

// Validar sessão para AJAX
requireAjaxLogin(['ADM']);

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para buscar gestores
function buscarGestores($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT u.id, p.nome, p.email, p.telefone, u.role
            FROM usuario u 
            JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.role = 'GESTAO' AND u.ativo = 1";
    
    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.email LIKE :busca)";
    }
    
    $sql .= " ORDER BY p.nome ASC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar requisição
$busca = $_GET['busca'] ?? '';

try {
    $gestores = buscarGestores($busca);
    echo json_encode($gestores);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
