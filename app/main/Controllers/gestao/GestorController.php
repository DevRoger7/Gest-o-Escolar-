<?php
// Verificar se a sessão já foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurar headers para AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    echo json_encode(['status' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para buscar gestores
function buscarGestores($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT g.id as gestor_id, p.nome, p.email, p.telefone, p.cpf, g.cargo
            FROM gestor g
            INNER JOIN pessoa p ON g.pessoa_id = p.id
            WHERE g.ativo = 1";
    
    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.email LIKE :busca OR p.cpf LIKE :busca)";
    }
    
    $sql .= " ORDER BY p.nome ASC LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar dados completos do gestor por ID
function buscarGestorPorId($gestorId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT g.id as gestor_id, p.nome, p.email, p.telefone, p.cpf, g.cargo
            FROM gestor g
            INNER JOIN pessoa p ON g.pessoa_id = p.id
            WHERE g.id = :gestor_id AND g.ativo = 1
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':gestor_id', $gestorId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processar requisição
$acao = $_GET['acao'] ?? '';
$busca = $_GET['busca'] ?? '';
$gestor_id = $_GET['gestor_id'] ?? '';

try {
    if ($acao === 'buscar_por_id' && !empty($gestor_id)) {
        $gestor = buscarGestorPorId($gestor_id);
        if ($gestor) {
            echo json_encode(['status' => true, 'gestor' => $gestor]);
        } else {
            echo json_encode(['status' => false, 'mensagem' => 'Gestor não encontrado.']);
        }
    } else {
        $gestores = buscarGestores($busca);
        echo json_encode(['status' => true, 'gestores' => $gestores]);
    }
} catch (Exception $e) {
    error_log("Erro ao buscar gestores: " . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => 'Erro interno do servidor.']);
}
?>