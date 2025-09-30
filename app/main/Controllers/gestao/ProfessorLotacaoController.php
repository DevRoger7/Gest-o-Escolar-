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

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

function buscarProfessoresDisponiveis($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Buscar professores que não estão lotados em nenhuma escola ou que estão disponíveis
    $sql = "SELECT u.id AS id, p.nome AS nome, p.email AS email, p.telefone AS telefone,
                   CASE WHEN pl.id IS NULL THEN 'Disponível' ELSE 'Lotado' END as status_lotacao
            FROM usuario u
            INNER JOIN pessoa p ON p.id = u.pessoa_id
            LEFT JOIN professor_lotacao pl ON u.id = pl.professor_id AND pl.ativo = 1
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

function buscarProfessoresEscola($escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT u.id AS professor_id, p.nome AS nome, p.email AS email, p.telefone AS telefone,
                   pl.data_inicio, pl.data_fim, pl.ativo
            FROM professor_lotacao pl
            INNER JOIN usuario u ON pl.professor_id = u.id
            INNER JOIN pessoa p ON u.pessoa_id = p.id
            WHERE pl.escola_id = :escola_id AND pl.ativo = 1
            ORDER BY p.nome ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':escola_id', $escola_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lotarProfessor($professor_id, $escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Verificar se o professor já está lotado na escola
        $stmt = $conn->prepare("SELECT id FROM professor_lotacao WHERE professor_id = :professor_id AND escola_id = :escola_id AND ativo = 1");
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            throw new Exception('Professor já está lotado nesta escola.');
        }

        // Inserir nova lotação
        $stmt = $conn->prepare("INSERT INTO professor_lotacao (professor_id, escola_id, data_inicio, ativo) VALUES (:professor_id, :escola_id, CURDATE(), 1)");
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();

        $conn->commit();
        return ['status' => true, 'mensagem' => 'Professor lotado com sucesso!'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao lotar professor: ' . $e->getMessage()];
    }
}

function removerLotacaoProfessor($professor_id, $escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Atualizar lotação para inativa
        $stmt = $conn->prepare("UPDATE professor_lotacao SET ativo = 0, data_fim = CURDATE() WHERE professor_id = :professor_id AND escola_id = :escola_id AND ativo = 1");
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();

        $conn->commit();
        return ['status' => true, 'mensagem' => 'Lotação removida com sucesso!'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao remover lotação: ' . $e->getMessage()];
    }
}

// Processar requisições
try {
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

    switch ($acao) {
        case 'listar_disponiveis':
        case 'buscar_disponiveis':
            $busca = $_GET['busca'] ?? $_GET['termo'] ?? '';
            $professores = buscarProfessoresDisponiveis($busca);
            echo json_encode(['success' => true, 'professores' => $professores]);
            break;

        case 'listar_escola':
            $escola_id = $_GET['escola_id'] ?? '';
            if (empty($escola_id)) {
                throw new Exception('ID da escola é obrigatório.');
            }
            $professores = buscarProfessoresEscola($escola_id);
            echo json_encode(['status' => true, 'professores' => $professores]);
            break;

        case 'lotar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido.');
            }
            $professor_id = $_POST['professor_id'] ?? '';
            $escola_id = $_POST['escola_id'] ?? '';
            
            if (empty($professor_id) || empty($escola_id)) {
                throw new Exception('ID do professor e da escola são obrigatórios.');
            }
            
            $resultado = lotarProfessor($professor_id, $escola_id);
            echo json_encode($resultado);
            break;

        case 'remover':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido.');
            }
            $professor_id = $_POST['professor_id'] ?? '';
            $escola_id = $_POST['escola_id'] ?? '';
            
            if (empty($professor_id) || empty($escola_id)) {
                throw new Exception('ID do professor e da escola são obrigatórios.');
            }
            
            $resultado = removerLotacaoProfessor($professor_id, $escola_id);
            echo json_encode($resultado);
            break;

        default:
            // Se não há ação específica, listar professores disponíveis
            $busca = $_GET['busca'] ?? '';
            $professores = buscarProfessoresDisponiveis($busca);
            echo json_encode(['status' => true, 'professores' => $professores]);
            break;
    }
} catch (Exception $e) {
    error_log('Erro no ProfessorLotacaoController: ' . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
}

?>