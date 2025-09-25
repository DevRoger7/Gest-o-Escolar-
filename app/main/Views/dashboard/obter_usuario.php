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

// Obter os dados do usuário
$resultado = obterUsuario($id);

// Retornar os dados em formato JSON
header('Content-Type: application/json');
echo json_encode($resultado);
?>