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

function buscarProfessoresDisponiveis($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Buscar professores que não estão lotados em nenhuma escola ou que estão disponíveis
    $sql = "SELECT prof.id AS id, p.nome AS nome, p.email AS email, p.telefone AS telefone,
                   CASE WHEN pl.id IS NULL THEN 'Disponível' ELSE 'Lotado' END as status_lotacao
            FROM usuario u
            INNER JOIN pessoa p ON p.id = u.pessoa_id
            INNER JOIN professor prof ON prof.pessoa_id = u.pessoa_id
            LEFT JOIN professor_lotacao pl ON prof.id = pl.professor_id AND pl.fim IS NULL
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

    $sql = "SELECT pl.id, pl.professor_id, p.nome AS nome, p.email AS email, p.telefone AS telefone,
                   pl.inicio AS data_inicio, pl.fim AS data_fim, pl.carga_horaria, pl.observacao,
                   CASE WHEN pl.fim IS NULL THEN 1 ELSE 0 END AS ativo
            FROM professor_lotacao pl
            INNER JOIN professor prof ON pl.professor_id = prof.id
            INNER JOIN pessoa p ON prof.pessoa_id = p.id
            WHERE pl.escola_id = :escola_id AND pl.fim IS NULL
            ORDER BY p.nome ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':escola_id', $escola_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lotarProfessor($professor_id, $escola_id, $data_inicio) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Verificar se o professor já está lotado na escola (lotação ativa = fim IS NULL)
        $stmt = $conn->prepare("SELECT id FROM professor_lotacao WHERE professor_id = :professor_id AND escola_id = :escola_id AND fim IS NULL");
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            throw new Exception('Professor já está lotado nesta escola.');
        }

        // Inserir nova lotação
        $stmt = $conn->prepare("INSERT INTO professor_lotacao (professor_id, escola_id, inicio) VALUES (:professor_id, :escola_id, :data_inicio)");
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->execute();

        $conn->commit();
        return ['success' => true, 'message' => 'Professor lotado com sucesso!'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Erro ao lotar professor: ' . $e->getMessage()];
    }
}

function removerLotacaoProfessor($professor_id, $escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Finalizar lotação (definir data de fim)
        $stmt = $conn->prepare("UPDATE professor_lotacao SET fim = CURDATE() WHERE professor_id = :professor_id AND escola_id = :escola_id AND fim IS NULL");
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();

        $conn->commit();
        return ['success' => true, 'message' => 'Lotação removida com sucesso!'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Erro ao remover lotação: ' . $e->getMessage()];
    }
}

function buscarLotacoesProfessor($professor_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT pl.id, pl.professor_id, pl.escola_id, e.nome AS escola_nome, 
                   pl.inicio AS data_inicio, pl.fim AS data_fim, pl.carga_horaria, 
                   pl.observacao, pl.criado_em,
                   CASE WHEN pl.fim IS NULL THEN 'Ativa' ELSE 'Finalizada' END as status
            FROM professor_lotacao pl
            INNER JOIN escola e ON pl.escola_id = e.id
            WHERE pl.professor_id = :professor_id
            ORDER BY pl.criado_em DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':professor_id', $professor_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function finalizarLotacao($lotacao_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Atualizar lotação para finalizada
        $stmt = $conn->prepare("UPDATE professor_lotacao SET fim = CURDATE() WHERE id = :lotacao_id");
        $stmt->bindParam(':lotacao_id', $lotacao_id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception('Lotação não encontrada.');
        }

        $conn->commit();
        return ['success' => true, 'message' => 'Lotação finalizada com sucesso!'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Erro ao finalizar lotação: ' . $e->getMessage()];
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

        case 'listar_lotados':
            $escola_id = $_GET['escola_id'] ?? '';
            if (empty($escola_id)) {
                throw new Exception('ID da escola é obrigatório.');
            }
            $professores = buscarProfessoresEscola($escola_id);
            echo json_encode(['success' => true, 'professores' => $professores]);
            break;

        case 'lotar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido.');
            }
            $professor_id = $_POST['professor_id'] ?? '';
            $escola_id = $_POST['escola_id'] ?? '';
            $data_inicio = $_POST['data_inicio'] ?? '';
            
            if (empty($professor_id) || empty($escola_id)) {
                throw new Exception('ID do professor e da escola são obrigatórios.');
            }
            
            if (empty($data_inicio)) {
                throw new Exception('Data de início é obrigatória.');
            }
            
            $resultado = lotarProfessor($professor_id, $escola_id, $data_inicio);
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

        case 'buscar_lotacoes':
            $professor_id = $_GET['professor_id'] ?? '';
            if (empty($professor_id)) {
                throw new Exception('ID do professor é obrigatório.');
            }
            $lotacoes = buscarLotacoesProfessor($professor_id);
            echo json_encode(['success' => true, 'lotacoes' => $lotacoes]);
            break;

        case 'finalizar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido.');
            }
            $lotacao_id = $_POST['lotacao_id'] ?? '';
            
            if (empty($lotacao_id)) {
                throw new Exception('ID da lotação é obrigatório.');
            }
            
            $resultado = finalizarLotacao($lotacao_id);
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