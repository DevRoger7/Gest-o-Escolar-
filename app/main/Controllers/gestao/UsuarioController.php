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
        // Buscar dados específicos conforme o tipo de usuário
        $role = strtoupper($usuario['role']);
        
        if ($role === 'ALUNO') {
            $stmt = $conn->prepare("SELECT * FROM aluno WHERE pessoa_id = :pessoa_id");
            $stmt->bindParam(':pessoa_id', $usuario['pessoa_id']);
            $stmt->execute();
            $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($aluno) {
                $usuario['matricula'] = $aluno['matricula'] ?? null;
                $usuario['nis'] = $aluno['nis'] ?? null;
                $usuario['responsavel_id'] = $aluno['responsavel_id'] ?? null;
                $usuario['data_matricula'] = $aluno['data_matricula'] ?? null;
            }
        } elseif ($role === 'PROFESSOR') {
            $stmt = $conn->prepare("SELECT * FROM professor WHERE pessoa_id = :pessoa_id");
            $stmt->bindParam(':pessoa_id', $usuario['pessoa_id']);
            $stmt->execute();
            $professor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($professor) {
                $usuario['matricula'] = $professor['matricula'] ?? null;
                $usuario['formacao'] = $professor['formacao'] ?? null;
                $usuario['data_admissao'] = $professor['data_admissao'] ?? null;
            }
        } elseif ($role === 'GESTAO') {
            $stmt = $conn->prepare("SELECT * FROM gestor WHERE pessoa_id = :pessoa_id");
            $stmt->bindParam(':pessoa_id', $usuario['pessoa_id']);
            $stmt->execute();
            $gestor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($gestor) {
                $usuario['cargo'] = $gestor['cargo'] ?? 'gestor';
            }
        }
        
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