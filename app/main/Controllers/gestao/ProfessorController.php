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
if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'ADM' && strtolower($_SESSION['tipo']) !== 'gestao')) {
    echo json_encode(['status' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

function listarProfessores($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Atenção: a base usa tabelas `usuario` e `pessoa` (singular) no código atual
    // Professores são usuários com role 'PROFESSOR' (case-insensitive)
    $sql = "SELECT u.id AS id, p.nome AS nome, p.email AS email, p.telefone AS telefone
            FROM usuario u
            INNER JOIN pessoa p ON p.id = u.pessoa_id
            WHERE LOWER(u.role) = 'professor' AND u.ativo = 1";

    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.email LIKE :busca)";
    }

    $sql .= " ORDER BY p.nome ASC";

    $stmt = $conn->prepare($sql);

    if (!empty($busca)) {
        $like = "%{$busca}%";
        $stmt->bindParam(':busca', $like);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $busca = $_GET['busca'] ?? '';
    $professores = listarProfessores($busca);
    echo json_encode(['status' => true, 'professores' => $professores]);
} catch (Exception $e) {
    error_log('Erro ao listar professores: ' . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => 'Erro interno do servidor.']);
}

?>


