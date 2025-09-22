<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

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
