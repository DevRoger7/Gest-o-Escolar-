<?php
/**
 * ComunicadoController - Controller para comunicados
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/comunicacao/ComunicadoModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$model = new ComunicadoModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            $filtros = [
                'turma_id' => $_GET['turma_id'] ?? null,
                'aluno_id' => $_GET['aluno_id'] ?? null,
                'escola_id' => $_GET['escola_id'] ?? null,
                'tipo' => $_GET['tipo'] ?? null,
                'lido' => $_GET['lido'] ?? null,
                'ativo' => $_GET['ativo'] ?? 1
            ];
            $comunicados = $model->listar($filtros);
            echo json_encode(['success' => true, 'data' => $comunicados]);
            break;
            
        case 'criar':
            // Verificar permissão (PROFESSOR, GESTAO ou ADM)
            if (!temPermissao('enviar_comunicados') && !temPermissao('supervisionar_comunicacao') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $dados = [
                'turma_id' => $_POST['turma_id'] ?? null,
                'aluno_id' => $_POST['aluno_id'] ?? null,
                'escola_id' => $_POST['escola_id'] ?? null,
                'titulo' => $_POST['titulo'] ?? '',
                'mensagem' => $_POST['mensagem'] ?? '',
                'tipo' => $_POST['tipo'] ?? 'GERAL',
                'prioridade' => $_POST['prioridade'] ?? 'NORMAL',
                'canal' => $_POST['canal'] ?? 'SISTEMA'
            ];
            $result = $model->criar($dados);
            echo json_encode($result);
            break;
            
        case 'marcar_lido':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->marcarComoLido($id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'adicionar_resposta':
            // Verificar permissão (RESPONSAVEL)
            if (!eResponsavel() && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $comunicadoId = $_POST['comunicado_id'] ?? null;
            $responsavelId = $_SESSION['usuario_id'];
            $resposta = $_POST['resposta'] ?? '';
            
            if (!$comunicadoId || !$resposta) {
                throw new Exception('Dados incompletos');
            }
            
            $result = $model->adicionarResposta($comunicadoId, $responsavelId, $resposta);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

