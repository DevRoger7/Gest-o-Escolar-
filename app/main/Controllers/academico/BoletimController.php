<?php
/**
 * BoletimController - Controller para geração de boletins
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/permissions_helper.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once(__DIR__ . '/../../Models/academico/BoletimModel.php');
require_once(__DIR__ . '/../../Models/academico/TurmaModel.php');

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar permissão (ADM ou GESTAO podem gerar boletins)
if (!eAdm() && !temPermissao('gerar_relatorios_pedagogicos') && !temPermissao('relatorio_pedagogico')) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para gerar boletins'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Ação não informada'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $boletimModel = new BoletimModel();
    $turmaModel = new TurmaModel();
    switch ($action) {
        case 'gerar_bimestral':
            $turmaId = $_POST['turma_id'] ?? null;
            $bimestre = $_POST['bimestre'] ?? null;
            $anoLetivo = $_POST['ano_letivo'] ?? null;
            
            if (!$turmaId || !$bimestre || !$anoLetivo) {
                throw new Exception('Dados incompletos. Informe turma, bimestre e ano letivo.');
            }
            
            // Buscar todos os alunos da turma
            $alunos = $turmaModel->buscarAlunos($turmaId);
            
            if (empty($alunos)) {
                throw new Exception('Nenhum aluno encontrado nesta turma.');
            }
            
            $boletinsGerados = 0;
            $boletinsExistentes = 0;
            $erros = [];
            
            // Gerar boletim para cada aluno
            foreach ($alunos as $aluno) {
                // Verificar se já existe antes de gerar
                $conn = Database::getInstance()->getConnection();
                $sqlCheck = "SELECT id FROM boletim WHERE aluno_id = :aluno_id AND turma_id = :turma_id 
                            AND ano_letivo = :ano_letivo AND bimestre = :bimestre";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bindParam(':aluno_id', $aluno['id']);
                $stmtCheck->bindParam(':turma_id', $turmaId);
                $stmtCheck->bindParam(':ano_letivo', $anoLetivo);
                $stmtCheck->bindParam(':bimestre', $bimestre);
                $stmtCheck->execute();
                $existe = $stmtCheck->fetch();
                
                $result = $boletimModel->gerar($aluno['id'], $turmaId, $anoLetivo, $bimestre);
                
                if ($result['success']) {
                    if ($existe) {
                        $boletinsExistentes++;
                    } else {
                        $boletinsGerados++;
                    }
                } else {
                    $erros[] = $aluno['nome'] . ': ' . ($result['message'] ?? 'Erro desconhecido');
                }
            }
            
            $mensagem = "Boletins processados com sucesso! ";
            if ($boletinsGerados > 0) {
                $mensagem .= "{$boletinsGerados} boletim(s) criado(s) ";
            }
            if ($boletinsExistentes > 0) {
                $mensagem .= "{$boletinsExistentes} boletim(s) já existente(s) ";
            }
            $mensagem .= "para o {$bimestre}º bimestre de {$anoLetivo}.";
            
            if (!empty($erros)) {
                $mensagem .= " Erros: " . implode('; ', $erros);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $mensagem,
                'boletins_gerados' => $boletinsGerados,
                'erros' => $erros
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'gerar_final':
            $turmaId = $_POST['turma_id'] ?? null;
            $anoLetivo = $_POST['ano_letivo'] ?? null;
            
            if (!$turmaId || !$anoLetivo) {
                throw new Exception('Dados incompletos. Informe turma e ano letivo.');
            }
            
            // Buscar todos os alunos da turma
            $alunos = $turmaModel->buscarAlunos($turmaId);
            
            if (empty($alunos)) {
                throw new Exception('Nenhum aluno encontrado nesta turma.');
            }
            
            $boletinsGerados = 0;
            $erros = [];
            
            // Gerar boletim final para cada aluno (todos os bimestres)
            foreach ($alunos as $aluno) {
                for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                    $result = $boletimModel->gerar($aluno['id'], $turmaId, $anoLetivo, $bimestre);
                    
                    if ($result['success']) {
                        $boletinsGerados++;
                    } else {
                        $erros[] = $aluno['nome'] . " (Bimestre {$bimestre}): " . ($result['message'] ?? 'Erro desconhecido');
                    }
                }
            }
            
            $mensagem = "Boletins finais gerados com sucesso! ";
            $mensagem .= "{$boletinsGerados} boletim(s) criado(s) para o ano letivo de {$anoLetivo}.";
            
            if (!empty($erros)) {
                $mensagem .= " Erros: " . implode('; ', array_slice($erros, 0, 5));
                if (count($erros) > 5) {
                    $mensagem .= " e mais " . (count($erros) - 5) . " erro(s).";
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => $mensagem,
                'boletins_gerados' => $boletinsGerados,
                'erros' => $erros
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

