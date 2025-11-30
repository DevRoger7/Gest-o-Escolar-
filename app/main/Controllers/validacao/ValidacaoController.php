<?php
/**
 * ValidacaoController - Controller para validação de informações
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/validacao/ValidacaoModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Verificar permissão (ADM ou GESTAO)
if (!temPermissao('validar_informacoes') && !temPermissao('validar_lancamentos') && !eAdm()) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

$model = new ValidacaoModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar_pendentes':
            $filtros = [
                'tipo_registro' => $_GET['tipo_registro'] ?? null
            ];
            $validacoes = $model->listarPendentes($filtros);
            echo json_encode(['success' => true, 'data' => $validacoes]);
            break;
            
        case 'aprovar':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->aprovar($id, $_POST['observacoes'] ?? null);
            echo json_encode($result);
            break;
            
        case 'rejeitar':
            $id = $_POST['id'] ?? null;
            $observacoes = $_POST['observacoes'] ?? '';
            if (!$id || !$observacoes) {
                throw new Exception('Dados incompletos');
            }
            $result = $model->rejeitar($id, $observacoes);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

