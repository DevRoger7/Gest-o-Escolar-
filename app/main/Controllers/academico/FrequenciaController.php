<?php
/**
 * FrequenciaController - Controller para registro de frequência
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/academico/FrequenciaModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Verificar permissão
if (!temPermissao('lancar_frequencia') && !temPermissao('acompanhar_frequencia') && !eAdm()) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

$model = new FrequenciaModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'registrar':
            $dados = [
                'aluno_id' => $_POST['aluno_id'] ?? null,
                'turma_id' => $_POST['turma_id'] ?? null,
                'data' => $_POST['data'] ?? date('Y-m-d'),
                'presenca' => $_POST['presenca'] ?? 1,
                'observacao' => $_POST['observacao'] ?? null
            ];
            $result = $model->registrar($dados);
            
            // Se retornou array, é um erro
            if (is_array($result) && isset($result['success']) && !$result['success']) {
                echo json_encode($result);
            } elseif ($result === true) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao registrar frequência']);
            }
            break;
            
        case 'registrar_lote':
            $turmaId = $_POST['turma_id'] ?? null;
            $data = $_POST['data'] ?? date('Y-m-d');
            $frequencias = json_decode($_POST['frequencias'] ?? '[]', true);
            
            if (!$turmaId || empty($frequencias)) {
                throw new Exception('Dados incompletos');
            }
            
            $result = $model->registrarLote($turmaId, $data, $frequencias);
            echo json_encode($result);
            break;
            
        case 'buscar':
            $alunoId = $_GET['aluno_id'] ?? null;
            if (!$alunoId) {
                throw new Exception('ID do aluno não informado');
            }
            $frequencias = $model->buscarPorAluno(
                $alunoId,
                $_GET['turma_id'] ?? null,
                $_GET['periodo_inicio'] ?? null,
                $_GET['periodo_fim'] ?? null
            );
            echo json_encode(['success' => true, 'data' => $frequencias]);
            break;
            
        case 'calcular_percentual':
            $alunoId = $_GET['aluno_id'] ?? null;
            $turmaId = $_GET['turma_id'] ?? null;
            if (!$alunoId || !$turmaId) {
                throw new Exception('Dados incompletos');
            }
            $result = $model->calcularPercentual(
                $alunoId,
                $turmaId,
                $_GET['periodo_inicio'] ?? null,
                $_GET['periodo_fim'] ?? null
            );
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'validar':
            if (!temPermissao('validar_lancamentos') && !eAdm()) {
                throw new Exception('Sem permissão para validar');
            }
            $id = $_POST['id'] ?? null;
            $validado = $_POST['validado'] ?? true;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->validar($id, $validado);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

