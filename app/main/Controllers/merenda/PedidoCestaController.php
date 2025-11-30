<?php
/**
 * PedidoCestaController - Controller para pedidos de cesta
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/merenda/PedidoCestaModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$model = new PedidoCestaModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            $filtros = [
                'escola_id' => $_GET['escola_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'mes' => $_GET['mes'] ?? null
            ];
            $pedidos = $model->listar($filtros);
            echo json_encode(['success' => true, 'data' => $pedidos]);
            break;
            
        case 'buscar':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $pedido = $model->buscarPorId($id);
            echo json_encode(['success' => true, 'data' => $pedido]);
            break;
            
        case 'criar':
            // Verificar permissão (NUTRICIONISTA)
            if (!temPermissao('env_pedidos') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $dados = [
                'escola_id' => $_POST['escola_id'] ?? null,
                'mes' => $_POST['mes'] ?? null,
                'itens' => json_decode($_POST['itens'] ?? '[]', true)
            ];
            $result = $model->criar($dados);
            echo json_encode($result);
            break;
            
        case 'enviar':
            // Verificar permissão (NUTRICIONISTA)
            if (!temPermissao('env_pedidos') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->enviar($id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'aprovar':
            // Verificar permissão (ADM_MERENDA ou ADM)
            if (!temPermissao('aprovar_pedidos') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->aprovar($id, $_POST['observacoes'] ?? null);
            echo json_encode(['success' => $result]);
            break;
            
        case 'rejeitar':
            // Verificar permissão (ADM_MERENDA ou ADM)
            if (!temPermissao('rejeitar_pedidos') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $id = $_POST['id'] ?? null;
            $motivo = $_POST['motivo_rejeicao'] ?? '';
            if (!$id || !$motivo) {
                throw new Exception('Dados incompletos');
            }
            $result = $model->rejeitar($id, $motivo);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

