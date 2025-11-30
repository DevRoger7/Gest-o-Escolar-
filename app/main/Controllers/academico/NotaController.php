<?php
/**
 * NotaController - Controller para lançamento de notas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/academico/NotaModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Verificar permissão
if (!temPermissao('lancar_nota') && !temPermissao('acompanhar_notas') && !eAdm()) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

$model = new NotaModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'lancar':
            $dados = [
                'avaliacao_id' => $_POST['avaliacao_id'] ?? null,
                'disciplina_id' => $_POST['disciplina_id'] ?? null,
                'turma_id' => $_POST['turma_id'] ?? null,
                'aluno_id' => $_POST['aluno_id'] ?? null,
                'nota' => $_POST['nota'] ?? null,
                'bimestre' => $_POST['bimestre'] ?? null,
                'recuperacao' => $_POST['recuperacao'] ?? 0,
                'comentario' => $_POST['comentario'] ?? null
            ];
            $result = $model->lancar($dados);
            echo json_encode(['success' => $result]);
            break;
            
        case 'lancar_lote':
            $notas = json_decode($_POST['notas'] ?? '[]', true);
            if (empty($notas)) {
                throw new Exception('Nenhuma nota informada');
            }
            $result = $model->lancarLote($notas);
            echo json_encode($result);
            break;
            
        case 'buscar':
            $alunoId = $_GET['aluno_id'] ?? null;
            if (!$alunoId) {
                throw new Exception('ID do aluno não informado');
            }
            $notas = $model->buscarPorAluno(
                $alunoId,
                $_GET['turma_id'] ?? null,
                $_GET['disciplina_id'] ?? null,
                $_GET['bimestre'] ?? null
            );
            echo json_encode(['success' => true, 'data' => $notas]);
            break;
            
        case 'calcular_media':
            $alunoId = $_GET['aluno_id'] ?? null;
            $disciplinaId = $_GET['disciplina_id'] ?? null;
            $turmaId = $_GET['turma_id'] ?? null;
            if (!$alunoId || !$disciplinaId || !$turmaId) {
                throw new Exception('Dados incompletos');
            }
            $result = $model->calcularMedia($alunoId, $disciplinaId, $turmaId, $_GET['bimestre'] ?? null);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'atualizar':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $dados = [
                'nota' => $_POST['nota'] ?? null,
                'bimestre' => $_POST['bimestre'] ?? null,
                'recuperacao' => $_POST['recuperacao'] ?? 0,
                'comentario' => $_POST['comentario'] ?? null
            ];
            $result = $model->atualizar($id, $dados);
            echo json_encode(['success' => $result]);
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

