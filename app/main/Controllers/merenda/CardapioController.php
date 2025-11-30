<?php
/**
 * CardapioController - Controller para gerenciamento de cardápios
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/merenda/CardapioModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$model = new CardapioModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            // Verificar permissão
            if (!temPermissao('visualizar_cardapios') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $filtros = [
                'escola_id' => $_GET['escola_id'] ?? null,
                'mes' => $_GET['mes'] ?? null,
                'ano' => $_GET['ano'] ?? null,
                'status' => $_GET['status'] ?? null
            ];
            $cardapios = $model->listar($filtros);
            echo json_encode(['success' => true, 'data' => $cardapios]);
            break;
            
        case 'buscar':
            if (!temPermissao('visualizar_cardapios') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $cardapio = $model->buscarPorId($id);
            echo json_encode(['success' => true, 'data' => $cardapio]);
            break;
            
        case 'criar':
            // Verificar permissão (NUTRICIONISTA ou ADM)
            if (!temPermissao('adc_cardapio') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $dados = [
                'escola_id' => $_POST['escola_id'] ?? null,
                'mes' => $_POST['mes'] ?? null,
                'ano' => $_POST['ano'] ?? null,
                'itens' => json_decode($_POST['itens'] ?? '[]', true)
            ];
            $result = $model->criar($dados);
            echo json_encode($result);
            break;
            
        case 'aprovar':
            // Verificar permissão (ADM_MERENDA ou ADM)
            if (!temPermissao('revisar_cardapios') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->aprovar($id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'publicar':
            // Verificar permissão (ADM_MERENDA ou ADM)
            if (!temPermissao('revisar_cardapios') && !eAdm()) {
                throw new Exception('Sem permissão');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->publicar($id);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

