<?php
// Iniciar output buffering para evitar problemas com headers
if (!ob_get_level()) {
    ob_start();
}
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/academico/AlunoModel.php');
require_once('../../Models/pessoas/ResponsavelModel.php');
require_once('../../Models/academico/TurmaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO
if ($_SESSION['tipo'] !== 'GESTAO' && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$alunoModel = new AlunoModel();
$responsavelModel = new ResponsavelModel();
$turmaModel = new TurmaModel();

// Buscar escola do gestor logado
$db = Database::getInstance();
$conn = $db->getConnection();
$escolaGestor = null;
$escolaGestorId = null;

// Log inicial
error_log("DEBUG GESTOR INICIAL - Tipo: " . ($_SESSION['tipo'] ?? 'NULL') . ", usuario_id: " . ($_SESSION['usuario_id'] ?? 'NULL'));

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

// Usar a escola atual encontrada
if ($escolaIdAtual) {
    $escolaGestorId = $escolaIdAtual;
    $escolaGestor = $escolaNomeAtual;
}

// Se ainda não encontrou escola e é gestor, redirecionar
if (!$escolaGestorId && $_SESSION['tipo'] === 'GESTAO') {
    header('Location: gestao_escolar.php?erro=escola_nao_encontrada');
    exit;
}

// Buscar turmas da escola
$turmas = [];
if ($escolaGestorId) {
    try {
        $turmas = $turmaModel->listar(['escola_id' => $escolaGestorId, 'ativo' => 1]);
    } catch (Exception $e) {
        error_log("Erro ao buscar turmas: " . $e->getMessage());
    }
}

// Processar requisições POST
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_e_matricular_aluno') {
    header('Content-Type: application/json');
    try {
        // Preparar dados
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
        $emailInformado = !empty($_POST['email']) ? trim($_POST['email']) : '';
        
        // Validar CPF
        if (empty($cpf) || strlen($cpf) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }
        
        // Verificar se CPF já existe
        $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf");
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            throw new Exception('CPF já cadastrado no sistema');
        }
        
        // Verificar se email já existe
        if (!empty($emailInformado)) {
            $stmtEmail = $conn->prepare("SELECT id FROM pessoa WHERE email = :email LIMIT 1");
            $stmtEmail->bindParam(':email', $emailInformado);
            $stmtEmail->execute();
            if ($stmtEmail->fetch()) {
                throw new Exception('Email já cadastrado no sistema');
            }
        }
        
        // Gerar matrícula se não informada
        $matricula = !empty($_POST['matricula']) ? trim($_POST['matricula']) : '';
        if (empty($matricula)) {
            $ano = date('Y');
            $sqlMatricula = "SELECT MAX(CAST(SUBSTRING(matricula, 5) AS UNSIGNED)) as ultima_matricula 
                            FROM aluno 
                            WHERE matricula LIKE :ano_prefix";
            $stmtMatricula = $conn->prepare($sqlMatricula);
            $anoPrefix = $ano . '%';
            $stmtMatricula->bindParam(':ano_prefix', $anoPrefix);
            $stmtMatricula->execute();
            $result = $stmtMatricula->fetch(PDO::FETCH_ASSOC);
            $proximoNumero = ($result['ultima_matricula'] ?? 0) + 1;
            $matricula = $ano . str_pad($proximoNumero, 4, '0', STR_PAD_LEFT);
        }
        
        // Preparar dados para o model
        $dados = [
            'cpf' => $cpf,
            'nome' => trim($_POST['nome'] ?? ''),
            'data_nascimento' => $_POST['data_nascimento'] ?? null,
            'sexo' => $_POST['sexo'] ?? null,
            'email' => !empty($emailInformado) ? $emailInformado : null,
            'telefone' => !empty($telefone) ? $telefone : null,
            'endereco' => !empty($_POST['endereco']) ? trim($_POST['endereco']) : null,
            'numero' => !empty($_POST['numero']) ? trim($_POST['numero']) : null,
            'complemento' => !empty($_POST['complemento']) ? trim($_POST['complemento']) : null,
            'bairro' => !empty($_POST['bairro']) ? trim($_POST['bairro']) : null,
            'cidade' => !empty($_POST['cidade']) ? trim($_POST['cidade']) : null,
            'estado' => !empty($_POST['estado']) ? trim($_POST['estado']) : 'CE',
            'cep' => !empty($_POST['cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['cep'])) : null,
            'matricula' => $matricula,
            'nis' => !empty($_POST['nis']) ? preg_replace('/[^0-9]/', '', trim($_POST['nis'])) : null,
            'escola_id' => $escolaGestorId,
            'data_matricula' => $_POST['data_matricula'] ?? date('Y-m-d'),
            'situacao' => 'MATRICULADO',
            'precisa_transporte' => isset($_POST['precisa_transporte']) ? 1 : 0,
            'distrito_transporte' => !empty($_POST['distrito_transporte']) ? trim($_POST['distrito_transporte']) : null,
            'localidade_transporte' => !empty($_POST['localidade_transporte']) ? trim($_POST['localidade_transporte']) : null,
            'nome_social' => !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null,
            'raca' => !empty($_POST['raca']) ? trim($_POST['raca']) : null,
            'is_pcd' => isset($_POST['is_pcd']) ? 1 : 0,
            'cids' => !empty($_POST['cids']) && is_array($_POST['cids']) ? $_POST['cids'] : []
        ];
        
        // Validar campos obrigatórios
        if (empty($dados['nome'])) {
            throw new Exception('Nome é obrigatório.');
        }
        if (empty($dados['data_nascimento'])) {
            throw new Exception('Data de nascimento é obrigatória.');
        }
        if (empty($dados['sexo'])) {
            throw new Exception('Sexo é obrigatório.');
        }
        
        // Usar o model para criar o aluno
        $result = $alunoModel->criar($dados);
        
        if ($result['success']) {
            $alunoId = $result['id'] ?? null;
            $mensagem = 'Aluno cadastrado com sucesso!';
            
            // Atualizar campos de transporte se necessário
            if (isset($dados['precisa_transporte']) || isset($dados['distrito_transporte']) || isset($dados['localidade_transporte'])) {
                try {
                    // Verificar se as colunas existem
                    $stmtCheckPrecisa = $conn->query("SHOW COLUMNS FROM aluno LIKE 'precisa_transporte'");
                    $temPrecisaTransporte = $stmtCheckPrecisa->rowCount() > 0;
                    
                    $stmtCheckDistrito = $conn->query("SHOW COLUMNS FROM aluno LIKE 'distrito_transporte'");
                    $temDistritoTransporte = $stmtCheckDistrito->rowCount() > 0;
                    
                    $stmtCheckLocalidade = $conn->query("SHOW COLUMNS FROM aluno LIKE 'localidade_transporte'");
                    $temLocalidadeTransporte = $stmtCheckLocalidade->rowCount() > 0;
                    
                    if ($temPrecisaTransporte || $temDistritoTransporte || $temLocalidadeTransporte) {
                        $camposUpdate = [];
                        $paramsUpdate = [':aluno_id' => $alunoId];
                        
                        if ($temPrecisaTransporte) {
                            $camposUpdate[] = 'precisa_transporte = :precisa_transporte';
                            $paramsUpdate[':precisa_transporte'] = isset($dados['precisa_transporte']) ? (int)$dados['precisa_transporte'] : 0;
                        }
                        
                        // Sempre salvar distrito_transporte se a coluna existir e o valor não for vazio
                        if ($temDistritoTransporte && isset($dados['distrito_transporte']) && trim($dados['distrito_transporte']) !== '') {
                            $camposUpdate[] = 'distrito_transporte = :distrito_transporte';
                            $paramsUpdate[':distrito_transporte'] = trim($dados['distrito_transporte']);
                        } elseif ($temDistritoTransporte && isset($dados['distrito_transporte']) && trim($dados['distrito_transporte']) === '') {
                            // Se foi enviado vazio, limpar o campo
                            $camposUpdate[] = 'distrito_transporte = NULL';
                        }
                        
                        // Sempre salvar localidade_transporte se a coluna existir e o valor não for vazio
                        if ($temLocalidadeTransporte && isset($dados['localidade_transporte']) && trim($dados['localidade_transporte']) !== '') {
                            $camposUpdate[] = 'localidade_transporte = :localidade_transporte';
                            $paramsUpdate[':localidade_transporte'] = trim($dados['localidade_transporte']);
                        } elseif ($temLocalidadeTransporte && isset($dados['localidade_transporte']) && trim($dados['localidade_transporte']) === '') {
                            // Se foi enviado vazio, limpar o campo
                            $camposUpdate[] = 'localidade_transporte = NULL';
                        }
                        
                        if (!empty($camposUpdate)) {
                            $sqlUpdate = "UPDATE aluno SET " . implode(', ', $camposUpdate) . " WHERE id = :aluno_id";
                            $stmtUpdate = $conn->prepare($sqlUpdate);
                            foreach ($paramsUpdate as $key => $value) {
                                $stmtUpdate->bindValue($key, $value);
                            }
                            $stmtUpdate->execute();
                        }
                    }
                } catch (Exception $e) {
                    error_log("Erro ao atualizar campos de transporte: " . $e->getMessage());
                }
            }
            
            // Matricular em turma se informada
            $turmaId = !empty($_POST['turma_id']) ? $_POST['turma_id'] : null;
            if ($turmaId && $alunoId) {
                $resultadoMatricula = $alunoModel->matricularEmTurma($alunoId, $turmaId, $dados['data_matricula']);
                if ($resultadoMatricula) {
                    $mensagem .= ' Aluno matriculado na turma com sucesso!';
                }
            }
            
            // Cadastrar responsável se informado
            $criarResponsavel = !empty($_POST['responsavel_nome']) && !empty($_POST['responsavel_cpf']);
            if ($criarResponsavel && $alunoId) {
                $responsavelCpf = preg_replace('/[^0-9]/', '', $_POST['responsavel_cpf'] ?? '');
                $responsavelTelefone = preg_replace('/[^0-9]/', '', $_POST['responsavel_telefone'] ?? '');
                $responsavelEmail = !empty($_POST['responsavel_email']) ? trim($_POST['responsavel_email']) : '';
                
                if (strlen($responsavelCpf) !== 11) {
                    throw new Exception('CPF do responsável inválido. Deve conter 11 dígitos.');
                }
                
                if (empty($_POST['responsavel_nome'])) {
                    throw new Exception('Nome do responsável é obrigatório.');
                }
                
                $dadosResponsavel = [
                    'cpf' => $responsavelCpf,
                    'nome' => trim($_POST['responsavel_nome']),
                    'email' => !empty($responsavelEmail) ? $responsavelEmail : null,
                    'telefone' => !empty($responsavelTelefone) ? $responsavelTelefone : null,
                    'endereco' => !empty($_POST['responsavel_endereco']) ? trim($_POST['responsavel_endereco']) : null,
                    'numero' => !empty($_POST['responsavel_numero']) ? trim($_POST['responsavel_numero']) : null,
                    'complemento' => !empty($_POST['responsavel_complemento']) ? trim($_POST['responsavel_complemento']) : null,
                    'bairro' => !empty($_POST['responsavel_bairro']) ? trim($_POST['responsavel_bairro']) : null,
                    'cidade' => !empty($_POST['responsavel_cidade']) ? trim($_POST['responsavel_cidade']) : null,
                    'estado' => !empty($_POST['responsavel_estado']) ? trim($_POST['responsavel_estado']) : 'CE',
                    'cep' => !empty($_POST['responsavel_cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['responsavel_cep'])) : null,
                ];
                
                $resultadoResponsavel = $responsavelModel->criar($dadosResponsavel);
                
                if ($resultadoResponsavel['success']) {
                    $responsavelPessoaId = $resultadoResponsavel['pessoa_id'] ?? null;
                    
                    if ($responsavelPessoaId) {
                        // Atualizar o responsavel_id na tabela aluno
                        $sqlAtualizarResponsavel = "UPDATE aluno SET responsavel_id = :responsavel_id WHERE id = :aluno_id";
                        $stmtAtualizarResp = $conn->prepare($sqlAtualizarResponsavel);
                        $stmtAtualizarResp->bindParam(':responsavel_id', $responsavelPessoaId);
                        $stmtAtualizarResp->bindParam(':aluno_id', $alunoId);
                        $stmtAtualizarResp->execute();
                        
                        // Associar responsável ao aluno
                        $parentesco = !empty($_POST['responsavel_parentesco']) ? $_POST['responsavel_parentesco'] : 'OUTRO';
                        $associacao = $responsavelModel->associarAlunos($responsavelPessoaId, [$alunoId], $parentesco);
                        
                        if ($associacao['success']) {
                            $mensagem .= ' Responsável cadastrado e associado com sucesso!';
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => $mensagem,
                'id' => $alunoId,
                'matricula' => $matricula
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Erro ao cadastrar aluno.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Buscar turmas para o select
$turmasSelect = [];
if ($escolaGestorId) {
    try {
        $sqlTurmas = "SELECT id, CONCAT(serie, ' ', letra, ' - ', turno) as nome, serie, letra, turno
                      FROM turma 
                      WHERE escola_id = :escola_id AND ativo = 1
                      ORDER BY serie, letra, turno";
        $stmtTurmas = $conn->prepare($sqlTurmas);
        $stmtTurmas->bindParam(':escola_id', $escolaGestorId);
        $stmtTurmas->execute();
        $turmasSelect = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao buscar turmas: " . $e->getMessage());
    }
}

// Endpoint para buscar localidades por distrito
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'buscar_localidades' && !empty($_GET['distrito'])) {
    header('Content-Type: application/json');
    try {
        $distrito = $_GET['distrito'];
        
        $sql = "SELECT id, localidade, endereco, bairro, cidade, estado, cep
                FROM distrito_localidade
                WHERE distrito = :distrito AND ativo = 1
                ORDER BY localidade ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':distrito', $distrito);
        $stmt->execute();
        $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'localidades' => $localidades]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Matricular Aluno') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
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
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .etapa-conteudo {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .autocomplete-container {
            position: relative;
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 4px;
            display: none;
        }
        .autocomplete-dropdown.show {
            display: block;
        }
        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.15s;
            border-bottom: 1px solid #f3f4f6;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background-color: #f3f4f6;
        }
    </style>
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
                        <h1 class="text-xl font-semibold text-gray-800">Matricular Aluno</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="gestao_escolar.php" class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                            Voltar
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-6xl mx-auto">
                <!-- Mensagens -->
                <div id="alerta-erro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"></div>
                <div id="alerta-sucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"></div>
                
                <form id="formMatricularAluno" class="space-y-6 bg-white rounded-lg shadow-lg p-6">
                    <!-- Indicador de Etapas -->
                    <div class="flex items-center space-x-4 mb-6 pb-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div id="step-indicator-1" class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-sm">1</div>
                            <span class="ml-2 text-sm font-medium text-gray-700">Dados do Aluno</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div id="step-indicator-2" class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-semibold text-sm">2</div>
                            <span class="ml-2 text-sm font-medium text-gray-500">Responsável (Opcional)</span>
                        </div>
                    </div>
                    
                    <!-- ETAPA 1: Dados do Aluno -->
                    <div id="etapa-aluno" class="etapa-conteudo">
                        <!-- Informações Pessoais -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo <span class="text-red-500">*</span></label>
                                    <input type="text" name="nome" id="nome" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CPF <span class="text-red-500">*</span></label>
                                    <input type="text" name="cpf" id="cpf" required maxlength="14"
                                           placeholder="000.000.000-00"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="formatarCPF(this)">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento <span class="text-red-500">*</span></label>
                                    <input type="date" name="data_nascimento" id="data_nascimento" required max="<?= date('Y-m-d') ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sexo <span class="text-red-500">*</span></label>
                                    <select name="sexo" id="sexo" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                        <option value="">Selecione...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Feminino</option>
                                        <option value="OUTRO">Outro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" id="email"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                    <input type="text" name="telefone" id="telefone" maxlength="15"
                                           placeholder="(00) 00000-0000"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="formatarTelefone(this)">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                                    <input type="text" name="nome_social" id="nome_social"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           placeholder="Nome pelo qual prefere ser chamado">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Raça/Cor</label>
                                    <select name="raca" id="raca"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                        <option value="">Selecione...</option>
                                        <option value="BRANCA">Branca</option>
                                        <option value="PRETA">Preta</option>
                                        <option value="PARDA">Parda</option>
                                        <option value="AMARELA">Amarela</option>
                                        <option value="INDIGENA">Indígena</option>
                                        <option value="NAO_DECLARADA">Não declarada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Endereço do Aluno -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço</h3>
                            <div class="mb-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" name="endereco_mesmo_responsavel" id="endereco_mesmo_responsavel" value="1" 
                                           onchange="toggleEnderecoAluno()"
                                           class="w-5 h-5 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                    <span class="text-sm font-medium text-gray-700">Endereço é o mesmo do responsável</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1 ml-7">Se marcado, o endereço do responsável será usado para o aluno</p>
                            </div>
                            <div id="container-endereco-aluno" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CEP <span class="text-red-500">*</span></label>
                                    <input type="text" name="cep" id="cep" maxlength="9"
                                           placeholder="00000-000"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="formatarCEP(this)">
                                    <p class="text-xs text-gray-500 mt-1">Digite o CEP para preencher automaticamente</p>
                                </div>
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                    <input type="text" name="endereco" id="endereco"
                                           placeholder="Rua, Avenida, etc."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                    <input type="text" name="numero" id="numero"
                                           placeholder="Número"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                    <input type="text" name="complemento" id="complemento"
                                           placeholder="Apartamento, bloco, etc."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                    <input type="text" name="bairro" id="bairro"
                                           placeholder="Bairro"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                    <input type="text" name="cidade" id="cidade"
                                           placeholder="Cidade"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                    <select name="estado" id="estado"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                        <option value="">Selecione...</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE" selected>Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações Acadêmicas -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Acadêmicas</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                                    <input type="text" name="matricula" id="matricula" readonly
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                           placeholder="Será gerada automaticamente">
                                    <p class="text-xs text-gray-500 mt-1">A matrícula será gerada automaticamente se deixada em branco</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">NIS (Número de Identificação Social)</label>
                                    <input type="text" name="nis" id="nis" maxlength="14"
                                           placeholder="000.00000.00-0"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Escola <span class="text-red-500">*</span></label>
                                    <input type="text" value="<?= htmlspecialchars($escolaGestor ?? 'Escola não encontrada') ?>" 
                                           disabled
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                                    <input type="hidden" name="escola_id" value="<?= $escolaGestorId ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                                    <select name="turma_id" id="turma_id"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                        <option value="">Selecione uma turma (opcional)...</option>
                                        <?php foreach ($turmasSelect as $turma): ?>
                                            <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Matrícula</label>
                                    <input type="date" name="data_matricula" id="data_matricula"
                                           value="<?= date('Y-m-d') ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações de PCD -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Deficiência</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <input type="checkbox" name="is_pcd" id="is_pcd" value="1" 
                                           onchange="toggleCamposPCD()"
                                           class="w-5 h-5 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                    <label for="is_pcd" class="text-sm font-medium text-gray-700 cursor-pointer">
                                        Aluno é Pessoa com Deficiência (PCD)
                                    </label>
                                </div>
                                <div id="container-cids" class="hidden">
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <label class="block text-sm font-semibold text-gray-800 mb-3">CID (Código de Classificação Internacional de Doenças)</label>
                                        <div id="lista-cids" class="space-y-3 mb-4">
                                            <!-- CIDs serão adicionados dinamicamente aqui -->
                                        </div>
                                        <button type="button" onclick="adicionarCampoCID()" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium flex items-center space-x-2 inline-flex">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            <span>Adicionar CID</span>
                                        </button>
                                        <p class="text-xs text-gray-600 mt-3">Adicione um ou mais códigos CID caso o aluno tenha deficiência</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações de Transporte -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Transporte Escolar</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="precisa_transporte" id="precisa_transporte" value="1" 
                                               onchange="toggleDistritoTransporte()"
                                               class="w-5 h-5 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                        <span class="text-sm font-medium text-gray-700">Aluno precisa de transporte escolar</span>
                                    </label>
                                </div>
                                <div id="container-distrito-transporte" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Distrito de Origem</label>
                                        <div class="autocomplete-container">
                                            <input type="text" name="distrito_transporte" id="distrito_transporte" 
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" 
                                                   placeholder="Digite o distrito..." autocomplete="off"
                                                   oninput="buscarDistritos(this.value)"
                                                   onchange="if(this.value) { distritoSelecionado = this.value; carregarLocalidades(this.value); }">
                                            <div id="autocomplete-dropdown-transporte" class="autocomplete-dropdown"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Selecione o distrito onde o aluno precisa de transporte</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Localidade</label>
                                        <div class="autocomplete-container">
                                            <input type="text" name="localidade_transporte" id="localidade_transporte" 
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" 
                                                   placeholder="Digite a localidade..." autocomplete="off"
                                                   oninput="buscarLocalidades(this.value)">
                                            <div id="autocomplete-dropdown-localidade" class="autocomplete-dropdown"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Selecione a localidade do distrito selecionado</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Navegação Etapa 1 -->
                        <div class="flex justify-end space-x-3 pt-6 mt-6 border-t border-gray-200">
                            <a href="gestao_escolar.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Cancelar
                            </a>
                            <button type="button" onclick="mostrarEtapa(2)" 
                                    class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green font-medium transition-colors">
                                Próximo: Responsável (Opcional)
                            </button>
                            <button type="button" onclick="salvarAluno(true)" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                                Cadastrar e Matricular
                            </button>
                        </div>
                    </div>
                    
                    <!-- ETAPA 2: Dados do Responsável (Opcional) -->
                    <div id="etapa-responsavel" class="etapa-conteudo hidden">
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <strong>Opcional:</strong> Você pode cadastrar um responsável para este aluno agora. Se preferir, pode fazer isso depois.
                            </p>
                        </div>
                        
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Dados do Responsável</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo</label>
                                    <input type="text" name="responsavel_nome" id="responsavel_nome"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                                    <input type="text" name="responsavel_cpf" id="responsavel_cpf" maxlength="14"
                                           placeholder="000.000.000-00"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="formatarCPF(this)">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                    <input type="text" name="responsavel_telefone" id="responsavel_telefone" maxlength="15"
                                           placeholder="(00) 00000-0000"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="formatarTelefone(this)">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" name="responsavel_email" id="responsavel_email"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Endereço do Responsável -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço do Responsável</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                    <input type="text" name="responsavel_cep" id="responsavel_cep" maxlength="9"
                                           placeholder="00000-000"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="formatarCEP(this)">
                                    <p class="text-xs text-gray-500 mt-1">Digite o CEP para preencher automaticamente</p>
                                </div>
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                    <input type="text" name="responsavel_endereco" id="responsavel_endereco"
                                           placeholder="Rua, Avenida, etc."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="copiarEnderecoParaAluno()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                    <input type="text" name="responsavel_numero" id="responsavel_numero"
                                           placeholder="Número"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="copiarEnderecoParaAluno()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                    <input type="text" name="responsavel_complemento" id="responsavel_complemento"
                                           placeholder="Apartamento, bloco, etc."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="copiarEnderecoParaAluno()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                    <input type="text" name="responsavel_bairro" id="responsavel_bairro"
                                           placeholder="Bairro"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="copiarEnderecoParaAluno()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                    <input type="text" name="responsavel_cidade" id="responsavel_cidade"
                                           placeholder="Cidade"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           oninput="copiarEnderecoParaAluno()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                    <select name="responsavel_estado" id="responsavel_estado"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                            onchange="copiarEnderecoParaAluno()">
                                        <option value="">Selecione...</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE" selected>Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Acesso ao Sistema -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Acesso ao Sistema</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                                    <input type="password" name="responsavel_senha" id="responsavel_senha" minlength="6"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                           placeholder="Mínimo 6 caracteres">
                                    <p class="text-xs text-gray-500 mt-1">A senha deve ter no mínimo 6 caracteres</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Parentesco</label>
                                    <select name="responsavel_parentesco" id="responsavel_parentesco"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                        <option value="">Selecione...</option>
                                        <option value="PAI">Pai</option>
                                        <option value="MAE">Mãe</option>
                                        <option value="AVO">Avô/Avó</option>
                                        <option value="TIO">Tio/Tia</option>
                                        <option value="OUTRO">Outro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Navegação Etapa 2 -->
                        <div class="flex justify-between space-x-3 pt-6 mt-6 border-t border-gray-200">
                            <button type="button" onclick="mostrarEtapa(1)" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                Voltar
                            </button>
                            <div class="flex space-x-3">
                                <a href="gestao_escolar.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                                    Cancelar
                                </a>
                                <button type="button" onclick="salvarAluno(false)" 
                                        class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green font-medium transition-colors">
                                    Cadastrar e Matricular
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Lista de distritos de Maranguape
        const distritosMaranguape = [
            'Amanari', 'Antônio Marques', 'Cachoeira', 'Itapebussu', 'Jubaia',
            'Ladeira Grande', 'Lages', 'Lagoa do Juvenal', 'Manoel Guedes',
            'Sede', 'Papara', 'Penedo', 'Sapupara', 'São João do Amanari',
            'Tanques', 'Umarizeiras', 'Vertentes do Lagedo'
        ];
        
        // Funções de formatação
        function formatarCPF(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                input.value = value;
            }
        }
        
        function formatarTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                input.value = value;
            }
        }
        
        function formatarCEP(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                input.value = value;
            }
        }
        
        function buscarCEP(cep, campoEndereco, campoBairro, campoCidade, campoEstado) {
            // Remover formatação do CEP
            const cepLimpo = cep.replace(/\D/g, '');
            
            // Validar CEP (deve ter 8 dígitos)
            if (cepLimpo.length !== 8) {
                return;
            }
            
            // Mostrar loading
            const inputEndereco = document.getElementById(campoEndereco);
            if (inputEndereco) {
                inputEndereco.disabled = true;
                inputEndereco.placeholder = 'Buscando...';
            }
            
            // Buscar CEP na API ViaCEP
            fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`)
                .then(response => response.json())
                .then(data => {
                    // Reabilitar campos
                    if (inputEndereco) {
                        inputEndereco.disabled = false;
                        inputEndereco.placeholder = 'Rua, Avenida, etc.';
                    }
                    
                    // Verificar se o CEP foi encontrado
                    if (data.erro) {
                        console.log('CEP não encontrado');
                        return;
                    }
                    
                    // Preencher campos
                    if (inputEndereco && data.logradouro) {
                        inputEndereco.value = data.logradouro;
                    }
                    
                    const inputBairro = document.getElementById(campoBairro);
                    if (inputBairro && data.bairro) {
                        inputBairro.value = data.bairro;
                    }
                    
                    const inputCidade = document.getElementById(campoCidade);
                    if (inputCidade && data.localidade) {
                        inputCidade.value = data.localidade;
                    }
                    
                    const selectEstado = document.getElementById(campoEstado);
                    if (selectEstado && data.uf) {
                        selectEstado.value = data.uf;
                    }
                    
                    // Se for endereço do responsável e o checkbox estiver marcado, copiar para aluno
                    if (campoEndereco === 'responsavel_endereco' && document.getElementById('endereco_mesmo_responsavel')?.checked) {
                        copiarEnderecoParaAluno();
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    // Reabilitar campos em caso de erro
                    if (inputEndereco) {
                        inputEndereco.disabled = false;
                        inputEndereco.placeholder = 'Rua, Avenida, etc.';
                    }
                });
        }
        
        // Navegação entre etapas
        function mostrarEtapa(etapa) {
            document.getElementById('etapa-aluno').classList.add('hidden');
            document.getElementById('etapa-responsavel').classList.add('hidden');
            
            document.getElementById('step-indicator-1').classList.remove('bg-blue-600', 'text-white');
            document.getElementById('step-indicator-1').classList.add('bg-gray-300', 'text-gray-600');
            document.getElementById('step-indicator-2').classList.remove('bg-blue-600', 'text-white');
            document.getElementById('step-indicator-2').classList.add('bg-gray-300', 'text-gray-600');
            
            if (etapa === 1) {
                document.getElementById('etapa-aluno').classList.remove('hidden');
                document.getElementById('step-indicator-1').classList.remove('bg-gray-300', 'text-gray-600');
                document.getElementById('step-indicator-1').classList.add('bg-blue-600', 'text-white');
            } else if (etapa === 2) {
                document.getElementById('etapa-responsavel').classList.remove('hidden');
                document.getElementById('step-indicator-2').classList.remove('bg-gray-300', 'text-gray-600');
                document.getElementById('step-indicator-2').classList.add('bg-blue-600', 'text-white');
            }
        }
        
        // Toggle endereço
        function toggleEnderecoAluno() {
            const mesmoResponsavel = document.getElementById('endereco_mesmo_responsavel').checked;
            const container = document.getElementById('container-endereco-aluno');
            if (mesmoResponsavel) {
                container.classList.add('hidden');
            } else {
                container.classList.remove('hidden');
            }
        }
        
        // Copiar endereço do responsável para o aluno
        function copiarEnderecoParaAluno() {
            if (document.getElementById('endereco_mesmo_responsavel').checked) {
                document.getElementById('endereco').value = document.getElementById('responsavel_endereco').value || '';
                document.getElementById('numero').value = document.getElementById('responsavel_numero').value || '';
                document.getElementById('complemento').value = document.getElementById('responsavel_complemento').value || '';
                document.getElementById('bairro').value = document.getElementById('responsavel_bairro').value || '';
                document.getElementById('cidade').value = document.getElementById('responsavel_cidade').value || '';
                document.getElementById('estado').value = document.getElementById('responsavel_estado').value || '';
                document.getElementById('cep').value = document.getElementById('responsavel_cep').value || '';
            }
        }
        
        // Toggle campos PCD
        function toggleCamposPCD() {
            const isPCD = document.getElementById('is_pcd').checked;
            const container = document.getElementById('container-cids');
            if (isPCD) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
        
        // Adicionar campo CID
        function adicionarCampoCID() {
            const container = document.getElementById('lista-cids');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-end';
            div.innerHTML = `
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Código CID</label>
                    <input type="text" name="cids[${index}][codigo]" placeholder="Ex: F84.0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Descrição</label>
                    <input type="text" name="cids[${index}][descricao]" placeholder="Descrição (opcional)" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="button" onclick="removerCampoCID(this)" 
                        class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
        }
        
        function removerCampoCID(btn) {
            btn.parentElement.remove();
        }
        
        // Toggle distrito transporte
        function toggleDistritoTransporte() {
            const precisaTransporte = document.getElementById('precisa_transporte').checked;
            const container = document.getElementById('container-distrito-transporte');
            const containerLocalidade = document.getElementById('container-localidade-transporte');
            if (precisaTransporte) {
                container.classList.remove('hidden');
                initAutocompleteDistrito();
            } else {
                container.classList.add('hidden');
                document.getElementById('distrito_transporte').value = '';
                document.getElementById('localidade_transporte').value = '';
                distritoSelecionado = null;
                localidadesDisponiveis = [];
            }
        }
        
        // Autocomplete distrito
        let distritoSelecionado = null;
        let localidadesDisponiveis = [];
        
        function buscarDistritos(query) {
            const input = document.getElementById('distrito_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-transporte');
            if (!input || !dropdown) return;
            
            const queryLower = query.trim().toLowerCase();
            
            if (queryLower.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            const filteredDistritos = distritosMaranguape.filter(distrito => 
                distrito.toLowerCase().includes(queryLower)
            );
            
            if (filteredDistritos.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            dropdown.innerHTML = filteredDistritos.map((distrito) => `
                <div class="autocomplete-item" onclick="selecionarDistrito('${distrito}')">
                    <div>${distrito}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        function selecionarDistrito(distrito) {
            document.getElementById('distrito_transporte').value = distrito;
            document.getElementById('autocomplete-dropdown-transporte').classList.remove('show');
            distritoSelecionado = distrito;
            
            // Carregar localidades do distrito
            carregarLocalidades(distrito);
        }
        
        function carregarLocalidades(distrito) {
            if (!distrito) {
                return;
            }
            
            fetch(`?acao=buscar_localidades&distrito=${encodeURIComponent(distrito)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.localidades && data.localidades.length > 0) {
                        localidadesDisponiveis = data.localidades;
                        distritoSelecionado = distrito;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar localidades:', error);
                });
        }
        
        function buscarLocalidades(query) {
            const input = document.getElementById('localidade_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-localidade');
            if (!input || !dropdown || !distritoSelecionado) return;
            
            const queryLower = query.trim().toLowerCase();
            
            if (queryLower.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            const filteredLocalidades = localidadesDisponiveis.filter(loc => 
                loc.localidade.toLowerCase().includes(queryLower)
            );
            
            if (filteredLocalidades.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            dropdown.innerHTML = filteredLocalidades.map((loc) => `
                <div class="autocomplete-item" onclick="selecionarLocalidade('${loc.localidade.replace(/'/g, "\\'")}')">
                    <div>${loc.localidade}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        function initAutocompleteLocalidade() {
            const input = document.getElementById('localidade_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-localidade');
            if (!input || !dropdown) return;
            
            let selectedIndex = -1;
            
            input.addEventListener('keydown', function(e) {
                if (!dropdown.classList.contains('show')) return;
                const items = dropdown.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        items[selectedIndex].click();
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });
            
            function updateSelection(items) {
                items.forEach((item, index) => {
                    if (index === selectedIndex) {
                        item.classList.add('selected');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
            
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
        
        function selecionarLocalidade(localidade) {
            document.getElementById('localidade_transporte').value = localidade;
            document.getElementById('autocomplete-dropdown-localidade').classList.remove('show');
        }
        
        function initAutocompleteDistrito() {
            const input = document.getElementById('distrito_transporte');
            if (!input) return;
            
            // Limpar event listeners anteriores se existirem
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            
            // Adicionar novo event listener
            newInput.addEventListener('keydown', function(e) {
                const dropdown = document.getElementById('autocomplete-dropdown-transporte');
                if (!dropdown || !dropdown.classList.contains('show')) return;
                
                const items = dropdown.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const selected = dropdown.querySelector('.autocomplete-item.selected');
                    let next = selected ? selected.nextElementSibling : items[0];
                    if (next) {
                        items.forEach(i => i.classList.remove('selected'));
                        next.classList.add('selected');
                        next.scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const selected = dropdown.querySelector('.autocomplete-item.selected');
                    let prev = selected ? selected.previousElementSibling : items[items.length - 1];
                    if (prev) {
                        items.forEach(i => i.classList.remove('selected'));
                        prev.classList.add('selected');
                        prev.scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const selected = dropdown.querySelector('.autocomplete-item.selected');
                    if (selected) {
                        selected.click();
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });
            
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('autocomplete-dropdown-transporte');
                if (dropdown && !newInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
        
        // Salvar aluno
        function salvarAluno(pularResponsavel = false) {
            const form = document.getElementById('formMatricularAluno');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            formData.append('acao', 'cadastrar_e_matricular_aluno');
            
            // Limpar formatação
            const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
            const telefone = document.getElementById('telefone')?.value.replace(/\D/g, '') || '';
            const responsavelCpf = document.getElementById('responsavel_cpf')?.value.replace(/\D/g, '') || '';
            const responsavelTelefone = document.getElementById('responsavel_telefone')?.value.replace(/\D/g, '') || '';
            const cep = document.getElementById('cep')?.value.replace(/\D/g, '') || '';
            const responsavelCep = document.getElementById('responsavel_cep')?.value.replace(/\D/g, '') || '';
            
            formData.set('cpf', cpf);
            if (telefone) formData.set('telefone', telefone);
            if (cep) formData.set('cep', cep);
            
            if (pularResponsavel) {
                formData.delete('responsavel_nome');
                formData.delete('responsavel_cpf');
                formData.delete('responsavel_telefone');
                formData.delete('responsavel_email');
            } else {
                if (responsavelCpf) formData.set('responsavel_cpf', responsavelCpf);
                if (responsavelTelefone) formData.set('responsavel_telefone', responsavelTelefone);
                if (responsavelCep) formData.set('responsavel_cep', responsavelCep);
            }
            
            // Mostrar loading
            const btnSalvar = event?.target || document.querySelector('[onclick*="salvarAluno"]');
            const textoOriginal = btnSalvar?.textContent || 'Cadastrar e Matricular';
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.textContent = 'Salvando...';
            }
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                    btnSalvar.textContent = textoOriginal;
                }
                
                if (data.success) {
                    const alertaSucesso = document.getElementById('alerta-sucesso');
                    if (alertaSucesso) {
                        alertaSucesso.textContent = data.message || 'Aluno cadastrado e matriculado com sucesso!';
                        alertaSucesso.classList.remove('hidden');
                    }
                    
                    setTimeout(() => {
                        window.location.href = 'gestao_escolar.php';
                    }, 2000);
                } else {
                    const alertaErro = document.getElementById('alerta-erro');
                    if (alertaErro) {
                        alertaErro.textContent = data.message || 'Erro ao cadastrar aluno';
                        alertaErro.classList.remove('hidden');
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                    btnSalvar.textContent = textoOriginal;
                }
                const alertaErro = document.getElementById('alerta-erro');
                if (alertaErro) {
                    alertaErro.textContent = 'Erro ao cadastrar aluno. Tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            });
        }
        
        // Inicializar autocomplete quando necessário
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar autocomplete de distrito se o checkbox estiver marcado
            if (document.getElementById('precisa_transporte')?.checked) {
                initAutocompleteDistrito();
            }
        });
    </script>
</body>
</html>

