<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/NotaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$notaModel = new NotaModel();

$professorId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $pessoaIdParam = $pessoaId;
    $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
}

if (!$professorId) {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$pessoaId && $usuarioId) {
        $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
        $stmtPessoa = $conn->prepare($sqlPessoa);
        $usuarioIdParam = $usuarioId;
        $stmtPessoa->bindParam(':usuario_id', $usuarioIdParam);
        $stmtPessoa->execute();
        $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
        $pessoaId = $usuario['pessoa_id'] ?? null;
    }
    if (!$pessoaId) {
        $cpf = $_SESSION['cpf'] ?? null;
        if ($cpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            $sqlPessoaCpf = "SELECT id FROM pessoa WHERE cpf = :cpf LIMIT 1";
            $stmtPessoaCpf = $conn->prepare($sqlPessoaCpf);
            $stmtPessoaCpf->bindParam(':cpf', $cpfLimpo);
            $stmtPessoaCpf->execute();
            $pessoa = $stmtPessoaCpf->fetch(PDO::FETCH_ASSOC);
            $pessoaId = $pessoa['id'] ?? null;
        }
    }
    if ($pessoaId) {
        $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
        $stmtProfessor = $conn->prepare($sqlProfessor);
        $pessoaIdParam = $pessoaId;
        $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
        $stmtProfessor->execute();
        $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
        $professorId = $professor['id'] ?? null;
    }
}

$turmasProfessor = [];
if ($professorId) {
    // Remover filtro de escola selecionada - professor deve ver todas as suas turmas
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    d.id as disciplina_id,
                    d.nome as disciplina_nome,
                    e.nome as escola_nome
                  FROM turma_professor tp
                  INNER JOIN turma t ON tp.turma_id = t.id
                  INNER JOIN disciplina d ON tp.disciplina_id = d.id
                  INNER JOIN escola e ON t.escola_id = e.id
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1
                  ORDER BY t.serie, t.letra, d.nome";
    
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    $stmtTurmas->execute();
    $turmasProfessor = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'lancar_notas' && $professorId) {
        $turmaId = $_POST['turma_id'] ?? null;
        $disciplinaId = $_POST['disciplina_id'] ?? null;
        $notas = json_decode($_POST['notas'] ?? '[]', true);
        
        if ($turmaId && $disciplinaId && !empty($notas)) {
            try {
                $bimestre = $notas[0]['bimestre'] ?? 1;
                
                // Extrair IDs dos alunos das notas que serão lançadas
                $alunoIds = array_unique(array_column($notas, 'aluno_id'));
                
                // Verificar se já existem notas para esses alunos neste bimestre
                $notasExistentes = $notaModel->verificarNotasExistentes($turmaId, $disciplinaId, $bimestre, $alunoIds);
                
                if (!empty($notasExistentes)) {
                    // Buscar nomes dos alunos que já têm notas
                    $alunosComNotas = [];
                    foreach ($notasExistentes as $notaExistente) {
                        $sqlAluno = "SELECT p.nome FROM aluno a INNER JOIN pessoa p ON a.pessoa_id = p.id WHERE a.id = :aluno_id";
                        $stmtAluno = $conn->prepare($sqlAluno);
                        $stmtAluno->bindParam(':aluno_id', $notaExistente['aluno_id']);
                        $stmtAluno->execute();
                        $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
                        if ($aluno) {
                            $alunosComNotas[] = $aluno['nome'];
                        }
                    }
                    
                    $mensagem = 'Já existem notas lançadas para o ' . $bimestre . 'º bimestre para os seguintes alunos: ' . implode(', ', $alunosComNotas) . '. Use a opção "Editar" para modificar as notas existentes.';
                    echo json_encode(['success' => false, 'message' => $mensagem, 'alunos_com_notas' => array_column($notasExistentes, 'aluno_id')]);
                    exit;
                }
                
                // Desabilitar autocommit explicitamente
                $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
                $conn->beginTransaction();
                
                // Criar cache de avaliações para evitar múltiplas consultas
                $avaliacoesCache = [];
                
                // Buscar ou criar avaliações para PARCIAL, BIMESTRAL e PARTICIPATIVA
                foreach (['PARCIAL', 'BIMESTRAL', 'PARTICIPATIVA'] as $tipo) {
                    $tipoAvaliacao = ($tipo === 'PARCIAL') ? 'ATIVIDADE' : (($tipo === 'BIMESTRAL') ? 'PROVA' : 'TRABALHO');
                    $sqlAvaliacao = "SELECT id FROM avaliacao 
                                    WHERE turma_id = :turma_id 
                                    AND disciplina_id = :disciplina_id 
                                    AND tipo = :tipo 
                                    AND DATE_FORMAT(data, '%Y') = YEAR(CURDATE())
                                    AND ativo = 1
                                    LIMIT 1";
                    $stmtAvaliacao = $conn->prepare($sqlAvaliacao);
                    $stmtAvaliacao->bindParam(':turma_id', $turmaId);
                    $stmtAvaliacao->bindParam(':disciplina_id', $disciplinaId);
                    $stmtAvaliacao->bindValue(':tipo', $tipoAvaliacao);
                    $stmtAvaliacao->execute();
                    $avaliacao = $stmtAvaliacao->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$avaliacao) {
                        // Criar nova avaliação
                        $titulo = ($tipo === 'PARCIAL') ? "Avaliação Parcial - {$bimestre}º Bimestre" : 
                                 (($tipo === 'BIMESTRAL') ? "Avaliação Bimestral - {$bimestre}º Bimestre" : 
                                 "Avaliação Participativa - {$bimestre}º Bimestre");
                        
                        // Validar e obter usuario_id válido
                        $usuarioId = null;
                        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
                            $usuarioIdParam = (int)$_SESSION['usuario_id'];
                            // Verificar se o usuário existe na tabela
                            $sqlVerificarUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
                            $stmtVerificarUsuario = $conn->prepare($sqlVerificarUsuario);
                            $stmtVerificarUsuario->bindParam(':usuario_id', $usuarioIdParam);
                            $stmtVerificarUsuario->execute();
                            $usuarioExiste = $stmtVerificarUsuario->fetch(PDO::FETCH_ASSOC);
                            if ($usuarioExiste) {
                                $usuarioId = $usuarioIdParam;
                            }
                        }
                        
                        $sqlInsertAvaliacao = "INSERT INTO avaliacao (turma_id, disciplina_id, titulo, tipo, data, criado_por, criado_em)
                                               VALUES (:turma_id, :disciplina_id, :titulo, :tipo, CURDATE(), :criado_por, NOW())";
                        $stmtInsertAvaliacao = $conn->prepare($sqlInsertAvaliacao);
                        $stmtInsertAvaliacao->bindParam(':turma_id', $turmaId);
                        $stmtInsertAvaliacao->bindParam(':disciplina_id', $disciplinaId);
                        $stmtInsertAvaliacao->bindParam(':titulo', $titulo);
                        $stmtInsertAvaliacao->bindValue(':tipo', $tipoAvaliacao);
                        $stmtInsertAvaliacao->bindParam(':criado_por', $usuarioId);
                        $stmtInsertAvaliacao->execute();
                        $avaliacoesCache[$tipo] = $conn->lastInsertId();
                    } else {
                        $avaliacoesCache[$tipo] = $avaliacao['id'];
                    }
                }
                
                $notasFormatadas = [];
                $notasJaAdicionadas = []; // Rastrear notas já adicionadas para evitar duplicação no array
                foreach ($notas as $nota) {
                    if (!isset($nota['aluno_id']) || !isset($nota['nota'])) {
                        continue; // Pular notas inválidas
                    }
                    $tipo = $nota['tipo'] ?? 'PARCIAL';
                    $avaliacaoId = $avaliacoesCache[$tipo] ?? null;
                    
                    if (!$avaliacaoId) {
                        continue; // Pular se não encontrou avaliação
                    }
                    
                    // Criar chave única para verificar duplicatas no array
                    $chaveArray = $nota['aluno_id'] . '_' . $avaliacaoId . '_' . $bimestre;
                    if (isset($notasJaAdicionadas[$chaveArray])) {
                        continue; // Já adicionamos esta nota, pular
                    }
                    $notasJaAdicionadas[$chaveArray] = true;
                    
                    // Determinar o tipo de avaliação (ATIVIDADE para PARCIAL, PROVA para BIMESTRAL, TRABALHO para PARTICIPATIVA)
                    $tipoAvaliacao = ($tipo === 'PARCIAL') ? 'ATIVIDADE' : (($tipo === 'BIMESTRAL') ? 'PROVA' : 'TRABALHO');
                    
                    // Preparar nota para inserção
                    $notasFormatadas[] = [
                        'avaliacao_id' => $avaliacaoId,
                        'disciplina_id' => $disciplinaId,
                        'turma_id' => $turmaId,
                        'aluno_id' => $nota['aluno_id'],
                        'nota' => $nota['nota'],
                        'bimestre' => $bimestre,
                        'recuperacao' => 0,
                        'comentario' => $nota['comentario'] ?? null,
                        'tipo_avaliacao' => $tipoAvaliacao // Adicionar tipo para verificação
                    ];
                }
                
                // Inserir notas
                // O método lancar() já verifica duplicatas internamente
                // Usar um array para rastrear notas já processadas e evitar duplicação
                $notasProcessadas = [];
                $idsInseridos = []; // Rastrear IDs inseridos para verificação
                
                foreach ($notasFormatadas as $index => $nota) {
                    // Criar chave única para esta nota
                    $chaveNota = $nota['aluno_id'] . '_' . $nota['avaliacao_id'] . '_' . $nota['bimestre'];
                    
                    // Verificar se já processamos esta nota nesta requisição
                    if (isset($notasProcessadas[$chaveNota])) {
                        error_log("NOTA JÁ PROCESSADA - pulando: {$chaveNota}");
                        continue; // Pular se já foi processada
                    }
                    
                    // Marcar como processada ANTES de chamar lancar()
                    $notasProcessadas[$chaveNota] = true;
                    
                    // Remover tipo_avaliacao antes de passar para o modelo (não é campo da tabela)
                    unset($nota['tipo_avaliacao']);
                    
                    // Log para debug
                    error_log("Chamando lancar() para aluno_id: {$nota['aluno_id']}, avaliacao_id: {$nota['avaliacao_id']}, bimestre: {$nota['bimestre']}");
                    
                    // Verificar se já existe antes de inserir (verificação adicional)
                    $sqlVerificarAntes = "SELECT id FROM nota 
                                         WHERE aluno_id = ? 
                                         AND avaliacao_id = ? 
                                         AND disciplina_id = ? 
                                         AND turma_id = ? 
                                         AND bimestre = ?
                                         LIMIT 1";
                    $stmtVerificarAntes = $conn->prepare($sqlVerificarAntes);
                    $stmtVerificarAntes->execute([
                        $nota['aluno_id'],
                        $nota['avaliacao_id'],
                        $nota['disciplina_id'],
                        $nota['turma_id'],
                        $nota['bimestre']
                    ]);
                    $existeAntes = $stmtVerificarAntes->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existeAntes) {
                        error_log("NOTA JÁ EXISTE NO BANCO - pulando inserção: aluno_id: {$nota['aluno_id']}, avaliacao_id: {$nota['avaliacao_id']}");
                        continue; // Já existe, não inserir
                    }
                    
                    // Inserir usando o modelo (que já verifica duplicatas)
                    $resultado = $notaModel->lancar($nota);
                    
                    // Log após chamada
                    error_log("Resultado do lancar(): " . ($resultado ? 'true' : 'false') . " para aluno_id: {$nota['aluno_id']}");
                    
                    // Verificar se a inserção foi bem-sucedida
                    if ($resultado === false) {
                        // Verificar se foi porque já existe ou se houve erro real
                        $errorInfo = $conn->errorInfo();
                        if ($errorInfo[0] !== '00000' && $errorInfo[0] !== null) {
                            throw new Exception("Erro ao inserir nota: " . ($errorInfo[2] ?? 'Erro desconhecido'));
                        }
                        // Se não houver erro SQL, provavelmente já existe (verificação do método lancar retornou false)
                    }
                }
                
                // Verificar duplicatas ANTES do commit
                foreach ($notasFormatadas as $nota) {
                    $alunoId = $nota['aluno_id'];
                    $avaliacaoId = $nota['avaliacao_id'];
                    $disciplinaId = $nota['disciplina_id'];
                    $turmaId = $nota['turma_id'];
                    $bimestre = $nota['bimestre'];
                    
                    $sqlContarAntes = "SELECT COUNT(*) as total FROM nota 
                                      WHERE aluno_id = ? 
                                      AND avaliacao_id = ? 
                                      AND disciplina_id = ? 
                                      AND turma_id = ? 
                                      AND bimestre = ?";
                    $stmtContarAntes = $conn->prepare($sqlContarAntes);
                    $stmtContarAntes->execute([$alunoId, $avaliacaoId, $disciplinaId, $turmaId, $bimestre]);
                    $contagemAntes = $stmtContarAntes->fetch(PDO::FETCH_ASSOC);
                    $totalAntes = $contagemAntes['total'] ?? 0;
                    
                    if ($totalAntes > 1) {
                        error_log("DUPLICATA DETECTADA ANTES DO COMMIT: {$totalAntes} notas para aluno_id: {$alunoId}, avaliacao_id: {$avaliacaoId}");
                        // Remover duplicatas mantendo apenas a mais recente
                        $sqlRemoverDuplicatas = "DELETE n1 FROM nota n1
                                                INNER JOIN nota n2 
                                                WHERE n1.id < n2.id 
                                                AND n1.aluno_id = n2.aluno_id 
                                                AND n1.avaliacao_id = n2.avaliacao_id 
                                                AND n1.disciplina_id = n2.disciplina_id 
                                                AND n1.turma_id = n2.turma_id 
                                                AND n1.bimestre = n2.bimestre
                                                AND n1.aluno_id = ?
                                                AND n1.avaliacao_id = ?
                                                AND n1.disciplina_id = ?
                                                AND n1.turma_id = ?
                                                AND n1.bimestre = ?";
                        $stmtRemover = $conn->prepare($sqlRemoverDuplicatas);
                        $stmtRemover->execute([$alunoId, $avaliacaoId, $disciplinaId, $turmaId, $bimestre]);
                        error_log("Duplicatas removidas antes do commit");
                    }
                }
                
                $conn->commit();
                
                // SEMPRE verificar e remover duplicatas APÓS o commit
                // Isso garante que mesmo se houver trigger ou problema, as duplicatas serão removidas
                foreach ($notasFormatadas as $nota) {
                    $alunoId = $nota['aluno_id'];
                    $avaliacaoId = $nota['avaliacao_id'];
                    $disciplinaId = $nota['disciplina_id'];
                    $turmaId = $nota['turma_id'];
                    $bimestre = $nota['bimestre'];
                    
                    // Verificar quantas notas existem
                    $sqlContarDepois = "SELECT COUNT(*) as total FROM nota 
                                       WHERE aluno_id = ? 
                                       AND avaliacao_id = ? 
                                       AND disciplina_id = ? 
                                       AND turma_id = ? 
                                       AND bimestre = ?";
                    $stmtContarDepois = $conn->prepare($sqlContarDepois);
                    $stmtContarDepois->execute([$alunoId, $avaliacaoId, $disciplinaId, $turmaId, $bimestre]);
                    $contagemDepois = $stmtContarDepois->fetch(PDO::FETCH_ASSOC);
                    $totalDepois = $contagemDepois['total'] ?? 0;
                    
                    if ($totalDepois > 1) {
                        error_log("DUPLICATA DETECTADA APÓS COMMIT: {$totalDepois} notas para aluno_id: {$alunoId}, avaliacao_id: {$avaliacaoId}");
                        
                        // Remover duplicatas mantendo apenas a mais recente (maior ID)
                        $conn->beginTransaction();
                        $sqlRemoverDuplicatas = "DELETE n1 FROM nota n1
                                                INNER JOIN nota n2 
                                                WHERE n1.id < n2.id 
                                                AND n1.aluno_id = n2.aluno_id 
                                                AND n1.avaliacao_id = n2.avaliacao_id 
                                                AND n1.disciplina_id = n2.disciplina_id 
                                                AND n1.turma_id = n2.turma_id 
                                                AND n1.bimestre = n2.bimestre
                                                AND n1.aluno_id = ?
                                                AND n1.avaliacao_id = ?
                                                AND n1.disciplina_id = ?
                                                AND n1.turma_id = ?
                                                AND n1.bimestre = ?";
                        $stmtRemover = $conn->prepare($sqlRemoverDuplicatas);
                        $stmtRemover->execute([$alunoId, $avaliacaoId, $disciplinaId, $turmaId, $bimestre]);
                        $removidas = $stmtRemover->rowCount();
                        $conn->commit();
                        error_log("Removidas {$removidas} duplicatas após commit para aluno_id: {$alunoId}, avaliacao_id: {$avaliacaoId}");
                    }
                }
                
                // Reabilitar autocommit
                $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
                
                echo json_encode(['success' => true, 'message' => 'Notas registradas com sucesso']);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Erro ao registrar notas: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_alunos_turma' && !empty($_GET['turma_id'])) {
        $turmaId = $_GET['turma_id'];
        
        // Validar se o professor tem acesso a esta turma
        if ($professorId) {
            $sqlValidar = "SELECT COUNT(*) as total
                          FROM turma_professor tp
                          WHERE tp.turma_id = :turma_id 
                          AND tp.professor_id = :professor_id 
                          AND tp.fim IS NULL";
            $stmtValidar = $conn->prepare($sqlValidar);
            $stmtValidar->bindParam(':turma_id', $turmaId);
            $stmtValidar->bindParam(':professor_id', $professorId);
            $stmtValidar->execute();
            $validacao = $stmtValidar->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacao || $validacao['total'] == 0) {
                error_log("Professor $professorId tentou acessar turma $turmaId sem permissão");
                echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma', 'alunos' => []]);
                exit;
            }
        }
        
        $sql = "SELECT a.id, p.nome, COALESCE(a.matricula, '') as matricula
                FROM aluno_turma at
                INNER JOIN aluno a ON at.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE at.turma_id = :turma_id AND at.fim IS NULL AND a.ativo = 1
                ORDER BY p.nome ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'alunos' => $alunos]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_info_turma' && !empty($_GET['turma_id']) && !empty($_GET['disciplina_id'])) {
        $turmaId = $_GET['turma_id'];
        $disciplinaId = $_GET['disciplina_id'];
        
        // Verificar se a turma pertence à escola selecionada
        $escolaIdSelecionada = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
        $sql = "SELECT CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome, d.nome as disciplina_nome, t.escola_id
                FROM turma t
                INNER JOIN disciplina d ON d.id = :disciplina_id
                WHERE t.id = :turma_id";
        if ($escolaIdSelecionada) {
            $sql .= " AND t.escola_id = :escola_id";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        if ($escolaIdSelecionada) {
            $stmt->bindParam(':escola_id', $escolaIdSelecionada, PDO::PARAM_INT);
        }
        $stmt->execute();
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$info) {
            echo json_encode(['success' => false, 'message' => 'Turma não encontrada ou não pertence à escola selecionada']);
            exit;
        }
        
        echo json_encode(['success' => true, 'turma_nome' => $info['turma_nome'] ?? '', 'disciplina_nome' => $info['disciplina_nome'] ?? '']);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_historico_notas' && !empty($_GET['turma_id']) && !empty($_GET['disciplina_id'])) {
        try {
            $turmaId = $_GET['turma_id'];
            $disciplinaId = $_GET['disciplina_id'];
            $bimestre = $_GET['bimestre'] ?? null;
            
            // Validar se o professor tem acesso a esta turma
            if ($professorId) {
                $sqlValidar = "SELECT COUNT(*) as total
                              FROM turma_professor tp
                              WHERE tp.turma_id = :turma_id 
                              AND tp.professor_id = :professor_id 
                              AND tp.disciplina_id = :disciplina_id
                              AND tp.fim IS NULL";
                $stmtValidar = $conn->prepare($sqlValidar);
                $stmtValidar->bindParam(':turma_id', $turmaId);
                $stmtValidar->bindParam(':professor_id', $professorId);
                $stmtValidar->bindParam(':disciplina_id', $disciplinaId);
                $stmtValidar->execute();
                $validacao = $stmtValidar->fetch(PDO::FETCH_ASSOC);
                
                if (!$validacao || $validacao['total'] == 0) {
                    error_log("Professor $professorId tentou acessar histórico de turma $turmaId sem permissão");
                    echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma', 'notas' => []]);
                    exit;
                }
            }
            
            $notas = $notaModel->buscarPorTurmaDisciplina($turmaId, $disciplinaId, $bimestre);
            echo json_encode(['success' => true, 'notas' => $notas]);
        } catch (Exception $e) {
            error_log("Erro ao buscar histórico de notas: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar histórico: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_nota' && !empty($_GET['nota_id'])) {
        $notaId = $_GET['nota_id'];
        $nota = $notaModel->buscarPorId($notaId);
        if ($nota) {
            echo json_encode(['success' => true, 'nota' => $nota]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nota não encontrada']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_notas_existentes_bimestre' && !empty($_GET['turma_id']) && !empty($_GET['disciplina_id']) && !empty($_GET['bimestre'])) {
        $turmaId = $_GET['turma_id'];
        $disciplinaId = $_GET['disciplina_id'];
        $bimestre = $_GET['bimestre'];
        $alunoIds = isset($_GET['aluno_ids']) ? json_decode($_GET['aluno_ids'], true) : [];
        
        // Validar se o professor tem acesso a esta turma
        if ($professorId) {
            $sqlValidar = "SELECT COUNT(*) as total
                          FROM turma_professor tp
                          WHERE tp.turma_id = :turma_id 
                          AND tp.professor_id = :professor_id 
                          AND tp.disciplina_id = :disciplina_id
                          AND tp.fim IS NULL";
            $stmtValidar = $conn->prepare($sqlValidar);
            $stmtValidar->bindParam(':turma_id', $turmaId);
            $stmtValidar->bindParam(':professor_id', $professorId);
            $stmtValidar->bindParam(':disciplina_id', $disciplinaId);
            $stmtValidar->execute();
            $validacao = $stmtValidar->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacao || $validacao['total'] == 0) {
                error_log("Professor $professorId tentou acessar notas de turma $turmaId sem permissão");
                echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma', 'notas' => []]);
                exit;
            }
        }
        
        try {
            $notasExistentes = $notaModel->buscarNotasPorBimestre($turmaId, $disciplinaId, $bimestre, $alunoIds);
            
            // Organizar notas por aluno e tipo
            $notasOrganizadas = [];
            foreach ($notasExistentes as $nota) {
                $alunoId = $nota['aluno_id'];
                if (!isset($notasOrganizadas[$alunoId])) {
                    $notasOrganizadas[$alunoId] = [
                        'aluno_id' => $alunoId,
                        'aluno_nome' => $nota['aluno_nome'],
                        'aluno_matricula' => $nota['aluno_matricula'],
                        'parcial' => null,
                        'bimestral' => null,
                        'participativa' => null
                    ];
                }
                
                $tipoAvaliacao = $nota['avaliacao_tipo'] ?? '';
                if ($tipoAvaliacao === 'ATIVIDADE') {
                    $notasOrganizadas[$alunoId]['parcial'] = $nota;
                } else if ($tipoAvaliacao === 'PROVA') {
                    $notasOrganizadas[$alunoId]['bimestral'] = $nota;
                } else if ($tipoAvaliacao === 'TRABALHO') {
                    $notasOrganizadas[$alunoId]['participativa'] = $nota;
                }
            }
            
            // Converter para array numérico para JSON, mas manter a estrutura organizada
            echo json_encode(['success' => true, 'notas' => array_values($notasOrganizadas)]);
        } catch (Exception $e) {
            error_log("Erro ao buscar notas existentes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar notas: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_notas_aluno_bimestre' && !empty($_GET['aluno_id']) && !empty($_GET['turma_id']) && !empty($_GET['disciplina_id']) && !empty($_GET['bimestre'])) {
        $alunoId = $_GET['aluno_id'];
        $turmaId = $_GET['turma_id'];
        $disciplinaId = $_GET['disciplina_id'];
        $bimestre = $_GET['bimestre'];
        
        // Validar se o professor tem acesso a esta turma
        if ($professorId) {
            $sqlValidar = "SELECT COUNT(*) as total
                          FROM turma_professor tp
                          WHERE tp.turma_id = :turma_id 
                          AND tp.professor_id = :professor_id 
                          AND tp.disciplina_id = :disciplina_id
                          AND tp.fim IS NULL";
            $stmtValidar = $conn->prepare($sqlValidar);
            $stmtValidar->bindParam(':turma_id', $turmaId);
            $stmtValidar->bindParam(':professor_id', $professorId);
            $stmtValidar->bindParam(':disciplina_id', $disciplinaId);
            $stmtValidar->execute();
            $validacao = $stmtValidar->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacao || $validacao['total'] == 0) {
                error_log("Professor $professorId tentou acessar notas de aluno $alunoId na turma $turmaId sem permissão");
                echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma']);
                exit;
            }
        }
        
        try {
            // Buscar todas as notas do aluno no bimestre
            $notas = $notaModel->buscarPorAluno($alunoId, $turmaId, $disciplinaId, $bimestre);
            
            // Separar parcial, bimestral e participativa
            $parcial = null;
            $bimestral = null;
            $participativa = null;
            
            foreach ($notas as $nota) {
                // Buscar tipo da avaliação
                $sqlAvaliacao = "SELECT tipo FROM avaliacao WHERE id = :avaliacao_id LIMIT 1";
                $stmtAvaliacao = $conn->prepare($sqlAvaliacao);
                $avaliacaoId = $nota['avaliacao_id'] ?? null;
                if ($avaliacaoId) {
                    $stmtAvaliacao->bindParam(':avaliacao_id', $avaliacaoId);
                    $stmtAvaliacao->execute();
                    $avaliacao = $stmtAvaliacao->fetch(PDO::FETCH_ASSOC);
                    $tipoAvaliacao = $avaliacao['tipo'] ?? null;
                    
                    if ($tipoAvaliacao === 'ATIVIDADE') {
                        $parcial = $nota;
                    } else if ($tipoAvaliacao === 'PROVA') {
                        $bimestral = $nota;
                    } else if ($tipoAvaliacao === 'TRABALHO') {
                        $participativa = $nota;
                    }
                }
            }
            
            // Buscar nome do aluno
            $sqlAluno = "SELECT p.nome, COALESCE(a.matricula, '') as matricula 
                        FROM aluno a 
                        INNER JOIN pessoa p ON a.pessoa_id = p.id 
                        WHERE a.id = :aluno_id";
            $stmtAluno = $conn->prepare($sqlAluno);
            $stmtAluno->bindParam(':aluno_id', $alunoId);
            $stmtAluno->execute();
            $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'aluno' => $aluno,
                'parcial' => $parcial,
                'bimestral' => $bimestral,
                'participativa' => $participativa
            ]);
        } catch (Exception $e) {
            error_log("Erro ao buscar notas do aluno: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar notas: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Processar edição de nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_nota' && $professorId) {
    header('Content-Type: application/json');
    
    $notaId = $_POST['nota_id'] ?? null;
    $notaValor = $_POST['nota'] ?? null;
    $bimestre = $_POST['bimestre'] ?? null;
    $comentario = $_POST['comentario'] ?? null;
    
    if (!$notaId || $notaValor === null) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }
    
    // Validar que a nota está entre 0 e 10
    $notaValor = floatval(str_replace(',', '.', $notaValor));
    if ($notaValor < 0 || $notaValor > 10) {
        echo json_encode(['success' => false, 'message' => 'Nota deve estar entre 0 e 10']);
        exit;
    }
    
    try {
        $result = $notaModel->atualizar($notaId, [
            'nota' => $notaValor,
            'bimestre' => $bimestre,
            'comentario' => $comentario
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Nota atualizada com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar nota']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançar Notas - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }
        /* Estilos para o modal fullscreen de notas */
        .nota-input {
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .nota-input:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
            transform: scale(1.02);
        }
        .nota-input:hover:not(:disabled) {
            border-color: #fb923c;
        }
        .media-badge {
            min-width: 56px;
            text-align: center;
            font-weight: 600;
            padding: 6px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .aluno-row {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .aluno-row:hover {
            background-color: #f9fafb;
            border-left-color: #ea580c;
            transform: translateX(2px);
        }
        .turma-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .turma-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-left-color: #ea580c;
        }
        .header-section {
            background: linear-gradient(135deg, #fff 0%, #fef3f2 100%);
        }
        
        /* Animação para modal de sucesso */
        @keyframes bounce-in {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-sucesso-icon {
            animation: bounce-in 0.6s ease-out;
        }
        
        .modal-sucesso-content {
            transition: transform 0.2s ease-out;
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_professor.php'; ?>
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left flex items-center gap-3">
                        <div class="hidden lg:block p-2 bg-orange-100 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">Lançar Notas</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php echo !empty($_SESSION['escola_atual']) ? htmlspecialchars($_SESSION['escola_atual']) : 'N/A'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-6 sm:p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Lançar Notas</h1>
                            <p class="text-sm text-gray-600 mt-1">Registre as notas dos alunos nas suas disciplinas</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Minhas Turmas</h2>
                            <p class="text-sm text-gray-500 mt-1"><?= count($turmasProfessor) ?> turma(s) atribuída(s)</p>
                        </div>
                    </div>
                    
                    <?php if (empty($turmasProfessor)): ?>
                        <div class="text-center py-16">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 font-medium">Você não possui turmas atribuídas no momento.</p>
                            <p class="text-sm text-gray-500 mt-2">Entre em contato com o gestor da escola para receber atribuições.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <div class="turma-card bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                                    <div class="mb-4">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-900 text-base mb-1"><?= htmlspecialchars($turma['turma_nome']) ?></h3>
                                                <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                    </svg>
                                                    <span><?= htmlspecialchars($turma['disciplina_nome']) ?></span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                    <span class="truncate"><?= htmlspecialchars($turma['escola_nome']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 pt-3 border-t border-gray-100">
                                        <button onclick="verTurma(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['disciplina_nome'], ENT_QUOTES) ?>')" class="flex-1 flex items-center justify-center gap-2 text-blue-600 hover:text-blue-700 font-medium text-sm py-2.5 border border-blue-200 rounded-lg hover:bg-blue-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Ver
                                        </button>
                                        <button onclick="abrirModalLancarNotas(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['disciplina_nome'], ENT_QUOTES) ?>')" class="flex-1 flex items-center justify-center gap-2 text-white bg-orange-600 hover:bg-orange-700 font-medium text-sm py-2.5 rounded-lg transition-all shadow-sm hover:shadow">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Lançar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Lançar Notas - Fullscreen Moderno -->
    <div id="modal-lancar-notas" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col">
            <!-- Header Moderno -->
            <div class="header-section border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <div class="flex items-center gap-4">
                    <button onclick="fecharModalLancarNotas()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Lançar Notas
                        </h3>
                        <p id="notas-info-turma" class="text-sm text-gray-500 mt-0.5">Selecione uma turma</p>
                    </div>
                </div>
                <button onclick="salvarNotas()" class="px-5 py-2.5 text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-all shadow-sm hover:shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Salvar
                </button>
            </div>
            
            <!-- Barra de Controles Melhorada -->
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-100 px-6 py-4">
                <div class="flex items-center justify-between gap-6 flex-wrap">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Bimestre:</label>
                            <select id="notas-bimestre" class="text-sm px-4 py-2 border-2 border-orange-200 rounded-lg bg-white focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 font-medium" onchange="atualizarNotasAoMudarBimestre()">
                                <option value="1">1º Bimestre</option>
                                <option value="2">2º Bimestre</option>
                                <option value="3">3º Bimestre</option>
                                <option value="4">4º Bimestre</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-orange-200">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-gray-600"><span id="total-alunos" class="font-bold text-gray-900">0</span> alunos</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-orange-200">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-gray-600"><span id="notas-preenchidas" class="font-bold text-orange-600">0</span> notas</span>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="notas-turma-id">
                <input type="hidden" id="notas-disciplina-id">
            </div>
            
            <!-- Content com Scroll -->
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-5xl mx-auto py-6 px-6">
                    <!-- Header da Tabela Melhorado -->
                    <div class="grid grid-cols-12 gap-4 text-xs font-bold text-gray-700 uppercase tracking-wider px-4 py-3 bg-gray-50 rounded-lg mb-3 border border-gray-200">
                        <div class="col-span-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Aluno
                        </div>
                        <div class="col-span-2 text-center">Parcial</div>
                        <div class="col-span-2 text-center">Bimestral</div>
                        <div class="col-span-2 text-center">Participativa</div>
                        <div class="col-span-1 text-center">Média</div>
                        <div class="col-span-2">Observação</div>
                    </div>
                    
                    <div id="notas-alunos-container" class="space-y-2">
                        <!-- Alunos serão carregados aqui -->
                        <div class="text-center py-20 text-gray-400">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-medium">Selecione uma turma para carregar os alunos</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Melhorado -->
            <div class="bg-white border-t border-gray-200 px-6 py-4 rounded-b-2xl">
                <div class="max-w-5xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Preencha as notas de 0 a 10 (use vírgula para decimais)</span>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="fecharModalLancarNotas()" class="px-5 py-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all border border-gray-200">
                            Cancelar
                        </button>
                        <button onclick="salvarNotas()" class="px-5 py-2.5 text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-all shadow-sm hover:shadow-md flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Notas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Ver Turma - Fullscreen -->
    <div id="modal-ver-turma" class="fixed inset-0 bg-gray-50 z-[60] hidden flex flex-col">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="fecharModalVerTurma()" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Visualizar Turma</h3>
                    <p id="ver-turma-info" class="text-xs text-gray-500"></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs font-medium text-gray-600">Bimestre:</label>
                <select id="ver-turma-bimestre" class="text-sm px-3 py-1.5 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-blue-500" onchange="carregarHistoricoNotas()">
                    <option value="">Todos</option>
                    <option value="1">1º Bimestre</option>
                    <option value="2">2º Bimestre</option>
                    <option value="3">3º Bimestre</option>
                    <option value="4">4º Bimestre</option>
                </select>
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto py-4 px-4">
                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <div class="flex gap-4">
                        <button onclick="mostrarTabTurma('alunos')" id="tab-alunos" class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                            Alunos
                        </button>
                        <button onclick="mostrarTabTurma('historico')" id="tab-historico" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                            Histórico de Notas
                        </button>
                    </div>
                </div>
                
                <!-- Tab Alunos -->
                <div id="conteudo-alunos" class="tab-conteudo">
                    <div id="ver-turma-alunos-container" class="space-y-2">
                        <div class="text-center py-16 text-gray-400">
                            <p class="text-sm">Carregando alunos...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Histórico -->
                <div id="conteudo-historico" class="tab-conteudo hidden">
                    <div id="ver-turma-historico-container" class="space-y-2">
                        <div class="text-center py-16 text-gray-400">
                            <p class="text-sm">Selecione um bimestre para ver o histórico</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <input type="hidden" id="ver-turma-id">
        <input type="hidden" id="ver-turma-disciplina-id">
    </div>
    
    <!-- Modal Editar Notas -->
    <div id="modal-editar-nota" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Editar Notas</h3>
                <button onclick="fecharModalEditarNota()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                    <p id="editar-nota-aluno" class="text-sm text-gray-600"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bimestre</label>
                    <select id="editar-nota-bimestre" class="w-full px-3 py-2 border border-gray-200 rounded-lg" onchange="carregarNotasParaEdicao()">
                        <option value="1">1º Bimestre</option>
                        <option value="2">2º Bimestre</option>
                        <option value="3">3º Bimestre</option>
                        <option value="4">4º Bimestre</option>
                    </select>
                </div>
                
                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Nota Parcial</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nota (0 a 10)</label>
                            <input type="text" id="editar-nota-parcial-valor" class="w-full px-3 py-2 border border-gray-200 rounded-lg" placeholder="0,0" oninput="aplicarMascaraNota(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                            <textarea id="editar-nota-parcial-comentario" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Nota Bimestral</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nota (0 a 10)</label>
                            <input type="text" id="editar-nota-bimestral-valor" class="w-full px-3 py-2 border border-gray-200 rounded-lg" placeholder="0,0" oninput="aplicarMascaraNota(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                            <textarea id="editar-nota-bimestral-comentario" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Nota Participativa</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nota (0 a 10)</label>
                            <input type="text" id="editar-nota-participativa-valor" class="w-full px-3 py-2 border border-gray-200 rounded-lg" placeholder="0,0" oninput="aplicarMascaraNota(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                            <textarea id="editar-nota-participativa-comentario" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button onclick="fecharModalEditarNota()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEdicaoNota()" class="flex-1 px-4 py-2 text-white bg-orange-600 hover:bg-orange-700 rounded-lg font-medium transition-colors">
                    Salvar
                </button>
            </div>
            
            <input type="hidden" id="editar-nota-aluno-id">
            <input type="hidden" id="editar-nota-parcial-id">
            <input type="hidden" id="editar-nota-bimestral-id">
            <input type="hidden" id="editar-nota-participativa-id">
        </div>
    </div>
    
    <!-- Modal de Sucesso -->
    <div id="modal-sucesso" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-2xl modal-sucesso-content scale-95">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 modal-sucesso-icon">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sucesso!</h3>
                <p id="modal-sucesso-mensagem" class="text-sm text-gray-600 mb-6"></p>
                <button onclick="fecharModalSucesso()" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
    
    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmar Saída</h3>
                    <p class="text-sm text-gray-600">Tem certeza que deseja sair do sistema?</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="window.logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Sim, Sair
                </button>
            </div>
        </div>
    </div>
    
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
        
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        };
        
        window.logout = function() {
            window.location.href = '../auth/logout.php';
        };
        
        function abrirModalLancarNotas(turmaId = null, disciplinaId = null, turmaNome = '', disciplinaNome = '') {
            const modal = document.getElementById('modal-lancar-notas');
            if (modal) {
                modal.classList.remove('hidden');
                if (turmaId && disciplinaId) {
                    document.getElementById('notas-turma-id').value = turmaId;
                    document.getElementById('notas-disciplina-id').value = disciplinaId;
                    
                    // Exibir informações da turma
                    const infoElement = document.getElementById('notas-info-turma');
                    if (infoElement && turmaNome && disciplinaNome) {
                        infoElement.textContent = turmaNome + ' - ' + disciplinaNome;
                    } else if (infoElement) {
                        buscarInfoTurmaNotas(turmaId, disciplinaId);
                    }
                    
                    carregarAlunosParaNotas(turmaId);
                }
            }
        }
        
        function buscarInfoTurmaNotas(turmaId, disciplinaId) {
            fetch('?acao=buscar_info_turma&turma_id=' + turmaId + '&disciplina_id=' + disciplinaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const infoElement = document.getElementById('notas-info-turma');
                        if (infoElement && data.turma_nome && data.disciplina_nome) {
                            infoElement.textContent = data.turma_nome + ' - ' + data.disciplina_nome;
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar info da turma:', error);
                });
        }
        
        function fecharModalLancarNotas() {
            const modal = document.getElementById('modal-lancar-notas');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function atualizarContadores() {
            const inputs = document.querySelectorAll('#notas-alunos-container .nota-input');
            let preenchidas = 0;
            inputs.forEach(input => {
                if (input.value && parseFloat(input.value) >= 0) {
                    preenchidas++;
                }
            });
            document.getElementById('notas-preenchidas').textContent = preenchidas;
        }
        
        function carregarAlunosParaNotas(turmaId) {
            console.log('=== INICIANDO carregarAlunosParaNotas ===');
            console.log('Turma ID:', turmaId);
            
            if (!turmaId) {
                console.error('Turma ID não fornecido!');
                return;
            }
            
            var disciplinaIdElement = document.getElementById('notas-disciplina-id');
            var bimestreElement = document.getElementById('notas-bimestre');
            
            if (!disciplinaIdElement || !bimestreElement) {
                console.error('Elementos disciplina ou bimestre não encontrados!');
                return;
            }
            
            var disciplinaId = disciplinaIdElement.value;
            var bimestre = bimestreElement.value;
            
            console.log('Disciplina ID:', disciplinaId);
            console.log('Bimestre:', bimestre);
            
            var container = document.getElementById('notas-alunos-container');
            if (!container) {
                console.error('Container notas-alunos-container não encontrado!');
                return;
            }
            
            container.innerHTML = '<div class="text-center py-8"><p>Carregando alunos...</p></div>';
            
            var urlAlunos = '?acao=buscar_alunos_turma&turma_id=' + encodeURIComponent(turmaId);
            var urlNotas = '?acao=buscar_notas_existentes_bimestre&turma_id=' + encodeURIComponent(turmaId) + '&disciplina_id=' + encodeURIComponent(disciplinaId) + '&bimestre=' + encodeURIComponent(bimestre);
            
            console.log('URL Alunos:', urlAlunos);
            console.log('URL Notas:', urlNotas);
            
            var xhrAlunos = new XMLHttpRequest();
            var xhrNotas = new XMLHttpRequest();
            var alunosData = null;
            var notasData = null;
            var alunosLoaded = false;
            var notasLoaded = false;
            
            function processarDados() {
                if (!alunosLoaded || !notasLoaded) {
                    return;
                }
                
                console.log('Processando dados...');
                console.log('Alunos:', alunosData);
                console.log('Notas:', notasData);
                
                if (!alunosData || !alunosData.success) {
                    var msg = alunosData && alunosData.message ? alunosData.message : 'Erro ao carregar alunos';
                    container.innerHTML = '<div class="col-span-full text-center py-12">' +
                        '<div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">' +
                        '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                        '</svg></div>' +
                        '<p class="text-sm font-medium text-red-600">Erro ao carregar alunos</p>' +
                        '<p class="text-xs text-gray-500 mt-1">' + msg + '</p>' +
                        '</div>';
                    return;
                }
                
                if (!alunosData.alunos || alunosData.alunos.length === 0) {
                    container.innerHTML = '<div class="col-span-full text-center py-12">' +
                        '<div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">' +
                        '<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>' +
                        '</svg></div>' +
                        '<p class="text-sm font-medium text-gray-600">Nenhum aluno encontrado</p>' +
                        '<p class="text-xs text-gray-500 mt-1">Esta turma não possui alunos cadastrados</p>' +
                        '</div>';
                    
                    var totalAlunosEl = document.getElementById('total-alunos');
                    if (totalAlunosEl) {
                        totalAlunosEl.textContent = '0';
                    }
                    var notasPreenchidasEl = document.getElementById('notas-preenchidas');
                    if (notasPreenchidasEl) {
                        notasPreenchidasEl.textContent = '0';
                    }
                    return;
                }
                
                console.log('Total de alunos:', alunosData.alunos.length);
                container.innerHTML = '';
                
                var totalAlunos = alunosData.alunos.length;
                var totalAlunosEl = document.getElementById('total-alunos');
                if (totalAlunosEl) {
                    totalAlunosEl.textContent = totalAlunos;
                }
                var notasPreenchidasEl = document.getElementById('notas-preenchidas');
                if (notasPreenchidasEl) {
                    notasPreenchidasEl.textContent = '0';
                }
                
                var notasExistentes = {};
                if (notasData && notasData.success && notasData.notas) {
                    if (Array.isArray(notasData.notas)) {
                        for (var i = 0; i < notasData.notas.length; i++) {
                            var nota = notasData.notas[i];
                            var alunoId = nota.aluno_id;
                            notasExistentes[alunoId] = {
                                aluno_id: alunoId,
                                aluno_nome: nota.aluno_nome,
                                aluno_matricula: nota.aluno_matricula,
                                parcial: nota.parcial || null,
                                bimestral: nota.bimestral || null,
                                participativa: nota.participativa || null
                            };
                        }
                    } else {
                        var keys = Object.keys(notasData.notas);
                        for (var j = 0; j < keys.length; j++) {
                            var alunoIdKey = keys[j];
                            notasExistentes[alunoIdKey] = notasData.notas[alunoIdKey];
                        }
                    }
                }
                
                var temNotasExistentes = Object.keys(notasExistentes).length > 0;
                if (temNotasExistentes) {
                    var avisoDiv = document.createElement('div');
                    avisoDiv.className = 'mb-4 p-4 bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-xl shadow-sm';
                    avisoDiv.innerHTML = '<div class="flex items-start gap-3">' +
                        '<div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">' +
                        '<svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>' +
                        '</svg></div>' +
                        '<div class="flex-1">' +
                        '<p class="text-sm font-bold text-amber-900">Atenção: Já existem notas lançadas para este bimestre!</p>' +
                        '<p class="text-xs text-amber-700 mt-1.5">As notas existentes serão exibidas abaixo. Para editar, use o botão "Editar" na visualização da turma.</p>' +
                        '</div></div>';
                    container.appendChild(avisoDiv);
                }
                
                for (var k = 0; k < alunosData.alunos.length; k++) {
                    var aluno = alunosData.alunos[k];
                    var notaExistente = notasExistentes[aluno.id];
                    var temNota = notaExistente !== undefined;
                    
                    var notaParcial = '';
                    var notaBimestral = '';
                    var notaParticipativa = '';
                    var comentario = '';
                    
                    if (notaExistente) {
                        if (notaExistente.parcial) {
                            notaParcial = parseFloat(notaExistente.parcial.nota).toFixed(1).replace('.', ',');
                        }
                        if (notaExistente.bimestral) {
                            notaBimestral = parseFloat(notaExistente.bimestral.nota).toFixed(1).replace('.', ',');
                            if (notaExistente.bimestral.comentario) {
                                comentario = notaExistente.bimestral.comentario;
                            }
                        }
                        if (notaExistente.participativa) {
                            notaParticipativa = parseFloat(notaExistente.participativa.nota).toFixed(1).replace('.', ',');
                            if (!comentario && notaExistente.participativa.comentario) {
                                comentario = notaExistente.participativa.comentario;
                            }
                        }
                        if (!comentario && notaExistente.parcial && notaExistente.parcial.comentario) {
                            comentario = notaExistente.parcial.comentario;
                        }
                    }
                    
                    var alunoNomeEscapado = (aluno.nome || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    var alunoMatriculaEscapada = (aluno.matricula || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    var comentarioEscapado = (comentario || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    
                    var mediaTexto = '-';
                    if (temNota && (notaParcial || notaBimestral || notaParticipativa)) {
                        var p = parseFloat((notaParcial || '0').replace(',', '.')) || 0;
                        var b = parseFloat((notaBimestral || '0').replace(',', '.')) || 0;
                        var part = parseFloat((notaParticipativa || '0').replace(',', '.')) || 0;
                        var notas = [];
                        if (p > 0) notas.push(p);
                        if (b > 0) notas.push(b);
                        if (part > 0) notas.push(part);
                        if (notas.length > 0) {
                            var soma = 0;
                            for (var n = 0; n < notas.length; n++) {
                                soma += notas[n];
                            }
                            var media = soma / notas.length;
                            mediaTexto = media.toFixed(1);
                        }
                    }
                    
                    var readonlyAttr = temNota ? 'readonly title="Nota já lançada. Use a opção Editar para modificar."' : '';
                    var placeholderAttr = temNota ? '' : 'placeholder="0,0"';
                    var oninputAttr = temNota ? '' : 'oninput="aplicarMascaraNota(this); calcularMediaAluno(this); atualizarContadores();"';
                    var onblurAttr = temNota ? '' : 'onblur="aplicarMascaraNota(this); calcularMediaAluno(this);"';
                    var oninputComentarioAttr = temNota ? '' : 'oninput="atualizarContadores();"';
                    var readonlyComentarioAttr = temNota ? 'readonly title="Observação já lançada. Use a opção Editar para modificar."' : 'placeholder="Opcional"';
                    
                    var borderClass = temNota ? 'border-amber-300 bg-amber-50' : 'border-gray-200';
                    var rowBgClass = temNota ? 'bg-amber-50 border-amber-200' : 'bg-white border-gray-100';
                    
                    var matriculaHtml = alunoMatriculaEscapada ? '<div class="text-xs text-gray-400">' + alunoMatriculaEscapada + '</div>' : '';
                    var temNotaHtml = temNota ? '<div class="text-xs text-amber-600 mt-0.5">Notas já lançadas</div>' : '';
                    
                    var div = document.createElement('div');
                    div.className = 'aluno-row grid grid-cols-12 gap-4 items-center px-4 py-3.5 rounded-xl border-2 ' + rowBgClass;
                    div.innerHTML = '<div class="col-span-3 flex items-center gap-3">' +
                        '<div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">' +
                        '<span class="text-xs font-semibold text-gray-600">' + (k + 1) + '</span>' +
                        '</div>' +
                        '<div class="flex-1 min-w-0">' +
                        '<div class="text-sm font-semibold text-gray-900 truncate">' + alunoNomeEscapado + '</div>' +
                        matriculaHtml +
                        temNotaHtml +
                        '</div></div>' +
                        '<div class="col-span-2">' +
                        '<input type="text" class="nota-input nota-parcial w-full px-3 py-2 text-sm font-semibold text-center border-2 rounded-lg ' + borderClass + '" data-aluno-id="' + aluno.id + '" value="' + notaParcial + '" ' + readonlyAttr + ' ' + placeholderAttr + ' ' + oninputAttr + ' ' + onblurAttr + '>' +
                        '</div>' +
                        '<div class="col-span-2">' +
                        '<input type="text" class="nota-input nota-bimestral w-full px-3 py-2 text-sm font-semibold text-center border-2 rounded-lg ' + borderClass + '" data-aluno-id="' + aluno.id + '" value="' + notaBimestral + '" ' + readonlyAttr + ' ' + placeholderAttr + ' ' + oninputAttr + ' ' + onblurAttr + '>' +
                        '</div>' +
                        '<div class="col-span-2">' +
                        '<input type="text" class="nota-input nota-participativa w-full px-3 py-2 text-sm font-semibold text-center border-2 rounded-lg ' + borderClass + '" data-aluno-id="' + aluno.id + '" value="' + notaParticipativa + '" ' + readonlyAttr + ' ' + placeholderAttr + ' ' + oninputAttr + ' ' + onblurAttr + '>' +
                        '</div>' +
                        '<div class="col-span-1 flex justify-center">' +
                        '<div class="media-badge media-aluno text-sm font-bold py-2 rounded-lg" data-aluno-id="' + aluno.id + '">' + mediaTexto + '</div>' +
                        '</div>' +
                        '<div class="col-span-2">' +
                        '<input type="text" class="comentario-input w-full px-3 py-2 text-sm border-2 rounded-lg ' + borderClass + '" data-aluno-id="' + aluno.id + '" value="' + comentarioEscapado + '" ' + readonlyComentarioAttr + ' ' + oninputComentarioAttr + '>' +
                        '</div>';
                    
                    container.appendChild(div);
                    
                    if (temNota && (notaParcial || notaBimestral || notaParticipativa)) {
                        setTimeout(function() {
                            var mediaDiv = div.querySelector('.media-aluno');
                            if (mediaDiv && mediaTexto !== '-') {
                                var mediaNum = parseFloat(mediaTexto);
                                mediaDiv.textContent = mediaNum.toFixed(1);
                                mediaDiv.className = 'media-badge media-aluno text-sm font-medium py-1 rounded';
                                if (mediaNum >= 7) {
                                    mediaDiv.classList.add('text-green-600', 'bg-green-50');
                                } else if (mediaNum >= 5) {
                                    mediaDiv.classList.add('text-amber-600', 'bg-amber-50');
                                } else {
                                    mediaDiv.classList.add('text-red-600', 'bg-red-50');
                                }
                            }
                        }, 10);
                    }
                }
                
                if (typeof atualizarContadores === 'function') {
                    atualizarContadores();
                }
            }
            
            xhrAlunos.onreadystatechange = function() {
                if (xhrAlunos.readyState === 4) {
                    alunosLoaded = true;
                    if (xhrAlunos.status === 200) {
                        try {
                            alunosData = JSON.parse(xhrAlunos.responseText);
                            console.log('Dados alunos recebidos:', alunosData);
                        } catch (e) {
                            console.error('Erro ao parsear JSON de alunos:', e);
                            alunosData = { success: false, message: 'Erro ao processar dados dos alunos.', alunos: [] };
                        }
                    } else {
                        console.error('Erro HTTP ao buscar alunos:', xhrAlunos.status);
                        alunosData = { success: false, message: 'Erro na requisição HTTP para alunos: ' + xhrAlunos.status, alunos: [] };
                    }
                    processarDados();
                }
            };
            
            xhrNotas.onreadystatechange = function() {
                if (xhrNotas.readyState === 4) {
                    notasLoaded = true;
                    if (xhrNotas.status === 200) {
                        try {
                            notasData = JSON.parse(xhrNotas.responseText);
                            console.log('Dados notas recebidos:', notasData);
                        } catch (e) {
                            console.error('Erro ao parsear JSON de notas:', e);
                            notasData = { success: false, notas: [] };
                        }
                    } else {
                        console.error('Erro HTTP ao buscar notas:', xhrNotas.status);
                        notasData = { success: false, notas: [] };
                    }
                    processarDados();
                }
            };
            
            xhrAlunos.open('GET', urlAlunos, true);
            xhrAlunos.send();
            
            xhrNotas.open('GET', urlNotas, true);
            xhrNotas.send();
            
            console.log('Função carregarAlunosParaNotas definida com sucesso!');
        }
        
        function calcularMediaTexto(parcial, bimestral, participativa) {
            const p = parseFloat((parcial || '0').replace(',', '.')) || 0;
            const b = parseFloat((bimestral || '0').replace(',', '.')) || 0;
            const part = parseFloat((participativa || '0').replace(',', '.')) || 0;
            let media = 0;
            const notas = [p, b, part].filter(n => n > 0);
            if (notas.length > 0) {
                media = notas.reduce((a, b) => a + b, 0) / notas.length;
            }
            return media > 0 ? media.toFixed(1) : '-';
        }
        
        function aplicarMascaraNota(input) {
            let valor = input.value.replace(/[^0-9,]/g, ''); // Remove tudo exceto números e vírgula
            
            if (valor.length === 0) {
                input.value = '';
                return;
            }
            
            // Se já tem vírgula, mantém o formato
            if (valor.includes(',')) {
                const partes = valor.split(',');
                if (partes.length > 2) {
                    // Múltiplas vírgulas, mantém apenas a primeira
                    valor = partes[0] + ',' + partes.slice(1).join('');
                }
                // Limita a uma casa decimal após a vírgula
                if (partes.length > 1 && partes[1].length > 1) {
                    valor = partes[0] + ',' + partes[1].substring(0, 1);
                }
                // Garante que não passe de 10
                const numero = parseFloat(valor.replace(',', '.'));
                if (numero > 10) {
                    valor = '10,0';
                }
            } else {
                // Não tem vírgula ainda
                const numero = parseInt(valor);
                
                // Se o número for maior que 10, adiciona vírgula automaticamente
                if (numero > 10 && numero < 100) {
                    // Exemplo: 11 vira 1,1 | 12 vira 1,2 | 19 vira 1,9
                    const primeiroDigito = Math.floor(numero / 10);
                    const segundoDigito = numero % 10;
                    valor = primeiroDigito + ',' + segundoDigito;
                } else if (numero >= 100) {
                    // Exemplo: 100 vira 10,0 | 123 vira 10,0 (limite máximo)
                    valor = '10,0';
                } else if (numero === 10) {
                    // Permite 10 ou 10,0
                    valor = '10';
                }
            }
            
            input.value = valor;
        }
        
        function calcularMediaAluno(input) {
            const alunoId = input.dataset.alunoId;
            const row = input.closest('.aluno-row');
            const notaParcialInput = row.querySelector('.nota-parcial[data-aluno-id="' + alunoId + '"]');
            const notaBimestralInput = row.querySelector('.nota-bimestral[data-aluno-id="' + alunoId + '"]');
            const notaParticipativaInput = row.querySelector('.nota-participativa[data-aluno-id="' + alunoId + '"]');
            const mediaDiv = row.querySelector('.media-aluno[data-aluno-id="' + alunoId + '"]');
            
            // Converter vírgula para ponto para cálculo
            const notaParcial = parseFloat((notaParcialInput.value || '0').replace(',', '.')) || 0;
            const notaBimestral = parseFloat((notaBimestralInput.value || '0').replace(',', '.')) || 0;
            const notaParticipativa = parseFloat((notaParticipativaInput.value || '0').replace(',', '.')) || 0;
            
            let media = 0;
            const notas = [notaParcial, notaBimestral, notaParticipativa].filter(n => n > 0);
            if (notas.length > 0) {
                media = notas.reduce((a, b) => a + b, 0) / notas.length;
            }
            
            // Resetar classes
            mediaDiv.className = 'media-badge media-aluno text-sm font-medium py-1 rounded';
            mediaDiv.setAttribute('data-aluno-id', alunoId);
            
            if (media > 0) {
                mediaDiv.textContent = media.toFixed(1);
                if (media >= 7) {
                    mediaDiv.classList.add('text-green-600', 'bg-green-50');
                } else if (media >= 5) {
                    mediaDiv.classList.add('text-amber-600', 'bg-amber-50');
                } else {
                    mediaDiv.classList.add('text-red-600', 'bg-red-50');
                }
            } else {
                mediaDiv.textContent = '-';
                mediaDiv.classList.add('text-gray-400');
            }
        }
        
        function atualizarNotasAoMudarBimestre() {
            const turmaId = document.getElementById('notas-turma-id').value;
            if (turmaId) {
                carregarAlunosParaNotas(turmaId);
            }
        }
        
        // Variável para controlar se já está salvando
        let salvandoNotas = false;
        
        function salvarNotas() {
            // Prevenir múltiplas execuções simultâneas
            if (salvandoNotas) {
                return;
            }
            
            const turmaId = document.getElementById('notas-turma-id').value;
            const disciplinaId = document.getElementById('notas-disciplina-id').value;
            const bimestre = document.getElementById('notas-bimestre').value;
            const notas = [];
            
            const alunosProcessados = new Set();
            document.querySelectorAll('#notas-alunos-container .nota-parcial').forEach(input => {
                const alunoId = input.dataset.alunoId;
                // Ignorar campos readonly (notas já existentes)
                if (input.readOnly) {
                    return;
                }
                
                if (!alunosProcessados.has(alunoId)) {
                    const notaParcialInput = document.querySelector(`.nota-parcial[data-aluno-id="${alunoId}"]`);
                    const notaBimestralInput = document.querySelector(`.nota-bimestral[data-aluno-id="${alunoId}"]`);
                    const notaParticipativaInput = document.querySelector(`.nota-participativa[data-aluno-id="${alunoId}"]`);
                    const comentarioInput = document.querySelector(`.comentario-input[data-aluno-id="${alunoId}"]`);
                    
                    // Verificar se os inputs não são readonly
                    if (notaParcialInput && notaParcialInput.readOnly) {
                        alunosProcessados.add(alunoId);
                        return;
                    }
                    if (notaBimestralInput && notaBimestralInput.readOnly) {
                        alunosProcessados.add(alunoId);
                        return;
                    }
                    if (notaParticipativaInput && notaParticipativaInput.readOnly) {
                        alunosProcessados.add(alunoId);
                        return;
                    }
                    
                    // Converter vírgula para ponto para envio ao servidor
                    const notaParcial = notaParcialInput ? parseFloat((notaParcialInput.value || '').replace(',', '.')) : null;
                    const notaBimestral = notaBimestralInput ? parseFloat((notaBimestralInput.value || '').replace(',', '.')) : null;
                    const notaParticipativa = notaParticipativaInput ? parseFloat((notaParticipativaInput.value || '').replace(',', '.')) : null;
                    const comentario = comentarioInput && !comentarioInput.readOnly ? comentarioInput.value : '';
                    
                    if (notaParcial !== null && notaParcial > 0) {
                        notas.push({
                            aluno_id: alunoId,
                            nota: notaParcial,
                            tipo: 'PARCIAL',
                            bimestre: bimestre,
                            comentario: comentario
                        });
                    }
                    
                    if (notaBimestral !== null && notaBimestral > 0) {
                        notas.push({
                            aluno_id: alunoId,
                            nota: notaBimestral,
                            tipo: 'BIMESTRAL',
                            bimestre: bimestre,
                            comentario: comentario
                        });
                    }
                    
                    if (notaParticipativa !== null && notaParticipativa > 0) {
                        notas.push({
                            aluno_id: alunoId,
                            nota: notaParticipativa,
                            tipo: 'PARTICIPATIVA',
                            bimestre: bimestre,
                            comentario: comentario
                        });
                    }
                    
                    alunosProcessados.add(alunoId);
                }
            });
            
            if (notas.length === 0) {
                alert('Nenhuma nota nova foi preenchida. As notas existentes não podem ser modificadas aqui. Use a opção "Editar" na visualização da turma.');
                return;
            }
            
            // Marcar como salvando e desabilitar botões
            salvandoNotas = true;
            const botoesSalvar = document.querySelectorAll('button[onclick="salvarNotas()"]');
            botoesSalvar.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                const textoOriginal = btn.textContent;
                btn.textContent = 'Salvando...';
                btn.dataset.textoOriginal = textoOriginal;
            });
            
            const formData = new FormData();
            formData.append('acao', 'lancar_notas');
            formData.append('turma_id', turmaId);
            formData.append('disciplina_id', disciplinaId);
            formData.append('notas', JSON.stringify(notas));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalSucesso('Notas registradas com sucesso!');
                    setTimeout(() => {
                        fecharModalLancarNotas();
                        location.reload();
                    }, 1500);
                } else {
                    alert('Erro ao registrar notas: ' + (data.message || 'Erro desconhecido'));
                    // Reabilitar botões em caso de erro
                    salvandoNotas = false;
                    botoesSalvar.forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        btn.textContent = btn.dataset.textoOriginal || 'Salvar';
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar notas');
                // Reabilitar botões em caso de erro
                salvandoNotas = false;
                botoesSalvar.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.textContent = btn.dataset.textoOriginal || 'Salvar';
                });
            });
        }
        
        // Funções para modal Ver Turma
        function verTurma(turmaId, disciplinaId, turmaNome, disciplinaNome) {
            const modal = document.getElementById('modal-ver-turma');
            if (modal) {
                modal.classList.remove('hidden');
                document.getElementById('ver-turma-id').value = turmaId;
                document.getElementById('ver-turma-disciplina-id').value = disciplinaId;
                document.getElementById('ver-turma-info').textContent = turmaNome + ' - ' + disciplinaNome;
                
                // Mostrar tab de alunos por padrão
                mostrarTabTurma('alunos');
                carregarAlunosTurma(turmaId);
            }
        }
        
        function fecharModalVerTurma() {
            const modal = document.getElementById('modal-ver-turma');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function mostrarTabTurma(tab) {
            // Atualizar botões das tabs
            document.getElementById('tab-alunos').classList.remove('text-blue-600', 'border-blue-600');
            document.getElementById('tab-alunos').classList.add('text-gray-500');
            document.getElementById('tab-historico').classList.remove('text-blue-600', 'border-blue-600');
            document.getElementById('tab-historico').classList.add('text-gray-500');
            
            // Esconder todos os conteúdos
            document.getElementById('conteudo-alunos').classList.add('hidden');
            document.getElementById('conteudo-historico').classList.add('hidden');
            
            // Mostrar conteúdo selecionado
            if (tab === 'alunos') {
                document.getElementById('tab-alunos').classList.remove('text-gray-500');
                document.getElementById('tab-alunos').classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                document.getElementById('conteudo-alunos').classList.remove('hidden');
            } else if (tab === 'historico') {
                document.getElementById('tab-historico').classList.remove('text-gray-500');
                document.getElementById('tab-historico').classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                document.getElementById('conteudo-historico').classList.remove('hidden');
                carregarHistoricoNotas();
            }
        }
        
        function carregarAlunosTurma(turmaId) {
            fetch('?acao=buscar_alunos_turma&turma_id=' + turmaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        const container = document.getElementById('ver-turma-alunos-container');
                        container.innerHTML = '';
                        
                        // Header da tabela
                        const header = document.createElement('div');
                        header.className = 'grid grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wide px-3 py-2 border-b border-gray-200 mb-2';
                        header.innerHTML = `
                            <div class="col-span-1">#</div>
                            <div class="col-span-5">Aluno</div>
                            <div class="col-span-3">Matrícula</div>
                            <div class="col-span-3 text-center">Ações</div>
                        `;
                        container.appendChild(header);
                        
                        data.alunos.forEach((aluno, index) => {
                            const div = document.createElement('div');
                            div.className = 'grid grid-cols-12 gap-3 items-center px-3 py-3 bg-white rounded-lg border border-gray-100 hover:bg-gray-50';
                            div.innerHTML = `
                                <div class="col-span-1 text-sm text-gray-400">${index + 1}</div>
                                <div class="col-span-5">
                                    <div class="text-sm font-medium text-gray-900">${aluno.nome}</div>
                                </div>
                                <div class="col-span-3 text-sm text-gray-600">${aluno.matricula || '-'}</div>
                                <div class="col-span-3 flex gap-2 justify-center">
                                    <button onclick="verHistoricoAluno(${aluno.id}, '${aluno.nome.replace(/'/g, "\\'")}')" class="px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors">
                                        Ver Notas
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar alunos:', error);
                    alert('Erro ao carregar alunos da turma');
                });
        }
        
        function carregarHistoricoNotas() {
            const turmaId = document.getElementById('ver-turma-id').value;
            const disciplinaId = document.getElementById('ver-turma-disciplina-id').value;
            const bimestre = document.getElementById('ver-turma-bimestre').value;
            
            console.log('=== carregarHistoricoNotas ===');
            console.log('Turma ID:', turmaId);
            console.log('Disciplina ID:', disciplinaId);
            console.log('Bimestre:', bimestre);
            
            if (!turmaId || !disciplinaId) {
                console.error('Turma ID ou Disciplina ID não encontrados!');
                return;
            }
            
            let url = '?acao=buscar_historico_notas&turma_id=' + encodeURIComponent(turmaId) + '&disciplina_id=' + encodeURIComponent(disciplinaId);
            if (bimestre) {
                url += '&bimestre=' + encodeURIComponent(bimestre);
            }
            
            console.log('URL:', url);
            
            const container = document.getElementById('ver-turma-historico-container');
            if (container) {
                container.innerHTML = '<div class="text-center py-16 text-gray-400"><p class="text-sm">Carregando histórico...</p></div>';
            }
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Erro na resposta do servidor: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    const container = document.getElementById('ver-turma-historico-container');
                    if (!container) {
                        console.error('Container ver-turma-historico-container não encontrado!');
                        return;
                    }
                    
                    if (!data.success) {
                        console.error('Erro na resposta:', data.message);
                        container.innerHTML = '<div class="text-center py-16 text-red-400">' +
                            '<div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">' +
                            '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                            '</svg></div>' +
                            '<p class="text-sm font-medium text-red-600">Erro ao carregar histórico</p>' +
                            '<p class="text-xs text-gray-500 mt-1">' + (data.message || 'Erro desconhecido') + '</p>' +
                            '</div>';
                        return;
                    }
                    
                    if (data.success && data.notas) {
                        console.log('Total de notas encontradas:', data.notas.length);
                        container.innerHTML = '';
                        
                        if (data.notas.length === 0) {
                            container.innerHTML = '<div class="text-center py-16 text-gray-400"><p class="text-sm">Nenhuma nota lançada ainda</p></div>';
                            return;
                        }
                        
                        // Agrupar notas por aluno e bimestre
                        const notasPorAlunoBimestre = {};
                        data.notas.forEach(nota => {
                            const key = `${nota.aluno_id}_${nota.bimestre || '0'}`;
                            if (!notasPorAlunoBimestre[key]) {
                                notasPorAlunoBimestre[key] = {
                                    aluno_id: nota.aluno_id,
                                    aluno_nome: nota.aluno_nome,
                                    aluno_matricula: nota.aluno_matricula,
                                    bimestre: nota.bimestre || '-',
                                    notas: [],
                                    parcial: null,
                                    bimestral: null,
                                    participativa: null
                                };
                            }
                            
                            // Separar parcial, bimestral e participativa
                            const tipoAvaliacao = nota.avaliacao_tipo === 'PROVA' ? 'BIMESTRAL' : 
                                                 (nota.avaliacao_tipo === 'ATIVIDADE' ? 'PARCIAL' : 
                                                 (nota.avaliacao_tipo === 'TRABALHO' ? 'PARTICIPATIVA' : null));
                            if (tipoAvaliacao === 'PARCIAL') {
                                notasPorAlunoBimestre[key].parcial = nota;
                            } else if (tipoAvaliacao === 'BIMESTRAL') {
                                notasPorAlunoBimestre[key].bimestral = nota;
                            } else if (tipoAvaliacao === 'PARTICIPATIVA') {
                                notasPorAlunoBimestre[key].participativa = nota;
                            }
                            
                            notasPorAlunoBimestre[key].notas.push(nota);
                        });
                        
                        // Calcular médias
                        Object.values(notasPorAlunoBimestre).forEach(item => {
                            const notas = [];
                            if (item.parcial) notas.push(parseFloat(item.parcial.nota));
                            if (item.bimestral) notas.push(parseFloat(item.bimestral.nota));
                            if (item.participativa) notas.push(parseFloat(item.participativa.nota));
                            
                            if (notas.length > 0) {
                                item.media = (notas.reduce((a, b) => a + b, 0) / notas.length).toFixed(1).replace('.', ',');
                            } else {
                                item.media = '-';
                            }
                        });
                        
                        // Header da tabela
                        const header = document.createElement('div');
                        header.className = 'grid grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wide px-3 py-2 border-b border-gray-200 mb-2';
                        header.innerHTML = `
                            <div class="col-span-3">Aluno</div>
                            <div class="col-span-1 text-center">Bim.</div>
                            <div class="col-span-1 text-center">Parcial</div>
                            <div class="col-span-1 text-center">Bimestral</div>
                            <div class="col-span-1 text-center">Participativa</div>
                            <div class="col-span-1 text-center">Média</div>
                            <div class="col-span-3">Observação</div>
                            <div class="col-span-1 text-center">Ação</div>
                        `;
                        container.appendChild(header);
                        
                        // Renderizar notas agrupadas
                        Object.values(notasPorAlunoBimestre).forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'grid grid-cols-12 gap-3 items-center px-3 py-3 bg-white rounded-lg border border-gray-100 hover:bg-gray-50';
                            
                            const notaParcial = item.parcial ? parseFloat(item.parcial.nota).toFixed(1).replace('.', ',') : '-';
                            const notaBimestral = item.bimestral ? parseFloat(item.bimestral.nota).toFixed(1).replace('.', ',') : '-';
                            const notaParticipativa = item.participativa ? parseFloat(item.participativa.nota).toFixed(1).replace('.', ',') : '-';
                            const mediaNum = item.media !== '-' ? parseFloat(item.media.replace(',', '.')) : 0;
                            
                            // Pegar observação (priorizar a do bimestral, senão da parcial, senão da participativa)
                            const observacao = (item.bimestral && item.bimestral.comentario) ? item.bimestral.comentario : 
                                             (item.parcial && item.parcial.comentario) ? item.parcial.comentario : 
                                             (item.participativa && item.participativa.comentario) ? item.participativa.comentario : '-';
                            
                            div.innerHTML = `
                                <div class="col-span-3">
                                    <div class="text-sm font-medium text-gray-900">${item.aluno_nome}</div>
                                    ${item.aluno_matricula ? `<div class="text-xs text-gray-400">${item.aluno_matricula}</div>` : ''}
                                </div>
                                <div class="col-span-1 text-center text-sm text-gray-600">${item.bimestre}</div>
                                <div class="col-span-1 text-center">
                                    ${item.parcial ? `<span class="px-2 py-1 text-sm font-medium rounded ${parseFloat(item.parcial.nota) >= 7 ? 'text-green-600 bg-green-50' : parseFloat(item.parcial.nota) >= 5 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50'}">${notaParcial}</span>` : '<span class="text-gray-400">-</span>'}
                                </div>
                                <div class="col-span-1 text-center">
                                    ${item.bimestral ? `<span class="px-2 py-1 text-sm font-medium rounded ${parseFloat(item.bimestral.nota) >= 7 ? 'text-green-600 bg-green-50' : parseFloat(item.bimestral.nota) >= 5 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50'}">${notaBimestral}</span>` : '<span class="text-gray-400">-</span>'}
                                </div>
                                <div class="col-span-1 text-center">
                                    ${item.participativa ? `<span class="px-2 py-1 text-sm font-medium rounded ${parseFloat(item.participativa.nota) >= 7 ? 'text-green-600 bg-green-50' : parseFloat(item.participativa.nota) >= 5 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50'}">${notaParticipativa}</span>` : '<span class="text-gray-400">-</span>'}
                                </div>
                                <div class="col-span-1 text-center">
                                    ${item.media !== '-' ? `<span class="px-2 py-1 text-sm font-semibold rounded ${mediaNum >= 7 ? 'text-green-600 bg-green-50' : mediaNum >= 5 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50'}">${item.media}</span>` : '<span class="text-gray-400">-</span>'}
                                </div>
                                <div class="col-span-3 text-sm text-gray-600 truncate" title="${observacao}">${observacao}</div>
                                <div class="col-span-1 text-center">
                                    <button onclick="editarNotasAluno(${item.aluno_id}, ${item.bimestre || 0})" class="px-2 py-1 text-xs font-medium text-orange-600 hover:text-orange-700 hover:bg-orange-50 rounded transition-colors">
                                        Editar
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar histórico:', error);
                    const container = document.getElementById('ver-turma-historico-container');
                    if (container) {
                        container.innerHTML = '<div class="text-center py-16 text-red-400">' +
                            '<div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">' +
                            '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                            '</svg></div>' +
                            '<p class="text-sm font-medium text-red-600">Erro ao carregar histórico de notas</p>' +
                            '<p class="text-xs text-gray-500 mt-1">' + error.message + '</p>' +
                            '</div>';
                    }
                });
        }
        
        function verHistoricoAluno(alunoId, alunoNome) {
            mostrarTabTurma('historico');
            // Filtrar histórico para mostrar apenas notas deste aluno
            // Por enquanto, apenas muda para a tab de histórico
            setTimeout(() => {
                carregarHistoricoNotas();
            }, 100);
        }
        
        // Funções para editar notas
        function editarNotasAluno(alunoId, bimestre) {
            const turmaId = document.getElementById('ver-turma-id').value;
            const disciplinaId = document.getElementById('ver-turma-disciplina-id').value;
            
            if (!turmaId || !disciplinaId || !alunoId) {
                alert('Erro: dados incompletos');
                return;
            }
            
            fetch(`?acao=buscar_notas_aluno_bimestre&aluno_id=${alunoId}&turma_id=${turmaId}&disciplina_id=${disciplinaId}&bimestre=${bimestre}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar-nota-aluno-id').value = alunoId;
                        document.getElementById('editar-nota-aluno').textContent = data.aluno ? (data.aluno.nome + (data.aluno.matricula ? ' - ' + data.aluno.matricula : '')) : 'Aluno';
                        document.getElementById('editar-nota-bimestre').value = bimestre;
                        
                        // Preencher parcial
                        if (data.parcial) {
                            document.getElementById('editar-nota-parcial-id').value = data.parcial.id;
                            document.getElementById('editar-nota-parcial-valor').value = parseFloat(data.parcial.nota).toFixed(1).replace('.', ',');
                            document.getElementById('editar-nota-parcial-comentario').value = data.parcial.comentario || '';
                        } else {
                            document.getElementById('editar-nota-parcial-id').value = '';
                            document.getElementById('editar-nota-parcial-valor').value = '';
                            document.getElementById('editar-nota-parcial-comentario').value = '';
                        }
                        
                        // Preencher bimestral
                        if (data.bimestral) {
                            document.getElementById('editar-nota-bimestral-id').value = data.bimestral.id;
                            document.getElementById('editar-nota-bimestral-valor').value = parseFloat(data.bimestral.nota).toFixed(1).replace('.', ',');
                            document.getElementById('editar-nota-bimestral-comentario').value = data.bimestral.comentario || '';
                        } else {
                            document.getElementById('editar-nota-bimestral-id').value = '';
                            document.getElementById('editar-nota-bimestral-valor').value = '';
                            document.getElementById('editar-nota-bimestral-comentario').value = '';
                        }
                        
                        // Preencher participativa
                        if (data.participativa) {
                            document.getElementById('editar-nota-participativa-id').value = data.participativa.id;
                            document.getElementById('editar-nota-participativa-valor').value = parseFloat(data.participativa.nota).toFixed(1).replace('.', ',');
                            document.getElementById('editar-nota-participativa-comentario').value = data.participativa.comentario || '';
                        } else {
                            document.getElementById('editar-nota-participativa-id').value = '';
                            document.getElementById('editar-nota-participativa-valor').value = '';
                            document.getElementById('editar-nota-participativa-comentario').value = '';
                        }
                        
                        const modal = document.getElementById('modal-editar-nota');
                        if (modal) {
                            modal.classList.remove('hidden');
                            modal.style.display = 'flex';
                        }
                    } else {
                        alert('Erro ao carregar dados das notas: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados das notas');
                });
        }
        
        function carregarNotasParaEdicao() {
            const alunoId = document.getElementById('editar-nota-aluno-id').value;
            const bimestre = document.getElementById('editar-nota-bimestre').value;
            if (alunoId && bimestre) {
                editarNotasAluno(alunoId, bimestre);
            }
        }
        
        function fecharModalEditarNota() {
            const modal = document.getElementById('modal-editar-nota');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }
        
        function salvarEdicaoNota() {
            const alunoId = document.getElementById('editar-nota-aluno-id').value;
            const bimestre = document.getElementById('editar-nota-bimestre').value;
            const turmaId = document.getElementById('ver-turma-id').value;
            const disciplinaId = document.getElementById('ver-turma-disciplina-id').value;
            
            const parcialId = document.getElementById('editar-nota-parcial-id').value;
            const parcialValor = document.getElementById('editar-nota-parcial-valor').value;
            const parcialComentario = document.getElementById('editar-nota-parcial-comentario').value;
            
            const bimestralId = document.getElementById('editar-nota-bimestral-id').value;
            const bimestralValor = document.getElementById('editar-nota-bimestral-valor').value;
            const bimestralComentario = document.getElementById('editar-nota-bimestral-comentario').value;
            
            const participativaId = document.getElementById('editar-nota-participativa-id').value;
            const participativaValor = document.getElementById('editar-nota-participativa-valor').value;
            const participativaComentario = document.getElementById('editar-nota-participativa-comentario').value;
            
            if (!alunoId || !bimestre || !turmaId || !disciplinaId) {
                alert('Erro: dados incompletos');
                return;
            }
            
            const notasParaSalvar = [];
            const notasParaEditar = [];
            
            // Processar parcial
            if (parcialValor && parcialValor.trim() !== '') {
                const notaParcial = parseFloat(parcialValor.replace(',', '.'));
                if (notaParcial < 0 || notaParcial > 10) {
                    alert('Nota parcial deve estar entre 0 e 10');
                    return;
                }
                
                if (parcialId) {
                    notasParaEditar.push({
                        id: parcialId,
                        nota: notaParcial,
                        bimestre: bimestre,
                        comentario: parcialComentario
                    });
                } else {
                    notasParaSalvar.push({
                        aluno_id: alunoId,
                        nota: notaParcial,
                        tipo: 'PARCIAL',
                        bimestre: bimestre,
                        comentario: parcialComentario
                    });
                }
            }
            
            // Processar bimestral
            if (bimestralValor && bimestralValor.trim() !== '') {
                const notaBimestral = parseFloat(bimestralValor.replace(',', '.'));
                if (notaBimestral < 0 || notaBimestral > 10) {
                    alert('Nota bimestral deve estar entre 0 e 10');
                    return;
                }
                
                if (bimestralId) {
                    notasParaEditar.push({
                        id: bimestralId,
                        nota: notaBimestral,
                        bimestre: bimestre,
                        comentario: bimestralComentario
                    });
                } else {
                    notasParaSalvar.push({
                        aluno_id: alunoId,
                        nota: notaBimestral,
                        tipo: 'BIMESTRAL',
                        bimestre: bimestre,
                        comentario: bimestralComentario
                    });
                }
            }
            
            // Processar participativa
            if (participativaValor && participativaValor.trim() !== '') {
                const notaParticipativa = parseFloat(participativaValor.replace(',', '.'));
                if (notaParticipativa < 0 || notaParticipativa > 10) {
                    alert('Nota participativa deve estar entre 0 e 10');
                    return;
                }
                
                if (participativaId) {
                    notasParaEditar.push({
                        id: participativaId,
                        nota: notaParticipativa,
                        bimestre: bimestre,
                        comentario: participativaComentario
                    });
                } else {
                    notasParaSalvar.push({
                        aluno_id: alunoId,
                        nota: notaParticipativa,
                        tipo: 'PARTICIPATIVA',
                        bimestre: bimestre,
                        comentario: participativaComentario
                    });
                }
            }
            
            // Salvar novas notas
            if (notasParaSalvar.length > 0) {
                const formData = new FormData();
                formData.append('acao', 'lancar_notas');
                formData.append('turma_id', turmaId);
                formData.append('disciplina_id', disciplinaId);
                formData.append('notas', JSON.stringify(notasParaSalvar));
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Erro ao salvar notas');
                    }
                    // Continuar para editar
                    editarNotasRestantes(notasParaEditar);
                })
                .catch(error => {
                    console.error('Erro ao salvar notas:', error);
                    alert('Erro ao salvar notas: ' + error.message);
                });
            } else {
                // Apenas editar
                editarNotasRestantes(notasParaEditar);
            }
        }
        
        // Funções para modal de sucesso
        function mostrarModalSucesso(mensagem) {
            const modal = document.getElementById('modal-sucesso');
            const mensagemElement = document.getElementById('modal-sucesso-mensagem');
            if (modal && mensagemElement) {
                mensagemElement.textContent = mensagem;
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                
                // Animação de entrada
                setTimeout(() => {
                    const modalContent = modal.querySelector('.modal-sucesso-content');
                    if (modalContent) {
                        modalContent.classList.remove('scale-95');
                        modalContent.classList.add('scale-100');
                    }
                }, 10);
            }
        }
        
        function fecharModalSucesso() {
            const modal = document.getElementById('modal-sucesso');
            if (modal) {
                const modalContent = modal.querySelector('.modal-sucesso-content');
                if (modalContent) {
                    modalContent.classList.remove('scale-100');
                    modalContent.classList.add('scale-95');
                }
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }, 200);
            }
        }
        
        function editarNotasRestantes(notasParaEditar) {
            if (notasParaEditar.length === 0) {
                mostrarModalSucesso('Notas atualizadas com sucesso!');
                fecharModalEditarNota();
                carregarHistoricoNotas();
                return;
            }
            
            // Editar uma por vez
            let index = 0;
            function editarProxima() {
                if (index >= notasParaEditar.length) {
                    mostrarModalSucesso('Notas atualizadas com sucesso!');
                    fecharModalEditarNota();
                    carregarHistoricoNotas();
                    return;
                }
                
                const nota = notasParaEditar[index];
                const formData = new FormData();
                formData.append('acao', 'editar_nota');
                formData.append('nota_id', nota.id);
                formData.append('nota', nota.nota);
                formData.append('bimestre', nota.bimestre);
                formData.append('comentario', nota.comentario || '');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        index++;
                        editarProxima();
                    } else {
                        throw new Error(data.message || 'Erro ao atualizar nota');
                    }
                })
                .catch(error => {
                    console.error('Erro ao editar nota:', error);
                    alert('Erro ao atualizar nota: ' + error.message);
                });
            }
            
            editarProxima();
        }
    </script>
</body>
</html>

