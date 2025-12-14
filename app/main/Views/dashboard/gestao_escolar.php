<?php
// Iniciar output buffering para evitar problemas com headers
if (!ob_get_level()) {
    ob_start();
}
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/academico/TurmaModel.php');
require_once('../../Models/academico/AlunoModel.php');
require_once('../../Models/academico/NotaModel.php');
require_once('../../Models/academico/FrequenciaModel.php');
require_once('../../Models/dashboard/DashboardStats.php');
require_once('../../Models/pessoas/FuncionarioModel.php');
require_once('../../Models/pessoas/ResponsavelModel.php');
require_once('../../Models/merenda/DesperdicioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO
if ($_SESSION['tipo'] !== 'GESTAO' && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

require_once('../../config/Database.php');

$turmaModel = new TurmaModel();
$alunoModel = new AlunoModel();
$funcionarioModel = new FuncionarioModel();
$responsavelModel = new ResponsavelModel();
$stats = new DashboardStats();
$desperdicioModel = new DesperdicioModel();

// Buscar escola do gestor logado
$db = Database::getInstance();
$conn = $db->getConnection();
$escolaGestor = null;
$escolaGestorId = null;

// Log inicial
error_log("DEBUG GESTOR INICIAL - Tipo: " . ($_SESSION['tipo'] ?? 'NULL') . ", usuario_id: " . ($_SESSION['usuario_id'] ?? 'NULL'));

// ... existing code (requires, session, $conn, etc.) ...

if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    error_log("DEBUG GESTOR - usuario_id: " . ($usuarioId ?? 'NULL'));
    
    // Primeiro, verificar se há escola selecionada na sessão
    $escolaIdSessao = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
    
    if ($escolaIdSessao) {
        // Verificar se a escola da sessão é válida e pertence ao gestor
        try {
            $sqlVerificarEscola = "SELECT e.id, e.nome, e.ativo
                                   FROM escola e
                                   INNER JOIN gestor_lotacao gl ON e.id = gl.escola_id
                                   INNER JOIN gestor g ON gl.gestor_id = g.id
                                   INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                                   WHERE u.id = :usuario_id 
                                   AND e.id = :escola_id 
                                   AND e.ativo = 1
                                   AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                                   LIMIT 1";
            $stmtVerificar = $conn->prepare($sqlVerificarEscola);
            $stmtVerificar->bindParam(':usuario_id', $usuarioId);
            $stmtVerificar->bindParam(':escola_id', $escolaIdSessao, PDO::PARAM_INT);
            $stmtVerificar->execute();
            $escolaValida = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            if ($escolaValida) {
                $escolaGestorId = (int)$escolaValida['id'];
                $escolaGestor = $escolaValida['nome'];
                error_log("DEBUG GESTOR - Escola da sessão validada: ID=" . $escolaGestorId . ", Nome=" . $escolaGestor);
            }
        } catch (Exception $e) {
            error_log("DEBUG GESTOR - Erro ao validar escola da sessão: " . $e->getMessage());
        }
    }
    
    if (!$escolaGestorId && $usuarioId) {
        try {
            $sqlCheckGestor = "SELECT g.id as gestor_id, g.pessoa_id, g.ativo
                               FROM gestor g
                               INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                               WHERE u.id = :usuario_id";
            $stmtCheck = $conn->prepare($sqlCheckGestor);
            $stmtCheck->bindParam(':usuario_id', $usuarioId);
            $stmtCheck->execute();
            $checkGestor = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            error_log("DEBUG GESTOR - Check gestor: " . json_encode($checkGestor));

            $sqlGestor = "SELECT g.id as gestor_id, gl.escola_id, e.nome as escola_nome, gl.responsavel, gl.fim, gl.inicio
                          FROM gestor g
                          INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                          INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                          INNER JOIN escola e ON gl.escola_id = e.id
                          WHERE u.id = :usuario_id AND g.ativo = 1 AND e.ativo = 1
                          ORDER BY 
                            CASE WHEN gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' THEN 0 ELSE 1 END,
                            gl.responsavel DESC, 
                            gl.inicio DESC,
                            gl.id DESC
                          LIMIT 1";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':usuario_id', $usuarioId);
            $stmtGestor->execute();
            $gestorEscola = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            error_log("DEBUG GESTOR - Query 1 resultado: " . json_encode($gestorEscola));
            
            if ($gestorEscola) {
                $escolaGestorId = (int)$gestorEscola['escola_id'];
                $escolaGestor = $gestorEscola['escola_nome'];
                
                $_SESSION['escola_selecionada_id'] = $escolaGestorId;
                $_SESSION['escola_selecionada_nome'] = $escolaGestor;
                $_SESSION['escola_id'] = $escolaGestorId;
                $_SESSION['escola_atual'] = $escolaGestor;
                
                error_log("DEBUG GESTOR - Escola encontrada (Query 1): ID=" . $escolaGestorId . ", Nome=" . $escolaGestor);
            } else {
                $sqlGestor2 = "SELECT g.id as gestor_id, gl.escola_id, e.nome as escola_nome, gl.responsavel, gl.fim, gl.inicio
                               FROM gestor g
                               INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                               INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                               INNER JOIN escola e ON gl.escola_id = e.id
                               WHERE u.id = :usuario_id AND g.ativo = 1 AND e.ativo = 1
                               ORDER BY gl.responsavel DESC, gl.inicio DESC, gl.id DESC
                               LIMIT 1";
                $stmtGestor2 = $conn->prepare($sqlGestor2);
                $stmtGestor2->bindParam(':usuario_id', $usuarioId);
                $stmtGestor2->execute();
                $gestorEscola2 = $stmtGestor2->fetch(PDO::FETCH_ASSOC);
                error_log("DEBUG GESTOR - Query 2 resultado: " . json_encode($gestorEscola2));
                
                if ($gestorEscola2) {
                    $fimLotacao = $gestorEscola2['fim'];
                    $lotacaoAtiva = ($fimLotacao === null || $fimLotacao === '' || $fimLotacao === '0000-00-00' || strtotime($fimLotacao) >= strtotime('today'));
                    error_log("DEBUG GESTOR - Fim lotação: " . var_export($fimLotacao, true) . ", Ativa: " . ($lotacaoAtiva ? 'SIM' : 'NÃO'));
                    
                    if ($lotacaoAtiva) {
                        $escolaGestorId = (int)$gestorEscola2['escola_id'];
                        $escolaGestor = $gestorEscola2['escola_nome'];
                        
                        // Atualizar sessão com a escola encontrada
                        $_SESSION['escola_selecionada_id'] = $escolaGestorId;
                        $_SESSION['escola_selecionada_nome'] = $escolaGestor;
                        $_SESSION['escola_id'] = $escolaGestorId;
                        $_SESSION['escola_atual'] = $escolaGestor;
                        
                        error_log("DEBUG GESTOR - Escola encontrada (Query 2): ID=" . $escolaGestorId . ", Nome=" . $escolaGestor);
                    } else {
                        $escolaGestorId = null;
                        $escolaGestor = null;
                        error_log("DEBUG GESTOR - Lotação encontrada mas não está ativa (fim: " . var_export($fimLotacao, true) . ")");
                    }
                } else {
                    // Verificar se existe lotação mesmo que inativa
                    $sqlCheckLotacao = "SELECT gl.*, e.nome as escola_nome
                                        FROM gestor g
                                        INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                                        INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                                        INNER JOIN escola e ON gl.escola_id = e.id
                                        WHERE u.id = :usuario_id AND e.ativo = 1
                                        ORDER BY gl.id DESC
                                        LIMIT 5";
                    $stmtCheckLot = $conn->prepare($sqlCheckLotacao);
                    $stmtCheckLot->bindParam(':usuario_id', $usuarioId);
                    $stmtCheckLot->execute();
                    $todasLotacoes = $stmtCheckLot->fetchAll(PDO::FETCH_ASSOC);
                    error_log("DEBUG GESTOR - Todas as lotações encontradas: " . json_encode($todasLotacoes));
                    
                    $escolaGestorId = null;
                    $escolaGestor = null;
                    error_log("DEBUG GESTOR - Nenhuma escola encontrada para o gestor");
                }
            }
        } catch (Exception $e) {
            error_log("DEBUG GESTOR - Erro ao buscar escola do gestor: " . $e->getMessage());
            error_log("DEBUG GESTOR - Stack trace: " . $e->getTraceAsString());
            $escolaGestorId = null;
            $escolaGestor = null;
        }
    } else {
        error_log("DEBUG GESTOR - usuario_id é NULL");
    }
} else {
    error_log("DEBUG GESTOR - Tipo de usuário não é GESTAO: " . ($_SESSION['tipo'] ?? 'NULL'));
}

// Garantir que sempre temos uma escola válida para o gestor
if ($_SESSION['tipo'] === 'GESTAO' && !$escolaGestorId) {
    // Se não encontrou escola, tentar usar da sessão
    $escolaIdSessao = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
    if ($escolaIdSessao) {
        try {
            $sqlBuscarEscola = "SELECT id, nome FROM escola WHERE id = :escola_id AND ativo = 1 LIMIT 1";
            $stmtBuscarEscola = $conn->prepare($sqlBuscarEscola);
            $stmtBuscarEscola->bindParam(':escola_id', $escolaIdSessao, PDO::PARAM_INT);
            $stmtBuscarEscola->execute();
            $escolaEncontrada = $stmtBuscarEscola->fetch(PDO::FETCH_ASSOC);
            if ($escolaEncontrada) {
                $escolaGestorId = (int)$escolaEncontrada['id'];
                $escolaGestor = $escolaEncontrada['nome'];
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar escola da sessão: " . $e->getMessage());
        }
    }
}

// Variável auxiliar que sempre retorna a escola correta (prioriza sessão, depois variável)
$escolaIdAtual = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? $escolaGestorId ?? null;
$escolaNomeAtual = $_SESSION['escola_selecionada_nome'] ?? $_SESSION['escola_atual'] ?? $escolaGestor ?? null;

// Se temos ID mas não temos nome, buscar o nome
if ($escolaIdAtual && !$escolaNomeAtual) {
    try {
        $sqlBuscarNome = "SELECT nome FROM escola WHERE id = :escola_id AND ativo = 1 LIMIT 1";
        $stmtBuscarNome = $conn->prepare($sqlBuscarNome);
        $stmtBuscarNome->bindParam(':escola_id', $escolaIdAtual, PDO::PARAM_INT);
        $stmtBuscarNome->execute();
        $resultNome = $stmtBuscarNome->fetch(PDO::FETCH_ASSOC);
        if ($resultNome && !empty($resultNome['nome'])) {
            $escolaNomeAtual = $resultNome['nome'];
            $escolaGestor = $resultNome['nome'];
            $_SESSION['escola_selecionada_nome'] = $resultNome['nome'];
            $_SESSION['escola_atual'] = $resultNome['nome'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar nome da escola: " . $e->getMessage());
    }
}

// Atualizar variáveis principais se necessário
if ($escolaIdAtual && !$escolaGestorId) {
    $escolaGestorId = $escolaIdAtual;
}
if ($escolaNomeAtual && !$escolaGestor) {
    $escolaGestor = $escolaNomeAtual;
}

// Buscar produtos para o modal de desperdício
$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar ações
$mensagem = '';
$tipoMensagem = '';

// Recuperar mensagens da sessão (após redirect)
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem = $_SESSION['mensagem_sucesso'];
    $tipoMensagem = 'success';
    unset($_SESSION['mensagem_sucesso']);
}
if (isset($_SESSION['mensagem_erro'])) {
    $mensagem = $_SESSION['mensagem_erro'];
    $tipoMensagem = 'error';
    unset($_SESSION['mensagem_erro']);
}

// Processar requisições GET (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['acao'])) {
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'buscar_professores_escola':
            // Limpar qualquer output anterior
            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            try {
                $escolaId = $_GET['escola_id'] ?? null;
                
                if (empty($escolaId)) {
                    throw new Exception('ID da escola é obrigatório.');
                }
                
                // Verificar se o gestor tem acesso a esta escola
                if ($_SESSION['tipo'] === 'GESTAO' && $escolaIdAtual && $escolaId != $escolaIdAtual) {
                    throw new Exception('Você não tem permissão para acessar esta escola.');
                }
                
                // Buscar professores lotados na escola
                $sql = "SELECT pl.professor_id, p.nome, p.email, p.telefone
                        FROM professor_lotacao pl
                        INNER JOIN professor pr ON pl.professor_id = pr.id
                        INNER JOIN pessoa p ON pr.pessoa_id = p.id
                        INNER JOIN usuario u ON u.pessoa_id = p.id
                        WHERE pl.escola_id = :escola_id 
                        AND pl.fim IS NULL 
                        AND pr.ativo = 1 
                        AND u.ativo = 1
                        ORDER BY p.nome ASC";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':escola_id', $escolaId);
                $stmt->execute();
                $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'professores' => $professores]);
            } catch (Exception $e) {
                error_log("Erro ao buscar professores da escola: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => $e->getMessage(), 'professores' => []]);
            }
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'criar_turma':
            $escolaIdTurma = $_POST['escola_id'] ?? null;
            
            // Validar permissão: gestor só pode criar turmas na sua escola
            if ($_SESSION['tipo'] === 'GESTAO' && $escolaIdAtual && $escolaIdTurma != $escolaIdAtual) {
                $_SESSION['mensagem_erro'] = 'Você não tem permissão para criar turmas nesta escola.';
                header('Location: gestao_escolar.php');
                exit;
            }

            $serie = strtoupper(trim($_POST['serie'] ?? ''));
            $letra = strtoupper(trim($_POST['letra'] ?? ''));
            $turno = $_POST['turno'] ?? 'MANHA';
            $anoLetivo = $_POST['ano_letivo'] ?? date('Y');

            // Validar campos obrigatórios
            if (empty($serie) || empty($letra) || empty($escolaIdTurma)) {
                $_SESSION['mensagem_erro'] = 'Série, Letra e Escola são obrigatórios.';
                header('Location: gestao_escolar.php');
                exit;
            }

            // Verificar se já existe turma com os mesmos dados (escola, série, letra, turno, ano letivo)
            $sqlVerificar = "SELECT id FROM turma 
                            WHERE escola_id = :escola_id 
                            AND UPPER(TRIM(serie)) = :serie 
                            AND UPPER(TRIM(letra)) = :letra 
                            AND turno = :turno 
                            AND ano_letivo = :ano_letivo 
                            AND ativo = 1";
            $stmtVerificar = $conn->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':escola_id', $escolaIdTurma);
            $stmtVerificar->bindParam(':serie', $serie);
            $stmtVerificar->bindParam(':letra', $letra);
            $stmtVerificar->bindParam(':turno', $turno);
            $stmtVerificar->bindParam(':ano_letivo', $anoLetivo);
            $stmtVerificar->execute();
            
            if ($stmtVerificar->fetch()) {
                $_SESSION['mensagem_erro'] = "Já existe uma turma cadastrada com esses dados: {$serie} {$letra} - {$turno} no ano letivo {$anoLetivo}. Não é possível criar turmas duplicadas.";
                header('Location: gestao_escolar.php');
                exit;
            }
            
            $resultado = $turmaModel->criar([
                'escola_id' => $escolaIdTurma,
                'serie_id' => $_POST['serie_id'] ?? null,
                'ano_letivo' => $anoLetivo,
                'serie' => $serie,
                'letra' => $letra,
                'turno' => $turno,
                'capacidade' => $_POST['capacidade'] ?? null,
                'sala' => $_POST['sala'] ?? null
            ]);
            
            if ($resultado['success']) {
                $_SESSION['mensagem_sucesso'] = 'Turma criada com sucesso!';
                // POST-REDIRECT-GET: redirecionar para evitar reenvio de formulário
                header('Location: gestao_escolar.php');
                exit;
            } else {
                $_SESSION['mensagem_erro'] = $resultado['message'] ?? 'Erro ao criar turma.';
                header('Location: gestao_escolar.php');
                exit;
            }
            break;
            
        case 'editar_turma':
            $turmaId = $_POST['turma_id'] ?? null;
            if ($turmaId) {
                // Validar permissão: gestor só pode editar turmas da sua escola
                if ($_SESSION['tipo'] === 'GESTAO' && $escolaIdAtual) {
                    $turma = $turmaModel->buscarPorId($turmaId);
                    if (!$turma || $turma['escola_id'] != $escolaIdAtual) {
                        $_SESSION['mensagem_erro'] = 'Você não tem permissão para editar esta turma.';
                        header('Location: gestao_escolar.php');
                        exit;
                    }
                    // Forçar escola_id para a escola do gestor
                    $_POST['escola_id'] = $escolaIdAtual;
                }
                
                $escolaIdTurma = $_POST['escola_id'] ?? null;
                
                // Validar se gestor está tentando mudar para outra escola
                if ($_SESSION['tipo'] === 'GESTAO' && $escolaIdAtual && $escolaIdTurma != $escolaIdAtual) {
                    $_SESSION['mensagem_erro'] = 'Você não tem permissão para alterar a escola desta turma.';
                    header('Location: gestao_escolar.php');
                    exit;
                }

                $serie = strtoupper(trim($_POST['serie'] ?? ''));
                $letra = strtoupper(trim($_POST['letra'] ?? ''));
                $turno = $_POST['turno'] ?? 'MANHA';
                $anoLetivo = $_POST['ano_letivo'] ?? date('Y');

                // Validar campos obrigatórios
                if (empty($serie) || empty($letra) || empty($escolaIdTurma)) {
                    $_SESSION['mensagem_erro'] = 'Série, Letra e Escola são obrigatórios.';
                    header('Location: gestao_escolar.php');
                    exit;
                }

                // Verificar se já existe outra turma com os mesmos dados (exceto a atual)
                $sqlVerificar = "SELECT id FROM turma 
                                WHERE escola_id = :escola_id 
                                AND UPPER(TRIM(serie)) = :serie 
                                AND UPPER(TRIM(letra)) = :letra 
                                AND turno = :turno 
                                AND ano_letivo = :ano_letivo 
                                AND ativo = 1
                                AND id != :turma_id";
                $stmtVerificar = $conn->prepare($sqlVerificar);
                $stmtVerificar->bindParam(':escola_id', $escolaIdTurma);
                $stmtVerificar->bindParam(':serie', $serie);
                $stmtVerificar->bindParam(':letra', $letra);
                $stmtVerificar->bindParam(':turno', $turno);
                $stmtVerificar->bindParam(':ano_letivo', $anoLetivo);
                $stmtVerificar->bindParam(':turma_id', $turmaId);
                $stmtVerificar->execute();
                
                if ($stmtVerificar->fetch()) {
                    $_SESSION['mensagem_erro'] = "Já existe outra turma cadastrada com esses dados: {$serie} {$letra} - {$turno} no ano letivo {$anoLetivo}. Não é possível ter turmas duplicadas.";
                    header('Location: gestao_escolar.php');
                    exit;
                }
                
                $resultado = $turmaModel->atualizar($turmaId, [
                    'escola_id' => $escolaIdTurma,
                    'serie_id' => $_POST['serie_id'] ?? null,
                    'ano_letivo' => $anoLetivo,
                    'serie' => $serie,
                    'letra' => $letra,
                    'turno' => $turno,
                    'capacidade' => $_POST['capacidade'] ?? null,
                    'sala' => $_POST['sala'] ?? null,
                    'ativo' => $_POST['ativo'] ?? 1
                ]);
                
                if ($resultado) {
                    $_SESSION['mensagem_sucesso'] = 'Turma atualizada com sucesso!';
                    header('Location: gestao_escolar.php');
                    exit;
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao atualizar turma.';
                    header('Location: gestao_escolar.php');
                    exit;
                }
            }
            break;
            
        case 'matricular_aluno':
            $resultado = $alunoModel->matricularEmTurma(
                $_POST['aluno_id'],
                $_POST['turma_id'],
                $_POST['data_inicio'] ?? date('Y-m-d')
            );
            
            if ($resultado) {
                $mensagem = 'Aluno matriculado com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = 'Erro ao matricular aluno.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'transferir_aluno':
            $resultado = $alunoModel->transferirTurma(
                $_POST['aluno_id'],
                $_POST['turma_antiga_id'],
                $_POST['turma_nova_id']
            );
            
            if ($resultado['success']) {
                $mensagem = 'Aluno transferido com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'] ?? 'Erro ao transferir aluno.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'atribuir_professor':
            $turmaId = $_POST['turma_id'] ?? null;
            $professorId = $_POST['professor_id'] ?? null;
            $disciplinaId = $_POST['disciplina_id'] ?? null;
            $regime = $_POST['regime'] ?? 'REGULAR';
            
            if ($turmaId && $professorId && $disciplinaId) {
                $resultado = $turmaModel->atribuirProfessor($turmaId, $professorId, $disciplinaId, $regime);
                
                if ($resultado) {
                    $mensagem = 'Professor atribuído com sucesso!';
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = 'Erro ao atribuir professor.';
                    $tipoMensagem = 'error';
                }
            } else {
                $mensagem = 'Dados incompletos para atribuir professor.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'remover_professor':
            $turmaId = $_POST['turma_id'] ?? null;
            $professorId = $_POST['professor_id'] ?? null;
            $disciplinaId = $_POST['disciplina_id'] ?? null;
            
            if ($turmaId && $professorId && $disciplinaId) {
                $resultado = $turmaModel->removerProfessor($turmaId, $professorId, $disciplinaId);
                
                if ($resultado) {
                    $mensagem = 'Professor removido com sucesso!';
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = 'Erro ao remover professor.';
                    $tipoMensagem = 'error';
                }
            } else {
                $mensagem = 'Dados incompletos para remover professor.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'aprovar_lancamento':
            $tipo = $_POST['tipo'] ?? null;
            $id = $_POST['id'] ?? null;
            
            if ($tipo && $id) {
                if ($tipo === 'NOTA') {
                    $notaModel = new NotaModel();
                    $resultado = $notaModel->validar($id, true);
                } elseif ($tipo === 'FREQUENCIA') {
                    $frequenciaModel = new FrequenciaModel();
                    $resultado = $frequenciaModel->validar($id, true);
                } else {
                    $resultado = false;
                }
                
                if ($resultado) {
                    $mensagem = 'Lançamento aprovado com sucesso!';
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = 'Erro ao aprovar lançamento.';
                    $tipoMensagem = 'error';
                }
            } else {
                $mensagem = 'Dados incompletos para aprovar lançamento.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'rejeitar_lancamento':
            $tipo = $_POST['tipo'] ?? null;
            $id = $_POST['id'] ?? null;
            $observacoes = $_POST['observacoes'] ?? '';
            
            if ($tipo && $id && $observacoes) {
                if ($tipo === 'NOTA') {
                    $notaModel = new NotaModel();
                    $resultado = $notaModel->validar($id, false);
                } elseif ($tipo === 'FREQUENCIA') {
                    $frequenciaModel = new FrequenciaModel();
                    $resultado = $frequenciaModel->validar($id, false);
                } else {
                    $resultado = false;
                }
                
                if ($resultado) {
                    $mensagem = 'Lançamento rejeitado.';
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = 'Erro ao rejeitar lançamento.';
                    $tipoMensagem = 'error';
                }
            } else {
                $mensagem = 'Dados incompletos. É necessário informar o motivo da rejeição.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'cadastrar_professor':
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            try {
                $conn->beginTransaction();
                
                // Validar CPF
                $cpfLimpo = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
                if (strlen($cpfLimpo) !== 11) {
                    throw new Exception('CPF inválido');
                }
                
                // Verificar se CPF já existe
                $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf");
                $stmt->bindParam(':cpf', $cpfLimpo);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    throw new Exception('CPF já cadastrado no sistema');
                }
                
                // Gerar username único
                $nome = $_POST['nome'] ?? '';
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $nome)[0]));
                $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $count = 1;
                    $newUsername = $username . $count;
                    while (true) {
                        $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
                        $stmt->bindParam(':username', $newUsername);
                        $stmt->execute();
                        if ($stmt->rowCount() == 0) {
                            $username = $newUsername;
                            break;
                        }
                        $count++;
                        $newUsername = $username . $count;
                    }
                }
                
                // 1. Criar pessoa
                $nome = trim($_POST['nome'] ?? '');
                $dataNascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
                $sexo = !empty($_POST['sexo']) ? $_POST['sexo'] : null;
                $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
                $telefone = !empty($_POST['telefone']) ? trim($_POST['telefone']) : null;
                $whatsapp = !empty($_POST['whatsapp']) ? trim($_POST['whatsapp']) : null;
                $telefoneSecundario = !empty($_POST['telefone_secundario']) ? trim($_POST['telefone_secundario']) : null;
                $endereco = !empty($_POST['endereco']) ? trim($_POST['endereco']) : null;
                $numero = !empty($_POST['numero']) ? trim($_POST['numero']) : null;
                $complemento = !empty($_POST['complemento']) ? trim($_POST['complemento']) : null;
                $bairro = !empty($_POST['bairro']) ? trim($_POST['bairro']) : null;
                $cidade = !empty($_POST['cidade']) ? trim($_POST['cidade']) : null;
                $estado = !empty($_POST['estado']) ? trim($_POST['estado']) : null;
                $cep = !empty($_POST['cep']) ? trim($_POST['cep']) : null;
                $nomeSocial = !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null;
                $raca = !empty($_POST['raca']) ? trim($_POST['raca']) : null;
                $criadoPor = $_SESSION['usuario_id'];
                
                $sqlPessoa = "INSERT INTO pessoa (
                                cpf, nome, data_nascimento, sexo, email, telefone, whatsapp, telefone_secundario,
                                endereco, numero, complemento, bairro, cidade, estado, cep,
                                tipo, criado_por, nome_social, raca
                              )
                              VALUES (
                                :cpf, :nome, :data_nascimento, :sexo, :email, :telefone, :whatsapp, :telefone_secundario,
                                :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep,
                                'PROFESSOR', :criado_por, :nome_social, :raca
                              )";
                $stmtPessoa = $conn->prepare($sqlPessoa);
                $stmtPessoa->bindParam(':cpf', $cpfLimpo);
                $stmtPessoa->bindParam(':nome', $nome);
                $stmtPessoa->bindParam(':data_nascimento', $dataNascimento);
                $stmtPessoa->bindParam(':sexo', $sexo);
                $stmtPessoa->bindParam(':email', $email);
                $stmtPessoa->bindParam(':telefone', $telefone);
                $stmtPessoa->bindParam(':whatsapp', $whatsapp);
                $stmtPessoa->bindParam(':telefone_secundario', $telefoneSecundario);
                $stmtPessoa->bindParam(':endereco', $endereco);
                $stmtPessoa->bindParam(':numero', $numero);
                $stmtPessoa->bindParam(':complemento', $complemento);
                $stmtPessoa->bindParam(':bairro', $bairro);
                $stmtPessoa->bindParam(':cidade', $cidade);
                $stmtPessoa->bindParam(':estado', $estado);
                $stmtPessoa->bindParam(':cep', $cep);
                $stmtPessoa->bindParam(':criado_por', $criadoPor);
                $stmtPessoa->bindParam(':nome_social', $nomeSocial);
                $stmtPessoa->bindParam(':raca', $raca);
                $stmtPessoa->execute();
                $pessoaId = $conn->lastInsertId();
                
                // 2. Criar professor
                $matricula = !empty($_POST['matricula']) ? $_POST['matricula'] : null;
                $formacao = !empty($_POST['formacao']) ? $_POST['formacao'] : null;
                $especializacao = !empty($_POST['especializacao']) ? $_POST['especializacao'] : null;
                $registroProfissional = !empty($_POST['registro_profissional']) ? $_POST['registro_profissional'] : null;
                $dataAdmissao = !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : date('Y-m-d');
                
                $sqlProf = "INSERT INTO professor (pessoa_id, matricula, formacao, especializacao, registro_profissional, data_admissao, ativo, criado_por)
                           VALUES (:pessoa_id, :matricula, :formacao, :especializacao, :registro_profissional, :data_admissao, 1, :criado_por)";
                $stmtProf = $conn->prepare($sqlProf);
                $stmtProf->bindParam(':pessoa_id', $pessoaId);
                $stmtProf->bindParam(':matricula', $matricula);
                $stmtProf->bindParam(':formacao', $formacao);
                $stmtProf->bindParam(':especializacao', $especializacao);
                $stmtProf->bindParam(':registro_profissional', $registroProfissional);
                $stmtProf->bindParam(':data_admissao', $dataAdmissao);
                $stmtProf->bindParam(':criado_por', $criadoPor);
                $stmtProf->execute();
                $professorId = $conn->lastInsertId();
                
                // 3. Criar usuário
                $senhaPadrao = $_POST['senha'] ?? '123456';
                $senhaHash = password_hash($senhaPadrao, PASSWORD_DEFAULT);
                $sqlUsuario = "INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo)
                              VALUES (:pessoa_id, :username, :senha_hash, 'PROFESSOR', 1)";
                $stmtUsuario = $conn->prepare($sqlUsuario);
                $stmtUsuario->bindParam(':pessoa_id', $pessoaId);
                $stmtUsuario->bindParam(':username', $username);
                $stmtUsuario->bindParam(':senha_hash', $senhaHash);
                $stmtUsuario->execute();
                
                // 4. Lotar professor na escola (se informado)
                if (!empty($_POST['escola_id'])) {
                    $escolaId = $_POST['escola_id'];
                    $cargaHoraria = !empty($_POST['carga_horaria']) ? $_POST['carga_horaria'] : null;
                    $observacaoLotacao = !empty($_POST['observacao_lotacao']) ? $_POST['observacao_lotacao'] : null;
                    
                    $sqlLotacao = "INSERT INTO professor_lotacao (professor_id, escola_id, inicio, carga_horaria, observacao, criado_em)
                                  VALUES (:professor_id, :escola_id, CURDATE(), :carga_horaria, :observacao, NOW())";
                    $stmtLotacao = $conn->prepare($sqlLotacao);
                    $stmtLotacao->bindParam(':professor_id', $professorId);
                    $stmtLotacao->bindParam(':escola_id', $escolaId);
                    $stmtLotacao->bindParam(':carga_horaria', $cargaHoraria);
                    $stmtLotacao->bindParam(':observacao', $observacaoLotacao);
                    $stmtLotacao->execute();
                }
                
                $conn->commit();
                $mensagem = 'Professor cadastrado com sucesso!';
                $tipoMensagem = 'success';
                
            } catch (Exception $e) {
                $conn->rollBack();
                $mensagem = 'Erro ao cadastrar professor: ' . $e->getMessage();
                $tipoMensagem = 'error';
            }
            break;
            
        case 'criar_responsavel':
            header('Content-Type: application/json');
            try {
                $dados = [
                    'nome' => trim($_POST['nome'] ?? ''),
                    'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''),
                    'data_nascimento' => !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null,
                    'sexo' => $_POST['sexo'] ?? null,
                    'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                    'telefone' => !empty($_POST['telefone']) ? preg_replace('/[^0-9]/', '', $_POST['telefone']) : null,
                    'senha' => $_POST['senha'] ?? ''
                ];
                
                if (empty($dados['nome']) || empty($dados['cpf'])) {
                    throw new Exception('Nome e CPF são obrigatórios');
                }
                
                if (strlen($dados['cpf']) !== 11) {
                    throw new Exception('CPF deve conter 11 dígitos');
                }
                
                if (empty($dados['senha']) || strlen($dados['senha']) < 6) {
                    throw new Exception('A senha é obrigatória e deve ter no mínimo 6 caracteres');
                }
                
                // Validar que alunos foram selecionados (obrigatório)
                $alunosJson = $_POST['alunos_ids'] ?? '[]';
                $alunos = json_decode($alunosJson, true);
                $parentesco = $_POST['parentesco'] ?? '';
                
                if (empty($parentesco)) {
                    throw new Exception('É obrigatório selecionar o parentesco');
                }
                
                if (empty($alunos) || !is_array($alunos) || count($alunos) === 0) {
                    throw new Exception('É obrigatório associar pelo menos um aluno ao responsável');
                }
                
                $resultado = $responsavelModel->criar($dados);
                
                if ($resultado['success']) {
                    // Associar alunos (obrigatório)
                    $associacao = $responsavelModel->associarAlunos($resultado['pessoa_id'], $alunos, $parentesco);
                    if (!$associacao['success']) {
                        // Responsável foi criado, mas associação falhou - isso é um erro crítico
                        throw new Exception('Responsável criado, mas houve erro ao associar alunos: ' . ($associacao['message'] ?? 'Erro desconhecido'));
                    }
                    
                    echo json_encode($resultado);
                } else {
                    echo json_encode(['success' => false, 'message' => $resultado['message'] ?? 'Erro ao criar responsável']);
                }
            } catch (Exception $e) {
                error_log("Erro ao criar responsável (gestao_escolar.php): " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'excluir_responsavel':
            header('Content-Type: application/json');
            try {
                $responsavelId = $_POST['responsavel_id'] ?? null;
                
                if (!$responsavelId) {
                    throw new Exception('ID do responsável não informado');
                }
                
                $resultado = $responsavelModel->excluir($responsavelId);
                
                if ($resultado['success']) {
                    echo json_encode(['success' => true, 'message' => 'Responsável excluído com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => $resultado['message'] ?? 'Erro ao excluir responsável']);
                }
            } catch (Exception $e) {
                error_log("Erro ao excluir responsável: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'associar_alunos':
            header('Content-Type: application/json');
            try {
                $responsavelId = $_POST['responsavel_id'] ?? null;
                $parentesco = $_POST['parentesco'] ?? 'OUTRO';
                $alunosJson = $_POST['alunos'] ?? '[]';
                $alunos = json_decode($alunosJson, true);
                
                if (!$responsavelId) {
                    throw new Exception('ID do responsável não informado');
                }
                
                if (empty($alunos) || !is_array($alunos)) {
                    throw new Exception('Nenhum aluno selecionado');
                }
                
                $resultado = $responsavelModel->associarAlunos($responsavelId, $alunos, $parentesco);
                
                if ($resultado['success']) {
                    echo json_encode(['success' => true, 'message' => 'Alunos associados com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => $resultado['message'] ?? 'Erro ao associar alunos']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'cadastrar_funcionario':
            $resultado = $funcionarioModel->criar([
                'cpf' => $_POST['cpf'] ?? '',
                'nome' => trim($_POST['nome'] ?? ''),
                'data_nascimento' => $_POST['data_nascimento'] ?: null,
                'sexo' => $_POST['sexo'] ?: null,
                'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'telefone' => !empty($_POST['telefone']) ? trim($_POST['telefone']) : null,
                'whatsapp' => !empty($_POST['whatsapp']) ? trim($_POST['whatsapp']) : null,
                'telefone_secundario' => !empty($_POST['telefone_secundario']) ? trim($_POST['telefone_secundario']) : null,
                'endereco' => !empty($_POST['endereco']) ? trim($_POST['endereco']) : null,
                'numero' => !empty($_POST['numero']) ? trim($_POST['numero']) : null,
                'complemento' => !empty($_POST['complemento']) ? trim($_POST['complemento']) : null,
                'bairro' => !empty($_POST['bairro']) ? trim($_POST['bairro']) : null,
                'cidade' => !empty($_POST['cidade']) ? trim($_POST['cidade']) : null,
                'estado' => !empty($_POST['estado']) ? trim($_POST['estado']) : null,
                'cep' => !empty($_POST['cep']) ? trim($_POST['cep']) : null,
                'nome_social' => !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null,
                'raca' => !empty($_POST['raca']) ? trim($_POST['raca']) : null,
                'matricula' => $_POST['matricula'] ?: null,
                'cargo' => $_POST['cargo'] ?? '',
                'setor' => $_POST['setor'] ?: null,
                'data_admissao' => $_POST['data_admissao'] ?: date('Y-m-d')
            ]);
            
            if ($resultado['success']) {
                // Criar usuário para o funcionário
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                try {
                    $funcionario = $funcionarioModel->buscarPorId($resultado['id']);
                    if ($funcionario) {
                        // Gerar username
                        $nome = $funcionario['nome'];
                        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $nome)[0]));
                        $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
                        $stmt->bindParam(':username', $username);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            $count = 1;
                            $newUsername = $username . $count;
                            while (true) {
                                $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
                                $stmt->bindParam(':username', $newUsername);
                                $stmt->execute();
                                if ($stmt->rowCount() == 0) {
                                    $username = $newUsername;
                                    break;
                                }
                                $count++;
                                $newUsername = $username . $count;
                            }
                        }
                        
                        // Criar usuário
                        $senhaHash = password_hash($_POST['senha'] ?? '123456', PASSWORD_DEFAULT);
                        $sqlUsuario = "INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo)
                                      VALUES (:pessoa_id, :username, :senha_hash, :role, 1)";
                        $stmtUsuario = $conn->prepare($sqlUsuario);
                        $stmtUsuario->bindParam(':pessoa_id', $funcionario['pessoa_id']);
                        $stmtUsuario->bindParam(':username', $username);
                        $stmtUsuario->bindParam(':senha_hash', $senhaHash);
                        $role = $_POST['role_funcionario'] ?? 'FUNCIONARIO';
                        $stmtUsuario->bindParam(':role', $role);
                        $stmtUsuario->execute();
                        
                        // Lotar funcionário na escola (se informado)
                        if (!empty($_POST['escola_id'])) {
                            $funcionarioModel->lotarEmEscola($resultado['id'], $_POST['escola_id'], $_POST['setor'] ?: null);
                        }
                    }
                } catch (Exception $e) {
                    // Log do erro, mas não falha o cadastro
                }
                
                $mensagem = 'Funcionário cadastrado com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'] ?? 'Erro ao cadastrar funcionário.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'registrar_desperdicio':
            if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
                try {
                    $motivo = $_POST['motivo'] ?? 'OUTROS';
                    
                    // Validar se observação foi preenchida quando motivo é OUTROS
                    if ($motivo === 'OUTROS') {
                        $observacoesOutros = trim($_POST['observacoes_outros'] ?? '');
                        if (empty($observacoesOutros)) {
                            $mensagem = 'Por favor, descreva o motivo do desperdício quando selecionar "Outro".';
                            $tipoMensagem = 'error';
                            break;
                        }
                    }
                    
                    $dados = [
                        'escola_id' => $escolaGestorId, // Usar escola do gestor logado
                        'data' => $_POST['data'] ?? date('Y-m-d'),
                        'turno' => $_POST['turno'] ?? null,
                        'produto_id' => $_POST['produto_id'] ?? null,
                        'quantidade' => $_POST['quantidade'] ?? null,
                        'unidade_medida' => $_POST['unidade_medida'] ?? null,
                        'peso_kg' => $_POST['peso_kg'] ?? null,
                        'motivo' => $motivo,
                        'motivo_detalhado' => $_POST['motivo_detalhado'] ?? null,
                        'observacoes' => $_POST['observacoes'] ?? null
                    ];
                    
                    // Se o motivo for OUTROS, usar o campo observações como motivo detalhado
                    if ($dados['motivo'] === 'OUTROS' && !empty($_POST['observacoes_outros'])) {
                        $dados['motivo_detalhado'] = $_POST['observacoes_outros'];
                    }
                    
                    $resultado = $desperdicioModel->registrar($dados);
                    
                    if ($resultado['success']) {
                        $mensagem = 'Desperdício registrado com sucesso!';
                        $tipoMensagem = 'success';
                    } else {
                        $mensagem = $resultado['message'] ?? 'Erro ao registrar desperdício.';
                        $tipoMensagem = 'error';
                    }
                } catch (Exception $e) {
                    $mensagem = 'Erro ao registrar desperdício: ' . $e->getMessage();
                    $tipoMensagem = 'error';
                }
            } else {
                $mensagem = 'Acesso não autorizado ou escola não encontrada.';
                $tipoMensagem = 'error';
            }
            break;
    }
}

// Buscar turmas por escola (AJAX)
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_turmas' && !empty($_GET['escola_id'])) {
    header('Content-Type: application/json');
    
    $escolaId = $_GET['escola_id'];
    
    // Validar permissão: gestor só pode ver turmas da sua escola
    if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId && $escolaId != $escolaGestorId) {
        echo json_encode([
            'success' => false,
            'message' => 'Você não tem permissão para visualizar turmas desta escola.'
        ]);
        exit;
    }
    
    $turmas = $turmaModel->listar(['escola_id' => $escolaId, 'ativo' => 1]);
    
    echo json_encode([
        'success' => true,
        'turmas' => $turmas
    ]);
    exit;
}

// Buscar aluno por ID (AJAX)
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_aluno' && !empty($_GET['aluno_id'])) {
    header('Content-Type: application/json');
    
    $alunoId = $_GET['aluno_id'];
    $aluno = $alunoModel->buscarPorId($alunoId);
    
    if ($aluno) {
        echo json_encode([
            'success' => true,
            'aluno' => $aluno
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não encontrado'
        ]);
    }
    exit;
}

// Buscar aluno com turma atual (AJAX)
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_aluno_com_turma' && !empty($_GET['aluno_id'])) {
    header('Content-Type: application/json');
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $alunoId = $_GET['aluno_id'];
    
    $sql = "SELECT 
                a.id,
                p.nome,
                p.cpf,
                a.matricula,
                at.turma_id,
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
            FROM aluno a
            INNER JOIN pessoa p ON a.pessoa_id = p.id
            LEFT JOIN aluno_turma at ON at.aluno_id = a.id AND at.fim IS NULL
            LEFT JOIN turma t ON at.turma_id = t.id
            WHERE a.id = :aluno_id AND a.ativo = 1
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':aluno_id', $alunoId);
    $stmt->execute();
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($aluno) {
        echo json_encode([
            'success' => true,
            'aluno' => $aluno
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não encontrado'
        ]);
    }
    exit;
}

// Buscar alunos por termo (AJAX)
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_alunos' && !empty($_GET['termo'])) {
    header('Content-Type: application/json');
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $termo = '%' . $_GET['termo'] . '%';
    
    $sql = "SELECT 
                a.id,
                p.nome,
                p.cpf,
                a.matricula,
                at.turma_id,
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
            FROM aluno a
            INNER JOIN pessoa p ON a.pessoa_id = p.id
            LEFT JOIN aluno_turma at ON at.aluno_id = a.id AND at.fim IS NULL
            LEFT JOIN turma t ON at.turma_id = t.id
            WHERE a.ativo = 1 
            AND (p.nome LIKE :termo OR p.cpf LIKE :termo OR a.matricula LIKE :termo)";
    
    // Filtrar por escola do gestor se necessário
    if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
        $sql .= " AND (a.escola_id = :escola_id OR t.escola_id = :escola_id)";
    }
    
    $sql .= " ORDER BY p.nome ASC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':termo', $termo);
    if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
        $stmt->bindParam(':escola_id', $escolaGestorId);
    }
    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'alunos' => $alunos
    ]);
    exit;
}

// Buscar dados
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar escolas (filtrar pela escola do gestor se necessário)
$isGestorComEscola = isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO' && !empty($escolaGestorId) && $escolaGestorId > 0;
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1";
if ($isGestorComEscola) {
    // Gestor só vê sua escola
    $sqlEscolas .= " AND id = :escola_id";
}
$sqlEscolas .= " ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
if ($isGestorComEscola) {
    $stmtEscolas->bindParam(':escola_id', $escolaGestorId, PDO::PARAM_INT);
}
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar turmas
$filtrosTurma = ['ativo' => 1];
// Se for gestor, forçar filtro pela escola selecionada
if ($_SESSION['tipo'] === 'GESTAO') {
    // Priorizar escola selecionada na sessão
    $escolaIdTurmas = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? $escolaGestorId ?? null;
    if ($escolaIdTurmas) {
        $filtrosTurma['escola_id'] = $escolaIdTurmas;
        error_log("DEBUG TURMAS - Filtro escola_id para gestor: " . $escolaIdTurmas);
    }
} elseif (!empty($_GET['escola_id'])) {
    // Admin pode filtrar por escola específica
    $filtrosTurma['escola_id'] = $_GET['escola_id'];
}
if (!empty($_GET['ano_letivo'])) {
    $filtrosTurma['ano_letivo'] = $_GET['ano_letivo'];
}
$turmas = $turmaModel->listar($filtrosTurma);
error_log("DEBUG TURMAS - Total de turmas encontradas: " . count($turmas));
error_log("DEBUG TURMAS - Filtros aplicados: " . json_encode($filtrosTurma));

// Buscar séries para os formulários
$sqlSeries = "SELECT id, nome, codigo FROM serie WHERE ativo = 1 ORDER BY ordem ASC";
$stmtSeries = $conn->prepare($sqlSeries);
$stmtSeries->execute();
$series = $stmtSeries->fetchAll(PDO::FETCH_ASSOC);

// Buscar disciplinas para atribuição de professores
$sqlDisciplinas = "SELECT id, nome, codigo FROM disciplina WHERE ativo = 1 ORDER BY nome ASC";
$stmtDisciplinas = $conn->prepare($sqlDisciplinas);
$stmtDisciplinas->execute();
$disciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);

// Buscar professores com suas atribuições
function buscarProfessoresComAtribuicoes($escolaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                pr.id as professor_id,
                p.nome as nome_professor,
                p.email,
                p.telefone,
                pr.matricula,
                pl.inicio as lotacao_inicio,
                pl.carga_horaria,
                COALESCE(GROUP_CONCAT(DISTINCT CONCAT(t.serie, ' ', t.letra, ' - ', d.nome) SEPARATOR ', '), '') as atribuicoes,
                COALESCE(COUNT(DISTINCT CASE WHEN tp.fim IS NULL THEN tp.turma_id END), 0) as total_turmas
            FROM professor pr
            INNER JOIN pessoa p ON pr.pessoa_id = p.id
            INNER JOIN usuario u ON u.pessoa_id = p.id
            INNER JOIN professor_lotacao pl ON pl.professor_id = pr.id 
                AND pl.escola_id = :escola_id 
                AND pl.fim IS NULL
            LEFT JOIN turma_professor tp ON tp.professor_id = pr.id AND tp.fim IS NULL
            LEFT JOIN turma t ON tp.turma_id = t.id AND t.escola_id = :escola_id
            LEFT JOIN disciplina d ON tp.disciplina_id = d.id
            WHERE pr.ativo = 1 AND u.ativo = 1";
    
    if ($escolaId) {
        $sql .= " AND (t.escola_id = :escola_id OR t.escola_id IS NULL)";
    }
    
    $sql .= " GROUP BY pr.id, p.nome, p.email, p.telefone, pr.matricula
              ORDER BY p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':escola_id', $escolaId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Filtrar professores pela escola do gestor se necessário
$escolaIdProfessores = null;
if ($_SESSION['tipo'] === 'GESTAO') {
    // Priorizar escola selecionada na sessão
    $escolaIdProfessores = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? $escolaGestorId ?? null;
} elseif (!empty($_GET['escola_id'])) {
    $escolaIdProfessores = $_GET['escola_id'];
}
$professoresComAtribuicoes = buscarProfessoresComAtribuicoes($escolaIdProfessores);

// Função para buscar dados de acompanhamento acadêmico
function buscarAcompanhamentoAcademico($turmaId = null, $escolaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                a.id as aluno_id,
                p.nome as nome_aluno,
                p.cpf,
                a.matricula,
                CONCAT(t.serie, ' ', t.letra) as turma_nome,
                t.id as turma_id,
                COALESCE(AVG(n.nota), 0) as media_geral,
                COUNT(DISTINCT n.id) as total_notas,
                COALESCE(SUM(CASE WHEN f.presenca = 1 THEN 1 ELSE 0 END), 0) as dias_presentes,
                COALESCE(SUM(CASE WHEN f.presenca = 0 THEN 1 ELSE 0 END), 0) as dias_faltas,
                COALESCE(COUNT(DISTINCT f.id), 0) as total_dias_registrados,
                CASE 
                    WHEN COUNT(DISTINCT f.id) > 0 THEN 
                        ROUND((SUM(CASE WHEN f.presenca = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT f.id)) * 100, 1)
                    ELSE 0 
                END as percentual_frequencia
            FROM aluno a
            INNER JOIN pessoa p ON a.pessoa_id = p.id
            INNER JOIN aluno_turma at ON a.id = at.aluno_id AND at.fim IS NULL
            INNER JOIN turma t ON at.turma_id = t.id
            LEFT JOIN nota n ON n.aluno_id = a.id AND n.turma_id = t.id
            LEFT JOIN frequencia f ON f.aluno_id = a.id AND f.turma_id = t.id
            WHERE a.ativo = 1";
    
    if ($turmaId) {
        $sql .= " AND t.id = :turma_id";
    }
    
    if ($escolaId) {
        $sql .= " AND t.escola_id = :escola_id";
    }
    
    $sql .= " GROUP BY a.id, p.nome, p.cpf, a.matricula, t.serie, t.letra, t.id
              ORDER BY media_geral DESC, p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    if ($turmaId) {
        $stmt->bindParam(':turma_id', $turmaId);
    }
    if ($escolaId) {
        $stmt->bindParam(':escola_id', $escolaId);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar estatísticas gerais de acompanhamento
function buscarEstatisticasAcompanhamento($turmaId = null, $escolaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                COUNT(DISTINCT a.id) as total_alunos,
                COALESCE(AVG(media_aluno.media), 0) as media_geral_turma,
                COALESCE(AVG(freq_aluno.frequencia), 0) as frequencia_media,
                COUNT(DISTINCT CASE WHEN media_aluno.media >= 7 THEN a.id END) as aprovados,
                COUNT(DISTINCT CASE WHEN media_aluno.media < 7 AND media_aluno.media >= 5 THEN a.id END) as recuperacao,
                COUNT(DISTINCT CASE WHEN media_aluno.media < 5 THEN a.id END) as reprovados
            FROM aluno a
            INNER JOIN aluno_turma at ON a.id = at.aluno_id AND at.fim IS NULL
            INNER JOIN turma t ON at.turma_id = t.id
            LEFT JOIN (
                SELECT aluno_id, turma_id, AVG(nota) as media
                FROM nota
                GROUP BY aluno_id, turma_id
            ) media_aluno ON media_aluno.aluno_id = a.id AND media_aluno.turma_id = t.id
            LEFT JOIN (
                SELECT aluno_id, turma_id, 
                       CASE 
                           WHEN COUNT(*) > 0 THEN 
                               (SUM(CASE WHEN presenca = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100
                           ELSE 0 
                       END as frequencia
                FROM frequencia
                GROUP BY aluno_id, turma_id
            ) freq_aluno ON freq_aluno.aluno_id = a.id AND freq_aluno.turma_id = t.id
            WHERE a.ativo = 1";
    
    if ($turmaId) {
        $sql .= " AND t.id = :turma_id";
    }
    
    if ($escolaId) {
        $sql .= " AND t.escola_id = :escola_id";
    }
    
    $stmt = $conn->prepare($sql);
    if ($turmaId) {
        $stmt->bindParam(':turma_id', $turmaId);
    }
    if ($escolaId) {
        $stmt->bindParam(':escola_id', $escolaId);
    }
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$filtroTurmaAcompanhamento = !empty($_GET['turma_acompanhamento']) ? $_GET['turma_acompanhamento'] : null;
// Filtrar acompanhamento acadêmico pela escola do gestor se necessário
$escolaIdAcompanhamento = null;
if ($_SESSION['tipo'] === 'GESTAO') {
    // Priorizar escola selecionada na sessão
    $escolaIdAcompanhamento = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? $escolaGestorId ?? null;
} elseif (!empty($_GET['escola_id'])) {
    $escolaIdAcompanhamento = $_GET['escola_id'];
}
$acompanhamentoDados = buscarAcompanhamentoAcademico($filtroTurmaAcompanhamento, $escolaIdAcompanhamento);
$estatisticasAcompanhamento = buscarEstatisticasAcompanhamento($filtroTurmaAcompanhamento, $escolaIdAcompanhamento);

// Função para buscar lançamentos pendentes de validação
function buscarLancamentosPendentes($tipoRegistro = null, $escolaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $lancamentos = [];
    
    // Buscar notas pendentes
    if (!$tipoRegistro || $tipoRegistro === 'NOTA') {
        $sql = "SELECT 
                    n.id,
                    'NOTA' as tipo,
                    n.nota,
                    n.bimestre,
                    p.nome as aluno_nome,
                    d.nome as disciplina_nome,
                    CONCAT(t.serie, ' ', t.letra) as turma_nome,
                    u.username as lancado_por,
                    DATE_FORMAT(n.lancado_em, '%d/%m/%Y %H:%i') as data_lancamento,
                    n.comentario,
                    t.escola_id
                FROM nota n
                INNER JOIN aluno a ON n.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                INNER JOIN disciplina d ON n.disciplina_id = d.id
                INNER JOIN turma t ON n.turma_id = t.id
                LEFT JOIN usuario u ON n.lancado_por = u.id
                WHERE (n.validado = 0 OR n.validado IS NULL)";
        
        if ($escolaId) {
            $sql .= " AND t.escola_id = :escola_id";
        }
        
        $sql .= " ORDER BY n.lancado_em DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($escolaId) {
            $stmt->bindParam(':escola_id', $escolaId);
        }
        $stmt->execute();
        $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notas as $nota) {
            $lancamentos[] = $nota;
        }
    }
    
    // Buscar frequências pendentes
    if (!$tipoRegistro || $tipoRegistro === 'FREQUENCIA') {
        $sql = "SELECT 
                    f.id,
                    'FREQUENCIA' as tipo,
                    CASE WHEN f.presenca = 1 THEN 'Presente' ELSE 'Falta' END as status_frequencia,
                    p.nome as aluno_nome,
                    CONCAT(t.serie, ' ', t.letra) as turma_nome,
                    u.username as lancado_por,
                    DATE_FORMAT(f.data, '%d/%m/%Y') as data_lancamento,
                    DATE_FORMAT(f.registrado_em, '%d/%m/%Y %H:%i') as data_registro,
                    f.observacao as comentario,
                    t.escola_id
                FROM frequencia f
                INNER JOIN aluno a ON f.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                INNER JOIN turma t ON f.turma_id = t.id
                LEFT JOIN usuario u ON f.registrado_por = u.id
                WHERE (f.validado = 0 OR f.validado IS NULL)";
        
        if ($escolaId) {
            $sql .= " AND t.escola_id = :escola_id";
        }
        
        $sql .= " ORDER BY f.registrado_em DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($escolaId) {
            $stmt->bindParam(':escola_id', $escolaId);
        }
        $stmt->execute();
        $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($frequencias as $freq) {
            $lancamentos[] = $freq;
        }
    }
    
    // Ordenar por data de lançamento
    usort($lancamentos, function($a, $b) {
        return strtotime(str_replace('/', '-', $b['data_lancamento'] ?? $b['data_registro'] ?? '')) 
               - strtotime(str_replace('/', '-', $a['data_lancamento'] ?? $a['data_registro'] ?? ''));
    });
    
    return $lancamentos;
}

// Função para contar lançamentos pendentes por tipo
function contarLancamentosPendentes($escolaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                'NOTA' as tipo,
                COUNT(*) as total
            FROM nota n
            INNER JOIN turma t ON n.turma_id = t.id
            WHERE (n.validado = 0 OR n.validado IS NULL)";
    
    if ($escolaId) {
        $sql .= " AND t.escola_id = :escola_id";
    }
    
    $stmt = $conn->prepare($sql);
    if ($escolaId) {
        $stmt->bindParam(':escola_id', $escolaId);
    }
    $stmt->execute();
    $notas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sql = "SELECT 
                'FREQUENCIA' as tipo,
                COUNT(*) as total
            FROM frequencia f
            INNER JOIN turma t ON f.turma_id = t.id
            WHERE (f.validado = 0 OR f.validado IS NULL)";
    
    if ($escolaId) {
        $sql .= " AND t.escola_id = :escola_id";
    }
    
    $stmt = $conn->prepare($sql);
    if ($escolaId) {
        $stmt->bindParam(':escola_id', $escolaId);
    }
    $stmt->execute();
    $frequencias = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'NOTA' => $notas['total'] ?? 0,
        'FREQUENCIA' => $frequencias['total'] ?? 0,
        'TOTAL' => ($notas['total'] ?? 0) + ($frequencias['total'] ?? 0)
    ];
}

// Filtrar lançamentos pendentes pela escola do gestor se necessário
$escolaIdLancamentos = null;
if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
    $escolaIdLancamentos = $escolaGestorId;
} elseif (!empty($_GET['escola_id'])) {
    $escolaIdLancamentos = $_GET['escola_id'];
}
$filtroTipoValidacao = !empty($_GET['tipo_validacao']) ? $_GET['tipo_validacao'] : null;
$lancamentosPendentes = buscarLancamentosPendentes($filtroTipoValidacao, $escolaIdLancamentos);
$contadoresValidacao = contarLancamentosPendentes($escolaIdLancamentos);

// Processar requisições AJAX
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_turma' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    
    $turmaId = $_GET['id'];
    $turma = $turmaModel->buscarPorId($turmaId);
    
    if ($turma) {
        // Validar permissão: gestor só pode ver turmas da sua escola
        if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId && $turma['escola_id'] != $escolaGestorId) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para visualizar esta turma.'
            ]);
            exit;
        }
        
        $alunos = $turmaModel->buscarAlunos($turmaId);
        $professores = $turmaModel->buscarProfessores($turmaId);
        
        echo json_encode([
            'success' => true,
            'turma' => $turma,
            'alunos' => $alunos,
            'professores' => $professores
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Turma não encontrada'
        ]);
    }
    exit;
}

// Buscar detalhes do professor
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_professor' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    
    $professorId = $_GET['id'];
    
    $sql = "SELECT pr.id, p.nome, p.email, p.telefone, pr.matricula
            FROM professor pr
            INNER JOIN pessoa p ON pr.pessoa_id = p.id
            WHERE pr.id = :professor_id AND pr.ativo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':professor_id', $professorId);
    $stmt->execute();
    $professor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($professor) {
        // Buscar atribuições do professor
        $sqlAtribuicoes = "SELECT 
                            CONCAT(t.serie, ' ', t.letra) as turma,
                            d.nome as disciplina,
                            tp.regime,
                            DATE_FORMAT(tp.inicio, '%d/%m/%Y') as inicio,
                            e.nome as escola_nome,
                            t.escola_id
                          FROM turma_professor tp
                          INNER JOIN turma t ON tp.turma_id = t.id
                          INNER JOIN disciplina d ON tp.disciplina_id = d.id
                          INNER JOIN escola e ON t.escola_id = e.id
                          WHERE tp.professor_id = :professor_id AND tp.fim IS NULL";
        
        // Filtrar por escola do gestor se necessário
        if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
            $sqlAtribuicoes .= " AND t.escola_id = :escola_id";
        }
        
        $sqlAtribuicoes .= " ORDER BY t.serie, t.letra, d.nome";
        
        $stmtAtrib = $conn->prepare($sqlAtribuicoes);
        $stmtAtrib->bindParam(':professor_id', $professorId);
        if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
            $stmtAtrib->bindParam(':escola_id', $escolaGestorId);
        }
        $stmtAtrib->execute();
        $atribuicoes = $stmtAtrib->fetchAll(PDO::FETCH_ASSOC);
        
        // Se for gestor e o professor não tiver atribuições na escola dele, negar acesso
        if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId && empty($atribuicoes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para visualizar este professor.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'professor' => $professor,
            'atribuicoes' => $atribuicoes
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Professor não encontrado'
        ]);
    }
    exit;
}

// Buscar detalhes de acompanhamento do aluno
if (!empty($_GET['acao']) && $_GET['acao'] === 'listar_responsaveis') {
    header('Content-Type: application/json');
    try {
        $filtros = [
            'busca' => $_GET['busca'] ?? '',
            'ativo' => 1
        ];
        $responsaveis = $responsavelModel->listar($filtros);
        echo json_encode(['success' => true, 'responsaveis' => $responsaveis]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_alunos' && !empty($_GET['busca'])) {
    header('Content-Type: application/json');
    try {
        $busca = trim($_GET['busca']);
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT a.id, p.nome, a.matricula, e.nome as escola_nome
                FROM aluno a
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON a.escola_id = e.id
                WHERE a.ativo = 1 
                AND (p.nome LIKE :busca OR a.matricula LIKE :busca OR p.cpf LIKE :busca)
                ORDER BY p.nome ASC
                LIMIT 20";
        
        $stmt = $conn->prepare($sql);
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'alunos' => $alunos]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_aluno_acompanhamento' && !empty($_GET['aluno_id']) && !empty($_GET['turma_id'])) {
    header('Content-Type: application/json');
    
    $alunoId = $_GET['aluno_id'];
    $turmaId = $_GET['turma_id'];
    
    // Buscar dados básicos do aluno
    $sql = "SELECT 
                a.id, a.matricula,
                p.nome,
                CONCAT(t.serie, ' ', t.letra) as turma_nome,
                COALESCE(AVG(n.nota), 0) as media_geral,
                COALESCE(SUM(CASE WHEN f.presenca = 1 THEN 1 ELSE 0 END), 0) as dias_presentes,
                COALESCE(SUM(CASE WHEN f.presenca = 0 THEN 1 ELSE 0 END), 0) as dias_faltas,
                CASE 
                    WHEN COUNT(DISTINCT f.id) > 0 THEN 
                        ROUND((SUM(CASE WHEN f.presenca = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT f.id)) * 100, 1)
                    ELSE 0 
                END as percentual_frequencia
            FROM aluno a
            INNER JOIN pessoa p ON a.pessoa_id = p.id
            INNER JOIN aluno_turma at ON a.id = at.aluno_id AND at.turma_id = :turma_id AND at.fim IS NULL
            INNER JOIN turma t ON at.turma_id = t.id
            LEFT JOIN nota n ON n.aluno_id = a.id AND n.turma_id = t.id
            LEFT JOIN frequencia f ON f.aluno_id = a.id AND f.turma_id = t.id
            WHERE a.id = :aluno_id AND a.ativo = 1
            GROUP BY a.id, a.matricula, p.nome, t.serie, t.letra";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':aluno_id', $alunoId);
    $stmt->bindParam(':turma_id', $turmaId);
    $stmt->execute();
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($aluno) {
        // Buscar notas por disciplina
        $sqlNotas = "SELECT 
                        n.id,
                        d.nome as disciplina_nome,
                        n.nota,
                        n.bimestre,
                        n.recuperacao,
                        DATE_FORMAT(n.lancado_em, '%d/%m/%Y') as data_lancamento
                     FROM nota n
                     INNER JOIN disciplina d ON n.disciplina_id = d.id
                     WHERE n.aluno_id = :aluno_id AND n.turma_id = :turma_id
                     ORDER BY d.nome, n.bimestre, n.lancado_em DESC";
        
        $stmtNotas = $conn->prepare($sqlNotas);
        $stmtNotas->bindParam(':aluno_id', $alunoId);
        $stmtNotas->bindParam(':turma_id', $turmaId);
        $stmtNotas->execute();
        $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'aluno' => $aluno,
            'notas' => $notas
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não encontrado'
        ]);
    }
    exit;
}

// Buscar alunos
$filtrosAluno = ['ativo' => 1];
if (!empty($_GET['escola_id'])) {
    $filtrosAluno['escola_id'] = $_GET['escola_id'];
}
$alunos = $alunoModel->listar($filtrosAluno);

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Escolar - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global-theme.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
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
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
        
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
        
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
        
        /* Corrigir setas duplicadas em selects */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        select:focus {
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23059669' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        }
    </style>
    <script src="js/modal-alerts.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>
    
    <!-- Sidebar -->
    <?php if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'ADM') { ?>
        <?php include('components/sidebar_adm.php'); ?>
    <?php } else { ?>
        <?php include('components/sidebar_gestao.php'); ?>
    <?php } ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Gestão Escolar</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
                            <button onclick="abrirModalRegistrarDesperdicio()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span class="hidden sm:inline">Registrar Desperdício</span>
                            </button>
                        <?php endif; ?>
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } elseif ($_SESSION['tipo'] === 'GESTAO') { ?>
                                <!-- Para GESTAO, mostrar nome da escola -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php 
                                            // Priorizar escola da sessão, depois variável, depois buscar
                                            $escolaNomeExibir = $_SESSION['escola_selecionada_nome'] ?? $_SESSION['escola_atual'] ?? $escolaGestor ?? null;
                                            
                                            if (!empty($escolaNomeExibir)) {
                                                echo htmlspecialchars($escolaNomeExibir);
                                            } else {
                                                // Se ainda não tem, buscar da sessão escola_id
                                                $escolaIdSessao = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
                                                if ($escolaIdSessao) {
                                                    try {
                                                        $sqlBuscarNome = "SELECT nome FROM escola WHERE id = :escola_id AND ativo = 1 LIMIT 1";
                                                        $stmtBuscarNome = $conn->prepare($sqlBuscarNome);
                                                        $stmtBuscarNome->bindParam(':escola_id', $escolaIdSessao, PDO::PARAM_INT);
                                                        $stmtBuscarNome->execute();
                                                        $resultNome = $stmtBuscarNome->fetch(PDO::FETCH_ASSOC);
                                                        if ($resultNome && !empty($resultNome['nome'])) {
                                                            echo htmlspecialchars($resultNome['nome']);
                                                            // Atualizar variáveis e sessão
                                                            $escolaGestor = $resultNome['nome'];
                                                            $escolaGestorId = (int)$escolaIdSessao;
                                                            $_SESSION['escola_selecionada_nome'] = $resultNome['nome'];
                                                            $_SESSION['escola_atual'] = $resultNome['nome'];
                                                        } else {
                                                            echo 'Escola não encontrada';
                                                        }
                                                    } catch (Exception $e) {
                                                        echo 'Erro ao buscar escola';
                                                        error_log("DEBUG HEADER - Erro: " . $e->getMessage());
                                                    }
                                                } else {
                                                    // Última tentativa: buscar qualquer escola do gestor
                                                    $usuarioIdDebug = $_SESSION['usuario_id'] ?? null;
                                                    if ($usuarioIdDebug) {
                                                        try {
                                                            $sqlDebug = "SELECT e.nome as escola_nome, e.id as escola_id
                                                                         FROM gestor g
                                                                         INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                                                                         INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                                                                         INNER JOIN escola e ON gl.escola_id = e.id
                                                                         WHERE u.id = :usuario_id AND g.ativo = 1 AND e.ativo = 1
                                                                         AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                                                                         ORDER BY gl.responsavel DESC, gl.inicio DESC
                                                                         LIMIT 1";
                                                            $stmtDebug = $conn->prepare($sqlDebug);
                                                            $stmtDebug->bindParam(':usuario_id', $usuarioIdDebug);
                                                            $stmtDebug->execute();
                                                            $resultDebug = $stmtDebug->fetch(PDO::FETCH_ASSOC);
                                                            if ($resultDebug && !empty($resultDebug['escola_nome'])) {
                                                                echo htmlspecialchars($resultDebug['escola_nome']);
                                                                // Atualizar variáveis e sessão
                                                                $escolaGestor = $resultDebug['escola_nome'];
                                                                $escolaGestorId = (int)$resultDebug['escola_id'];
                                                                $_SESSION['escola_selecionada_id'] = $escolaGestorId;
                                                                $_SESSION['escola_selecionada_nome'] = $resultDebug['escola_nome'];
                                                                $_SESSION['escola_id'] = $escolaGestorId;
                                                                $_SESSION['escola_atual'] = $resultDebug['escola_nome'];
                                                                error_log("DEBUG HEADER - Escola encontrada diretamente: " . $resultDebug['escola_nome']);
                                                            } else {
                                                                echo 'Escola não encontrada';
                                                                error_log("DEBUG HEADER - Nenhuma escola encontrada para usuario_id: " . $usuarioIdDebug);
                                                            }
                                                        } catch (Exception $e) {
                                                            echo 'Erro ao buscar escola';
                                                            error_log("DEBUG HEADER - Erro: " . $e->getMessage());
                                                        }
                                                    } else {
                                                        echo 'Escola não encontrada';
                                                        error_log("DEBUG HEADER - usuario_id é NULL");
                                                    }
                                                }
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php echo $_SESSION['escola_atual'] ?? 'Escola Municipal'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="max-w-7xl mx-auto">
        <!-- Mensagens -->
        <?php if ($mensagem): ?>
            <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Tabs de Navegação -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-8 overflow-x-auto">
                <button onclick="mostrarAba('turmas')" id="tab-turmas" class="tab-button py-4 px-1 border-b-2 border-primary-green font-medium text-sm text-primary-green">
                    Turmas
                </button>
                <button onclick="mostrarAba('matriculas')" id="tab-matriculas" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Matrículas
                </button>
                <button onclick="mostrarAba('professores')" id="tab-professores" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Professores
                </button>
                <button onclick="mostrarAba('acompanhamento')" id="tab-acompanhamento" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Acompanhamento
                </button>
                <button onclick="mostrarAba('validacao')" id="tab-validacao" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Validação
                </button>
                <button onclick="mostrarAba('cadastros')" id="tab-cadastros" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Cadastros
                </button>
                <button onclick="mostrarAba('responsaveis')" id="tab-responsaveis" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Responsáveis
                </button>
                <button onclick="mostrarAba('relatorios')" id="tab-relatorios" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Relatórios
                </button>
            </nav>
        </div>

        <!-- Conteúdo das Abas -->
        
        <!-- ABA: TURMAS -->
        <div id="conteudo-turmas" class="aba-conteudo">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Gerenciamento de Turmas</h2>
                    <button onclick="abrirModalCriarTurma()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-secondary-green transition-colors">
                        + Nova Turma
                    </button>
                </div>

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <?php 
                    // Verificar se é gestor com escola associada
                    $isGestorComEscola = isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO' && !empty($escolaGestorId) && $escolaGestorId > 0;
                    if ($isGestorComEscola): 
                    ?>
                    <!-- Gestor só vê sua escola - campo informativo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                        <input type="hidden" id="filtro-escola" value="<?= $escolaGestorId ?>">
                        <input type="text" value="<?= htmlspecialchars($escolaGestor ?? 'Escola não encontrada') ?>" disabled 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                        <select id="filtro-ano" onchange="filtrarTurmas()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <option value="">Todos os anos</option>
                            <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                                <option value="<?= $ano ?>" <?= (!empty($_GET['ano_letivo']) && $_GET['ano_letivo'] == $ano) ? 'selected' : '' ?>>
                                    <?= $ano ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" id="busca-turma" placeholder="Buscar turma..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                    </div>
                </div>

                <!-- Lista de Turmas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professores</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($turmas)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhuma turma encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($turmas as $turma): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars(($turma['serie'] ?? '') . ' ' . ($turma['letra'] ?? '') . ' - ' . ($turma['turno'] ?? '')) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($turma['escola_nome'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($turma['ano_letivo'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $turma['total_alunos'] ?? 0 ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $turma['total_professores'] ?? 0 ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="verDetalhesTurma(<?= $turma['id'] ?>)" class="text-primary-green hover:text-secondary-green mr-3">Ver</button>
                                            <button onclick="editarTurma(<?= $turma['id'] ?>)" class="text-blue-600 hover:text-blue-800 mr-3">Editar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA: MATRÍCULAS -->
        <div id="conteudo-matriculas" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Matrícula e Alocação de Alunos</h2>
                    <button onclick="abrirModalMatricularAluno()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-secondary-green transition-colors">
                        + Matricular Aluno
                    </button>
                </div>

                <!-- Lista de Alunos -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma Atual</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($alunos)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum aluno encontrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($aluno['nome'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($aluno['cpf'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            // Buscar turma atual do aluno
                                            $sqlTurmaAluno = "SELECT CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                                                             FROM aluno_turma at
                                                             INNER JOIN turma t ON at.turma_id = t.id
                                                             WHERE at.aluno_id = :aluno_id AND at.fim IS NULL
                                                             LIMIT 1";
                                            $stmtTurmaAluno = $conn->prepare($sqlTurmaAluno);
                                            $stmtTurmaAluno->bindParam(':aluno_id', $aluno['id']);
                                            $stmtTurmaAluno->execute();
                                            $turmaAluno = $stmtTurmaAluno->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($turmaAluno['turma_nome'] ?? 'Sem turma');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="matricularAluno(<?= $aluno['id'] ?>)" class="text-primary-green hover:text-secondary-green mr-3">Matricular</button>
                                            <button onclick="transferirAluno(<?= $aluno['id'] ?>)" class="text-blue-600 hover:text-blue-800">Transferir</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA: PROFESSORES -->
        <div id="conteudo-professores" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Atribuição de Professores</h2>
                    <div class="text-sm text-gray-600">
                        Total: <span class="font-semibold"><?= count($professoresComAtribuicoes) ?> professor(es)</span>
                    </div>
                </div>
                
                <?php if (empty($professoresComAtribuicoes)): ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="text-gray-600">Nenhum professor encontrado.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turmas Atribuídas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($professoresComAtribuicoes as $prof): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-primary-green rounded-full flex items-center justify-center">
                                                <span class="text-white font-medium text-sm">
                                                    <?= strtoupper(substr($prof['nome_professor'], 0, 2)) ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($prof['nome_professor']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($prof['matricula'] ?? 'Não informado') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div><?= htmlspecialchars($prof['email'] ?? 'Não informado') ?></div>
                                        <?php if (!empty($prof['telefone'])): ?>
                                            <div class="text-xs text-gray-400"><?= htmlspecialchars($prof['telefone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php if (!empty($prof['atribuicoes'])): ?>
                                            <div class="max-w-xs">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-1">
                                                    <?= $prof['total_turmas'] ?> turma(s)
                                                </span>
                                                <div class="text-xs text-gray-600 mt-1">
                                                    <?= htmlspecialchars($prof['atribuicoes']) ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">Nenhuma atribuição</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="verDetalhesProfessor(<?= $prof['professor_id'] ?>)" 
                                                class="text-primary-green hover:text-secondary-green mr-3" title="Ver detalhes">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ABA: ACOMPANHAMENTO -->
        <div id="conteudo-acompanhamento" class="aba-conteudo hidden">
            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Acompanhamento Acadêmico</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="escola_id" value="<?= htmlspecialchars($_GET['escola_id'] ?? '') ?>">
                    <div>
                        <label for="turma_acompanhamento" class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Turma</label>
                        <select id="turma_acompanhamento" name="turma_acompanhamento" 
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                onchange="this.form.submit()">
                            <option value="">Todas as turmas</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>" <?= ($filtroTurmaAcompanhamento == $turma['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($turma['serie'] . ' ' . $turma['letra'] . ' - ' . $turma['turno']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Estatísticas Gerais -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total de Alunos</p>
                            <p class="text-3xl font-bold text-blue-600"><?= $estatisticasAcompanhamento['total_alunos'] ?? 0 ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Média Geral</p>
                            <p class="text-3xl font-bold text-green-600">
                                <?= number_format($estatisticasAcompanhamento['media_geral_turma'] ?? 0, 1, ',', '.') ?>
                            </p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Frequência Média</p>
                            <p class="text-3xl font-bold text-purple-600">
                                <?= number_format($estatisticasAcompanhamento['frequencia_media'] ?? 0, 1, ',', '.') ?>%
                            </p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Aprovados</p>
                            <p class="text-3xl font-bold text-orange-600"><?= $estatisticasAcompanhamento['aprovados'] ?? 0 ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                $total = $estatisticasAcompanhamento['total_alunos'] ?? 1;
                                $percentual = $total > 0 ? round(($estatisticasAcompanhamento['aprovados'] ?? 0) / $total * 100, 1) : 0;
                                echo $percentual . '% do total';
                                ?>
                            </p>
                        </div>
                        <div class="bg-orange-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Distribuição de Situação -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Situação dos Alunos</h3>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-700">Aprovados</span>
                            </div>
                            <span class="font-semibold text-gray-900"><?= $estatisticasAcompanhamento['aprovados'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-700">Recuperação</span>
                            </div>
                            <span class="font-semibold text-gray-900"><?= $estatisticasAcompanhamento['recuperacao'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-700">Reprovados</span>
                            </div>
                            <span class="font-semibold text-gray-900"><?= $estatisticasAcompanhamento['reprovados'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Acompanhamento -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Desempenho dos Alunos</h3>
                
                <?php if (empty($acompanhamentoDados)): ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-600">Nenhum dado de acompanhamento encontrado.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média Geral</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faltas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($acompanhamentoDados as $aluno): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-primary-green rounded-full flex items-center justify-center">
                                                <span class="text-white font-medium text-sm">
                                                    <?= strtoupper(substr($aluno['nome_aluno'], 0, 2)) ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome_aluno']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($aluno['matricula'] ?? 'Sem matrícula') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($aluno['turma_nome']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-sm font-semibold <?= $aluno['media_geral'] >= 7 ? 'text-green-600' : ($aluno['media_geral'] >= 5 ? 'text-yellow-600' : 'text-red-600') ?>">
                                                <?= number_format($aluno['media_geral'], 1, ',', '.') ?>
                                            </span>
                                            <?php if ($aluno['total_notas'] > 0): ?>
                                                <span class="ml-2 text-xs text-gray-500">(<?= $aluno['total_notas'] ?> notas)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2" style="width: 80px;">
                                                <div class="bg-<?= $aluno['percentual_frequencia'] >= 75 ? 'green' : ($aluno['percentual_frequencia'] >= 50 ? 'yellow' : 'red') ?>-500 h-2 rounded-full" 
                                                     style="width: <?= min($aluno['percentual_frequencia'], 100) ?>%"></div>
                                            </div>
                                            <span class="text-sm font-medium <?= $aluno['percentual_frequencia'] >= 75 ? 'text-green-600' : ($aluno['percentual_frequencia'] >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                                                <?= number_format($aluno['percentual_frequencia'], 1, ',', '.') ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>
                                            <span class="font-medium text-red-600"><?= $aluno['dias_faltas'] ?></span> faltas
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            <?= $aluno['dias_presentes'] ?> presenças
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $situacao = 'PENDENTE';
                                        $corSituacao = 'gray';
                                        if ($aluno['media_geral'] >= 7 && $aluno['percentual_frequencia'] >= 75) {
                                            $situacao = 'APROVADO';
                                            $corSituacao = 'green';
                                        } elseif ($aluno['media_geral'] < 5 || $aluno['percentual_frequencia'] < 75) {
                                            $situacao = 'REPROVADO';
                                            $corSituacao = 'red';
                                        } elseif ($aluno['media_geral'] >= 5 && $aluno['media_geral'] < 7) {
                                            $situacao = 'RECUPERAÇÃO';
                                            $corSituacao = 'yellow';
                                        }
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-<?= $corSituacao ?>-100 text-<?= $corSituacao ?>-800">
                                            <?= $situacao ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="verDetalhesAluno(<?= $aluno['aluno_id'] ?>, <?= $aluno['turma_id'] ?>)" 
                                                class="text-primary-green hover:text-secondary-green" title="Ver detalhes">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ABA: VALIDAÇÃO -->
        <div id="conteudo-validacao" class="aba-conteudo hidden">
            <!-- Filtros e Estatísticas -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Validação de Lançamentos</h2>
                    <div class="text-sm text-gray-600">
                        Total pendente: <span class="font-semibold text-orange-600"><?= $contadoresValidacao['TOTAL'] ?></span>
                    </div>
                </div>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="escola_id" value="<?= htmlspecialchars($_GET['escola_id'] ?? '') ?>">
                    <div>
                        <label for="tipo_validacao" class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Tipo</label>
                        <select id="tipo_validacao" name="tipo_validacao" 
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                onchange="this.form.submit()">
                            <option value="">Todos os tipos</option>
                            <option value="NOTA" <?= ($filtroTipoValidacao == 'NOTA') ? 'selected' : '' ?>>Notas</option>
                            <option value="FREQUENCIA" <?= ($filtroTipoValidacao == 'FREQUENCIA') ? 'selected' : '' ?>>Frequências</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Notas Pendentes</p>
                            <p class="text-3xl font-bold text-blue-600"><?= $contadoresValidacao['NOTA'] ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Frequências Pendentes</p>
                            <p class="text-3xl font-bold text-green-600"><?= $contadoresValidacao['FREQUENCIA'] ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Pendente</p>
                            <p class="text-3xl font-bold text-orange-600"><?= $contadoresValidacao['TOTAL'] ?></p>
                        </div>
                        <div class="bg-orange-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Lançamentos Pendentes -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Lançamentos Pendentes de Validação</h3>
                
                <?php if (empty($lancamentosPendentes)): ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-600">Nenhum lançamento pendente de validação.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalhes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lançado por</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($lancamentosPendentes as $lancamento): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $lancamento['tipo'] == 'NOTA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= $lancamento['tipo'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($lancamento['aluno_nome']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($lancamento['turma_nome']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php if ($lancamento['tipo'] == 'NOTA'): ?>
                                            <div>
                                                <span class="font-semibold"><?= htmlspecialchars($lancamento['disciplina_nome']) ?></span>
                                                <?php if (!empty($lancamento['bimestre'])): ?>
                                                    <span class="text-gray-400"> - Bimestre <?= $lancamento['bimestre'] ?></span>
                                                <?php endif; ?>
                                                <div class="text-lg font-bold <?= floatval($lancamento['nota']) >= 7 ? 'text-green-600' : (floatval($lancamento['nota']) >= 5 ? 'text-yellow-600' : 'text-red-600') ?>">
                                                    Nota: <?= number_format($lancamento['nota'], 1, ',', '.') ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div>
                                                <span class="font-semibold <?= $lancamento['status_frequencia'] == 'Presente' ? 'text-green-600' : 'text-red-600' ?>">
                                                    <?= $lancamento['status_frequencia'] ?>
                                                </span>
                                                <div class="text-xs text-gray-400">Data: <?= $lancamento['data_lancamento'] ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($lancamento['comentario'])): ?>
                                            <div class="text-xs text-gray-500 mt-1 italic"><?= htmlspecialchars(substr($lancamento['comentario'], 0, 50)) ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($lancamento['lancado_por'] ?? 'Sistema') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($lancamento['data_lancamento'] ?? $lancamento['data_registro'] ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="aprovarLancamento('<?= $lancamento['tipo'] ?>', <?= $lancamento['id'] ?>)" 
                                                    class="text-green-600 hover:text-green-900" title="Aprovar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="rejeitarLancamento('<?= $lancamento['tipo'] ?>', <?= $lancamento['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-900" title="Rejeitar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ABA: CADASTROS -->
        <div id="conteudo-cadastros" class="aba-conteudo hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Card Cadastrar Professor -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="bg-blue-100 rounded-full p-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Cadastrar Professor</h3>
                            <p class="text-sm text-gray-500">Adicione um novo professor ao sistema</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Cadastre professores que atuarão nas turmas da escola, incluindo informações de formação e lotação.</p>
                    <button onclick="abrirModalCadastrarProfessor()" class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Cadastrar Professor
                    </button>
                </div>

                <!-- Card Cadastrar Funcionário -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="bg-green-100 rounded-full p-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Cadastrar Funcionário</h3>
                            <p class="text-sm text-gray-500">Adicione um novo funcionário da escola</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Cadastre funcionários da escola, como secretários, auxiliares, merendeiras, entre outros.</p>
                    <button onclick="abrirModalCadastrarFuncionario()" class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Cadastrar Funcionário
                    </button>
                </div>
            </div>
        </div>

        <!-- ABA: RESPONSÁVEIS -->
        <div id="conteudo-responsaveis" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Gerenciamento de Responsáveis</h2>
                    <button onclick="abrirModalCriarResponsavel()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-secondary-green transition-colors">
                        + Novo Responsável
                    </button>
                </div>

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" id="busca-responsavel" placeholder="Nome, CPF ou email..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                               onkeyup="filtrarResponsaveis()">
                    </div>
                </div>

                <!-- Lista de Responsáveis -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-responsaveis" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA: RELATÓRIOS -->
        <div id="conteudo-relatorios" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Geração de Relatórios</h2>
                </div>

                <!-- Cards de Relatórios -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Boletim Bimestral -->
                    <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6 hover:border-primary-green transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Boletim Bimestral</h3>
                        <p class="text-sm text-gray-600 mb-4">Gere boletins bimestrais com notas e frequência dos alunos</p>
                        <button onclick="abrirModalBoletimBimestral()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Boletim Bimestral
                        </button>
                    </div>

                    <!-- Boletim Final -->
                    <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6 hover:border-primary-green transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Boletim Final</h3>
                        <p class="text-sm text-gray-600 mb-4">Gere boletim final com média anual e situação do aluno</p>
                        <button onclick="abrirModalBoletimFinal()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Boletim Final
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Boletim Bimestral -->
    <div id="modal-boletim-bimestral" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-primary-green text-white p-6 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">Gerar Boletim Bimestral</h3>
                <button onclick="fecharModalBoletimBimestral()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <form id="form-boletim-bimestral" onsubmit="event.preventDefault(); gerarBoletimBimestral();">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turma <span class="text-red-500">*</span></label>
                            <select id="boletim-bimestral-turma" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                                <option value="">Selecione uma turma...</option>
                                <?php
                                $sqlTurmas = "SELECT t.id, t.serie, t.letra, t.turno, e.nome as escola_nome 
                                             FROM turma t 
                                             INNER JOIN escola e ON t.escola_id = e.id 
                                             WHERE t.ativo = 1 
                                             ORDER BY e.nome, t.serie, t.letra";
                                $stmtTurmas = $conn->prepare($sqlTurmas);
                                $stmtTurmas->execute();
                                $turmas = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($turmas as $turma):
                                ?>
                                    <option value="<?= $turma['id'] ?>">
                                        <?= htmlspecialchars($turma['escola_nome']) ?> - <?= htmlspecialchars($turma['serie']) ?> <?= htmlspecialchars($turma['letra']) ?> (<?= htmlspecialchars($turma['turno']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bimestre <span class="text-red-500">*</span></label>
                            <select id="boletim-bimestral-bimestre" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                                <option value="">Selecione o bimestre...</option>
                                <option value="1">1º Bimestre</option>
                                <option value="2">2º Bimestre</option>
                                <option value="3">3º Bimestre</option>
                                <option value="4">4º Bimestre</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo <span class="text-red-500">*</span></label>
                            <input type="number" id="boletim-bimestral-ano" required min="2020" max="2099" value="<?= date('Y') ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Boletim
                        </button>
                        <button type="button" onclick="fecharModalBoletimBimestral()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Boletim Final -->
    <div id="modal-boletim-final" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-green-600 text-white p-6 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">Gerar Boletim Final</h3>
                <button onclick="fecharModalBoletimFinal()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <form id="form-boletim-final" onsubmit="event.preventDefault(); gerarBoletimFinal();">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turma <span class="text-red-500">*</span></label>
                            <select id="boletim-final-turma" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                                <option value="">Selecione uma turma...</option>
                                <?php foreach ($turmas as $turma): ?>
                                    <option value="<?= $turma['id'] ?>">
                                        <?= htmlspecialchars($turma['escola_nome']) ?> - <?= htmlspecialchars($turma['serie']) ?> <?= htmlspecialchars($turma['letra']) ?> (<?= htmlspecialchars($turma['turno']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo <span class="text-red-500">*</span></label>
                            <input type="number" id="boletim-final-ano" required min="2020" max="2099" value="<?= date('Y') ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Boletim Final
                        </button>
                        <button type="button" onclick="fecharModalBoletimFinal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Criar Responsável -->
    <div id="modal-criar-responsavel" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-primary-green text-white sticky top-0 z-10 shadow-md">
                <div>
                    <h3 class="text-2xl font-bold">Novo Responsável</h3>
                    <p class="text-green-100 text-sm mt-1">Preencha os dados para criar um novo responsável</p>
                </div>
                <button onclick="fecharModalCriarResponsavel()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-green-700 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto">
                    <form id="form-criar-responsavel" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8" onsubmit="event.preventDefault(); return false;">
                        <div class="space-y-6">
                            <!-- Dados Pessoais -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Dados Pessoais</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nome Completo <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="responsavel-nome" required 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            CPF <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="responsavel-cpf" required maxlength="14" 
                                               placeholder="000.000.000-00"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                               oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                                        <input type="date" id="responsavel-data-nascimento" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                                        <select id="responsavel-sexo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                            <option value="">Selecione...</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Feminino</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                        <input type="email" id="responsavel-email" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                        <input type="text" id="responsavel-telefone" maxlength="15" 
                                               placeholder="(85) 99999-9999"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                               oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2')">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Acesso ao Sistema -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Acesso ao Sistema</h4>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Senha <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" id="responsavel-senha" required minlength="6"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                           placeholder="Digite a senha para acesso ao sistema">
                                    <p class="text-xs text-gray-500 mt-1">A senha deve ter no mínimo 6 caracteres</p>
                                </div>
                            </div>
                            
                            <!-- Associação de Alunos -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                                    Associar Alunos <span class="text-red-500">*</span>
                                </h4>
                                <p class="text-sm text-gray-600 mb-4">É obrigatório associar pelo menos um aluno a este responsável.</p>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Parentesco <span class="text-red-500">*</span>
                                    </label>
                                    <select id="responsavel-parentesco" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                                        <option value="">Selecione...</option>
                                        <option value="PAI">Pai</option>
                                        <option value="MAE">Mãe</option>
                                        <option value="AVO">Avô/Avó</option>
                                        <option value="TIO">Tio/Tia</option>
                                        <option value="OUTRO">Outro</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Aluno</label>
                                    <input type="text" id="busca-aluno-criar" placeholder="Nome, matrícula ou CPF..." 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green"
                                           onkeyup="buscarAlunosParaCriar()">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Disponíveis</label>
                                    <div id="lista-alunos-criar" class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto bg-gray-50">
                                        <div class="p-4 text-center text-gray-500">Digite para buscar alunos...</div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Selecionados</label>
                                    <div id="alunos-selecionados-criar" class="border border-gray-300 rounded-lg p-4 min-h-20 bg-gray-50">
                                        <p class="text-gray-500 text-sm">Nenhum aluno selecionado</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex justify-end space-x-3 pt-8 mt-8 border-t border-gray-200">
                            <button type="button" onclick="fecharModalCriarResponsavel()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="button" onclick="salvarResponsavel()" 
                                    class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 font-medium transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Criar Responsável
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Associar Alunos -->
    <div id="modal-associar-alunos" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-primary-green text-white sticky top-0 z-10 shadow-md">
                <div>
                    <h3 class="text-2xl font-bold">Associar Alunos</h3>
                    <p class="text-green-100 text-sm mt-1" id="responsavel-nome-associar"></p>
                </div>
                <button onclick="fecharModalAssociarAlunos()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-green-700 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                        <input type="hidden" id="responsavel-id-associar">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Parentesco</label>
                            <select id="parentesco-associar" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                                <option value="PAI">Pai</option>
                                <option value="MAE">Mãe</option>
                                <option value="AVO">Avô/Avó</option>
                                <option value="TIO">Tio/Tia</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Aluno</label>
                            <input type="text" id="busca-aluno-associar" placeholder="Nome, matrícula ou CPF..." 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green"
                                   onkeyup="buscarAlunosParaAssociar()">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Disponíveis</label>
                            <div id="lista-alunos-associar" class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">
                                <div class="p-4 text-center text-gray-500">Digite para buscar alunos...</div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Selecionados</label>
                            <div id="alunos-selecionados" class="border border-gray-300 rounded-lg p-4 min-h-20">
                                <p class="text-gray-500 text-sm">Nenhum aluno selecionado</p>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" onclick="fecharModalAssociarAlunos()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="button" onclick="salvarAssociacao()" 
                                    class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 font-medium transition-colors shadow-md hover:shadow-lg">
                                Associar Alunos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Criar Turma -->
    <div id="modal-criar-turma" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-primary-green text-white sticky top-0 z-10 shadow-md">
                <div>
                    <h3 class="text-2xl font-bold">Nova Turma</h3>
                    <p class="text-green-100 text-sm mt-1">Preencha os dados para criar uma nova turma</p>
                </div>
                <button onclick="fecharModalCriarTurma()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-green-700 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto">
                    <form method="POST" id="form-criar-turma" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <input type="hidden" name="acao" value="criar_turma">
                        
                        <div class="space-y-6">
                            <!-- Informações Básicas -->
                <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Informações Básicas</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Escola <span class="text-red-500">*</span>
                                        </label>
                                        <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
                                            <input type="hidden" name="escola_id" value="<?= $escolaGestorId ?>">
                                            <input type="text" value="<?= htmlspecialchars($escolaGestor) ?>" disabled 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-700">
                                        <?php else: ?>
                                            <select name="escola_id" required 
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                                <option value="">Selecione uma escola...</option>
                                                <?php foreach ($escolas as $escola): ?>
                                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                                        <select name="serie_id" 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                            <option value="">Selecione uma série...</option>
                                            <?php foreach ($series as $serie): ?>
                                                <option value="<?= $serie['id'] ?>"><?= htmlspecialchars($serie['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                </div>
                                    
                <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Série (Texto)</label>
                                        <input type="text" name="serie" placeholder="Ex: 1º Ano" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                        <p class="text-xs text-gray-500 mt-1">Ou informe manualmente se não houver série cadastrada</p>
                </div>
                                    
                <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Letra <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="letra" required placeholder="Ex: A" maxlength="1" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors text-center text-2xl font-bold uppercase">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Turno <span class="text-red-500">*</span>
                                        </label>
                                        <select name="turno" required 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                        <option value="MANHA">Manhã</option>
                        <option value="TARDE">Tarde</option>
                        <option value="NOITE">Noite</option>
                    </select>
                </div>
                                    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                                        <input type="number" name="ano_letivo" value="<?= date('Y') ?>" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                </div>
                                </div>
                            </div>
                            
                            <!-- Configurações Adicionais -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Configurações Adicionais</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidade</label>
                                        <input type="number" name="capacidade" placeholder="Ex: 30" min="1" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                        <p class="text-xs text-gray-500 mt-1">Número máximo de alunos na turma</p>
                </div>
                                    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sala</label>
                                        <input type="text" name="sala" placeholder="Ex: 101" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                        <p class="text-xs text-gray-500 mt-1">Identificação da sala de aula</p>
                </div>
                                </div>
                            </div>
                            
                            <!-- Observações -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                <textarea name="observacoes" rows="3" placeholder="Observações adicionais sobre a turma..." 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors resize-none"></textarea>
                            </div>
                            
                            <!-- Status -->
                            <div class="pt-4 border-t border-gray-200">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="ativo" value="1" checked 
                                           class="mr-3 w-5 h-5 text-primary-green focus:ring-primary-green border-gray-300 rounded cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700">Turma Ativa</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1 ml-8">Turmas inativas não aparecerão nas listagens principais</p>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex justify-end space-x-3 pt-8 mt-8 border-t border-gray-200">
                            <button type="button" onclick="fecharModalCriarTurma()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 font-medium transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Criar Turma
                            </button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalhes da Turma -->
    <div id="modal-ver-turma" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h3 class="text-2xl font-bold text-gray-900">Detalhes da Turma</h3>
                <button onclick="fecharModalVerTurma()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto">
                <div id="conteudo-ver-turma">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                        <p class="mt-2 text-gray-600">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Turma -->
    <div id="modal-editar-turma" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h3 class="text-2xl font-bold text-gray-900">Editar Turma</h3>
                <button onclick="fecharModalEditarTurma()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-4xl mx-auto">
                    <form method="POST" id="form-editar-turma" class="space-y-6">
                        <input type="hidden" name="acao" value="editar_turma">
                        <input type="hidden" name="turma_id" id="editar-turma-id">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
                                    <input type="hidden" name="escola_id" id="editar-escola-id" value="<?= $escolaGestorId ?>">
                                    <input type="text" value="<?= htmlspecialchars($escolaGestor) ?>" disabled 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-700">
                                <?php else: ?>
                                    <select name="escola_id" id="editar-escola-id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($escolas as $escola): ?>
                                            <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                                <select name="serie_id" id="editar-serie-id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($series as $serie): ?>
                                        <option value="<?= $serie['id'] ?>"><?= htmlspecialchars($serie['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Série (Texto)</label>
                                <input type="text" name="serie" id="editar-serie" placeholder="Ex: 1º Ano" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Letra *</label>
                                <input type="text" name="letra" id="editar-letra" required placeholder="Ex: A" maxlength="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Turno *</label>
                                <select name="turno" id="editar-turno" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="MANHA">Manhã</option>
                                    <option value="TARDE">Tarde</option>
                                    <option value="NOITE">Noite</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                                <input type="number" name="ano_letivo" id="editar-ano-letivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Capacidade</label>
                                <input type="number" name="capacidade" id="editar-capacidade" placeholder="Ex: 30" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sala</label>
                                <input type="text" name="sala" id="editar-sala" placeholder="Ex: 101" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                        </div>
                        
                        <div class="pt-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="ativo" id="editar-ativo" value="1" checked class="mr-2 w-5 h-5">
                                <span class="text-sm font-medium text-gray-700">Turma Ativa</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button type="button" onclick="fecharModalEditarTurma()" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">Cancelar</button>
                            <button type="submit" class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-secondary-green font-medium transition-colors">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Matricular Aluno -->
    <div id="modal-matricular-aluno" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-primary-green text-white sticky top-0 z-10 shadow-md">
                <div>
                    <h3 class="text-2xl font-bold">Matricular Aluno</h3>
                    <p class="text-green-100 text-sm mt-1">Selecione o aluno e a turma para realizar a matrícula</p>
                </div>
                <button onclick="fecharModalMatricularAluno()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-green-700 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto">
                    <form method="POST" id="form-matricular-aluno" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                        <input type="hidden" name="acao" value="matricular_aluno">
                        <input type="hidden" name="aluno_id" id="matricular-aluno-id">
                        
                        <div class="space-y-6">
                            <!-- Informações do Aluno -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Informações do Aluno</h4>
                                <div id="info-aluno-matricular" class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-gray-500 text-center">Selecione um aluno da lista abaixo</p>
                                </div>
                            </div>
                            
                            <!-- Seleção de Aluno -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Buscar Aluno <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" id="buscar-aluno-matricular" 
                                           placeholder="Digite o nome, CPF ou matrícula do aluno..."
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                           onkeyup="buscarAlunosParaMatricula(this.value)">
                                    <div id="resultados-busca-aluno" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Ou selecione um aluno da lista de alunos disponíveis</p>
                            </div>
                            
                            <!-- Lista de Alunos Disponíveis -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Disponíveis</label>
                                <div class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CPF</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrícula</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista-alunos-matricular" class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($alunos as $aluno): ?>
                                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="selecionarAlunoParaMatricula(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome'] ?? '') ?>', '<?= htmlspecialchars($aluno['cpf'] ?? '') ?>', '<?= htmlspecialchars($aluno['matricula'] ?? '') ?>')">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome'] ?? '') ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($aluno['cpf'] ?? '') ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($aluno['matricula'] ?? 'Não informado') ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <button type="button" class="text-primary-green hover:text-secondary-green font-medium">Selecionar</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Seleção de Turma -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Seleção de Turma</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Escola <span class="text-red-500">*</span>
                                        </label>
                                        <select name="escola_id" id="matricular-escola-id" required 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                                onchange="carregarTurmasParaMatricula(this.value)">
                                            <option value="">Selecione uma escola...</option>
                                            <?php foreach ($escolas as $escola): ?>
                                                <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Turma <span class="text-red-500">*</span>
                                        </label>
                                        <select name="turma_id" id="matricular-turma-id" required 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                            <option value="">Primeiro selecione uma escola</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Início</label>
                                        <input type="date" name="data_inicio" value="<?= date('Y-m-d') ?>" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                        <p class="text-xs text-gray-500 mt-1">Data em que o aluno será matriculado na turma</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Observações -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                <textarea name="observacoes" rows="3" placeholder="Observações sobre a matrícula..." 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors resize-none"></textarea>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex justify-end space-x-3 pt-8 mt-8 border-t border-gray-200">
                            <button type="button" onclick="fecharModalMatricularAluno()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 font-medium transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Confirmar Matrícula
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Transferir Aluno -->
    <div id="modal-transferir-aluno" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-600 text-white sticky top-0 z-10 shadow-md">
                <div>
                    <h3 class="text-2xl font-bold">Transferir Aluno</h3>
                    <p class="text-blue-100 text-sm mt-1">Transfira o aluno de uma turma para outra</p>
                </div>
                <button onclick="fecharModalTransferirAluno()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-blue-700 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto">
                    <form method="POST" id="form-transferir-aluno" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                        <input type="hidden" name="acao" value="transferir_aluno">
                        <input type="hidden" name="aluno_id" id="transferir-aluno-id">
                        <input type="hidden" name="turma_antiga_id" id="transferir-turma-antiga-id">
                        
                        <div class="space-y-6">
                            <!-- Informações do Aluno -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Informações do Aluno</h4>
                                <div id="info-aluno-transferir" class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-gray-500 text-center">Selecione um aluno da lista abaixo</p>
                                </div>
                            </div>
                            
                            <!-- Seleção de Aluno -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Buscar Aluno <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" id="buscar-aluno-transferir" 
                                           placeholder="Digite o nome, CPF ou matrícula do aluno..."
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors"
                                           onkeyup="buscarAlunosParaTransferencia(this.value)">
                                    <div id="resultados-busca-aluno-transferir" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Turma Atual -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Turma Atual</h4>
                                <div id="info-turma-atual" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p class="text-gray-500 text-center">Selecione um aluno para ver a turma atual</p>
                                </div>
                            </div>
                            
                            <!-- Nova Turma -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Nova Turma</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Escola <span class="text-red-500">*</span>
                                        </label>
                                        <select name="escola_id" id="transferir-escola-id" 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors"
                                                onchange="carregarTurmasParaTransferencia(this.value)">
                                            <option value="">Selecione uma escola...</option>
                                            <?php foreach ($escolas as $escola): ?>
                                                <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nova Turma <span class="text-red-500">*</span>
                                        </label>
                                        <select name="turma_nova_id" id="transferir-turma-nova-id" required 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                            <option value="">Primeiro selecione uma escola</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Observações -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações sobre a Transferência</label>
                                <textarea name="observacoes" rows="3" placeholder="Informe o motivo da transferência..." 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors resize-none"></textarea>
                            </div>
                            
                            <!-- Aviso -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-blue-900">Atenção</p>
                                        <p class="text-sm text-blue-700 mt-1">Ao transferir o aluno, a matrícula na turma atual será finalizada e uma nova matrícula será criada na turma selecionada.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex justify-end space-x-3 pt-8 mt-8 border-t border-gray-200">
                            <button type="button" onclick="fecharModalTransferirAluno()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Confirmar Transferência
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cadastrar Professor -->
<div id="modal-cadastrar-professor" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
    <div class="w-full h-full flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-600 text-white sticky top-0 z-10 shadow-md">
            <div>
                <h3 class="text-2xl font-bold">Cadastrar Professor</h3>
                <p class="text-blue-100 text-sm mt-1">Preencha os dados para cadastrar um novo professor</p>
            </div>
            <button onclick="fecharModalCadastrarProfessor()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-blue-700 rounded-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
            <div class="max-w-4xl mx-auto">
                <form method="POST" id="form-cadastrar-professor" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                    <input type="hidden" name="acao" value="cadastrar_professor">
                            
                    <div class="space-y-6">
                        <!-- Dados Pessoais -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Dados Pessoais</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Nome Completo <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="nome" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>

                                <!-- ADD: Nome Social -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                                    <input type="text" name="nome_social"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        CPF <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="cpf" required maxlength="14" 
                                           placeholder="000.000.000-00"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors"
                                           oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                                    <input type="date" name="data_nascimento" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                                    <select name="sexo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                        <option value="">Selecione...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Feminino</option>
                                    </select>
                                </div>

                                <!-- ADD: Raça -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Raça</label>
                                    <select name="raca" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                        <option value="">Selecione...</option>
                                        <option value="PRETO">Preto</option>
                                        <option value="BRANCO">Branco</option>
                                        <option value="INDIGENA">Indígena</option>
                                        <option value="AMARELO">Amarelo</option>
                                        <option value="PARDO">Pardo</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                    <input type="email" name="email" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                    <input type="text" name="telefone" maxlength="15" 
                                           placeholder="(85) 99999-9999"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors"
                                           oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2')">
                                </div>
                            </div>
                        </div>

                        <!-- ADD: Endereço -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Endereço</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                                    <input type="text" name="endereco" placeholder="Rua, Avenida..." 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                    <input type="text" name="numero"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                    <input type="text" name="complemento" placeholder="Apartamento, bloco, referência..." 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                    <input type="text" name="bairro"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                    <input type="text" name="cidade"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                    <input type="text" name="estado" maxlength="2"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors uppercase"
                                           oninput="this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                    <input type="text" name="cep" maxlength="9" placeholder="00000-000"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors"
                                           oninput="this.value = this.value.replace(/\D/g,'').replace(/(\d{5})(\d)/,'$1-$2').substring(0,9)">
                                </div>
                            </div>
                        </div>
                        
                       <!-- Dados Profissionais -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Dados Profissionais</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                                    <input type="text" name="matricula" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                                    <input type="date" name="data_admissao" value="<?= date('Y-m-d') ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>

                                <!-- Pós agora é SELECT -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pós</label>
                                    <select name="pos" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                        <option value="">Selecione...</option>
                                        <option value="ENSINO_MEDIO">Ensino Médio</option>
                                        <option value="GRADUANDO">Graduando</option>
                                        <option value="GRADUADO">Graduado</option>
                                        <option value="MESTRADO">Mestrado</option>
                                        <option value="DOUTORADO">Doutorado</option>
                                        <option value="POS_DOUTORADO">Pós Doutorado</option>
                                    </select>
                                </div>

                                <!-- Formação agora é INPUT texto -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Formação</label>
                                    <input type="text" name="formacao" placeholder="Descreva a formação"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Especialização</label>
                                    <input type="text" name="especializacao" placeholder="Ex: Especialização em Educação Especial" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional</label>
                                    <input type="text" name="registro_profissional" placeholder="Ex: CREA, CREF, etc." 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>

                            </div>
                        </div>

<!-- Lotação na Escola -->
<div>
    <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Lotação na Escola</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
            <select name="escola_id" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                <option value="">Selecione uma escola (opcional)</option>
                <?php foreach ($escolas as $escola): ?>
                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
        
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Carga Horária</label>
                                    <input type="number" name="carga_horaria" placeholder="Ex: 20" min="1" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observação da Lotação</label>
                                    <textarea name="observacao_lotacao" rows="2" placeholder="Observações sobre a lotação..." 
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors resize-none"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Acesso ao Sistema -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Acesso ao Sistema</h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha Inicial</label>
                                <input type="password" name="senha" value="123456" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-colors">
                                <p class="text-xs text-gray-500 mt-1">Senha padrão: 123456 (pode ser alterada após o primeiro login)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="flex justify-end space-x-3 pt-8 mt-8 border-t border-gray-200">
                        <button type="button" onclick="fecharModalCadastrarProfessor()" 
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Cadastrar Professor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Cadastrar Funcionário -->
    <div id="modal-cadastrar-funcionario" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-green-600 text-white sticky top-0 z-10 shadow-md">
                <div>
                    <h3 class="text-2xl font-bold">Cadastrar Funcionário</h3>
                    <p class="text-green-100 text-sm mt-1">Preencha os dados para cadastrar um novo funcionário</p>
                </div>
                <button onclick="fecharModalCadastrarFuncionario()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-green-700 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto">
                    <form method="POST" id="form-cadastrar-funcionario" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                        <input type="hidden" name="acao" value="cadastrar_funcionario">
                        
                        <div class="space-y-6">
                            <!-- Dados Pessoais -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Dados Pessoais</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nome Completo <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="nome" required 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            CPF <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="cpf" required maxlength="14" 
                                               placeholder="000.000.000-00"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors"
                                               oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                                        <input type="date" name="data_nascimento" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                                        <select name="sexo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                            <option value="">Selecione...</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Feminino</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                        <input type="email" name="email" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                        <input type="text" name="telefone" maxlength="15" 
                                               placeholder="(85) 99999-9999"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors"
                                               oninput="this.value = this.value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2')">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                                        <input type="text" name="whatsapp" maxlength="15" 
                                               placeholder="(85) 99999-9999"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors"
                                               oninput="this.value = this.value.replace(/\\D/g, '').replace(/(\\d{2})(\\d)/, '($1) $2').replace(/(\\d{5})(\\d)/, '$1-$2')">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone Secundário</label>
                                        <input type="text" name="telefone_secundario" maxlength="15" 
                                               placeholder="(85) 98888-7777"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors"
                                               oninput="this.value = this.value.replace(/\\D/g, '').replace(/(\\d{2})(\\d)/, '($1) $2').replace(/(\\d{5})(\\d)/, '$1-$2')">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                                        <input type="text" name="endereco" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                        <input type="text" name="numero" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                        <input type="text" name="complemento" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                        <input type="text" name="bairro" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                        <input type="text" name="cep" maxlength="9" 
                                               placeholder="00000-000"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors"
                                               oninput="this.value = this.value.replace(/\\D/g, '').replace(/(\\d{5})(\\d)/, '$1-$2')">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                        <input type="text" name="cidade" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus-border-green-600 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                        <input type="text" name="estado" maxlength="2" placeholder="UF" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus-border-green-600 transition-colors">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                                        <input type="text" name="nome_social" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus-border-green-600 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Raça/Cor</label>
                                        <select name="raca" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus-border-green-600 transition-colors">
                                            <option value="">Selecione...</option>
                                            <option value="BRANCA">Branca</option>
                                            <option value="PRETA">Preta</option>
                                            <option value="PARDA">Parda</option>
                                            <option value="AMARELA">Amarela</option>
                                            <option value="INDIGENA">Indígena</option>
                                            <option value="NAO_INFORMADO">Prefere não informar</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dados Profissionais -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Dados Profissionais</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Cargo <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="cargo" required placeholder="Ex: Secretário, Merendeira, Auxiliar" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Setor</label>
                                        <input type="text" name="setor" placeholder="Ex: Secretaria, Cozinha, Limpeza" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                                        <input type="text" name="matricula" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                                        <input type="date" name="data_admissao" value="<?= date('Y-m-d') ?>" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lotação na Escola -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Lotação na Escola</h4>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                                    <select name="escola_id" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                        <option value="">Selecione uma escola (opcional)</option>
                                        <?php foreach ($escolas as $escola): ?>
                                            <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Acesso ao Sistema -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Acesso ao Sistema</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário</label>
                                        <select name="role_funcionario" 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                            <option value="FUNCIONARIO">Funcionário</option>
                                            <option value="ADM_MERENDA">Administrador de Merenda</option>
                                            <option value="NUTRICIONISTA">Nutricionista</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Senha Inicial</label>
                                        <input type="password" name="senha" value="123456" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors">
                                        <p class="text-xs text-gray-500 mt-1">Senha padrão: 123456</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="flex justify-end space-x-3 pt-8 mt-8 border-t border-gray-200">
                            <button type="button" onclick="fecharModalCadastrarFuncionario()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Cadastrar Funcionário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Controle de abas
        function mostrarAba(aba) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.aba-conteudo').forEach(el => el.classList.add('hidden'));
            
            // Remover estilo ativo de todas as tabs
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar conteúdo da aba selecionada
            document.getElementById('conteudo-' + aba).classList.remove('hidden');
            
            // Ativar tab
            const tab = document.getElementById('tab-' + aba);
            tab.classList.remove('border-transparent', 'text-gray-500');
            tab.classList.add('border-primary-green', 'text-primary-green');
            
            // Carregar dados específicos da aba
            if (aba === 'responsaveis') {
                carregarResponsaveis();
            }
        }

        // Função para mostrar notificação de sucesso
        function mostrarNotificacaoSucesso(mensagem) {
            // Remover notificação anterior se existir
            const notifAnterior = document.getElementById('notificacao-boletim');
            if (notifAnterior) {
                notifAnterior.remove();
            }
            
            // Criar elemento de notificação
            const notificacao = document.createElement('div');
            notificacao.id = 'notificacao-boletim';
            notificacao.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3 animate-slide-in';
            notificacao.innerHTML = `
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-semibold">Sucesso!</p>
                    <p class="text-sm">${mensagem}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            document.body.appendChild(notificacao);
            
            // Remover automaticamente após 5 segundos
            setTimeout(() => {
                notificacao.style.opacity = '0';
                notificacao.style.transition = 'opacity 0.3s';
                setTimeout(() => notificacao.remove(), 300);
            }, 5000);
        }

        // Funções para Boletim Bimestral
        function abrirModalBoletimBimestral() {
            document.getElementById('modal-boletim-bimestral').classList.remove('hidden');
        }

        function fecharModalBoletimBimestral() {
            document.getElementById('modal-boletim-bimestral').classList.add('hidden');
        }

        function gerarBoletimBimestral() {
            const turmaId = document.getElementById('boletim-bimestral-turma').value;
            const bimestre = document.getElementById('boletim-bimestral-bimestre').value;
            const ano = document.getElementById('boletim-bimestral-ano').value;

            if (!turmaId || !bimestre || !ano) {
                showWarningAlert('Por favor, preencha todos os campos obrigatórios', 'Validação');
                return;
            }

            // Obter botão de submit para mostrar loading
            const submitBtn = document.querySelector('#form-boletim-bimestral button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span> Gerando...';

            // Preparar dados para envio
            const formData = new FormData();
            formData.append('action', 'gerar_bimestral');
            formData.append('turma_id', turmaId);
            formData.append('bimestre', bimestre);
            formData.append('ano_letivo', ano);

            // Fazer requisição AJAX
            fetch('../../Controllers/academico/BoletimController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                return response.text().then(text => {
                    if (!text || text.trim() === '') {
                        throw new Error('Resposta vazia do servidor');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Resposta não é JSON válido: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    mostrarNotificacaoSucesso(data.message || 'Boletins gerados com sucesso!');
                    fecharModalBoletimBimestral();
                    // Limpar formulário
                    document.getElementById('form-boletim-bimestral').reset();
                } else {
                    showErrorAlert('Erro ao gerar boletins: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Erro ao gerar boletins:', error);
                showErrorAlert('Erro ao gerar boletins: ' + error.message, 'Erro');
            });
        }

        // Funções para Boletim Final
        function abrirModalBoletimFinal() {
            document.getElementById('modal-boletim-final').classList.remove('hidden');
        }

        function fecharModalBoletimFinal() {
            document.getElementById('modal-boletim-final').classList.add('hidden');
        }

        function gerarBoletimFinal() {
            const turmaId = document.getElementById('boletim-final-turma').value;
            const ano = document.getElementById('boletim-final-ano').value;

            if (!turmaId || !ano) {
                showWarningAlert('Por favor, preencha todos os campos obrigatórios', 'Validação');
                return;
            }

            // Obter botão de submit para mostrar loading
            const submitBtn = document.querySelector('#form-boletim-final button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span> Gerando...';

            // Preparar dados para envio
            const formData = new FormData();
            formData.append('action', 'gerar_final');
            formData.append('turma_id', turmaId);
            formData.append('ano_letivo', ano);

            // Fazer requisição AJAX
            fetch('../../Controllers/academico/BoletimController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                return response.text().then(text => {
                    if (!text || text.trim() === '') {
                        throw new Error('Resposta vazia do servidor');
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Resposta não é JSON válido: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    mostrarNotificacaoSucesso(data.message || 'Boletins finais gerados com sucesso!');
                    fecharModalBoletimFinal();
                    // Limpar formulário
                    document.getElementById('form-boletim-final').reset();
                } else {
                    showErrorAlert('Erro ao gerar boletins: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Erro ao gerar boletins:', error);
                showErrorAlert('Erro ao gerar boletins: ' + error.message, 'Erro');
            });
        }

        // Modal Criar Turma
        function abrirModalCriarTurma() {
            document.getElementById('modal-criar-turma').classList.remove('hidden');
        }

        function fecharModalCriarTurma() {
            document.getElementById('modal-criar-turma').classList.add('hidden');
        }

        // Filtros
        function filtrarTurmas() {
            const escolaId = document.getElementById('filtro-escola').value;
            const anoLetivo = document.getElementById('filtro-ano').value;
            
            let url = 'gestao_escolar.php?';
            if (escolaId) url += 'escola_id=' + escolaId + '&';
            if (anoLetivo) url += 'ano_letivo=' + anoLetivo;
            
            window.location.href = url;
        }

        // Função para ver detalhes da turma
        function verDetalhesTurma(id) {
            document.getElementById('modal-ver-turma').classList.remove('hidden');
            document.getElementById('conteudo-ver-turma').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                    <p class="mt-2 text-gray-600">Carregando...</p>
                </div>
            `;
            
            // Buscar dados da turma via AJAX
            fetch('gestao_escolar.php?acao=buscar_turma&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const turma = data.turma;
                        const alunos = data.alunos || [];
                        const professores = data.professores || [];
                        
                        let html = `
                            <div class="space-y-8 max-w-6xl mx-auto">
                                <!-- Informações Básicas -->
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Informações Básicas</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Turma:</span>
                                            <p class="font-medium">${turma.serie || ''} ${turma.letra || ''} - ${turma.turno || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Escola:</span>
                                            <p class="font-medium">${turma.escola_nome || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Ano Letivo:</span>
                                            <p class="font-medium">${turma.ano_letivo || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Capacidade:</span>
                                            <p class="font-medium">${turma.capacidade || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Sala:</span>
                                            <p class="font-medium">${turma.sala || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Status:</span>
                                            <p class="font-medium"><span class="px-2 py-1 rounded ${turma.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${turma.ativo ? 'Ativa' : 'Inativa'}</span></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Alunos -->
                                <div class="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Alunos (${alunos.length})</h4>
                                    ${alunos.length > 0 ? `
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${alunos.map(aluno => `
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${aluno.nome || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${aluno.cpf || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${aluno.matricula || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${aluno.status || ''}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm py-4">Nenhum aluno matriculado nesta turma.</p>'}
                                </div>
                                
                                <!-- Professores -->
                                <div class="bg-white p-6 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="text-lg font-semibold text-gray-800">Professores (${professores.length})</h4>
                                        <button onclick="abrirModalAtribuirProfessor(${turma.id})" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Atribuir Professor
                                        </button>
                                    </div>
                                    ${professores.length > 0 ? `
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regime</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${professores.map(prof => `
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${prof.nome || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${prof.disciplina_nome || 'Não informado'}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${prof.regime || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                                <button onclick="removerProfessor(${turma.id}, ${prof.professor_id}, ${prof.disciplina_id ? prof.disciplina_id : 'null'})" class="text-red-600 hover:text-red-900" title="Remover">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm py-4">Nenhum professor atribuído a esta turma.</p>'}
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('conteudo-ver-turma').innerHTML = html;
                    } else {
                        document.getElementById('conteudo-ver-turma').innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-red-600">Erro ao carregar dados da turma.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('conteudo-ver-turma').innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-red-600">Erro ao carregar dados: ${error.message}</p>
                        </div>
                    `;
                });
        }

        function fecharModalVerTurma() {
            document.getElementById('modal-ver-turma').classList.add('hidden');
        }

        // Função para editar turma
        function editarTurma(id) {
            // Buscar dados da turma
            fetch('gestao_escolar.php?acao=buscar_turma&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.turma) {
                        const turma = data.turma;
                        
                        // Preencher formulário
                        document.getElementById('editar-turma-id').value = turma.id;
                        document.getElementById('editar-escola-id').value = turma.escola_id || '';
                        document.getElementById('editar-serie-id').value = turma.serie_id || '';
                        document.getElementById('editar-serie').value = turma.serie || '';
                        document.getElementById('editar-letra').value = turma.letra || '';
                        document.getElementById('editar-turno').value = turma.turno || 'MANHA';
                        document.getElementById('editar-ano-letivo').value = turma.ano_letivo || '';
                        document.getElementById('editar-capacidade').value = turma.capacidade || '';
                        document.getElementById('editar-sala').value = turma.sala || '';
                        document.getElementById('editar-ativo').checked = turma.ativo == 1;
                        
                        // Abrir modal
                        document.getElementById('modal-editar-turma').classList.remove('hidden');
                    } else {
                        showErrorAlert('Erro ao carregar dados da turma.', 'Erro');
                    }
                })
                .catch(error => {
                    showErrorAlert('Erro ao carregar dados: ' + error.message, 'Erro');
                });
        }

        function fecharModalEditarTurma() {
            document.getElementById('modal-editar-turma').classList.add('hidden');
        }

        // Modal Matricular Aluno
        function abrirModalMatricularAluno() {
            document.getElementById('modal-matricular-aluno').classList.remove('hidden');
            document.getElementById('form-matricular-aluno').reset();
            document.getElementById('matricular-aluno-id').value = '';
            document.getElementById('info-aluno-matricular').innerHTML = '<p class="text-gray-500 text-center">Selecione um aluno da lista abaixo</p>';
        }

        function fecharModalMatricularAluno() {
            document.getElementById('modal-matricular-aluno').classList.add('hidden');
            document.getElementById('form-matricular-aluno').reset();
            document.getElementById('matricular-aluno-id').value = '';
            document.getElementById('resultados-busca-aluno').classList.add('hidden');
        }

        function selecionarAlunoParaMatricula(alunoId, nome, cpf, matricula) {
            document.getElementById('matricular-aluno-id').value = alunoId;
            document.getElementById('info-aluno-matricular').innerHTML = `
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 h-12 w-12 bg-primary-green rounded-full flex items-center justify-center">
                        <span class="text-white font-medium text-lg">${nome.substring(0, 2).toUpperCase()}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">${nome}</p>
                        <p class="text-sm text-gray-500">CPF: ${cpf}</p>
                        <p class="text-sm text-gray-500">Matrícula: ${matricula || 'Não informado'}</p>
                    </div>
                </div>
            `;
            document.getElementById('resultados-busca-aluno').classList.add('hidden');
            document.getElementById('buscar-aluno-matricular').value = nome;
        }

        function buscarAlunosParaMatricula(termo) {
            const resultados = document.getElementById('resultados-busca-aluno');
            if (termo.length < 2) {
                resultados.classList.add('hidden');
                return;
            }
            
            // Buscar alunos que correspondem ao termo
            const alunos = Array.from(document.querySelectorAll('#lista-alunos-matricular tr'));
            const matches = alunos.filter(tr => {
                const texto = tr.textContent.toLowerCase();
                return texto.includes(termo.toLowerCase());
            });
            
            if (matches.length === 0) {
                resultados.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                resultados.classList.remove('hidden');
                return;
            }
            
            resultados.innerHTML = matches.map(tr => {
                const nome = tr.cells[0].textContent.trim();
                const cpf = tr.cells[1].textContent.trim();
                const matricula = tr.cells[2].textContent.trim();
                const alunoId = tr.getAttribute('onclick').match(/\d+/)[0];
                return `
                    <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-200" onclick="selecionarAlunoParaMatricula(${alunoId}, '${nome.replace(/'/g, "\\'")}', '${cpf}', '${matricula.replace(/'/g, "\\'")}')">
                        <p class="font-medium text-gray-900">${nome}</p>
                        <p class="text-sm text-gray-500">CPF: ${cpf} | Matrícula: ${matricula}</p>
                    </div>
                `;
            }).join('');
            resultados.classList.remove('hidden');
        }

        function carregarTurmasParaMatricula(escolaId) {
            const selectTurma = document.getElementById('matricular-turma-id');
            selectTurma.innerHTML = '<option value="">Carregando...</option>';
            
            if (!escolaId) {
                selectTurma.innerHTML = '<option value="">Primeiro selecione uma escola</option>';
                return;
            }
            
            fetch(`?acao=buscar_turmas&escola_id=${escolaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.turmas) {
                        selectTurma.innerHTML = '<option value="">Selecione uma turma...</option>';
                        data.turmas.forEach(turma => {
                            const option = document.createElement('option');
                            option.value = turma.id;
                            option.textContent = `${turma.serie || ''} ${turma.letra || ''} - ${turma.turno || ''}`;
                            selectTurma.appendChild(option);
                        });
                    } else {
                        selectTurma.innerHTML = '<option value="">Nenhuma turma encontrada</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar turmas:', error);
                    selectTurma.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                });
        }

        function matricularAluno(id) {
            // Buscar informações do aluno
            fetch(`?acao=buscar_aluno&aluno_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.aluno) {
                        const aluno = data.aluno;
                        selecionarAlunoParaMatricula(
                            aluno.id,
                            aluno.nome || '',
                            aluno.cpf || '',
                            aluno.matricula || ''
                        );
                        abrirModalMatricularAluno();
                    } else {
                        showErrorAlert('Erro ao carregar dados do aluno.', 'Erro');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showErrorAlert('Erro ao carregar dados do aluno.', 'Erro');
                });
        }

        // Modal Transferir Aluno
        function abrirModalTransferirAluno() {
            document.getElementById('modal-transferir-aluno').classList.remove('hidden');
            document.getElementById('form-transferir-aluno').reset();
            document.getElementById('transferir-aluno-id').value = '';
            document.getElementById('transferir-turma-antiga-id').value = '';
            document.getElementById('info-aluno-transferir').innerHTML = '<p class="text-gray-500 text-center">Selecione um aluno da lista abaixo</p>';
            document.getElementById('info-turma-atual').innerHTML = '<p class="text-gray-500 text-center">Selecione um aluno para ver a turma atual</p>';
        }

        function fecharModalTransferirAluno() {
            document.getElementById('modal-transferir-aluno').classList.add('hidden');
            document.getElementById('form-transferir-aluno').reset();
            document.getElementById('transferir-aluno-id').value = '';
            document.getElementById('transferir-turma-antiga-id').value = '';
            document.getElementById('resultados-busca-aluno-transferir').classList.add('hidden');
        }

        function selecionarAlunoParaTransferencia(alunoId, nome, cpf, matricula, turmaId, turmaNome) {
            document.getElementById('transferir-aluno-id').value = alunoId;
            document.getElementById('transferir-turma-antiga-id').value = turmaId || '';
            document.getElementById('info-aluno-transferir').innerHTML = `
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 h-12 w-12 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-medium text-lg">${nome.substring(0, 2).toUpperCase()}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">${nome}</p>
                        <p class="text-sm text-gray-500">CPF: ${cpf}</p>
                        <p class="text-sm text-gray-500">Matrícula: ${matricula || 'Não informado'}</p>
                    </div>
                </div>
            `;
            document.getElementById('info-turma-atual').innerHTML = `
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">${turmaNome || 'Sem turma'}</p>
                        <p class="text-sm text-gray-500">Turma atual do aluno</p>
                    </div>
                </div>
            `;
            document.getElementById('resultados-busca-aluno-transferir').classList.add('hidden');
            document.getElementById('buscar-aluno-transferir').value = nome;
        }

        function buscarAlunosParaTransferencia(termo) {
            const resultados = document.getElementById('resultados-busca-aluno-transferir');
            if (termo.length < 2) {
                resultados.classList.add('hidden');
                return;
            }
            
            // Buscar alunos que correspondem ao termo
            fetch(`?acao=buscar_alunos&termo=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos && data.alunos.length > 0) {
                        resultados.innerHTML = data.alunos.map(aluno => {
                            return `
                                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-200" onclick="selecionarAlunoParaTransferencia(${aluno.id}, '${(aluno.nome || '').replace(/'/g, "\\'")}', '${(aluno.cpf || '').replace(/'/g, "\\'")}', '${(aluno.matricula || '').replace(/'/g, "\\'")}', ${aluno.turma_id || 'null'}, '${(aluno.turma_nome || 'Sem turma').replace(/'/g, "\\'")}')">
                                    <p class="font-medium text-gray-900">${aluno.nome || ''}</p>
                                    <p class="text-sm text-gray-500">CPF: ${aluno.cpf || ''} | Matrícula: ${aluno.matricula || 'Não informado'}</p>
                                    ${aluno.turma_nome ? `<p class="text-xs text-blue-600 mt-1">Turma atual: ${aluno.turma_nome}</p>` : ''}
                                </div>
                            `;
                        }).join('');
                        resultados.classList.remove('hidden');
                    } else {
                        resultados.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                        resultados.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar alunos:', error);
                    resultados.innerHTML = '<div class="p-4 text-center text-red-500">Erro ao buscar alunos</div>';
                    resultados.classList.remove('hidden');
                });
        }

        function carregarTurmasParaTransferencia(escolaId) {
            const selectTurma = document.getElementById('transferir-turma-nova-id');
            selectTurma.innerHTML = '<option value="">Carregando...</option>';
            
            if (!escolaId) {
                selectTurma.innerHTML = '<option value="">Primeiro selecione uma escola</option>';
                return;
            }
            
            fetch(`?acao=buscar_turmas&escola_id=${escolaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.turmas) {
                        selectTurma.innerHTML = '<option value="">Selecione uma turma...</option>';
                        data.turmas.forEach(turma => {
                            const option = document.createElement('option');
                            option.value = turma.id;
                            option.textContent = `${turma.serie || ''} ${turma.letra || ''} - ${turma.turno || ''}`;
                            selectTurma.appendChild(option);
                        });
                    } else {
                        selectTurma.innerHTML = '<option value="">Nenhuma turma encontrada</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar turmas:', error);
                    selectTurma.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                });
        }

        function transferirAluno(id) {
            // Buscar informações do aluno e sua turma atual
            fetch(`?acao=buscar_aluno_com_turma&aluno_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.aluno) {
                        const aluno = data.aluno;
                        selecionarAlunoParaTransferencia(
                            aluno.id,
                            aluno.nome || '',
                            aluno.cpf || '',
                            aluno.matricula || '',
                            aluno.turma_id || null,
                            aluno.turma_nome || 'Sem turma'
                        );
                        abrirModalTransferirAluno();
                    } else {
                        showErrorAlert('Erro ao carregar dados do aluno.', 'Erro');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showErrorAlert('Erro ao carregar dados do aluno.', 'Erro');
                });
        }

        // Função para abrir modal de atribuir professor
        function abrirModalAtribuirProfessor(turmaId) {
            document.getElementById('atribuir-professor-turma-id').value = turmaId;
            document.getElementById('modal-atribuir-professor').classList.remove('hidden');
            carregarProfessoresDisponiveis();
        }

        function fecharModalAtribuirProfessor() {
            document.getElementById('modal-atribuir-professor').classList.add('hidden');
            document.getElementById('form-atribuir-professor').reset();
        }

        function carregarProfessoresDisponiveis() {
            const select = document.getElementById('professor_id');
            select.innerHTML = '<option value="">Carregando professores...</option>';
            
            fetch('../../Controllers/gestao/ProfessorController.php')
                .then(resp => resp.json())
                .then(data => {
                    if (data && data.status && Array.isArray(data.professores)) {
                        select.innerHTML = '<option value="">Selecione um professor</option>';
                        data.professores.forEach(prof => {
                            const option = document.createElement('option');
                            option.value = prof.id;
                            option.textContent = prof.nome;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">Nenhum professor disponível</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar professores:', error);
                    select.innerHTML = '<option value="">Erro ao carregar professores</option>';
                });
        }

        function removerProfessor(turmaId, professorId, disciplinaId) {
            if (!confirm('Tem certeza que deseja remover este professor da turma?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'remover_professor');
            formData.append('turma_id', turmaId);
            formData.append('professor_id', professorId);
            formData.append('disciplina_id', disciplinaId);
            
            fetch('gestao_escolar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao remover professor.', 'Erro');
            });
        }

        function verDetalhesProfessor(professorId) {
            // Buscar detalhes do professor e suas atribuições
            fetch(`gestao_escolar.php?acao=buscar_professor&id=${professorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const prof = data.professor;
                        const atribuicoes = data.atribuicoes || [];
                        
                        let html = `
                            <div class="space-y-6">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Professor</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Nome:</span>
                                            <p class="font-medium">${prof.nome || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Matrícula:</span>
                                            <p class="font-medium">${prof.matricula || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Email:</span>
                                            <p class="font-medium">${prof.email || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Telefone:</span>
                                            <p class="font-medium">${prof.telefone || 'Não informado'}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-white rounded-lg border border-gray-200 p-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Atribuições às Turmas (${atribuicoes.length})</h4>
                                    ${atribuicoes.length > 0 ? `
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turma</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Regime</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desde</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${atribuicoes.map(atrib => `
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${atrib.turma || ''}</td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">${atrib.disciplina || ''}</td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">${atrib.regime || ''}</td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">${atrib.inicio || ''}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm py-4">Nenhuma atribuição encontrada.</p>'}
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('conteudo-detalhes-professor').innerHTML = html;
                        document.getElementById('modal-detalhes-professor').classList.remove('hidden');
                    } else {
                        showErrorAlert('Erro ao carregar dados do professor.', 'Erro');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showErrorAlert('Erro ao carregar dados do professor.', 'Erro');
                });
        }

        function fecharModalDetalhesProfessor() {
            document.getElementById('modal-detalhes-professor').classList.add('hidden');
        }

        function verDetalhesAluno(alunoId, turmaId) {
            document.getElementById('modal-detalhes-aluno').classList.remove('hidden');
            document.getElementById('conteudo-detalhes-aluno').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                    <p class="mt-2 text-gray-600">Carregando...</p>
                </div>
            `;
            
            fetch(`gestao_escolar.php?acao=buscar_aluno_acompanhamento&aluno_id=${alunoId}&turma_id=${turmaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const aluno = data.aluno;
                        const notas = data.notas || [];
                        const frequencias = data.frequencias || [];
                        
                        let html = `
                            <div class="space-y-6">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Aluno</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Nome:</span>
                                            <p class="font-medium">${aluno.nome || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Matrícula:</span>
                                            <p class="font-medium">${aluno.matricula || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Turma:</span>
                                            <p class="font-medium">${aluno.turma_nome || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Média Geral:</span>
                                            <p class="font-medium text-lg ${aluno.media_geral >= 7 ? 'text-green-600' : (aluno.media_geral >= 5 ? 'text-yellow-600' : 'text-red-600')}">
                                                ${parseFloat(aluno.media_geral || 0).toFixed(1)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-white rounded-lg border border-gray-200 p-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Notas por Disciplina</h4>
                                    ${notas.length > 0 ? `
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bimestre</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nota</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${notas.map(nota => `
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${nota.disciplina_nome || ''}</td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">${nota.bimestre || '-'}</td>
                                                            <td class="px-4 py-3 text-sm font-semibold ${parseFloat(nota.nota || 0) >= 7 ? 'text-green-600' : (parseFloat(nota.nota || 0) >= 5 ? 'text-yellow-600' : 'text-red-600')}">
                                                                ${parseFloat(nota.nota || 0).toFixed(1)}
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                                ${nota.recuperacao == 1 ? '<span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded">Recuperação</span>' : 'Regular'}
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm py-4">Nenhuma nota registrada.</p>'}
                                </div>
                                
                                <div class="bg-white rounded-lg border border-gray-200 p-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Frequência</h4>
                                    <div class="grid grid-cols-3 gap-4 mb-4">
                                        <div class="text-center p-4 bg-green-50 rounded-lg">
                                            <p class="text-2xl font-bold text-green-600">${aluno.dias_presentes || 0}</p>
                                            <p class="text-sm text-gray-600">Presenças</p>
                                        </div>
                                        <div class="text-center p-4 bg-red-50 rounded-lg">
                                            <p class="text-2xl font-bold text-red-600">${aluno.dias_faltas || 0}</p>
                                            <p class="text-sm text-gray-600">Faltas</p>
                                        </div>
                                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                                            <p class="text-2xl font-bold text-blue-600">${parseFloat(aluno.percentual_frequencia || 0).toFixed(1)}%</p>
                                            <p class="text-sm text-gray-600">Frequência</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('conteudo-detalhes-aluno').innerHTML = html;
                    } else {
                        document.getElementById('conteudo-detalhes-aluno').innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-red-600">Erro ao carregar dados do aluno.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('conteudo-detalhes-aluno').innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-red-600">Erro ao carregar dados do aluno.</p>
                        </div>
                    `;
                });
        }

        function fecharModalDetalhesAluno() {
            document.getElementById('modal-detalhes-aluno').classList.add('hidden');
        }

        function aprovarLancamento(tipo, id) {
            if (!confirm('Tem certeza que deseja aprovar este lançamento?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'aprovar_lancamento');
            formData.append('tipo', tipo);
            formData.append('id', id);
            
            fetch('gestao_escolar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao aprovar lançamento.', 'Erro');
            });
        }

        function rejeitarLancamento(tipo, id) {
            const motivo = prompt('Informe o motivo da rejeição:');
            if (!motivo || motivo.trim() === '') {
                showWarningAlert('É necessário informar o motivo da rejeição.', 'Validação');
                return;
            }
            
            if (!confirm('Tem certeza que deseja rejeitar este lançamento?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'rejeitar_lancamento');
            formData.append('tipo', tipo);
            formData.append('id', id);
            formData.append('observacoes', motivo);
            
            fetch('gestao_escolar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao rejeitar lançamento.', 'Erro');
            });
        }

        // Modal Cadastrar Professor
        function abrirModalCadastrarProfessor() {
            document.getElementById('modal-cadastrar-professor').classList.remove('hidden');
            document.getElementById('form-cadastrar-professor').reset();
        }

        function fecharModalCadastrarProfessor() {
            document.getElementById('modal-cadastrar-professor').classList.add('hidden');
            document.getElementById('form-cadastrar-professor').reset();
        }

        // Modal Cadastrar Funcionário
        function abrirModalCadastrarFuncionario() {
            document.getElementById('modal-cadastrar-funcionario').classList.remove('hidden');
            document.getElementById('form-cadastrar-funcionario').reset();
        }

        function fecharModalCadastrarFuncionario() {
            document.getElementById('modal-cadastrar-funcionario').classList.add('hidden');
            document.getElementById('form-cadastrar-funcionario').reset();
        }
    </script>
    
    <!-- Modal Atribuir Professor -->
    <div id="modal-atribuir-professor" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-primary-green text-white p-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold">Atribuir Professor à Turma</h3>
                        <p class="text-green-100 text-sm mt-1">Selecione o professor e a disciplina</p>
                    </div>
                    <button onclick="fecharModalAtribuirProfessor()" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <form id="form-atribuir-professor" method="POST" class="p-6">
                <input type="hidden" name="acao" value="atribuir_professor">
                <input type="hidden" id="atribuir-professor-turma-id" name="turma_id">
                
                <div class="space-y-6">
                    <div>
                        <label for="professor_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Professor *
                        </label>
                        <select id="professor_id" name="professor_id" required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                            <option value="">Carregando professores...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="disciplina_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Disciplina *
                        </label>
                        <select id="disciplina_id" name="disciplina_id" required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                            <option value="">Selecione uma disciplina</option>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?= $disciplina['id'] ?>"><?= htmlspecialchars($disciplina['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="regime" class="block text-sm font-medium text-gray-700 mb-2">
                            Regime *
                        </label>
                        <select id="regime" name="regime" required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                            <option value="REGULAR">Regular</option>
                            <option value="SUBSTITUTO">Substituto</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200">
                    <button type="button" onclick="fecharModalAtribuirProfessor()" 
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors font-medium">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors font-medium">
                        Atribuir Professor
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Detalhes do Professor -->
    <div id="modal-detalhes-professor" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white w-full h-full flex flex-col">
            <div class="bg-primary-green text-white p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold">Detalhes do Professor</h3>
                        <p class="text-green-100 text-sm mt-1">Informações e atribuições às turmas</p>
                    </div>
                    <button onclick="fecharModalDetalhesProfessor()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-green-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div id="conteudo-detalhes-professor" class="flex-1 overflow-y-auto p-6">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green mx-auto"></div>
                    <p class="text-gray-600 mt-4">Carregando...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detalhes do Aluno -->
    <div id="modal-detalhes-aluno" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-primary-green text-white p-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold">Detalhes do Acompanhamento</h3>
                        <p class="text-green-100 text-sm mt-1">Notas, frequência e desempenho do aluno</p>
                    </div>
                    <button onclick="fecharModalDetalhesAluno()" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div id="conteudo-detalhes-aluno" class="p-6">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green mx-auto"></div>
                    <p class="text-gray-600 mt-4">Carregando...</p>
                </div>
            </div>
        </div>
    </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de Sucesso -->
    <div id="modal-sucesso" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-2xl modal-sucesso-content scale-95">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 modal-sucesso-icon">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sucesso!</h3>
                <div id="modal-sucesso-mensagem" class="text-sm text-gray-600 mb-6 whitespace-pre-line"></div>
                <button onclick="fecharModalSucesso()" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Logout -->
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
        
        // Funções para sidebar e logout
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
        
        // Fechar sidebar ao clicar no overlay
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('mobileOverlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    window.toggleSidebar();
                });
            }
        });
        // ========== FUNÇÕES DE RESPONSÁVEIS ==========
        
        let alunosSelecionados = [];
        let alunosSelecionadosCriar = [];
        
        // Carregar lista de responsáveis
        function carregarResponsaveis() {
            fetch('?acao=listar_responsaveis')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderizarResponsaveis(data.responsaveis);
                    } else {
                        document.getElementById('lista-responsaveis').innerHTML = 
                            '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Erro ao carregar responsáveis</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('lista-responsaveis').innerHTML = 
                        '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Erro ao carregar responsáveis</td></tr>';
                });
        }
        
        function renderizarResponsaveis(responsaveis) {
            const tbody = document.getElementById('lista-responsaveis');
            
            if (!responsaveis || responsaveis.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum responsável cadastrado</td></tr>';
                return;
            }
            
            tbody.innerHTML = responsaveis.map(resp => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${resp.nome || ''}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">${resp.cpf ? resp.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '-'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">${resp.email || '-'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">${resp.telefone || '-'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">${resp.total_alunos || 0} aluno(s)</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="abrirModalAssociarAlunos(${resp.id}, '${resp.nome || ''}')" 
                                class="text-primary-green hover:text-green-700 mr-3">
                            Associar Alunos
                        </button>
                        <button onclick="excluirResponsavel(${resp.id}, '${(resp.nome || '').replace(/'/g, "\\'")}')" 
                                class="text-red-600 hover:text-red-700">
                            Excluir
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function filtrarResponsaveis() {
            const busca = document.getElementById('busca-responsavel').value.toLowerCase();
            const linhas = document.querySelectorAll('#lista-responsaveis tr');
            
            linhas.forEach(linha => {
                const texto = linha.textContent.toLowerCase();
                linha.style.display = texto.includes(busca) ? '' : 'none';
            });
        }
        
        function excluirResponsavel(id, nome) {
            if (!confirm(`Tem certeza que deseja excluir o responsável "${nome}"?\n\nEsta ação irá desativar o responsável e todas as suas associações com alunos.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'excluir_responsavel');
            formData.append('responsavel_id', id);
            
            fetch('gestao_escolar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalSucesso('Responsável excluído com sucesso!');
                    carregarResponsaveis();
                } else {
                    showErrorAlert('Erro: ' + (data.message || 'Erro ao excluir responsável'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao excluir responsável', 'Erro');
            });
        }
        
        function abrirModalCriarResponsavel() {
            document.getElementById('modal-criar-responsavel').classList.remove('hidden');
            document.getElementById('form-criar-responsavel').reset();
            alunosSelecionadosCriar = [];
            atualizarAlunosSelecionadosCriar();
            document.getElementById('busca-aluno-criar').value = '';
            document.getElementById('lista-alunos-criar').innerHTML = '<div class="p-4 text-center text-gray-500">Digite para buscar alunos...</div>';
        }
        
        function fecharModalCriarResponsavel() {
            document.getElementById('modal-criar-responsavel').classList.add('hidden');
        }
        
        function buscarAlunosParaCriar() {
            const busca = document.getElementById('busca-aluno-criar').value.trim();
            
            if (busca.length < 2) {
                document.getElementById('lista-alunos-criar').innerHTML = 
                    '<div class="p-4 text-center text-gray-500">Digite pelo menos 2 caracteres...</div>';
                return;
            }
            
            fetch(`?acao=buscar_alunos&busca=${encodeURIComponent(busca)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        renderizarAlunosParaCriar(data.alunos);
                    } else {
                        document.getElementById('lista-alunos-criar').innerHTML = 
                            '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('lista-alunos-criar').innerHTML = 
                        '<div class="p-4 text-center text-red-500">Erro ao buscar alunos</div>';
                });
        }
        
        function renderizarAlunosParaCriar(alunos) {
            const container = document.getElementById('lista-alunos-criar');
            
            if (!alunos || alunos.length === 0) {
                container.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                return;
            }
            
            container.innerHTML = alunos.map(aluno => {
                const jaSelecionado = alunosSelecionadosCriar.find(a => a.id === aluno.id);
                return `
                    <div class="p-3 border-b border-gray-200 hover:bg-gray-100 flex items-center justify-between bg-white">
                        <div>
                            <div class="font-medium text-gray-900">${aluno.nome || ''}</div>
                            <div class="text-sm text-gray-500">
                                ${aluno.matricula ? 'Matrícula: ' + aluno.matricula : ''} 
                                ${aluno.escola_nome ? ' | ' + aluno.escola_nome : ''}
                            </div>
                        </div>
                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); ${jaSelecionado ? 'removerAlunoSelecionadoCriar' : 'adicionarAlunoSelecionadoCriar'}(${aluno.id}, '${(aluno.nome || '').replace(/'/g, "\\'")}', '${aluno.matricula || ''}')" 
                                class="px-4 py-2 ${jaSelecionado ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-primary-green text-white hover:bg-green-700'} rounded-lg text-sm font-medium transition-colors">
                            ${jaSelecionado ? 'Remover' : 'Adicionar'}
                        </button>
                    </div>
                `;
            }).join('');
        }
        
        function adicionarAlunoSelecionadoCriar(id, nome, matricula) {
            if (!alunosSelecionadosCriar.find(a => a.id === id)) {
                alunosSelecionadosCriar.push({ id, nome, matricula });
                atualizarAlunosSelecionadosCriar();
                buscarAlunosParaCriar(); // Atualizar lista
            }
        }
        
        function removerAlunoSelecionadoCriar(id) {
            alunosSelecionadosCriar = alunosSelecionadosCriar.filter(a => a.id !== id);
            atualizarAlunosSelecionadosCriar();
            buscarAlunosParaCriar(); // Atualizar lista
        }
        
        function atualizarAlunosSelecionadosCriar() {
            const container = document.getElementById('alunos-selecionados-criar');
            
            if (alunosSelecionadosCriar.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">Nenhum aluno selecionado</p>';
                return;
            }
            
            container.innerHTML = alunosSelecionadosCriar.map(aluno => `
                <div class="inline-flex items-center bg-primary-green text-white px-3 py-1 rounded-full mr-2 mb-2">
                    <span class="text-sm">${aluno.nome} ${aluno.matricula ? '(' + aluno.matricula + ')' : ''}</span>
                    <button type="button" onclick="event.preventDefault(); event.stopPropagation(); removerAlunoSelecionadoCriar(${aluno.id})" class="ml-2 hover:text-red-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
        }
        
        function salvarResponsavel() {
            const dados = {
                nome: document.getElementById('responsavel-nome').value,
                cpf: document.getElementById('responsavel-cpf').value.replace(/\D/g, ''),
                data_nascimento: document.getElementById('responsavel-data-nascimento').value || null,
                sexo: document.getElementById('responsavel-sexo').value || null,
                email: document.getElementById('responsavel-email').value || null,
                telefone: document.getElementById('responsavel-telefone').value.replace(/\D/g, '') || null,
                senha: document.getElementById('responsavel-senha').value
            };
            
            if (!dados.nome || !dados.cpf) {
                showWarningAlert('Nome e CPF são obrigatórios', 'Validação');
                return;
            }
            
            if (dados.cpf.length !== 11) {
                showWarningAlert('CPF deve conter 11 dígitos', 'Validação');
                return;
            }
            
            if (!dados.senha || dados.senha.length < 6) {
                showWarningAlert('A senha é obrigatória e deve ter no mínimo 6 caracteres', 'Validação');
                return;
            }
            
            const parentesco = document.getElementById('responsavel-parentesco').value;
            const alunosIds = alunosSelecionadosCriar.map(a => a.id);
            
            // Validar parentesco obrigatório
            if (!parentesco) {
                showWarningAlert('É obrigatório selecionar o parentesco', 'Validação');
                return;
            }
            
            // Validar que pelo menos um aluno foi selecionado
            if (alunosIds.length === 0) {
                showWarningAlert('É obrigatório associar pelo menos um aluno ao responsável', 'Validação');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'criar_responsavel');
            Object.keys(dados).forEach(key => {
                formData.append(key, dados[key]);
            });
            
            // Sempre incluir alunos (agora é obrigatório)
            formData.append('associar_alunos', '1');
            formData.append('alunos_ids', JSON.stringify(alunosIds));
            formData.append('parentesco', parentesco);
            
            fetch('gestao_escolar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        let mensagem = `Responsável criado com sucesso!\n\nUsuário: ${data.username}\n\n${alunosIds.length} aluno(s) associado(s) com sucesso!\n\nAnote essas informações!`;
                        mostrarModalSucesso(mensagem);
                        fecharModalCriarResponsavel();
                        carregarResponsaveis();
                    } else {
                        showErrorAlert('Erro: ' + (data.message || 'Erro ao criar responsável'), 'Erro');
                    }
                } catch (e) {
                    console.error('Erro ao parsear JSON:', e);
                    console.error('Resposta do servidor:', text);
                    showErrorAlert('Erro ao processar resposta do servidor. Verifique o console para mais detalhes.', 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao criar responsável: ' + error.message, 'Erro');
            });
        }
        
        function abrirModalAssociarAlunos(responsavelId, responsavelNome) {
            document.getElementById('modal-associar-alunos').classList.remove('hidden');
            document.getElementById('responsavel-id-associar').value = responsavelId;
            document.getElementById('responsavel-nome-associar').textContent = `Responsável: ${responsavelNome}`;
            alunosSelecionados = [];
            atualizarAlunosSelecionados();
            document.getElementById('busca-aluno-associar').value = '';
            document.getElementById('lista-alunos-associar').innerHTML = '<div class="p-4 text-center text-gray-500">Digite para buscar alunos...</div>';
        }
        
        function fecharModalAssociarAlunos() {
            document.getElementById('modal-associar-alunos').classList.add('hidden');
            alunosSelecionados = [];
        }
        
        function buscarAlunosParaAssociar() {
            const busca = document.getElementById('busca-aluno-associar').value.trim();
            
            if (busca.length < 2) {
                document.getElementById('lista-alunos-associar').innerHTML = 
                    '<div class="p-4 text-center text-gray-500">Digite pelo menos 2 caracteres...</div>';
                return;
            }
            
            fetch(`?acao=buscar_alunos&busca=${encodeURIComponent(busca)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        renderizarAlunosParaAssociar(data.alunos);
                    } else {
                        document.getElementById('lista-alunos-associar').innerHTML = 
                            '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('lista-alunos-associar').innerHTML = 
                        '<div class="p-4 text-center text-red-500">Erro ao buscar alunos</div>';
                });
        }
        
        function renderizarAlunosParaAssociar(alunos) {
            const container = document.getElementById('lista-alunos-associar');
            
            if (!alunos || alunos.length === 0) {
                container.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                return;
            }
            
            container.innerHTML = alunos.map(aluno => {
                const jaSelecionado = alunosSelecionados.find(a => a.id === aluno.id);
                return `
                    <div class="p-3 border-b border-gray-200 hover:bg-gray-50 flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900">${aluno.nome || ''}</div>
                            <div class="text-sm text-gray-500">
                                ${aluno.matricula ? 'Matrícula: ' + aluno.matricula : ''} 
                                ${aluno.escola_nome ? ' | ' + aluno.escola_nome : ''}
                            </div>
                        </div>
                        <button onclick="${jaSelecionado ? 'removerAlunoSelecionado' : 'adicionarAlunoSelecionado'}(${aluno.id}, '${(aluno.nome || '').replace(/'/g, "\\'")}', '${aluno.matricula || ''}')" 
                                class="px-4 py-2 ${jaSelecionado ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-primary-green text-white hover:bg-green-700'} rounded-lg text-sm font-medium transition-colors">
                            ${jaSelecionado ? 'Remover' : 'Adicionar'}
                        </button>
                    </div>
                `;
            }).join('');
        }
        
        function adicionarAlunoSelecionado(id, nome, matricula) {
            if (!alunosSelecionados.find(a => a.id === id)) {
                alunosSelecionados.push({ id, nome, matricula });
                atualizarAlunosSelecionados();
                buscarAlunosParaAssociar(); // Atualizar lista
            }
        }
        
        function removerAlunoSelecionado(id) {
            alunosSelecionados = alunosSelecionados.filter(a => a.id !== id);
            atualizarAlunosSelecionados();
            buscarAlunosParaAssociar(); // Atualizar lista
        }
        
        function atualizarAlunosSelecionados() {
            const container = document.getElementById('alunos-selecionados');
            
            if (alunosSelecionados.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">Nenhum aluno selecionado</p>';
                return;
            }
            
            container.innerHTML = alunosSelecionados.map(aluno => `
                <div class="inline-flex items-center bg-primary-green text-white px-3 py-1 rounded-full mr-2 mb-2">
                    <span class="text-sm">${aluno.nome} ${aluno.matricula ? '(' + aluno.matricula + ')' : ''}</span>
                    <button onclick="removerAlunoSelecionado(${aluno.id})" class="ml-2 hover:text-red-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
        }
        
        function salvarAssociacao() {
            const responsavelId = document.getElementById('responsavel-id-associar').value;
            const parentesco = document.getElementById('parentesco-associar').value;
            
            if (alunosSelecionados.length === 0) {
                showWarningAlert('Selecione pelo menos um aluno', 'Validação');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'associar_alunos');
            formData.append('responsavel_id', responsavelId);
            formData.append('parentesco', parentesco);
            formData.append('alunos', JSON.stringify(alunosSelecionados.map(a => a.id)));
            
            fetch('gestao_escolar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('Alunos associados com sucesso!', 'Sucesso');
                    fecharModalAssociarAlunos();
                    carregarResponsaveis();
                } else {
                    showErrorAlert('Erro: ' + (data.message || 'Erro ao associar alunos'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao associar alunos', 'Erro');
            });
        }
        
    </script>
    
    <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
    <script>
        // Funções para modal de desperdício
        function abrirModalRegistrarDesperdicio() {
            const modal = document.getElementById('modalRegistrarDesperdicio');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                document.getElementById('formDesperdicio').reset();
                document.getElementById('desperdicio-data').value = new Date().toISOString().split('T')[0];
                document.getElementById('desperdicio-escola-id').value = '<?= $escolaGestorId ?>';
                document.getElementById('desperdicio-escola-id').disabled = true;
                document.getElementById('alertaErroDesperdicio').classList.add('hidden');
                document.getElementById('alertaSucessoDesperdicio').classList.add('hidden');
                
                // Resetar campo de observação
                const observacaoContainer = document.getElementById('observacao-desperdicio-container');
                const observacaoInput = document.getElementById('desperdicio-observacoes-outros');
                observacaoContainer.classList.add('hidden');
                observacaoInput.removeAttribute('required');
                observacaoInput.classList.remove('border-red-300');
                observacaoInput.value = '';
            }
        }
        
        function fecharModalRegistrarDesperdicio() {
            const modal = document.getElementById('modalRegistrarDesperdicio');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        function toggleObservacaoDesperdicio() {
            const motivo = document.getElementById('desperdicio-motivo').value;
            const container = document.getElementById('observacao-desperdicio-container');
            const input = document.getElementById('desperdicio-observacoes-outros');
            
            if (motivo === 'OUTROS') {
                container.classList.remove('hidden');
                input.setAttribute('required', 'required');
                input.classList.add('border-red-300');
            } else {
                container.classList.add('hidden');
                input.removeAttribute('required');
                input.classList.remove('border-red-300');
                input.value = '';
            }
        }
        
        async function salvarDesperdicio() {
            const alertaErro = document.getElementById('alertaErroDesperdicio');
            const alertaSucesso = document.getElementById('alertaSucessoDesperdicio');
            const motivo = document.getElementById('desperdicio-motivo').value;
            
            // Esconder alertas anteriores
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Validar observação se motivo for OUTROS
            if (motivo === 'OUTROS') {
                const observacaoOutros = document.getElementById('desperdicio-observacoes-outros').value.trim();
                if (!observacaoOutros) {
                    alertaErro.textContent = 'Por favor, descreva o motivo do desperdício quando selecionar "Outro".';
                    alertaErro.classList.remove('hidden');
                    // Focar no campo de observação
                    document.getElementById('desperdicio-observacoes-outros').focus();
                    return;
                }
            }
            
            const formData = new FormData();
            formData.append('acao', 'registrar_desperdicio');
            formData.append('escola_id', '<?= $escolaGestorId ?>');
            formData.append('data', document.getElementById('desperdicio-data').value);
            formData.append('turno', document.getElementById('desperdicio-turno').value);
            formData.append('produto_id', document.getElementById('desperdicio-produto-id').value);
            formData.append('quantidade', document.getElementById('desperdicio-quantidade').value);
            
            const produtoSelect = document.getElementById('desperdicio-produto-id');
            const produtoOption = produtoSelect.options[produtoSelect.selectedIndex];
            if (produtoOption) {
                formData.append('unidade_medida', produtoOption.getAttribute('data-unidade') || '');
            }
            
            formData.append('peso_kg', document.getElementById('desperdicio-peso').value);
            formData.append('motivo', motivo);
            
            // Se motivo for OUTROS, usar observação como motivo_detalhado
            if (motivo === 'OUTROS') {
                formData.append('observacoes_outros', document.getElementById('desperdicio-observacoes-outros').value);
                formData.append('motivo_detalhado', document.getElementById('desperdicio-observacoes-outros').value);
            } else {
                formData.append('motivo_detalhado', '');
            }
            
            formData.append('observacoes', document.getElementById('desperdicio-observacoes').value);
            
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.text();
                let result;
                try {
                    result = JSON.parse(data);
                } catch (e) {
                    // Se não for JSON, pode ser HTML de erro
                    console.error('Resposta não é JSON:', data);
                    alertaErro.textContent = 'Erro ao processar resposta do servidor.';
                    alertaErro.classList.remove('hidden');
                    return;
                }
                
                if (result.success || <?= $tipoMensagem === 'success' ? 'true' : 'false' ?>) {
                    alertaSucesso.textContent = 'Desperdício registrado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
                    setTimeout(() => {
                        fecharModalRegistrarDesperdicio();
                        location.reload();
                    }, 1500);
                } else {
                    alertaErro.textContent = result.message || 'Erro ao registrar desperdício.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            }
        }
        
    </script>
    <?php endif; ?>
    
    <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
    <script>
        // Verificar se deve abrir modal de desperdício ao carregar a página
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const acao = urlParams.get('acao');
            
            if (acao === 'abrir_desperdicio') {
                setTimeout(() => {
                    if (typeof abrirModalRegistrarDesperdicio === 'function') {
                        abrirModalRegistrarDesperdicio();
                    } else {
                        // Se a função ainda não estiver disponível, tentar novamente
                        setTimeout(() => {
                            if (typeof abrirModalRegistrarDesperdicio === 'function') {
                                abrirModalRegistrarDesperdicio();
                            }
                        }, 500);
                    }
                }, 300);
            }
        });
    </script>
    <?php endif; ?>
    
    <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
    <!-- Modal Registrar Desperdício -->
    <div id="modalRegistrarDesperdicio" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl m-4">
            <div class="bg-red-600 text-white p-6 flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-2xl font-bold">Registrar Desperdício da Escola</h3>
                <button onclick="fecharModalRegistrarDesperdicio()" class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-red-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <form id="formDesperdicio" class="space-y-6">
                    <div id="alertaErroDesperdicio" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucessoDesperdicio" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                            <select id="desperdicio-escola-id" required disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="<?= $escolaGestorId ?>"><?= htmlspecialchars($escolaGestor) ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data *</label>
                            <input type="date" id="desperdicio-data" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <select id="desperdicio-produto-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">Selecione um produto</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>" data-unidade="<?= htmlspecialchars($produto['unidade_medida']) ?>">
                                        <?= htmlspecialchars($produto['nome']) ?> (<?= htmlspecialchars($produto['unidade_medida']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
                            <select id="desperdicio-turno" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">Selecione</option>
                                <option value="MANHA">Manhã</option>
                                <option value="TARDE">Tarde</option>
                                <option value="NOITE">Noite</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                            <input type="number" step="0.001" min="0" id="desperdicio-quantidade" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Peso (kg) *</label>
                            <input type="number" step="0.01" min="0" id="desperdicio-peso" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo *</label>
                            <select id="desperdicio-motivo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" onchange="toggleObservacaoDesperdicio()">
                                <option value="EXCESSO_PREPARO">Excesso de Preparo</option>
                                <option value="REJEICAO_ALUNOS">Rejeição dos Alunos</option>
                                <option value="VALIDADE_VENCIDA">Validade Vencida</option>
                                <option value="PREPARO_INCORRETO">Preparo Incorreto</option>
                                <option value="OUTROS">Outro</option>
                            </select>
                        </div>
                    </div>
                    <div id="observacao-desperdicio-container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observação (Motivo "Outro") <span class="text-red-500">*</span></label>
                        <textarea id="desperdicio-observacoes-outros" rows="3" placeholder="Descreva o motivo do desperdício..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações Adicionais</label>
                        <textarea id="desperdicio-observacoes" rows="3" placeholder="Observações adicionais sobre o desperdício..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 border-t border-gray-200 p-6 sticky bottom-0">
                <div class="flex space-x-3">
                    <button onclick="fecharModalRegistrarDesperdicio()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                        Cancelar
                    </button>
                    <button onclick="salvarDesperdicio()" class="flex-1 px-6 py-3 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors">
                        Salvar Registro
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>

