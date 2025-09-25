<?php
// Iniciar sessão
session_start();

// Configurar headers para AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    echo json_encode(['status' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'mensagem' => 'ID do usuário não fornecido.']);
    exit;
}

$id = intval($_GET['id']);

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para obter os dados do usuário
function obterUsuario($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT u.*, p.* FROM usuario u 
            INNER JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        return ['status' => true, 'usuario' => $usuario];
    } else {
        return ['status' => false, 'mensagem' => 'Usuário não encontrado.'];
    }
}

// Processar requisição
try {
    $resultado = obterUsuario($id);
    echo json_encode($resultado);
} catch (Exception $e) {
    error_log("Erro ao obter usuário: " . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => 'Erro interno do servidor.']);
}
?>