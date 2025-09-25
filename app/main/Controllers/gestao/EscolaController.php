<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'mensagem' => 'ID da escola não fornecido.']);
    exit;
}

$id = intval($_GET['id']);

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para obter os dados da escola
function obterEscola($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT e.id, e.nome, e.endereco, e.telefone, e.email, e.municipio, e.cep, e.qtd_salas, e.obs, e.codigo, e.criado_em as data_criacao,
                   p.nome as gestor_nome, p.email as gestor_email, g.id as gestor_id, u.id as gestor_usuario_id
            FROM escola e 
            LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1
            LEFT JOIN gestor g ON gl.gestor_id = g.id
            LEFT JOIN pessoa p ON g.pessoa_id = p.id
            LEFT JOIN usuario u ON u.pessoa_id = p.id
            WHERE e.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($escola) {
        return ['status' => true, 'escola' => $escola];
    } else {
        return ['status' => false, 'mensagem' => 'Escola não encontrada.'];
    }
}

// Processar requisição
try {
    $resultado = obterEscola($id);
    echo json_encode($resultado);
} catch (Exception $e) {
    error_log("Erro ao obter escola: " . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => 'Erro interno do servidor.']);
}
?>