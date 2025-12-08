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
$tipoUsuario = isset($_SESSION['tipo']) ? strtoupper(trim($_SESSION['tipo'])) : '';
$tiposPermitidos = ['ADM', 'GESTAO', 'GESTOR'];
$temPermissao = false;

if (isset($_SESSION['tipo'])) {
    $tipoUpper = strtoupper(trim($_SESSION['tipo']));
    $temPermissao = in_array($tipoUpper, $tiposPermitidos);
}

if (!isset($_SESSION['tipo']) || !$temPermissao) {
    error_log("Acesso negado - Tipo de usuário: " . ($tipoUsuario ?: 'não definido') . " | Sessão: " . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Tipo: ' . ($tipoUsuario ?: 'não definido')]);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para buscar escola por ID
function buscarEscolaPorId($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $sql = "SELECT e.*, p.nome AS gestor_nome, p.email AS gestor_email, p.telefone AS gestor_telefone, 
                       p.cpf AS gestor_cpf, g.id AS gestor_id, g.cargo AS gestor_cargo
            FROM escola e 
                LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1 AND gl.fim IS NULL
                LEFT JOIN gestor g ON gl.gestor_id = g.id AND g.ativo = 1
                LEFT JOIN pessoa p ON g.pessoa_id = p.id
            WHERE e.id = :id";

    $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log para debug (remover em produção se necessário)
        if (!$result) {
            error_log("Escola não encontrada com ID: " . $id);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Erro ao buscar escola por ID: " . $e->getMessage());
        throw $e;
    }
}

// Função para buscar professores de uma escola
function buscarProfessoresEscola($escolaId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $sql = "SELECT pr.id, p.nome, p.email, p.telefone, p.cpf, pr.formacao AS disciplina, 
                       pr.matricula, pl.carga_horaria, pl.observacao
                FROM professor_lotacao pl
                INNER JOIN professor pr ON pl.professor_id = pr.id
                INNER JOIN pessoa p ON pr.pessoa_id = p.id
                WHERE pl.escola_id = :escola_id AND pl.fim IS NULL AND pr.ativo = 1
                ORDER BY p.nome ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar professores da escola: " . $e->getMessage());
        throw $e;
    }
}

// Função para listar todas as escolas
function listarEscolas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT e.*, p.nome AS gestor_nome, p.email AS gestor_email 
            FROM escola e 
            LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1 AND gl.fim IS NULL
            LEFT JOIN gestor g ON gl.gestor_id = g.id
            LEFT JOIN pessoa p ON g.pessoa_id = p.id";

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
        case 'buscar_escola':
            // Buscar escola específica por ID
            if (isset($_GET['id'])) {
                $escola = buscarEscolaPorId($_GET['id']);
                if ($escola) {
                    echo json_encode(['success' => true, 'escola' => $escola]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID da escola não informado.']);
            }
            break;
            
        case 'buscar_professores':
            // Buscar professores de uma escola
            if (isset($_GET['escola_id'])) {
                $professores = buscarProfessoresEscola($_GET['escola_id']);
                echo json_encode(['success' => true, 'professores' => $professores]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID da escola não informado.']);
            }
            break;
            
        case 'remover_professor':
            // Remover professor de uma escola (finalizar lotação)
            if (isset($_POST['professor_id']) && isset($_POST['escola_id'])) {
                try {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    $conn->beginTransaction();
                    
                    // Finalizar lotação (definir data de fim)
                    $stmt = $conn->prepare("UPDATE professor_lotacao SET fim = CURDATE() WHERE professor_id = :professor_id AND escola_id = :escola_id AND fim IS NULL");
                    $stmt->bindParam(':professor_id', $_POST['professor_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':escola_id', $_POST['escola_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Professor removido com sucesso!']);
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Erro ao remover professor: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            }
            break;
            
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
                    echo json_encode(['success' => true, 'escola' => $escola]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Escola não encontrada.']);
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
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
    if ($acao === 'buscar_escola') {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
    }
}

?>