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

// Função para buscar escola por ID
function buscarEscolaPorId($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT e.*, p.nome AS gestor_nome, p.email AS gestor_email 
            FROM escola e 
            LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1
            LEFT JOIN gestor g ON gl.gestor_id = g.id
            LEFT JOIN usuario u ON g.usuario_id = u.id OR g.pessoa_id = u.pessoa_id
            LEFT JOIN pessoa p ON u.pessoa_id = p.id
            WHERE e.id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para listar todas as escolas
function listarEscolas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT e.*, p.nome AS gestor_nome, p.email AS gestor_email 
            FROM escola e 
            LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1
            LEFT JOIN gestor g ON gl.gestor_id = g.id
            LEFT JOIN usuario u ON g.usuario_id = u.id OR g.pessoa_id = u.pessoa_id
            LEFT JOIN pessoa p ON u.pessoa_id = p.id";

    if (!empty($busca)) {
        $sql .= " WHERE e.nome LIKE :busca OR e.municipio LIKE :busca OR e.codigo LIKE :busca";
    }

    $sql .= " ORDER BY e.nome ASC";

    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = '%' . $busca . '%';
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar requisições
try {
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

    switch ($acao) {
        case 'buscar':
        case 'listar':
            $busca = $_GET['busca'] ?? $_GET['termo'] ?? '';
            $escolas = listarEscolas($busca);
            echo json_encode(['status' => true, 'escolas' => $escolas]);
            break;

        default:
            // Se há um ID, buscar escola específica
            if (isset($_GET['id'])) {
                $escola = buscarEscolaPorId($_GET['id']);
                if ($escola) {
                    echo json_encode(['status' => true, 'escola' => $escola]);
                } else {
                    echo json_encode(['status' => false, 'mensagem' => 'Escola não encontrada.']);
                }
            } else {
                // Listar todas as escolas
                $escolas = listarEscolas();
                echo json_encode(['status' => true, 'escolas' => $escolas]);
            }
            break;
    }
} catch (Exception $e) {
    error_log('Erro no EscolaController: ' . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
}

?>