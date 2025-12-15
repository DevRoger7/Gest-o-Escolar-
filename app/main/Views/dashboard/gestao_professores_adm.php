<?php
// Iniciar output buffering para evitar problemas com headers
if (!ob_get_level()) {
    ob_start();
}

// Verificar se é requisição AJAX (GET ou POST com ação) - processar ANTES de qualquer output
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) || 
    ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']))) {
    
    // Limpar TODOS os buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Desabilitar exibição de erros ANTES de qualquer require
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
    
    // Capturar qualquer output dos requires e métodos de sessão
    ob_start();
    require_once('../../Models/sessao/sessions.php');
    require_once('../../config/permissions_helper.php');
    require_once('../../config/Database.php');
    require_once('../../config/system_helper.php');
    ob_end_clean();
    
    // Capturar qualquer output dos métodos de sessão
    // IMPORTANTE: Para requisições AJAX, não permitir redirects
    try {
        ob_start();
        $session = new sessions();
        
        // Verificar autenticação manualmente para AJAX (sem redirects)
        if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
            ob_end_clean();
            ob_clean();
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'message' => 'Sessão não autenticada'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verificar tempo de sessão manualmente
        $tempo_limite = 24 * 60 * 60;
        if (isset($_SESSION['ultimo_acesso'])) {
            $tempo_inativo = time() - $_SESSION['ultimo_acesso'];
            if ($tempo_inativo > $tempo_limite) {
                ob_end_clean();
                ob_clean();
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode(['success' => false, 'message' => 'Sessão expirada'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        $_SESSION['ultimo_acesso'] = time();
        
        $output = ob_get_clean();
        
        // Se houver output inesperado, limpar
        if (!empty($output)) {
            ob_clean();
        }
    } catch (Exception $e) {
        ob_end_clean();
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Erro de autenticação'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!eAdm()) {
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Processar requisições POST AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
        }
        
        if ($_POST['acao'] === 'cadastrar_professor') {
            try {
            // Preparar dados
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            
            // Validar CPF
            if (empty($cpf) || strlen($cpf) !== 11) {
                throw new Exception('CPF inválido. Deve conter 11 dígitos.');
            }
            
            // Validar data de nascimento (não pode ser futura)
            if (!empty($_POST['data_nascimento'])) {
                $dataNasc = new DateTime($_POST['data_nascimento']);
                $hoje = new DateTime();
                if ($dataNasc > $hoje) {
                    throw new Exception('Data de nascimento não pode ser futura.');
                }
            }
            
            // Verificar se CPF já existe
            $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf";
            $stmtVerificar = $conn->prepare($sqlVerificarCPF);
            $stmtVerificar->bindParam(':cpf', $cpf);
            $stmtVerificar->execute();
            if ($stmtVerificar->fetch()) {
                throw new Exception('CPF já cadastrado no sistema.');
            }
            
            // Gerar username único baseado no primeiro nome
            $nome = trim($_POST['nome'] ?? '');
            $primeiroNome = explode(' ', $nome)[0];
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $primeiroNome));
            
            // Verificar se username já existe e gerar um único
            $sqlVerificarUsername = "SELECT id FROM usuario WHERE username = :username";
            $stmtUsername = $conn->prepare($sqlVerificarUsername);
            $stmtUsername->bindParam(':username', $username);
            $stmtUsername->execute();
            
            if ($stmtUsername->fetch()) {
                $count = 1;
                $newUsername = $username . $count;
                while (true) {
                    $stmtUsername = $conn->prepare($sqlVerificarUsername);
                    $stmtUsername->bindParam(':username', $newUsername);
                    $stmtUsername->execute();
                    if (!$stmtUsername->fetch()) {
                        $username = $newUsername;
                        break;
                    }
                    $count++;
                    $newUsername = $username . $count;
                }
            }
            
            // Senha padrão
            $senhaPadrao = $_POST['senha'] ?? '123456';
            $senhaHash = password_hash($senhaPadrao, PASSWORD_DEFAULT);
            
            $conn->beginTransaction();
                
            // 1. Criar pessoa
            $dataNascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
            $sexo = !empty($_POST['sexo']) ? $_POST['sexo'] : null;
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $telefoneVal = !empty($telefone) ? $telefone : null;
            $criadoPor = $_SESSION['usuario_id'];
            
            // Preparar dados de endereço
            $endereco = !empty($_POST['endereco']) ? trim($_POST['endereco']) : null;
            $numero = !empty($_POST['numero']) ? trim($_POST['numero']) : null;
            $complemento = !empty($_POST['complemento']) ? trim($_POST['complemento']) : null;
            $bairro = !empty($_POST['bairro']) ? trim($_POST['bairro']) : null;
            $cidade = !empty($_POST['cidade']) ? trim($_POST['cidade']) : null;
            $estado = 'CE'; // Sempre Ceará (Maranguape/CE)
            $cep = !empty($_POST['cep']) ? preg_replace('/[^0-9]/', '', $_POST['cep']) : null;
            
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, 
                         endereco, numero, complemento, bairro, cidade, estado, cep, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone,
                         :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, 'PROFESSOR', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $nome);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimento);
            $stmtPessoa->bindParam(':sexo', $sexo);
            $stmtPessoa->bindParam(':email', $email);
            $stmtPessoa->bindParam(':telefone', $telefoneVal);
            $stmtPessoa->bindParam(':endereco', $endereco);
            $stmtPessoa->bindParam(':numero', $numero);
            $stmtPessoa->bindParam(':complemento', $complemento);
            $stmtPessoa->bindParam(':bairro', $bairro);
            $stmtPessoa->bindParam(':cidade', $cidade);
            $stmtPessoa->bindParam(':estado', $estado);
            $stmtPessoa->bindParam(':cep', $cep);
            $stmtPessoa->bindParam(':criado_por', $criadoPor);
            $stmtPessoa->execute();
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar professor
            $matricula = !empty($_POST['matricula']) ? trim($_POST['matricula']) : null;
            
            // Processar múltiplas formações
            $formacoes = [];
            if (!empty($_POST['formacoes']) && is_array($_POST['formacoes'])) {
                foreach ($_POST['formacoes'] as $form) {
                    $form = trim($form);
                    if (!empty($form)) {
                        $formacoes[] = $form;
                    }
                }
            }
            $formacao = !empty($formacoes) ? json_encode($formacoes, JSON_UNESCAPED_UNICODE) : null;
            
            // Processar múltiplas especializações
            $especializacoes = [];
            if (!empty($_POST['especializacoes']) && is_array($_POST['especializacoes'])) {
                foreach ($_POST['especializacoes'] as $esp) {
                    $esp = trim($esp);
                    if (!empty($esp)) {
                        $especializacoes[] = $esp;
                    }
                }
            }
            $especializacao = !empty($especializacoes) ? json_encode($especializacoes, JSON_UNESCAPED_UNICODE) : null;
            
            $registroProfissional = !empty($_POST['registro_profissional']) ? trim($_POST['registro_profissional']) : null;
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
                $observacaoLotacao = !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null;
                
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
            
            // Registrar log de criação de professor
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
            require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logCriarProfessor($usuarioLogadoId, $professorId, $nome);
            
            echo json_encode([
                'success' => true,
                'message' => 'Professor cadastrado com sucesso!',
                'id' => $professorId,
                'username' => $username
            ]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'editar_professor') {
        try {
            $professorId = $_POST['professor_id'] ?? null;
            if (empty($professorId)) {
                throw new Exception('ID do professor não informado.');
            }
            
            // Buscar professor existente
            $sqlBuscar = "SELECT pr.*, p.id as pessoa_id, p.cpf, p.endereco, p.numero, p.complemento, 
                         p.bairro, p.cidade, p.estado, p.cep, u.username
                         FROM professor pr
                         INNER JOIN pessoa p ON pr.pessoa_id = p.id
                         LEFT JOIN usuario u ON u.pessoa_id = p.id
                         WHERE pr.id = :id";
            $stmtBuscar = $conn->prepare($sqlBuscar);
            $stmtBuscar->bindParam(':id', $professorId);
            $stmtBuscar->execute();
            $professor = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
            
            if (!$professor) {
                throw new Exception('Professor não encontrado.');
            }
            
            // Preparar dados
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            
            // Validar CPF (se foi alterado)
            $cpfAtual = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            if (!empty($cpfAtual) && strlen($cpfAtual) !== 11) {
                throw new Exception('CPF inválido. Deve conter 11 dígitos.');
            }
            
            // Verificar se CPF já existe em outro professor
            if (!empty($cpfAtual) && $cpfAtual !== $professor['cpf']) {
                $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id";
                $stmtVerificar = $conn->prepare($sqlVerificarCPF);
                $stmtVerificar->bindParam(':cpf', $cpfAtual);
                $stmtVerificar->bindParam(':pessoa_id', $professor['pessoa_id']);
                $stmtVerificar->execute();
                if ($stmtVerificar->fetch()) {
                    throw new Exception('CPF já cadastrado para outro professor.');
                }
            }
            
            $conn->beginTransaction();
            
            // 1. Atualizar CPF se foi alterado
            if (!empty($cpfAtual) && $cpfAtual !== $professor['cpf']) {
                $sqlUpdateCPF = "UPDATE pessoa SET cpf = :cpf WHERE id = :pessoa_id";
                $stmtUpdateCPF = $conn->prepare($sqlUpdateCPF);
                $stmtUpdateCPF->bindParam(':cpf', $cpfAtual);
                $stmtUpdateCPF->bindParam(':pessoa_id', $professor['pessoa_id']);
                $stmtUpdateCPF->execute();
            }
            
            // 2. Atualizar pessoa
            $nomeUpdate = trim($_POST['nome'] ?? '');
            $dataNascimentoUpdate = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
            $sexoUpdate = !empty($_POST['sexo']) ? $_POST['sexo'] : null;
            $emailUpdate = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $telefoneUpdate = !empty($telefone) ? $telefone : null;
            
            // Preparar dados de endereço
            $endereco = !empty($_POST['endereco']) ? trim($_POST['endereco']) : null;
            $numero = !empty($_POST['numero']) ? trim($_POST['numero']) : null;
            $complemento = !empty($_POST['complemento']) ? trim($_POST['complemento']) : null;
            $bairro = !empty($_POST['bairro']) ? trim($_POST['bairro']) : null;
            $cidade = !empty($_POST['cidade']) ? trim($_POST['cidade']) : null;
            $estado = 'CE'; // Sempre Ceará (Maranguape/CE)
            $cep = !empty($_POST['cep']) ? preg_replace('/[^0-9]/', '', $_POST['cep']) : null;
            
            $sqlPessoa = "UPDATE pessoa SET nome = :nome, data_nascimento = :data_nascimento, 
                          sexo = :sexo, email = :email, telefone = :telefone,
                          endereco = :endereco, numero = :numero, complemento = :complemento,
                          bairro = :bairro, cidade = :cidade, estado = :estado, cep = :cep
                          WHERE id = :pessoa_id";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':nome', $nomeUpdate);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimentoUpdate);
            $stmtPessoa->bindParam(':sexo', $sexoUpdate);
            $stmtPessoa->bindParam(':email', $emailUpdate);
            $stmtPessoa->bindParam(':telefone', $telefoneUpdate);
            $stmtPessoa->bindParam(':endereco', $endereco);
            $stmtPessoa->bindParam(':numero', $numero);
            $stmtPessoa->bindParam(':complemento', $complemento);
            $stmtPessoa->bindParam(':bairro', $bairro);
            $stmtPessoa->bindParam(':cidade', $cidade);
            $stmtPessoa->bindParam(':estado', $estado);
            $stmtPessoa->bindParam(':cep', $cep);
            $stmtPessoa->bindParam(':pessoa_id', $professor['pessoa_id']);
            $stmtPessoa->execute();
            
            // 3. Atualizar professor
            $matriculaUpdate = !empty($_POST['matricula']) ? trim($_POST['matricula']) : null;
            
            // Processar múltiplas formações
            $formacoes = [];
            if (!empty($_POST['formacoes']) && is_array($_POST['formacoes'])) {
                foreach ($_POST['formacoes'] as $form) {
                    $form = trim($form);
                    if (!empty($form)) {
                        $formacoes[] = $form;
                    }
                }
            }
            $formacaoUpdate = !empty($formacoes) ? json_encode($formacoes, JSON_UNESCAPED_UNICODE) : null;
            
            // Processar múltiplas especializações
            $especializacoes = [];
            if (!empty($_POST['especializacoes']) && is_array($_POST['especializacoes'])) {
                foreach ($_POST['especializacoes'] as $esp) {
                    $esp = trim($esp);
                    if (!empty($esp)) {
                        $especializacoes[] = $esp;
                    }
                }
            }
            $especializacaoUpdate = !empty($especializacoes) ? json_encode($especializacoes, JSON_UNESCAPED_UNICODE) : null;
            
            $registroProfissionalUpdate = !empty($_POST['registro_profissional']) ? trim($_POST['registro_profissional']) : null;
            $dataAdmissaoUpdate = !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : $professor['data_admissao'];
            $ativoUpdate = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
            
            $sqlProf = "UPDATE professor SET matricula = :matricula, formacao = :formacao, 
                       especializacao = :especializacao, registro_profissional = :registro_profissional,
                       data_admissao = :data_admissao, ativo = :ativo
                       WHERE id = :id";
            $stmtProf = $conn->prepare($sqlProf);
            $stmtProf->bindParam(':matricula', $matriculaUpdate);
            $stmtProf->bindParam(':formacao', $formacaoUpdate);
            $stmtProf->bindParam(':especializacao', $especializacaoUpdate);
            $stmtProf->bindParam(':registro_profissional', $registroProfissionalUpdate);
            $stmtProf->bindParam(':data_admissao', $dataAdmissaoUpdate);
            $stmtProf->bindParam(':ativo', $ativoUpdate);
            $stmtProf->bindParam(':id', $professorId);
            $stmtProf->execute();
            
            // 4. Atualizar senha se fornecida
            if (!empty($_POST['senha']) && $_POST['senha'] !== '123456') {
                $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                $sqlSenha = "UPDATE usuario SET senha_hash = :senha_hash WHERE pessoa_id = :pessoa_id";
                $stmtSenha = $conn->prepare($sqlSenha);
                $stmtSenha->bindParam(':senha_hash', $senhaHash);
                $stmtSenha->bindParam(':pessoa_id', $professor['pessoa_id']);
                $stmtSenha->execute();
            }
            
            $conn->commit();
            
            // Registrar log de edição de professor
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
            require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logEditarProfessor($usuarioLogadoId, $professorId, $nomeUpdate);
            
            echo json_encode([
                'success' => true,
                'message' => 'Professor atualizado com sucesso!'
            ]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_professor') {
        try {
            $professorId = $_POST['professor_id'] ?? null;
            if (empty($professorId)) {
                throw new Exception('ID do professor não informado.');
            }
            
            // Verificar se o professor existe
            $sqlBuscar = "SELECT pr.*, p.nome
                         FROM professor pr
                         INNER JOIN pessoa p ON pr.pessoa_id = p.id
                         WHERE pr.id = :id";
            $stmtBuscar = $conn->prepare($sqlBuscar);
            $stmtBuscar->bindParam(':id', $professorId);
            $stmtBuscar->execute();
            $professor = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
            
            if (!$professor) {
                throw new Exception('Professor não encontrado.');
            }
            
            // Verificar se o professor está atribuído a alguma turma ativa
            $sqlTurmaAtiva = "SELECT COUNT(*) as total FROM turma_professor WHERE professor_id = :professor_id AND fim IS NULL";
            $stmtTurma = $conn->prepare($sqlTurmaAtiva);
            $stmtTurma->bindParam(':professor_id', $professorId);
            $stmtTurma->execute();
            $resultTurma = $stmtTurma->fetch(PDO::FETCH_ASSOC);
            
            if ($resultTurma['total'] > 0) {
                throw new Exception('Não é possível excluir o professor pois ele está atribuído a uma ou mais turmas ativas. Primeiro, remova o professor das turmas.');
            }
            
            // Soft delete
            $sqlExcluir = "UPDATE professor SET ativo = 0 WHERE id = :id";
            $stmtExcluir = $conn->prepare($sqlExcluir);
            $stmtExcluir->bindParam(':id', $professorId);
            $result = $stmtExcluir->execute();
            
            if ($result) {
                // Registrar log de exclusão/desativação de professor
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
                require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
                $logger = SystemLogger::getInstance();
                $logger->logExcluirProfessor($usuarioLogadoId, $professorId, $professor['nome']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Professor excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir professor.');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'reverter_exclusao_professor') {
        try {
            $professorId = $_POST['professor_id'] ?? null;
            if (empty($professorId)) {
                throw new Exception('ID do professor não informado.');
            }
            
            // Converter para inteiro
            $professorId = (int)$professorId;
            if ($professorId <= 0) {
                throw new Exception('ID do professor inválido.');
            }
            
            // Verificar se o professor existe (mesmo se estiver inativo)
            $sqlBuscar = "SELECT pr.*, p.nome
                         FROM professor pr
                         INNER JOIN pessoa p ON pr.pessoa_id = p.id
                         WHERE pr.id = :id";
            $stmtBuscar = $conn->prepare($sqlBuscar);
            $stmtBuscar->bindParam(':id', $professorId, PDO::PARAM_INT);
            $stmtBuscar->execute();
            $professor = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
            
            if (!$professor) {
                error_log("Tentativa de reverter exclusão: Professor ID $professorId não encontrado no banco de dados");
                throw new Exception('Professor não encontrado no banco de dados. ID: ' . $professorId);
            }
            
            // Reverter soft delete (ativar novamente)
            $sqlReverter = "UPDATE professor SET ativo = 1 WHERE id = :id";
            $stmtReverter = $conn->prepare($sqlReverter);
            $stmtReverter->bindParam(':id', $professorId, PDO::PARAM_INT);
            $result = $stmtReverter->execute();
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Exclusão revertida com sucesso! O professor foi reativado.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Erro ao reverter exclusão do professor.');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            error_log("Erro ao reverter exclusão: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição. Tente novamente.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'lotar_professor') {
        ob_clean();
        try {
            $professorId = $_POST['professor_id'] ?? null;
            $escolaId = $_POST['escola_id'] ?? null;
            $dataInicio = $_POST['data_inicio'] ?? date('Y-m-d');
            $cargaHoraria = !empty($_POST['carga_horaria']) ? (int)$_POST['carga_horaria'] : null;
            $observacao = !empty($_POST['observacao']) ? trim($_POST['observacao']) : null;
            $tipoOperacao = $_POST['tipo_operacao'] ?? 'alocar'; // 'transferir' ou 'alocar'
            
            if (empty($professorId) || empty($escolaId)) {
                throw new Exception('Professor e escola são obrigatórios.');
            }
            
            $professorId = (int)$professorId;
            $escolaId = (int)$escolaId;
            
            if ($professorId <= 0 || $escolaId <= 0) {
                throw new Exception('IDs inválidos.');
            }
            
            // Verificar se já existe lotação ativa para este professor nesta escola
            $sqlVerificar = "SELECT id FROM professor_lotacao 
                            WHERE professor_id = :professor_id 
                            AND escola_id = :escola_id 
                            AND fim IS NULL";
            $stmtVerificar = $conn->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmtVerificar->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtVerificar->execute();
            $lotacaoExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            if ($lotacaoExistente) {
                throw new Exception('O professor já está lotado nesta escola.');
            }
            
            // Se for transferência, encerrar todas as lotações anteriores
            if ($tipoOperacao === 'transferir') {
                $sqlEncerrar = "UPDATE professor_lotacao 
                               SET fim = CURDATE() 
                               WHERE professor_id = :professor_id 
                               AND fim IS NULL";
                $stmtEncerrar = $conn->prepare($sqlEncerrar);
                $stmtEncerrar->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
                $stmtEncerrar->execute();
            }
            // Se for alocação, manter todas as lotações ativas (não fazer nada)
            
            // Criar nova lotação
            $sqlLotar = "INSERT INTO professor_lotacao (professor_id, escola_id, inicio, carga_horaria, observacao, criado_em)
                        VALUES (:professor_id, :escola_id, :inicio, :carga_horaria, :observacao, NOW())";
            $stmtLotar = $conn->prepare($sqlLotar);
            $stmtLotar->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmtLotar->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtLotar->bindParam(':inicio', $dataInicio);
            $stmtLotar->bindParam(':carga_horaria', $cargaHoraria);
            $stmtLotar->bindParam(':observacao', $observacao);
            $stmtLotar->execute();
            
            ob_clean();
            $mensagem = $tipoOperacao === 'transferir' 
                ? 'Professor transferido com sucesso!' 
                : 'Professor alocado com sucesso!';
            
            echo json_encode([
                'success' => true,
                'message' => $mensagem
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            ob_clean();
            error_log("Erro ao lotar professor: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            ob_clean();
            error_log("Erro PDO ao lotar professor: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição. Tente novamente.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'remover_lotacao') {
        ob_clean();
        try {
            $professorId = $_POST['professor_id'] ?? null;
            $escolaId = $_POST['escola_id'] ?? null;
            
            if (empty($professorId) || empty($escolaId)) {
                throw new Exception('Professor e escola são obrigatórios.');
            }
            
            $professorId = (int)$professorId;
            $escolaId = (int)$escolaId;
            
            if ($professorId <= 0 || $escolaId <= 0) {
                throw new Exception('IDs inválidos.');
            }
            
            // Encerrar lotação
            $sqlRemover = "UPDATE professor_lotacao 
                          SET fim = CURDATE() 
                          WHERE professor_id = :professor_id 
                          AND escola_id = :escola_id 
                          AND fim IS NULL";
            $stmtRemover = $conn->prepare($sqlRemover);
            $stmtRemover->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmtRemover->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $result = $stmtRemover->execute();
            
            if ($result) {
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Lotação removida com sucesso!'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Erro ao remover lotação.');
            }
        } catch (Exception $e) {
            ob_clean();
            error_log("Erro ao remover lotação: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            ob_clean();
            error_log("Erro PDO ao remover lotação: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição. Tente novamente.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    } // Fechar bloco if POST
    
    // Processar requisições GET AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
        }
        
        if ($_GET['acao'] === 'buscar_professor') {
            try {
                $professorId = $_GET['id'] ?? null;
                if (empty($professorId)) {
                    echo json_encode(['success' => false, 'message' => 'ID do professor não informado'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                if (!is_numeric($professorId)) {
                    echo json_encode(['success' => false, 'message' => 'ID do professor inválido'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                $professorId = (int)$professorId;
                
                $sql = "SELECT pr.*, p.id as pessoa_id, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, p.sexo, u.username
                        FROM professor pr
                        INNER JOIN pessoa p ON pr.pessoa_id = p.id
                        LEFT JOIN usuario u ON u.pessoa_id = p.id
                        WHERE pr.id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $professorId, PDO::PARAM_INT);
                $stmt->execute();
                $professor = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($professor) {
                    if (!empty($professor['cpf']) && strlen($professor['cpf']) === 11) {
                        $professor['cpf_formatado'] = substr($professor['cpf'], 0, 3) . '.' . substr($professor['cpf'], 3, 3) . '.' . substr($professor['cpf'], 6, 3) . '-' . substr($professor['cpf'], 9, 2);
                    }
                    if (!empty($professor['telefone'])) {
                        $tel = $professor['telefone'];
                        if (strlen($tel) === 11) {
                            $professor['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                        } elseif (strlen($tel) === 10) {
                            $professor['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                        }
                    }
                    
                    try {
                        $sqlLotacao = "SELECT pl.*, e.nome as escola_nome
                                      FROM professor_lotacao pl
                                      LEFT JOIN escola e ON pl.escola_id = e.id
                                      WHERE pl.professor_id = :professor_id AND pl.fim IS NULL
                                      LIMIT 1";
                        $stmtLotacao = $conn->prepare($sqlLotacao);
                        $stmtLotacao->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
                        $stmtLotacao->execute();
                        $lotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
                        
                        if ($lotacao) {
                            $professor['lotacao_escola_id'] = $lotacao['escola_id'];
                            $professor['lotacao_carga_horaria'] = $lotacao['carga_horaria'];
                            $professor['lotacao_observacao'] = $lotacao['observacao'];
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao buscar lotação do professor: " . $e->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'professor' => $professor], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Professor não encontrado'], JSON_UNESCAPED_UNICODE);
                }
            } catch (PDOException $e) {
                error_log("Erro ao buscar professor: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados do professor. Por favor, tente novamente.'], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Erro ao buscar professor: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados do professor. Por favor, tente novamente.'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($_GET['acao'] === 'listar_professores') {
            $filtros = [];
            if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
            if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
            
            $sql = "SELECT pr.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, 
                    e.nome as escola_nome,
                    COUNT(DISTINCT pl.escola_id) as total_escolas
                    FROM professor pr
                    INNER JOIN pessoa p ON pr.pessoa_id = p.id
                    LEFT JOIN professor_lotacao pl ON pr.id = pl.professor_id AND pl.fim IS NULL
                    LEFT JOIN escola e ON pl.escola_id = e.id
                    WHERE pr.ativo = 1";
            
            $params = [];
            if (!empty($filtros['escola_id'])) {
                $sql .= " AND pl.escola_id = :escola_id";
                $params[':escola_id'] = $filtros['escola_id'];
            }
            if (!empty($filtros['busca'])) {
                $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR pr.matricula LIKE :busca)";
                $params[':busca'] = "%{$filtros['busca']}%";
            }
            
            $sql .= " GROUP BY pr.id ORDER BY p.nome ASC LIMIT 100";
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'professores' => $professores], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_GET['acao'] === 'buscar_lotacoes') {
            try {
                $professor_id = $_GET['professor_id'] ?? null;
                
                if (empty($professor_id)) {
                    throw new Exception('ID do professor é obrigatório.');
                }
                
                $professor_id = (int)$professor_id;
                if ($professor_id <= 0) {
                    throw new Exception('ID do professor inválido.');
                }
                
                $sql = "SELECT pl.*, e.nome as escola_nome, e.id as escola_id,
                        DATE_FORMAT(pl.inicio, '%d/%m/%Y') as inicio
                        FROM professor_lotacao pl
                        INNER JOIN escola e ON pl.escola_id = e.id
                        WHERE pl.professor_id = :professor_id AND pl.fim IS NULL
                        ORDER BY pl.inicio DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
                $stmt->execute();
                $lotacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode(['success' => true, 'lotacoes' => $lotacoes], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                ob_clean();
                error_log("Erro ao buscar lotações: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => $e->getMessage(), 'lotacoes' => []], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                ob_clean();
                error_log("Erro PDO ao buscar lotações: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao buscar lotações.', 'lotacoes' => []], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($_GET['acao'] === 'buscar_escolas_professor') {
            try {
                $professor_id = $_GET['professor_id'] ?? null;
                
                if (empty($professor_id)) {
                    throw new Exception('ID do professor é obrigatório.');
                }
                
                $professor_id = (int)$professor_id;
                if ($professor_id <= 0) {
                    throw new Exception('ID do professor inválido.');
                }
                
                $sql = "SELECT e.id, e.nome as escola_nome,
                        DATE_FORMAT(pl.inicio, '%d/%m/%Y') as inicio,
                        pl.carga_horaria
                        FROM professor_lotacao pl
                        INNER JOIN escola e ON pl.escola_id = e.id
                        WHERE pl.professor_id = :professor_id AND pl.fim IS NULL
                        ORDER BY e.nome ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
                $stmt->execute();
                $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode(['success' => true, 'escolas' => $escolas], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                ob_clean();
                error_log("Erro ao buscar escolas do professor: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => $e->getMessage(), 'escolas' => []], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                ob_clean();
                error_log("Erro PDO ao buscar escolas do professor: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao buscar escolas.', 'escolas' => []], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        // Se chegou aqui e é AJAX mas não processou nada, retornar erro
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Se chegou aqui, é uma requisição normal (não AJAX) - continuar com HTML
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar professores iniciais (apenas ativos)
$sqlProfessores = "SELECT pr.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, 
                    e.nome as escola_nome,
                    COUNT(DISTINCT pl.escola_id) as total_escolas
                    FROM professor pr
                    INNER JOIN pessoa p ON pr.pessoa_id = p.id
                    LEFT JOIN professor_lotacao pl ON pr.id = pl.professor_id AND pl.fim IS NULL
                    LEFT JOIN escola e ON pl.escola_id = e.id
                    WHERE pr.ativo = 1
                    GROUP BY pr.id
                    ORDER BY p.nome ASC
                    LIMIT 50";
$stmtProfessores = $conn->prepare($sqlProfessores);
$stmtProfessores->execute();
$professores = $stmtProfessores->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Gestão de Professores') ?></title>
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
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.12) 0%, rgba(220, 38, 38, 0.06) 100%);
            border-right: 3px solid #dc2626;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.08) 0%, rgba(220, 38, 38, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Professores</h1>
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
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Professores</h2>
                        <p class="text-gray-600 mt-1">Cadastre, edite e exclua professores do sistema</p>
                    </div>
                    <button onclick="abrirModalNovoProfessor()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Professor</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome, CPF ou Matrícula..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarProfessores()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarProfessores()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarProfessores()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Matrícula</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">CPF</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-professores">
                                <?php if (empty($professores)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhum professor encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($professores as $prof): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($prof['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($prof['matricula'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($prof['cpf'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <?php 
                                                $totalEscolas = (int)($prof['total_escolas'] ?? 0);
                                                if ($totalEscolas > 1): 
                                                ?>
                                                    <button onclick="mostrarEscolasProfessor(<?= $prof['id'] ?>)" 
                                                            class="text-blue-600 hover:text-blue-700 font-medium text-sm underline">
                                                        <?= $totalEscolas ?> escolas
                                                    </button>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($prof['escola_nome'] ?? '-') ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($prof['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarProfessor(<?= $prof['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="lotarProfessor(<?= $prof['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                        <?= !empty($prof['escola_nome']) ? 'Transferir' : 'Lotar' ?>
                                                    </button>
                                                    <button onclick="excluirProfessor(<?= $prof['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                        Excluir
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de Edição de Professor -->
    <div id="modalEditarProfessor" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Editar Professor</h2>
                <button onclick="fecharModalEditarProfessor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formEditarProfessor" class="space-y-6 max-w-6xl mx-auto">
                    <div id="alertaErroEditar" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucessoEditar" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <input type="hidden" name="professor_id" id="editar_professor_id">
                    
                    <!-- Informações Pessoais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" name="nome" id="editar_nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" name="cpf" id="editar_cpf" required maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" id="editar_data_nascimento" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                                <select name="sexo" id="editar_sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="editar_email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="editar_telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       oninput="formatarTelefone(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações Profissionais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Profissionais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                                <input type="text" name="matricula" id="editar_matricula"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formações</label>
                                <div id="editar-formacoes-container" class="space-y-2 mb-2">
                                    <!-- Formações serão adicionadas aqui dinamicamente -->
                                </div>
                                <button type="button" onclick="adicionarFormacaoEdicao()" class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Adicionar Formação</span>
                                </button>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Especializações</label>
                                <div id="editar-especializacoes-container" class="space-y-2 mb-2">
                                    <!-- Especializações serão adicionadas aqui dinamicamente -->
                                </div>
                                <button type="button" onclick="adicionarEspecializacaoEdicao()" class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Adicionar Especialização</span>
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional (Opcional)</label>
                                <input type="text" name="registro_profissional" id="editar_registro_profissional" placeholder="Ex: CREA, CREF, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                                <input type="date" name="data_admissao" id="editar_data_admissao"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="ativo" id="editar_ativo"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de Acesso -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Acesso</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha (deixe em branco para manter a atual)</label>
                                <input type="password" name="senha" id="editar_senha"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a senha atual</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                <input type="text" id="editar_username_preview" readonly
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalEditarProfessor()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formEditarProfessor" id="btnSalvarEdicao"
                        class="px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Alterações</span>
                    <svg id="spinnerSalvarEdicao" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Cadastro de Professor -->
    <div id="modalNovoProfessor" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Cadastrar Novo Professor</h2>
                <button onclick="fecharModalNovoProfessor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formNovoProfessor" class="space-y-6 max-w-6xl mx-auto">
                    <div id="alertaErro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <!-- Informações Pessoais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" name="nome" id="nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" name="cpf" id="cpf" required maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" id="data_nascimento" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                                <select name="sexo" id="sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       oninput="formatarTelefone(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações Profissionais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Profissionais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                                <input type="text" name="matricula" id="matricula"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formações</label>
                                <div id="formacoes-container" class="space-y-2 mb-2">
                                    <!-- Formações serão adicionadas aqui dinamicamente -->
                                </div>
                                <button type="button" onclick="adicionarFormacao()" class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Adicionar Formação</span>
                                </button>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Especializações</label>
                                <div id="especializacoes-container" class="space-y-2 mb-2">
                                    <!-- Especializações serão adicionadas aqui dinamicamente -->
                                </div>
                                <button type="button" onclick="adicionarEspecializacao()" class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Adicionar Especialização</span>
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional (Opcional)</label>
                                <input type="text" name="registro_profissional" id="registro_profissional" placeholder="Ex: CREA, CREF, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                                <input type="date" name="data_admissao" id="data_admissao"
                                       value="<?= date('Y-m-d') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Endereço -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                <input type="text" name="endereco" id="endereco"
                                       placeholder="Rua, Avenida, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" name="numero" id="numero"
                                       placeholder="Número"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                <input type="text" name="complemento" id="complemento"
                                       placeholder="Apto, Bloco, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                <input type="text" name="bairro" id="bairro"
                                       placeholder="Bairro"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                <input type="text" name="cidade" id="cidade"
                                       placeholder="Cidade"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <input type="text" name="estado" id="estado" value="CE" readonly
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                       placeholder="Ceará">
                                <input type="hidden" name="estado" value="CE">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                <input type="text" name="cep" id="cep" maxlength="9"
                                       placeholder="00000-000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       oninput="formatarCEP(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lotação (Opcional) -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Lotação (Opcional)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                                <select name="escola_id" id="escola_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Selecione uma escola...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Carga Horária (horas/semana)</label>
                                <input type="number" name="carga_horaria" id="carga_horaria" min="0" max="40"
                                       placeholder="Ex: 40"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2 lg:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observação da Lotação</label>
                                <textarea name="observacao_lotacao" id="observacao_lotacao" rows="2"
                                          placeholder="Observações sobre a lotação..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de Acesso -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Acesso</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha Padrão</label>
                                <input type="password" name="senha" id="senha" value="123456"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Senha padrão: 123456 (pode ser alterada pelo professor após o primeiro login)</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                <input type="text" id="username_preview" readonly
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                       placeholder="Será gerado automaticamente">
                                <p class="text-xs text-gray-500 mt-1">O username será gerado automaticamente baseado no primeiro nome</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalNovoProfessor()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formNovoProfessor" id="btnSalvarProfessor"
                        class="px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Professor</span>
                    <svg id="spinnerSalvar" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Escolha: Transferir ou Alocar -->
    <div id="modalEscolherTipoLotacao" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Escolher Tipo de Operação</h3>
                        <p class="text-sm text-gray-600">Como deseja proceder?</p>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-gray-700 mb-4">
                        O professor <strong id="escolher_professor_nome"></strong> já está lotado em uma ou mais escolas.
                    </p>
                    <div class="space-y-3">
                        <button onclick="escolherTransferir()" 
                                class="w-full p-4 border-2 border-blue-200 hover:border-blue-500 rounded-lg text-left transition-all duration-200 hover:bg-blue-50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">Transferir</h4>
                                    <p class="text-sm text-gray-600">Encerrar lotação atual e lotar em nova escola</p>
                                </div>
                            </div>
                        </button>
                        <button onclick="escolherAlocar()" 
                                class="w-full p-4 border-2 border-green-200 hover:border-green-500 rounded-lg text-left transition-all duration-200 hover:bg-green-50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">Alocar</h4>
                                    <p class="text-sm text-gray-600">Manter lotação atual e adicionar nova escola</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
                <button onclick="fecharModalEscolherTipoLotacao(true)" 
                        class="w-full px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Escolas do Professor -->
    <div id="modalEscolasProfessor" class="fixed inset-0 bg-black bg-opacity-50 z-[65] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Escolas do Professor</h3>
                    <button onclick="fecharModalEscolasProfessor()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="lista-escolas-professor" class="space-y-2 max-h-96 overflow-y-auto">
                    <!-- Lista de escolas será preenchida aqui -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Lotação de Professor -->
    <div id="modalLotarProfessor" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Lotar Professor em Escola</h2>
                <button onclick="fecharModalLotarProfessor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formLotarProfessor" class="space-y-6 max-w-4xl mx-auto">
                    <div id="alertaErroLotacao" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucessoLotacao" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <input type="hidden" id="lotar_professor_id" name="professor_id">
                    <input type="hidden" id="lotar_tipo_operacao" name="tipo_operacao" value="alocar">
                    
                    <!-- Informações do Professor -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações do Professor</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Professor</label>
                                <input type="text" id="lotar_professor_nome" readonly
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados da Lotação -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Dados da Lotação</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                <select id="lotar_escola_id" name="escola_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Selecione uma escola...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Início *</label>
                                <input type="date" id="lotar_data_inicio" name="data_inicio" required
                                       value="<?= date('Y-m-d') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Carga Horária</label>
                                <input type="number" id="lotar_carga_horaria" name="carga_horaria" min="1" max="40"
                                       placeholder="Ex: 20 horas semanais"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Carga horária semanal do professor</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                            <textarea id="lotar_observacao" name="observacao" rows="4"
                                      placeholder="Observações sobre a lotação (opcional)..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                    
                    <!-- Lista de Lotações Atuais -->
                    <div id="lotacoes-atuais-container" class="hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Lotações Atuais do Professor</h3>
                        <div id="lista-lotacoes" class="space-y-3 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalLotarProfessor()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formLotarProfessor" id="btnSalvarLotacao"
                        class="px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Lotação</span>
                    <svg id="spinnerLotacao" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
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
        // Funções globais que podem ser chamadas antes do DOM estar pronto
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
        
        // Função global para abrir modal de novo professor
        window.abrirModalNovoProfessor = function() {
            const modal = document.getElementById('modalNovoProfessor');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Limpar formulário
                document.getElementById('formNovoProfessor').reset();
                document.getElementById('data_admissao').value = new Date().toISOString().split('T')[0];
                document.getElementById('senha').value = '123456';
                // Limpar containers de formações e especializações
                document.getElementById('formacoes-container').innerHTML = '';
                document.getElementById('especializacoes-container').innerHTML = '';
                formacaoCount = 0;
                especializacaoCount = 0;
                // Limpar alertas
                document.getElementById('alertaErro').classList.add('hidden');
                document.getElementById('alertaSucesso').classList.add('hidden');
                // Atualizar preview do username
                atualizarPreviewUsername();
            }
        };
        
        // Função global para fechar modal de novo professor
        window.fecharModalNovoProfessor = function() {
            const modal = document.getElementById('modalNovoProfessor');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                // Limpar containers de formações e especializações
                document.getElementById('formacoes-container').innerHTML = '';
                document.getElementById('especializacoes-container').innerHTML = '';
                formacaoCount = 0;
                especializacaoCount = 0;
            }
        }
        
        function formatarCPF(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            input.value = value;
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
            }
            input.value = value;
        }
        
        function formatarCEP(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.slice(0, 5) + '-' + value.slice(5, 8);
            }
            input.value = value;
        }
        
        let formacaoCount = 0;
        let especializacaoCount = 0;
        
        function adicionarFormacao() {
            const container = document.getElementById('formacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `formacao-${formacaoCount}`;
            div.innerHTML = `
                <input type="text" name="formacoes[]" placeholder="Ex: Licenciatura em Matemática"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <button type="button" onclick="removerFormacao(${formacaoCount})" class="text-red-600 hover:text-red-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            formacaoCount++;
        }
        
        function removerFormacao(id) {
            const elemento = document.getElementById(`formacao-${id}`);
            if (elemento) {
                elemento.remove();
            }
        }
        
        function adicionarEspecializacao() {
            const container = document.getElementById('especializacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `especializacao-${especializacaoCount}`;
            div.innerHTML = `
                <input type="text" name="especializacoes[]" placeholder="Ex: Educação Especial"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <button type="button" onclick="removerEspecializacao(${especializacaoCount})" class="text-red-600 hover:text-red-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            especializacaoCount++;
        }
        
        function removerEspecializacao(id) {
            const elemento = document.getElementById(`especializacao-${id}`);
            if (elemento) {
                elemento.remove();
            }
        }
        
        let formacaoEdicaoCount = 0;
        let especializacaoEdicaoCount = 0;
        
        function adicionarFormacaoEdicao() {
            const container = document.getElementById('editar-formacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `editar-formacao-${formacaoEdicaoCount}`;
            div.innerHTML = `
                <input type="text" name="formacoes[]" placeholder="Ex: Licenciatura em Matemática"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <button type="button" onclick="removerFormacaoEdicao(${formacaoEdicaoCount})" class="text-red-600 hover:text-red-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            formacaoEdicaoCount++;
        }
        
        function removerFormacaoEdicao(id) {
            const elemento = document.getElementById(`editar-formacao-${id}`);
            if (elemento) {
                elemento.remove();
            }
        }
        
        function adicionarEspecializacaoEdicao() {
            const container = document.getElementById('editar-especializacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `editar-especializacao-${especializacaoEdicaoCount}`;
            div.innerHTML = `
                <input type="text" name="especializacoes[]" placeholder="Ex: Educação Especial"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <button type="button" onclick="removerEspecializacaoEdicao(${especializacaoEdicaoCount})" class="text-red-600 hover:text-red-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            especializacaoEdicaoCount++;
        }
        
        function removerEspecializacaoEdicao(id) {
            const elemento = document.getElementById(`editar-especializacao-${id}`);
            if (elemento) {
                elemento.remove();
            }
        }
        
        function atualizarPreviewUsername() {
            const nome = document.getElementById('nome').value.trim();
            const preview = document.getElementById('username_preview');
            if (nome) {
                const primeiroNome = nome.split(' ')[0];
                const username = primeiroNome.toLowerCase().replace(/[^a-z0-9]/g, '');
                preview.value = username || 'Será gerado automaticamente';
            } else {
                preview.value = 'Será gerado automaticamente';
            }
        }
        
        // Aguardar DOM estar pronto antes de registrar eventos
        document.addEventListener('DOMContentLoaded', function() {
            // Atualizar preview do username quando o nome mudar
            const nomeInput = document.getElementById('nome');
            if (nomeInput) {
                nomeInput.addEventListener('input', atualizarPreviewUsername);
            }
            
            // Submissão do formulário
            const formNovoProfessor = document.getElementById('formNovoProfessor');
            if (!formNovoProfessor) {
                console.error('Formulário formNovoProfessor não encontrado no DOM');
            } else {
                formNovoProfessor.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarProfessor');
            const spinner = document.getElementById('spinnerSalvar');
            const alertaErro = document.getElementById('alertaErro');
            const alertaSucesso = document.getElementById('alertaSucesso');
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'cadastrar_professor');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = `Professor cadastrado com sucesso! Username: ${data.username || 'gerado automaticamente'}`;
                    alertaSucesso.classList.remove('hidden');
                    
                    // Limpar formulário
                    this.reset();
                    document.getElementById('data_admissao').value = new Date().toISOString().split('T')[0];
                    document.getElementById('senha').value = '123456';
                    atualizarPreviewUsername();
                    
                    // Recarregar lista de professores após 1.5 segundos
                    setTimeout(() => {
                        fecharModalNovoProfessor();
                        filtrarProfessores();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao cadastrar professor. Por favor, tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = error.message || 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
                });
            }
            
            // Fechar modal ao clicar fora
            document.getElementById('modalNovoProfessor')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModalNovoProfessor();
                }
            });
            
            // Fechar modal de lotação ao clicar fora
            document.getElementById('modalLotarProfessor')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModalLotarProfessor();
                }
            });
            
            // Fechar modal de escolha ao clicar fora
            document.getElementById('modalEscolherTipoLotacao')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModalEscolherTipoLotacao(true);
                }
            });
            
            // Fechar modal de escolas ao clicar fora
            document.getElementById('modalEscolasProfessor')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModalEscolasProfessor();
                }
            });
        }); // Fechar DOMContentLoaded

        async function editarProfessor(id) {
            try {
                // Buscar dados do professor
                const response = await fetch('?acao=buscar_professor&id=' + id);
                
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                const data = await response.json();
                
                if (!data.success || !data.professor) {
                    alert('Erro ao carregar dados do professor: ' + (data.message || 'Professor não encontrado'));
                    return;
                }
                
                const prof = data.professor;
                
                // Função auxiliar para definir valor de elemento de forma segura
                function setValueSafely(elementId, value) {
                    const element = document.getElementById(elementId);
                    if (element) {
                        element.value = value || '';
                    }
                }
                
                // Preencher formulário
                setValueSafely('editar_professor_id', prof.id);
                setValueSafely('editar_nome', prof.nome);
                setValueSafely('editar_cpf', prof.cpf_formatado || prof.cpf);
                setValueSafely('editar_data_nascimento', prof.data_nascimento);
                setValueSafely('editar_sexo', prof.sexo);
                setValueSafely('editar_email', prof.email);
                setValueSafely('editar_telefone', prof.telefone_formatado || prof.telefone);
                setValueSafely('editar_matricula', prof.matricula);
                
                // Carregar formações (JSON)
                const formacoesContainer = document.getElementById('editar-formacoes-container');
                if (formacoesContainer) {
                    formacoesContainer.innerHTML = '';
                    formacaoEdicaoCount = 0;
                    if (prof.formacao) {
                        try {
                            const formacoes = JSON.parse(prof.formacao);
                            if (Array.isArray(formacoes)) {
                                formacoes.forEach(form => {
                                    if (form && form.trim()) {
                                        const div = document.createElement('div');
                                        div.className = 'flex items-center space-x-2';
                                        div.id = `editar-formacao-${formacaoEdicaoCount}`;
                                        div.innerHTML = `
                                            <input type="text" name="formacoes[]" value="${form.replace(/"/g, '&quot;')}"
                                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <button type="button" onclick="removerFormacaoEdicao(${formacaoEdicaoCount})" class="text-red-600 hover:text-red-700 p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        `;
                                        formacoesContainer.appendChild(div);
                                        formacaoEdicaoCount++;
                                    }
                                });
                            }
                        } catch (e) {
                            // Se não for JSON, tratar como string única
                            if (prof.formacao && prof.formacao.trim()) {
                                const div = document.createElement('div');
                                div.className = 'flex items-center space-x-2';
                                div.id = `editar-formacao-${formacaoEdicaoCount}`;
                                div.innerHTML = `
                                    <input type="text" name="formacoes[]" value="${prof.formacao.replace(/"/g, '&quot;')}"
                                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <button type="button" onclick="removerFormacaoEdicao(${formacaoEdicaoCount})" class="text-red-600 hover:text-red-700 p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                `;
                                formacoesContainer.appendChild(div);
                                formacaoEdicaoCount++;
                            }
                        }
                    }
                }
                
                // Carregar especializações (JSON)
                const especializacoesContainer = document.getElementById('editar-especializacoes-container');
                if (especializacoesContainer) {
                    especializacoesContainer.innerHTML = '';
                    especializacaoEdicaoCount = 0;
                    if (prof.especializacao) {
                        try {
                            const especializacoes = JSON.parse(prof.especializacao);
                            if (Array.isArray(especializacoes)) {
                                especializacoes.forEach(esp => {
                                    if (esp && esp.trim()) {
                                        const div = document.createElement('div');
                                        div.className = 'flex items-center space-x-2';
                                        div.id = `editar-especializacao-${especializacaoEdicaoCount}`;
                                        div.innerHTML = `
                                            <input type="text" name="especializacoes[]" value="${esp.replace(/"/g, '&quot;')}"
                                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <button type="button" onclick="removerEspecializacaoEdicao(${especializacaoEdicaoCount})" class="text-red-600 hover:text-red-700 p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        `;
                                        especializacoesContainer.appendChild(div);
                                        especializacaoEdicaoCount++;
                                    }
                                });
                            }
                        } catch (e) {
                            // Se não for JSON, tratar como string única
                            if (prof.especializacao && prof.especializacao.trim()) {
                                const div = document.createElement('div');
                                div.className = 'flex items-center space-x-2';
                                div.id = `editar-especializacao-${especializacaoEdicaoCount}`;
                                div.innerHTML = `
                                    <input type="text" name="especializacoes[]" value="${prof.especializacao.replace(/"/g, '&quot;')}"
                                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <button type="button" onclick="removerEspecializacaoEdicao(${especializacaoEdicaoCount})" class="text-red-600 hover:text-red-700 p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                `;
                                especializacoesContainer.appendChild(div);
                                especializacaoEdicaoCount++;
                            }
                        }
                    }
                }
                
                setValueSafely('editar_registro_profissional', prof.registro_profissional);
                setValueSafely('editar_data_admissao', prof.data_admissao);
                setValueSafely('editar_ativo', prof.ativo !== undefined ? prof.ativo : 1);
                setValueSafely('editar_username_preview', prof.username);
                
                // Preencher endereço (se os campos existirem)
                setValueSafely('editar_endereco', prof.endereco);
                setValueSafely('editar_numero', prof.numero);
                setValueSafely('editar_complemento', prof.complemento);
                setValueSafely('editar_bairro', prof.bairro);
                setValueSafely('editar_cidade', prof.cidade);
                setValueSafely('editar_estado', prof.estado || 'CE');
                if (prof.cep) {
                    const cep = prof.cep.replace(/\D/g, '');
                    const cepElement = document.getElementById('editar_cep');
                    if (cepElement) {
                        if (cep.length === 8) {
                            cepElement.value = cep.slice(0, 5) + '-' + cep.slice(5);
                        } else {
                            cepElement.value = prof.cep;
                        }
                    }
                }
                
                // Abrir modal
                const modal = document.getElementById('modalEditarProfessor');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    // Limpar alertas
                    const alertaErro = document.getElementById('alertaErroEditar');
                    const alertaSucesso = document.getElementById('alertaSucessoEditar');
                    if (alertaErro) {
                        alertaErro.classList.add('hidden');
                    }
                    if (alertaSucesso) {
                        alertaSucesso.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar professor:', error);
                alert('Erro ao carregar dados do professor. Por favor, tente novamente.');
            }
        }
        
        function fecharModalEditarProfessor() {
            const modal = document.getElementById('modalEditarProfessor');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        // Submissão do formulário de edição
        document.getElementById('formEditarProfessor').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarEdicao');
            const spinner = document.getElementById('spinnerSalvarEdicao');
            const alertaErro = document.getElementById('alertaErroEditar');
            const alertaSucesso = document.getElementById('alertaSucessoEditar');
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'editar_professor');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = 'Professor atualizado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
                    // Recarregar lista de professores após 1.5 segundos
                    setTimeout(() => {
                        fecharModalEditarProfessor();
                        filtrarProfessores();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao atualizar professor. Por favor, tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = error.message || 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        });
        
        // Fechar modal de edição ao clicar fora
        document.getElementById('modalEditarProfessor')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalEditarProfessor();
            }
        });


        // Variável global para armazenar o ID do professor durante a escolha
        let professorIdEscolha = null;
        
        // Funções de Lotação
        async function lotarProfessor(id) {
            try {
                professorIdEscolha = id;
                
                // Buscar dados do professor
                const response = await fetch('?acao=buscar_professor&id=' + id);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                let data;
                
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('Resposta não é JSON válido:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é JSON válido. Verifique o console para mais detalhes.');
                }
                
                if (!data.success || !data.professor) {
                    alert('Erro ao carregar dados do professor: ' + (data.message || 'Professor não encontrado'));
                    return;
                }
                
                const professor = data.professor;
                
                // Verificar se já está lotado
                const jaLotado = professor.lotacao_escola_id ? true : false;
                
                if (jaLotado) {
                    // Se já está lotado, mostrar modal de escolha
                    document.getElementById('escolher_professor_nome').textContent = professor.nome || 'este professor';
                    abrirModalEscolherTipoLotacao();
                } else {
                    // Se não está lotado, abrir diretamente o modal de lotação
                    abrirModalLotacao(id, professor);
                }
            } catch (error) {
                console.error('Erro ao carregar professor:', error);
                alert('Erro ao carregar dados do professor. Por favor, tente novamente.');
            }
        }
        
        function abrirModalEscolherTipoLotacao() {
            const modal = document.getElementById('modalEscolherTipoLotacao');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        }
        
        function fecharModalEscolherTipoLotacao(limparId = false) {
            const modal = document.getElementById('modalEscolherTipoLotacao');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
            // Só limpar o ID se explicitamente solicitado (ex: quando cancelar)
            if (limparId) {
                professorIdEscolha = null;
            }
        }
        
        async function escolherTransferir() {
            // Salvar o ID antes de fechar o modal
            const id = professorIdEscolha;
            
            if (!id) {
                alert('Erro: ID do professor não encontrado.');
                return;
            }
            
            // Fechar modal sem limpar o ID ainda
            fecharModalEscolherTipoLotacao(false);
            
            // Buscar dados do professor novamente
            try {
                const response = await fetch('?acao=buscar_professor&id=' + id);
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é JSON válido');
                }
                
                const data = await response.json();
                
                if (data.success && data.professor) {
                    // Definir tipo de operação como transferir
                    document.getElementById('lotar_tipo_operacao').value = 'transferir';
                    abrirModalLotacao(id, data.professor);
                } else {
                    alert('Erro ao carregar dados do professor: ' + (data.message || 'Professor não encontrado'));
                }
            } catch (error) {
                console.error('Erro ao carregar professor:', error);
                alert('Erro ao carregar dados do professor. Por favor, tente novamente.');
            }
        }
        
        async function escolherAlocar() {
            // Salvar o ID antes de fechar o modal
            const id = professorIdEscolha;
            
            if (!id) {
                alert('Erro: ID do professor não encontrado.');
                return;
            }
            
            // Fechar modal sem limpar o ID ainda
            fecharModalEscolherTipoLotacao(false);
            
            // Buscar dados do professor novamente
            try {
                const response = await fetch('?acao=buscar_professor&id=' + id);
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é JSON válido');
                }
                
                const data = await response.json();
                
                if (data.success && data.professor) {
                    // Definir tipo de operação como alocar
                    document.getElementById('lotar_tipo_operacao').value = 'alocar';
                    abrirModalLotacao(id, data.professor);
                } else {
                    alert('Erro ao carregar dados do professor: ' + (data.message || 'Professor não encontrado'));
                }
            } catch (error) {
                console.error('Erro ao carregar professor:', error);
                alert('Erro ao carregar dados do professor. Por favor, tente novamente.');
            }
        }
        
        async function abrirModalLotacao(id, professor) {
            // Atualizar título do modal baseado no tipo de operação
            const tipoOperacao = document.getElementById('lotar_tipo_operacao').value;
            const tituloModal = document.querySelector('#modalLotarProfessor h2');
            if (tituloModal) {
                if (tipoOperacao === 'transferir') {
                    tituloModal.textContent = 'Transferir Professor de Escola';
                } else {
                    tituloModal.textContent = 'Alocar Professor em Escola';
                }
            }
            
            // Atualizar texto do botão de salvar
            const btnSalvar = document.getElementById('btnSalvarLotacao');
            if (btnSalvar) {
                if (tipoOperacao === 'transferir') {
                    btnSalvar.innerHTML = '<span>Transferir</span>';
                } else {
                    btnSalvar.innerHTML = '<span>Alocar</span>';
                }
            }
            
            // Preencher formulário
            document.getElementById('lotar_professor_id').value = professor.id;
            document.getElementById('lotar_professor_nome').value = professor.nome || '';
            document.getElementById('lotar_escola_id').value = '';
            document.getElementById('lotar_data_inicio').value = new Date().toISOString().split('T')[0];
            document.getElementById('lotar_carga_horaria').value = '';
            document.getElementById('lotar_observacao').value = '';
            
            // Limpar alertas
            const alertaErroLotacao = document.getElementById('alertaErroLotacao');
            const alertaSucessoLotacao = document.getElementById('alertaSucessoLotacao');
            if (alertaErroLotacao) {
                alertaErroLotacao.classList.add('hidden');
            }
            if (alertaSucessoLotacao) {
                alertaSucessoLotacao.classList.add('hidden');
            }
            
            // Carregar lotações atuais
            await carregarLotacoesAtuais(id);
            
            // Abrir modal
            const modal = document.getElementById('modalLotarProfessor');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        }
        
        function fecharModalLotarProfessor() {
            const modal = document.getElementById('modalLotarProfessor');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        async function carregarLotacoesAtuais(professorId) {
            try {
                const response = await fetch('?acao=buscar_lotacoes&professor_id=' + professorId);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é JSON válido');
                }
                
                const data = await response.json();
                
                const container = document.getElementById('lotacoes-atuais-container');
                const lista = document.getElementById('lista-lotacoes');
                
                if (!container || !lista) {
                    console.warn('Elementos de lotação não encontrados');
                    return;
                }
                
                if (data.success && data.lotacoes && data.lotacoes.length > 0) {
                    container.classList.remove('hidden');
                    lista.innerHTML = '';
                    
                    data.lotacoes.forEach(lotacao => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200';
                        div.innerHTML = `
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">${lotacao.escola_nome || 'Escola não informada'}</p>
                                <p class="text-sm text-gray-500">Desde: ${lotacao.inicio || '-'}</p>
                            </div>
                            <button onclick="removerLotacao(${lotacao.professor_id}, ${lotacao.escola_id})" 
                                    class="text-red-600 hover:text-red-700 font-medium text-sm px-3 py-1">
                                Remover
                            </button>
                        `;
                        lista.appendChild(div);
                    });
                } else {
                    container.classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar lotações:', error);
                const container = document.getElementById('lotacoes-atuais-container');
                if (container) {
                    container.classList.add('hidden');
                }
            }
        }
        
        async function removerLotacao(professorId, escolaId) {
            if (!confirm('Tem certeza que deseja remover esta lotação?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('acao', 'remover_lotacao');
                formData.append('professor_id', professorId);
                formData.append('escola_id', escolaId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Lotação removida com sucesso!');
                    await carregarLotacoesAtuais(professorId);
                    filtrarProfessores();
                } else {
                    alert('Erro ao remover lotação: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao remover lotação:', error);
                alert(error.message || 'Erro ao processar requisição. Por favor, tente novamente.');
            }
        }
        
        // Submissão do formulário de lotação
        document.getElementById('formLotarProfessor')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarLotacao');
            const spinner = document.getElementById('spinnerLotacao');
            const alertaErro = document.getElementById('alertaErroLotacao');
            const alertaSucesso = document.getElementById('alertaSucessoLotacao');
            
            if (btnSalvar) {
                btnSalvar.disabled = true;
            }
            if (spinner) {
                spinner.classList.remove('hidden');
            }
            if (alertaErro) {
                alertaErro.classList.add('hidden');
            }
            if (alertaSucesso) {
                alertaSucesso.classList.add('hidden');
            }
            
            const formData = new FormData(this);
            formData.append('acao', 'lotar_professor');
            
            // O tipo de operação já está no formulário (transferir ou alocar)
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    if (alertaSucesso) {
                        alertaSucesso.textContent = data.message || 'Professor lotado com sucesso!';
                        alertaSucesso.classList.remove('hidden');
                    }
                    
                    // Recarregar lotações
                    const professorId = document.getElementById('lotar_professor_id').value;
                    await carregarLotacoesAtuais(professorId);
                    
                    // Limpar formulário
                    this.reset();
                    document.getElementById('lotar_data_inicio').value = new Date().toISOString().split('T')[0];
                    
                    // Recarregar lista após 1.5 segundos
                    setTimeout(() => {
                        filtrarProfessores();
                    }, 1500);
                } else {
                    if (alertaErro) {
                        alertaErro.textContent = data.message || 'Erro ao lotar professor.';
                        alertaErro.classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
                if (alertaErro) {
                    alertaErro.textContent = error.message || 'Erro ao processar requisição. Por favor, tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            } finally {
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('hidden');
                }
            }
        });
        
        function filtrarProfessores() {
            const busca = document.getElementById('filtro-busca').value;
            const escolaId = document.getElementById('filtro-escola').value;
            
            let url = '?acao=listar_professores';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (escolaId) url += '&escola_id=' + escolaId;
            
            fetch(url)
                .then(async response => {
                    // Verificar se a resposta é JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Resposta não é JSON:', text.substring(0, 200));
                        throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-professores');
                        tbody.innerHTML = '';
                        
                        if (data.professores.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhum professor encontrado.</td></tr>';
                            return;
                        }
                        
                        data.professores.forEach(prof => {
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${prof.nome}</td>
                                    <td class="py-3 px-4">${prof.matricula || '-'}</td>
                                    <td class="py-3 px-4">${prof.cpf || '-'}</td>
                                    <td class="py-3 px-4">
                                        ${(prof.total_escolas && parseInt(prof.total_escolas) > 1) 
                                            ? `<button onclick="mostrarEscolasProfessor(${prof.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm underline">${prof.total_escolas} escolas</button>`
                                            : (prof.escola_nome || '-')}
                                    </td>
                                    <td class="py-3 px-4">${prof.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarProfessor(${prof.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="lotarProfessor(${prof.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                ${prof.escola_nome ? 'Transferir' : 'Lotar'}
                                            </button>
                                            <button onclick="excluirProfessor(${prof.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar professores:', error);
                });
        }
        
        async function mostrarEscolasProfessor(professorId) {
            try {
                const response = await fetch('?acao=buscar_escolas_professor&professor_id=' + professorId);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é JSON válido');
                }
                
                const data = await response.json();
                
                if (data.success && data.escolas) {
                    const lista = document.getElementById('lista-escolas-professor');
                    lista.innerHTML = '';
                    
                    if (data.escolas.length === 0) {
                        lista.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhuma escola encontrada.</p>';
                    } else {
                        data.escolas.forEach(escola => {
                            const div = document.createElement('div');
                            div.className = 'p-3 bg-gray-50 rounded-lg border border-gray-200';
                            div.innerHTML = `
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">${escola.escola_nome || 'Escola não informada'}</p>
                                        <div class="flex items-center space-x-4 mt-1 text-sm text-gray-600">
                                            ${escola.inicio ? `<span>Desde: ${escola.inicio}</span>` : ''}
                                            ${escola.carga_horaria ? `<span>${escola.carga_horaria}h/semana</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                            lista.appendChild(div);
                        });
                    }
                    
                    // Abrir modal
                    const modal = document.getElementById('modalEscolasProfessor');
                    if (modal) {
                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                    }
                } else {
                    alert('Erro ao carregar escolas: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao buscar escolas do professor:', error);
                alert('Erro ao carregar escolas do professor. Por favor, tente novamente.');
            }
        }
        
        function fecharModalEscolasProfessor() {
            const modal = document.getElementById('modalEscolasProfessor');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
    </script>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div id="modalConfirmarExclusao" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar Exclusão</h3>
                        <p class="text-sm text-gray-600">Esta ação pode ser revertida</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-6" id="textoConfirmacaoExclusao">
                    Tem certeza que deseja excluir este professor?
                </p>
                <div class="flex space-x-3">
                    <button onclick="fecharModalConfirmarExclusao()" 
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmarExclusaoProfessor()" 
                            class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Erro -->
    <div id="modalErroExclusao" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Não é possível excluir</h3>
                        <p class="text-sm text-gray-600">Ação bloqueada</p>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-gray-700 mb-4" id="textoErroExclusao">
                        Não é possível excluir o professor pois ele está atribuído a uma ou mais turmas ativas.
                    </p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong class="font-medium">Solução:</strong> Primeiro, remova o professor das turmas antes de excluí-lo.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <button onclick="fecharModalErroExclusao()" 
                        class="w-full px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Notificação com Contagem Regressiva -->
    <div id="modalNotificacaoExclusao" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Professor Excluído</h3>
                        <p class="text-sm text-gray-600" id="textoNotificacaoExclusao">Professor excluído com sucesso!</p>
                    </div>
                </div>
                <div class="mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-blue-800">Tempo para reverter:</span>
                            <span id="contadorRegressivo" class="text-2xl font-bold text-blue-600">5</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div id="barraProgresso" class="bg-blue-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <button onclick="reverterExclusaoProfessor()" 
                        class="w-full px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors duration-200">
                    Reverter Exclusão
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Sucesso - Reversão -->
    <div id="modalSucessoReversao" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Exclusão Revertida</h3>
                        <p class="text-sm text-gray-600">Operação concluída com sucesso</p>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-gray-700 mb-4" id="textoSucessoReversao">
                        Exclusão revertida com sucesso! O professor foi reativado.
                    </p>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong class="font-medium">Status:</strong> O professor está novamente ativo no sistema e pode ser atribuído a turmas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <button onclick="fecharModalSucessoReversao()" 
                        class="w-full px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors duration-200">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let professorIdExcluido = null;
        let timerContagem = null;
        let tempoRestante = 5;
        
        function abrirModalConfirmarExclusao(id, nome) {
            try {
                professorIdExcluido = id;
                
                const textoElement = document.getElementById('textoConfirmacaoExclusao');
                if (textoElement) {
                    textoElement.textContent = 
                        `Tem certeza que deseja excluir o professor "${nome}"?\n\nEsta ação pode ser revertida nos próximos 5 segundos após a exclusão.`;
                }
                
                const modal = document.getElementById('modalConfirmarExclusao');
                if (!modal) {
                    console.error('Modal de confirmação não encontrado');
                    alert('Erro: Modal não encontrado. Por favor, recarregue a página.');
                    return;
                }
                
                // Armazenar ID no modal como backup
                modal.setAttribute('data-professor-id', id);
                
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            } catch (error) {
                console.error('Erro ao abrir modal de confirmação:', error);
                alert('Erro ao abrir modal de confirmação. Por favor, tente novamente.');
            }
        }
        
        function fecharModalConfirmarExclusao() {
            const modal = document.getElementById('modalConfirmarExclusao');
            if (modal) {
                // Preservar o ID no atributo data antes de fechar
                if (professorIdExcluido) {
                    modal.setAttribute('data-professor-id', professorIdExcluido);
                }
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
            // Não limpar o ID aqui, pois pode ser necessário para reverter
            // professorIdExcluido = null;
        }
        
        function confirmarExclusaoProfessor() {
            if (!professorIdExcluido) return;
            
            const formData = new FormData();
            formData.append('acao', 'excluir_professor');
            formData.append('professor_id', professorIdExcluido);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Preservar o ID antes de fechar o modal de confirmação
                    const modalConfirmacao = document.getElementById('modalConfirmarExclusao');
                    if (modalConfirmacao && professorIdExcluido) {
                        modalConfirmacao.setAttribute('data-professor-id', professorIdExcluido);
                    }
                    
                    fecharModalConfirmarExclusao();
                    
                    // Abrir modal de notificação com contagem regressiva
                    abrirModalNotificacaoExclusao();
                } else {
                    fecharModalConfirmarExclusao();
                    // Exibir modal de erro estilizado
                    abrirModalErroExclusao(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro ao excluir professor:', error);
                abrirModalErroExclusao(error.message || 'Erro ao processar requisição. Por favor, tente novamente.');
                fecharModalConfirmarExclusao();
            });
        }
        
        function abrirModalNotificacaoExclusao() {
            tempoRestante = 5;
            const modal = document.getElementById('modalNotificacaoExclusao');
            
            // Preservar o ID do professor no modal como backup
            if (professorIdExcluido) {
                modal.setAttribute('data-professor-id', professorIdExcluido);
            }
            
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            
            // Atualizar contador e barra de progresso
            atualizarContador();
            
            // Iniciar contagem regressiva
            timerContagem = setInterval(() => {
                tempoRestante--;
                atualizarContador();
                
                if (tempoRestante <= 0) {
                    fecharModalNotificacaoExclusao();
                }
            }, 1000);
        }
        
        function atualizarContador() {
            const contador = document.getElementById('contadorRegressivo');
            const barraProgresso = document.getElementById('barraProgresso');
            
            if (contador) {
                contador.textContent = tempoRestante;
            }
            
            if (barraProgresso) {
                const porcentagem = (tempoRestante / 5) * 100;
                barraProgresso.style.width = porcentagem + '%';
                
                // Mudar cor conforme o tempo diminui
                if (tempoRestante <= 2) {
                    barraProgresso.classList.remove('bg-blue-600');
                    barraProgresso.classList.add('bg-red-600');
                } else if (tempoRestante <= 3) {
                    barraProgresso.classList.remove('bg-blue-600');
                    barraProgresso.classList.add('bg-yellow-600');
                }
            }
        }
        
        function fecharModalNotificacaoExclusao() {
            if (timerContagem) {
                clearInterval(timerContagem);
                timerContagem = null;
            }
            
            const modal = document.getElementById('modalNotificacaoExclusao');
            if (modal) {
                // Preservar o ID no atributo data antes de fechar
                if (professorIdExcluido) {
                    modal.setAttribute('data-professor-id', professorIdExcluido);
                }
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
            
            // Recarregar lista
            filtrarProfessores();
            // Não limpar o ID imediatamente - deixar disponível por mais tempo para reversão
            // professorIdExcluido = null;
        }
        
        function abrirModalErroExclusao(mensagem) {
            const textoErro = document.getElementById('textoErroExclusao');
            
            // Verificar se a mensagem é sobre turmas ativas
            if (mensagem.includes('turmas ativas') || mensagem.includes('atribuído')) {
                textoErro.innerHTML = 'Não é possível excluir o professor pois ele está atribuído a uma ou mais turmas ativas.';
            } else {
                textoErro.textContent = mensagem;
            }
            
            const modal = document.getElementById('modalErroExclusao');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
        }
        
        function fecharModalErroExclusao() {
            const modal = document.getElementById('modalErroExclusao');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }
        
        function abrirModalSucessoReversao() {
            const modal = document.getElementById('modalSucessoReversao');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        }
        
        function fecharModalSucessoReversao() {
            const modal = document.getElementById('modalSucessoReversao');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        function reverterExclusaoProfessor() {
            // Tentar recuperar o ID da variável ou do atributo data do modal
            let idProfessor = professorIdExcluido;
            
            if (!idProfessor) {
                // Tentar recuperar do modal de notificação
                const modalNotificacao = document.getElementById('modalNotificacaoExclusao');
                if (modalNotificacao) {
                    idProfessor = modalNotificacao.getAttribute('data-professor-id');
                }
            }
            
            if (!idProfessor) {
                // Tentar recuperar do modal de confirmação
                const modalConfirmacao = document.getElementById('modalConfirmarExclusao');
                if (modalConfirmacao) {
                    idProfessor = modalConfirmacao.getAttribute('data-professor-id');
                }
            }
            
            if (!idProfessor) {
                console.error('ID do professor não está definido');
                alert('Erro: ID do professor não encontrado. Por favor, tente novamente.');
                return;
            }
            
            // Atualizar a variável global
            professorIdExcluido = idProfessor;
            
            // Parar contagem
            if (timerContagem) {
                clearInterval(timerContagem);
                timerContagem = null;
            }
            
            // Desabilitar botão para evitar cliques múltiplos
            const btnReverter = document.querySelector('#modalNotificacaoExclusao button[onclick*="reverterExclusaoProfessor"]');
            const textoOriginal = btnReverter ? btnReverter.textContent : '';
            if (btnReverter) {
                btnReverter.disabled = true;
                btnReverter.textContent = 'Revertendo...';
            }
            
            const formData = new FormData();
            formData.append('acao', 'reverter_exclusao_professor');
            formData.append('professor_id', idProfessor);
            
            console.log('Revertendo exclusão do professor ID:', idProfessor);
            
            fetch('', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(async response => {
                console.log('Resposta recebida:', response.status, response.statusText);
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type') || '';
                const text = await response.text();
                
                if (!contentType.includes('application/json')) {
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Texto recebido:', text);
                    throw new Error('Erro ao processar resposta do servidor.');
                }
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                
                fecharModalNotificacaoExclusao();
                
                if (data.success) {
                    fecharModalNotificacaoExclusao();
                    abrirModalSucessoReversao();
                    filtrarProfessores();
                    
                    // Limpar ID e atributos data após sucesso
                    professorIdExcluido = null;
                    const modalNotificacao = document.getElementById('modalNotificacaoExclusao');
                    if (modalNotificacao) {
                        modalNotificacao.removeAttribute('data-professor-id');
                    }
                    const modalConfirmacao = document.getElementById('modalConfirmarExclusao');
                    if (modalConfirmacao) {
                        modalConfirmacao.removeAttribute('data-professor-id');
                    }
                } else {
                    abrirModalErroExclusao('Erro ao reverter exclusão: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro ao reverter exclusão:', error);
                fecharModalNotificacaoExclusao();
                abrirModalErroExclusao(error.message || 'Erro ao processar requisição. Por favor, tente novamente.');
            })
            .finally(() => {
                // Reabilitar botão
                if (btnReverter) {
                    btnReverter.disabled = false;
                    btnReverter.textContent = textoOriginal || 'Reverter Exclusão';
                }
            });
        }
        
        // Substituir função excluirProfessor existente
        async function excluirProfessor(id) {
            if (!id) {
                alert('ID do professor não informado.');
                return;
            }
            
            try {
                const response = await fetch('?acao=buscar_professor&id=' + id);
                
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida. Por favor, tente novamente.');
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    alert('Erro ao carregar dados do professor: ' + (data.message || 'Professor não encontrado'));
                    return;
                }
                
                const nomeProfessor = data.professor && data.professor.nome ? data.professor.nome : 'este professor';
                
                abrirModalConfirmarExclusao(id, nomeProfessor);
            } catch (error) {
                console.error('Erro ao buscar dados do professor:', error);
                alert('Erro ao carregar dados do professor: ' + (error.message || 'Erro desconhecido'));
            }
        }
    </script>
</body>
</html>

