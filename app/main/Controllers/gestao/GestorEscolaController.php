<?php
/**
 * GestorEscolaController - Controller para gerenciar seleção de escola do gestor
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'GESTAO') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

require_once('../../config/Database.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    switch ($action) {
        case 'listar_escolas':
            // Buscar todas as escolas ativas do gestor
            $pessoaId = $_SESSION['pessoa_id'] ?? null;
            
            if (!$pessoaId) {
                throw new Exception('pessoa_id não encontrado na sessão');
            }
            
            // Primeiro, buscar o ID do gestor usando pessoa_id
            $sqlGestor = "SELECT g.id as gestor_id
                         FROM gestor g
                         WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1
                         LIMIT 1";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':pessoa_id', $pessoaId);
            $stmtGestor->execute();
            $gestorData = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestorData) {
                throw new Exception('Gestor não encontrado');
            }
            
            $gestorId = (int)$gestorData['gestor_id'];
            
            // Buscar todas as lotações ativas do gestor (sem duplicatas)
            $sqlEscolas = "SELECT DISTINCT 
                             gl.escola_id, 
                             e.nome as escola_nome, 
                             MAX(gl.responsavel) as responsavel,
                             MAX(gl.inicio) as inicio
                           FROM gestor_lotacao gl
                           INNER JOIN escola e ON gl.escola_id = e.id
                           WHERE gl.gestor_id = :gestor_id
                           AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                           AND e.ativo = 1
                           GROUP BY gl.escola_id, e.nome
                           ORDER BY 
                             MAX(gl.responsavel) DESC,
                             MAX(gl.inicio) DESC,
                             e.nome ASC";
            $stmtEscolas = $conn->prepare($sqlEscolas);
            $stmtEscolas->bindParam(':gestor_id', $gestorId);
            $stmtEscolas->execute();
            $escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $escolas]);
            break;
            
        case 'mudar_escola':
            // Mudar a escola selecionada na sessão
            error_log("DEBUG MUDAR ESCOLA - Recebido POST: " . json_encode($_POST));
            error_log("DEBUG MUDAR ESCOLA - Sessão atual: " . json_encode(['usuario_id' => $_SESSION['usuario_id'] ?? null, 'tipo' => $_SESSION['tipo'] ?? null, 'pessoa_id' => $_SESSION['pessoa_id'] ?? null]));
            
            $escolaId = $_POST['escola_id'] ?? null;
            
            if (!$escolaId) {
                error_log("DEBUG MUDAR ESCOLA - Erro: ID da escola não informado");
                throw new Exception('ID da escola não informado');
            }
            
            // Verificar se o gestor tem acesso a esta escola
            $pessoaId = $_SESSION['pessoa_id'] ?? null;
            
            if (!$pessoaId) {
                error_log("DEBUG MUDAR ESCOLA - Erro: pessoa_id não encontrado na sessão");
                throw new Exception('pessoa_id não encontrado na sessão');
            }
            
            $sqlGestor = "SELECT g.id as gestor_id
                         FROM gestor g
                         WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1
                         LIMIT 1";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':pessoa_id', $pessoaId);
            $stmtGestor->execute();
            $gestorData = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG MUDAR ESCOLA - Gestor encontrado: " . json_encode($gestorData));
            
            if (!$gestorData) {
                error_log("DEBUG MUDAR ESCOLA - Erro: Gestor não encontrado para pessoa_id=" . $pessoaId);
                throw new Exception('Gestor não encontrado');
            }
            
            $gestorId = (int)$gestorData['gestor_id'];
            
            // Verificar se o gestor tem lotação nesta escola
            $sqlVerificar = "SELECT gl.escola_id, e.nome as escola_nome
                            FROM gestor_lotacao gl
                            INNER JOIN escola e ON gl.escola_id = e.id
                            WHERE gl.gestor_id = :gestor_id
                            AND gl.escola_id = :escola_id
                            AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                            AND e.ativo = 1
                            LIMIT 1";
            $stmtVerificar = $conn->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':gestor_id', $gestorId);
            $stmtVerificar->bindParam(':escola_id', $escolaId);
            $stmtVerificar->execute();
            $escola = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG MUDAR ESCOLA - Escola verificada: " . json_encode($escola));
            
            if (!$escola) {
                error_log("DEBUG MUDAR ESCOLA - Erro: Gestor não tem acesso à escola ID=" . $escolaId);
                throw new Exception('Você não tem acesso a esta escola');
            }
            
            // Salvar na sessão
            $_SESSION['escola_selecionada_id'] = (int)$escolaId;
            $_SESSION['escola_selecionada_nome'] = $escola['escola_nome'];
            $_SESSION['escola_atual'] = $escola['escola_nome'];
            
            error_log("DEBUG MUDAR ESCOLA - Escola alterada com sucesso. Nova escola: ID=" . $escolaId . ", Nome=" . $escola['escola_nome']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Escola alterada com sucesso',
                'escola_id' => (int)$escolaId,
                'escola_nome' => $escola['escola_nome']
            ]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

