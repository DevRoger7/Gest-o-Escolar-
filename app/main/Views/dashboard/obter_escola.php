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
    error_log("obterEscola - Iniciando busca para ID: " . $id);
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        error_log("obterEscola - Conexão com banco estabelecida");
        
        // Buscar dados básicos da escola
        $sql = "SELECT e.*, 
                       g.id as gestor_id, 
                       p.nome as gestor_nome, 
                       p.email as gestor_email
                FROM escola e 
                LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1
                LEFT JOIN gestor g ON gl.gestor_id = g.id
                LEFT JOIN pessoa p ON g.pessoa_id = p.id
                WHERE e.id = :id";
        
        error_log("obterEscola - SQL preparado: " . $sql);
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $escola = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("obterEscola - Resultado da consulta: " . print_r($escola, true));
        
        if (!$escola) {
            error_log("obterEscola - Escola não encontrada para ID: " . $id);
            return ['status' => false, 'mensagem' => 'Escola não encontrada.'];
        }
    } catch (Exception $e) {
        error_log("obterEscola - Erro na consulta: " . $e->getMessage());
        return ['status' => false, 'mensagem' => 'Erro ao buscar dados da escola: ' . $e->getMessage()];
    }
    
    // Buscar professores da escola (se necessário)
    $sqlProfessores = "SELECT p.id, p.nome, p.email, p.telefone, u.role
                       FROM professor_lotacao pl
                       JOIN professor pr ON pl.professor_id = pr.id
                       JOIN pessoa p ON pr.pessoa_id = p.id
                       JOIN usuario u ON p.id = u.pessoa_id
                       WHERE pl.escola_id = :escola_id
                       ORDER BY p.nome ASC";
    
    $stmtProfessores = $conn->prepare($sqlProfessores);
    $stmtProfessores->bindParam(':escola_id', $id, PDO::PARAM_INT);
    $stmtProfessores->execute();
    
    $professores = $stmtProfessores->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'status' => true, 
        'escola' => $escola,
        'professores' => $professores
    ];
}

// Obter os dados da escola
$resultado = obterEscola($id);

// Retornar os dados em formato JSON
header('Content-Type: application/json');
echo json_encode($resultado);
?>