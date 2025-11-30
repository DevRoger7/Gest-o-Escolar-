<?php
/**
 * AlunoController - Controller para gerenciamento de alunos
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../Models/academico/AlunoModel.php');

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Verificar permissão
if (!temPermissao('cadastrar_pessoas') && !eAdm()) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

$model = new AlunoModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            $filtros = [
                'busca' => $_GET['busca'] ?? '',
                'escola_id' => $_GET['escola_id'] ?? null,
                'situacao' => $_GET['situacao'] ?? null,
                'ativo' => $_GET['ativo'] ?? 1
            ];
            $alunos = $model->listar($filtros);
            echo json_encode(['success' => true, 'data' => $alunos]);
            break;
            
        case 'buscar':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $aluno = $model->buscarPorId($id);
            echo json_encode(['success' => true, 'data' => $aluno]);
            break;
            
        case 'criar':
            $dados = [
                'cpf' => $_POST['cpf'] ?? '',
                'nome' => $_POST['nome'] ?? '',
                'data_nascimento' => $_POST['data_nascimento'] ?? null,
                'sexo' => $_POST['sexo'] ?? null,
                'email' => $_POST['email'] ?? null,
                'telefone' => $_POST['telefone'] ?? null,
                'matricula' => $_POST['matricula'] ?? null,
                'nis' => $_POST['nis'] ?? null,
                'responsavel_id' => $_POST['responsavel_id'] ?? null,
                'escola_id' => $_POST['escola_id'] ?? null,
                'data_matricula' => $_POST['data_matricula'] ?? date('Y-m-d'),
                'situacao' => $_POST['situacao'] ?? 'MATRICULADO'
            ];
            $result = $model->criar($dados);
            echo json_encode($result);
            break;
            
        case 'atualizar':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'data_nascimento' => $_POST['data_nascimento'] ?? null,
                'sexo' => $_POST['sexo'] ?? null,
                'email' => $_POST['email'] ?? null,
                'telefone' => $_POST['telefone'] ?? null,
                'endereco' => $_POST['endereco'] ?? null,
                'numero' => $_POST['numero'] ?? null,
                'complemento' => $_POST['complemento'] ?? null,
                'bairro' => $_POST['bairro'] ?? null,
                'cidade' => $_POST['cidade'] ?? null,
                'estado' => $_POST['estado'] ?? null,
                'cep' => $_POST['cep'] ?? null,
                'matricula' => $_POST['matricula'] ?? null,
                'nis' => $_POST['nis'] ?? null,
                'responsavel_id' => $_POST['responsavel_id'] ?? null,
                'escola_id' => $_POST['escola_id'] ?? null,
                'situacao' => $_POST['situacao'] ?? 'MATRICULADO',
                'ativo' => $_POST['ativo'] ?? 1
            ];
            $result = $model->atualizar($id, $dados);
            echo json_encode($result);
            break;
            
        case 'excluir':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não informado');
            }
            $result = $model->excluir($id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'matricular':
            $alunoId = $_POST['aluno_id'] ?? null;
            $turmaId = $_POST['turma_id'] ?? null;
            if (!$alunoId || !$turmaId) {
                throw new Exception('Dados incompletos');
            }
            $result = $model->matricularEmTurma($alunoId, $turmaId, $_POST['data_inicio'] ?? null);
            echo json_encode(['success' => $result]);
            break;
            
        case 'transferir':
            $alunoId = $_POST['aluno_id'] ?? null;
            $turmaAntigaId = $_POST['turma_antiga_id'] ?? null;
            $turmaNovaId = $_POST['turma_nova_id'] ?? null;
            if (!$alunoId || !$turmaAntigaId || !$turmaNovaId) {
                throw new Exception('Dados incompletos');
            }
            $result = $model->transferirTurma($alunoId, $turmaAntigaId, $turmaNovaId);
            echo json_encode($result);
            break;
            
        case 'buscar_por_turma':
            $turmaId = $_GET['turma_id'] ?? null;
            if (!$turmaId) {
                throw new Exception('ID da turma não informado');
            }
            $alunos = $model->buscarPorTurma($turmaId);
            echo json_encode(['success' => true, 'data' => $alunos]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

