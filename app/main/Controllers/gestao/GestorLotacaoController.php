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

function buscarGestoresDisponiveis($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Buscar gestores que não são responsáveis por nenhuma escola ou que estão disponíveis
    $sql = "SELECT u.id AS id, p.nome AS nome, p.email AS email, p.telefone AS telefone,
                   CASE WHEN gl.id IS NULL THEN 'Disponível' ELSE 'Lotado' END as status_lotacao,
                   e.nome as escola_atual
            FROM usuario u
            INNER JOIN pessoa p ON p.id = u.pessoa_id
            LEFT JOIN gestor g ON u.id = g.usuario_id OR u.pessoa_id = g.pessoa_id
            LEFT JOIN gestor_lotacao gl ON g.id = gl.gestor_id AND gl.responsavel = 1
            LEFT JOIN escola e ON gl.escola_id = e.id
            WHERE LOWER(u.role) = 'gestao' AND u.ativo = 1";

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

function buscarGestorEscola($escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT u.id AS gestor_id, p.nome AS nome, p.email AS email, p.telefone AS telefone,
                   gl.inicio, gl.responsavel
            FROM gestor_lotacao gl
            INNER JOIN gestor g ON gl.gestor_id = g.id
            INNER JOIN usuario u ON g.usuario_id = u.id OR g.pessoa_id = u.pessoa_id
            INNER JOIN pessoa p ON u.pessoa_id = p.id
            WHERE gl.escola_id = :escola_id AND gl.responsavel = 1
            ORDER BY p.nome ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':escola_id', $escola_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lotarGestor($gestor_id, $escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Primeiro, remover qualquer gestor responsável atual da escola
        $stmt = $conn->prepare("DELETE FROM gestor_lotacao WHERE escola_id = :escola_id AND responsavel = 1");
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();

        // Localizar o gestor.id correspondente ao usuario.id informado
        $gestorIdReal = null;
        
        // 1) Tentar via tabela gestor com coluna usuario_id
        try {
            $stmt = $conn->prepare("SELECT id FROM gestor WHERE usuario_id = :usuario_id LIMIT 1");
            $stmt->bindParam(':usuario_id', $gestor_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $gestorIdReal = (int)$row['id'];
            }
        } catch (PDOException $e) {
            // Se a tabela/coluna não existir, ignorar e tentar outro caminho
        }

        // 2) Caso não ache, tentar via ligação por pessoa: gestor.pessoa_id -> usuario.pessoa_id
        if ($gestorIdReal === null) {
            try {
                $stmt = $conn->prepare("SELECT g.id 
                                        FROM gestor g 
                                        INNER JOIN usuario u ON u.pessoa_id = g.pessoa_id 
                                        WHERE u.id = :usuario_id 
                                        LIMIT 1");
                $stmt->bindParam(':usuario_id', $gestor_id);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $gestorIdReal = (int)$row['id'];
                }
            } catch (PDOException $e) {
                // Ignorar e continuar para mensagem de erro amigável
            }
        }

        if ($gestorIdReal === null) {
            throw new Exception('Gestor selecionado não possui cadastro válido na tabela gestor.');
        }

        // Inserir nova lotação
        $stmt = $conn->prepare("INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel) VALUES (:gestor_id, :escola_id, CURDATE(), 1)");
        $stmt->bindParam(':gestor_id', $gestorIdReal);
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();

        $conn->commit();
        return ['status' => true, 'mensagem' => 'Gestor lotado como responsável pela escola com sucesso!'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao lotar gestor: ' . $e->getMessage()];
    }
}

function removerLotacaoGestor($escola_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Remover lotação do gestor responsável
        $stmt = $conn->prepare("DELETE FROM gestor_lotacao WHERE escola_id = :escola_id AND responsavel = 1");
        $stmt->bindParam(':escola_id', $escola_id);
        $stmt->execute();

        $conn->commit();
        return ['status' => true, 'mensagem' => 'Lotação do gestor removida com sucesso!'];
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
            $gestores = buscarGestoresDisponiveis($busca);
            echo json_encode(['success' => true, 'gestores' => $gestores]);
            break;

        case 'listar_escola':
            $escola_id = $_GET['escola_id'] ?? '';
            if (empty($escola_id)) {
                throw new Exception('ID da escola é obrigatório.');
            }
            $gestores = buscarGestorEscola($escola_id);
            echo json_encode(['status' => true, 'gestores' => $gestores]);
            break;

        case 'lotar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido.');
            }
            $gestor_id = $_POST['gestor_id'] ?? '';
            $escola_id = $_POST['escola_id'] ?? '';
            
            if (empty($gestor_id) || empty($escola_id)) {
                throw new Exception('ID do gestor e da escola são obrigatórios.');
            }
            
            $resultado = lotarGestor($gestor_id, $escola_id);
            echo json_encode($resultado);
            break;

        case 'remover':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido.');
            }
            $escola_id = $_POST['escola_id'] ?? '';
            
            if (empty($escola_id)) {
                throw new Exception('ID da escola é obrigatório.');
            }
            
            $resultado = removerLotacaoGestor($escola_id);
            echo json_encode($resultado);
            break;

        default:
            // Se não há ação específica, listar gestores disponíveis
            $busca = $_GET['busca'] ?? '';
            $gestores = buscarGestoresDisponiveis($busca);
            echo json_encode(['status' => true, 'gestores' => $gestores]);
            break;
    }
} catch (Exception $e) {
    error_log('Erro no GestorLotacaoController: ' . $e->getMessage());
    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
}

?>