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

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'mensagem' => 'ID não fornecido.']);
    exit;
}

$id = intval($_GET['id']);
$tipo = $_GET['tipo'] ?? '';

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para obter os dados da disciplina
function obterDisciplina($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM disciplina WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $disciplina = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($disciplina) {
        return ['status' => true, 'disciplina' => $disciplina];
    } else {
        return ['status' => false, 'mensagem' => 'Disciplina não encontrada.'];
    }
}

// Função para obter os dados da turma
function obterTurma($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT t.*, e.nome as escola_nome 
            FROM turma t
            INNER JOIN escola e ON t.escola_id = e.id
            WHERE t.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($turma) {
        return ['status' => true, 'turma' => $turma];
    } else {
        return ['status' => false, 'mensagem' => 'Turma não encontrada.'];
    }
}

// Processar requisição
try {
    if ($tipo === 'disciplina') {
        $resultado = obterDisciplina($id);
    } elseif ($tipo === 'turma') {
        $resultado = obterTurma($id);
    } else {
        $resultado = ['status' => false, 'mensagem' => 'Tipo não especificado.'];
    }
    
    echo json_encode($resultado);
} catch (Exception $e) {
    error_log("Erro ao obter dados: " . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => 'Erro interno do servidor.']);
}
?>

