<?php
// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1); // Habilitar exibição de erros temporariamente para debug
ini_set('log_errors', 1);

// Iniciar output buffering para capturar erros
ob_start();

try {
    require_once('../../Models/sessao/sessions.php');
    require_once('../../config/permissions_helper.php');
    require_once('../../config/Database.php');
    require_once('../../config/system_helper.php');
    require_once('../../Models/academico/AlunoModel.php');
    require_once('../../Models/pessoas/ResponsavelModel.php');

    $session = new sessions();
    $session->autenticar_session();
    $session->tempo_session();

    // Verificar se é ADM
    if (!eAdm()) {
        ob_end_clean();
        header('Location: ../auth/login.php?erro=sem_permissao');
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log("Erro em gestao_alunos_adm.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    die("Erro ao carregar a página: " . $e->getMessage() . ". Por favor, verifique os logs do servidor.");
} catch (Error $e) {
    ob_end_clean();
    error_log("Erro fatal em gestao_alunos_adm.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    die("Erro fatal ao carregar a página: " . $e->getMessage() . ". Por favor, verifique os logs do servidor.");
}

try {
$db = Database::getInstance();
$conn = $db->getConnection();
$alunoModel = new AlunoModel();
$responsavelModel = new ResponsavelModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao inicializar gestao_alunos_adm.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    die("Erro ao carregar a página. Por favor, verifique os logs do servidor.");
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    // Limpar qualquer output anterior
    if (ob_get_level()) { 
        ob_clean(); 
    }
    // Verificar se headers já foram enviados
    if (!headers_sent()) {
    header('Content-Type: application/json');
    }
    
            if ($_POST['acao'] === 'cadastrar_aluno') {
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
                    $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf";
                    $stmtVerificar = $conn->prepare($sqlVerificarCPF);
                    $stmtVerificar->bindParam(':cpf', $cpf);
                    $stmtVerificar->execute();
                    if ($stmtVerificar->fetch()) {
                        throw new Exception('CPF já cadastrado no sistema.');
                    }
                    if (!empty($emailInformado)) {
                        $sqlVerificarEmail = "SELECT id FROM pessoa WHERE email = :email LIMIT 1";
                        $stmtVerificarEmail = $conn->prepare($sqlVerificarEmail);
                        $stmtVerificarEmail->bindParam(':email', $emailInformado);
                        $stmtVerificarEmail->execute();
                        if ($stmtVerificarEmail->fetch()) {
                            throw new Exception('Email já cadastrado no sistema.');
                        }
                    }
            
            // Gerar matrícula se não fornecida
            $matricula = $_POST['matricula'] ?? '';
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
            
            $sqlVerificarMatricula = "SELECT id FROM aluno WHERE matricula = :matricula";
            $stmtVerificarMat = $conn->prepare($sqlVerificarMatricula);
            $stmtVerificarMat->bindParam(':matricula', $matricula);
            $stmtVerificarMat->execute();
            if ($stmtVerificarMat->fetch()) {
                // Se a matrícula já existe, gerar uma nova
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
            
            // Verificar se endereço é o mesmo do responsável
            $enderecoMesmoResponsavel = isset($_POST['endereco_mesmo_responsavel']) && $_POST['endereco_mesmo_responsavel'] === '1';
            
            // Preparar dados de endereço
            if ($enderecoMesmoResponsavel) {
                // Usar endereço do responsável
                $endereco = !empty($_POST['responsavel_endereco']) ? trim($_POST['responsavel_endereco']) : null;
                $numero = !empty($_POST['responsavel_numero']) ? trim($_POST['responsavel_numero']) : null;
                $complemento = !empty($_POST['responsavel_complemento']) ? trim($_POST['responsavel_complemento']) : null;
                $bairro = !empty($_POST['responsavel_bairro']) ? trim($_POST['responsavel_bairro']) : null;
                $cidade = !empty($_POST['responsavel_cidade']) ? trim($_POST['responsavel_cidade']) : null;
                $estado = !empty($_POST['responsavel_estado']) ? trim($_POST['responsavel_estado']) : 'CE';
                $cep = !empty($_POST['responsavel_cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['responsavel_cep'])) : null;
            } else {
                // Usar endereço do aluno
                $endereco = !empty($_POST['endereco']) ? trim($_POST['endereco']) : null;
                $numero = !empty($_POST['numero']) ? trim($_POST['numero']) : null;
                $complemento = !empty($_POST['complemento']) ? trim($_POST['complemento']) : null;
                $bairro = !empty($_POST['bairro']) ? trim($_POST['bairro']) : null;
                $cidade = !empty($_POST['cidade']) ? trim($_POST['cidade']) : null;
                $estado = !empty($_POST['estado']) ? trim($_POST['estado']) : 'CE';
                $cep = !empty($_POST['cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['cep'])) : null;
            }
            
            // Preparar dados para o model
                    $dados = [
                        'cpf' => $cpf,
                        'nome' => trim($_POST['nome'] ?? ''),
                        'data_nascimento' => $_POST['data_nascimento'] ?? null,
                        'sexo' => $_POST['sexo'] ?? null,
                        'email' => !empty($emailInformado) ? $emailInformado : null,
                        'telefone' => !empty($telefone) ? $telefone : null,
                        'endereco' => $endereco,
                        'numero' => $numero,
                        'complemento' => $complemento,
                        'bairro' => $bairro,
                        'cidade' => $cidade,
                        'estado' => $estado,
                        'cep' => $cep,
                        'matricula' => $matricula,
                        'nis' => !empty($_POST['nis']) ? preg_replace('/[^0-9]/', '', trim($_POST['nis'])) : null,
                        'responsavel_id' => !empty($_POST['responsavel_id']) ? $_POST['responsavel_id'] : null,
                        'escola_id' => !empty($_POST['escola_id']) ? $_POST['escola_id'] : null,
                'data_matricula' => $_POST['data_matricula'] ?? date('Y-m-d'),
                'situacao' => $_POST['situacao'] ?? 'MATRICULADO',
                'precisa_transporte' => isset($_POST['precisa_transporte']) ? 1 : 0,
                'distrito_transporte' => !empty($_POST['distrito_transporte']) ? trim($_POST['distrito_transporte']) : null,
                'localidade_transporte' => !empty($_POST['localidade_transporte']) ? trim($_POST['localidade_transporte']) : null,
                'nome_social' => !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null,
                'raca' => !empty($_POST['raca']) ? trim($_POST['raca']) : null,
                'is_pcd' => isset($_POST['is_pcd']) ? 1 : 0,
                'cids' => !empty($_POST['cids']) && is_array($_POST['cids']) ? $_POST['cids'] : []
            ];
            
            // Validar CIDs se is_pcd for marcado
            if ($dados['is_pcd'] && empty($dados['cids'])) {
                throw new Exception('É necessário informar pelo menos um CID quando o aluno é PCD.');
            }
            
            // Validar cada CID
            if ($dados['is_pcd'] && !empty($dados['cids'])) {
                foreach ($dados['cids'] as $index => $cid) {
                    if (empty($cid['codigo']) || trim($cid['codigo']) === '') {
                        throw new Exception("O código CID #" . ($index + 1) . " é obrigatório.");
                    }
                    // Validar formato do CID (letra seguida de números e ponto opcional)
                    $codigoCID = trim($cid['codigo']);
                    if (!preg_match('/^[A-Z][0-9]{1,2}(\.[0-9])?$/', $codigoCID)) {
                        throw new Exception("O código CID #" . ($index + 1) . " está em formato inválido. Use o formato: Letra + Números + Ponto opcional (ex: F84.0)");
                    }
                }
            }
            
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
                
                // Verificar se deve criar responsável
                $criarResponsavel = isset($_POST['criar_responsavel']) && $_POST['criar_responsavel'] === '1';
                
                if ($criarResponsavel && $alunoId) {
                    // Preparar dados do responsável
                    $responsavelCpf = preg_replace('/[^0-9]/', '', $_POST['responsavel_cpf'] ?? '');
                    $responsavelTelefone = preg_replace('/[^0-9]/', '', $_POST['responsavel_telefone'] ?? '');
                    $responsavelEmail = !empty($_POST['responsavel_email']) ? trim($_POST['responsavel_email']) : '';
                    
                    // Validar dados do responsável
                    if (empty($responsavelCpf) || strlen($responsavelCpf) !== 11) {
                        throw new Exception('CPF do responsável inválido. Deve conter 11 dígitos.');
                    }
                    
                    if (empty($_POST['responsavel_nome'])) {
                        throw new Exception('Nome do responsável é obrigatório.');
                    }
                    
                    if (empty($_POST['responsavel_senha']) || strlen($_POST['responsavel_senha']) < 6) {
                        throw new Exception('Senha do responsável é obrigatória e deve ter no mínimo 6 caracteres.');
                    }
                    
                    if (empty($_POST['responsavel_parentesco'])) {
                        throw new Exception('Parentesco é obrigatório.');
                    }
                    
                    // Verificar se CPF do responsável já existe
                    $sqlVerificarCPFResp = "SELECT id FROM pessoa WHERE cpf = :cpf";
                    $stmtVerificarResp = $conn->prepare($sqlVerificarCPFResp);
                    $stmtVerificarResp->bindParam(':cpf', $responsavelCpf);
                    $stmtVerificarResp->execute();
                    if ($stmtVerificarResp->fetch()) {
                        throw new Exception('CPF do responsável já cadastrado no sistema.');
                    }
                    
                    if (!empty($responsavelEmail)) {
                        $sqlVerificarEmailResp = "SELECT id FROM pessoa WHERE email = :email LIMIT 1";
                        $stmtVerificarEmailResp = $conn->prepare($sqlVerificarEmailResp);
                        $stmtVerificarEmailResp->bindParam(':email', $responsavelEmail);
                        $stmtVerificarEmailResp->execute();
                        if ($stmtVerificarEmailResp->fetch()) {
                            throw new Exception('Email do responsável já cadastrado no sistema.');
                        }
                    }
                    
                    // Preparar endereço do responsável
                    $responsavelEndereco = !empty($_POST['responsavel_endereco']) ? trim($_POST['responsavel_endereco']) : null;
                    $responsavelNumero = !empty($_POST['responsavel_numero']) ? trim($_POST['responsavel_numero']) : null;
                    $responsavelComplemento = !empty($_POST['responsavel_complemento']) ? trim($_POST['responsavel_complemento']) : null;
                    $responsavelBairro = !empty($_POST['responsavel_bairro']) ? trim($_POST['responsavel_bairro']) : null;
                    $responsavelCidade = !empty($_POST['responsavel_cidade']) ? trim($_POST['responsavel_cidade']) : null;
                    $responsavelEstado = !empty($_POST['responsavel_estado']) ? trim($_POST['responsavel_estado']) : 'CE';
                    $responsavelCep = !empty($_POST['responsavel_cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['responsavel_cep'])) : null;
                    
                    // Criar responsável
                    $dadosResponsavel = [
                        'nome' => trim($_POST['responsavel_nome'] ?? ''),
                        'cpf' => $responsavelCpf,
                        'data_nascimento' => !empty($_POST['responsavel_data_nascimento']) ? $_POST['responsavel_data_nascimento'] : null,
                        'sexo' => $_POST['responsavel_sexo'] ?? null,
                        'email' => !empty($responsavelEmail) ? $responsavelEmail : null,
                        'telefone' => !empty($responsavelTelefone) ? $responsavelTelefone : null,
                        'endereco' => $responsavelEndereco,
                        'numero' => $responsavelNumero,
                        'complemento' => $responsavelComplemento,
                        'bairro' => $responsavelBairro,
                        'cidade' => $responsavelCidade,
                        'estado' => $responsavelEstado,
                        'cep' => $responsavelCep,
                        'senha' => $_POST['responsavel_senha'] ?? ''
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
                            
                            // Associar responsável ao aluno na tabela aluno_responsavel
                            $parentesco = $_POST['responsavel_parentesco'] ?? 'OUTRO';
                            $associacao = $responsavelModel->associarAlunos($responsavelPessoaId, [$alunoId], $parentesco);
                            
                            if ($associacao['success']) {
                                $mensagem .= ' Responsável cadastrado e associado com sucesso!';
                            } else {
                                $mensagem .= ' Responsável cadastrado e vinculado ao aluno, mas houve erro ao associar na tabela aluno_responsavel: ' . ($associacao['message'] ?? 'Erro desconhecido');
                            }
                        } else {
                            throw new Exception('Aluno cadastrado, mas não foi possível obter o ID do responsável criado.');
                        }
                    } else {
                        throw new Exception('Aluno cadastrado, mas erro ao criar responsável: ' . ($resultadoResponsavel['message'] ?? 'Erro desconhecido'));
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
    
            if ($_POST['acao'] === 'editar_aluno') {
                try {
                    $alunoId = $_POST['aluno_id'] ?? null;
                    if (empty($alunoId)) {
                        throw new Exception('ID do aluno não informado.');
                    }
            
            // Buscar aluno existente
            $aluno = $alunoModel->buscarPorId($alunoId);
            if (!$aluno) {
                throw new Exception('Aluno não encontrado.');
            }
            
            // Preparar dados
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            
                    // Validar CPF (se foi alterado)
                    $cpfAtual = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
                    if (!empty($cpfAtual) && strlen($cpfAtual) !== 11) {
                        throw new Exception('CPF inválido. Deve conter 11 dígitos.');
                    }
                    $emailAtual = !empty($_POST['email']) ? trim($_POST['email']) : '';
            
            // Verificar se CPF já existe em outro aluno
                    if (!empty($cpfAtual) && $cpfAtual !== $aluno['cpf']) {
                        $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id";
                        $stmtVerificar = $conn->prepare($sqlVerificarCPF);
                        $stmtVerificar->bindParam(':cpf', $cpfAtual);
                        $stmtVerificar->bindParam(':pessoa_id', $aluno['pessoa_id']);
                        $stmtVerificar->execute();
                        if ($stmtVerificar->fetch()) {
                            throw new Exception('CPF já cadastrado para outro aluno.');
                        }
                    }
                    if (!empty($emailAtual) && $emailAtual !== ($aluno['email'] ?? '')) {
                        $sqlVerificarEmail = "SELECT id FROM pessoa WHERE email = :email AND id != :pessoa_id LIMIT 1";
                        $stmtVerificarEmail = $conn->prepare($sqlVerificarEmail);
                        $stmtVerificarEmail->bindParam(':email', $emailAtual);
                        $stmtVerificarEmail->bindParam(':pessoa_id', $aluno['pessoa_id']);
                        $stmtVerificarEmail->execute();
                        if ($stmtVerificarEmail->fetch()) {
                            throw new Exception('Email já cadastrado para outro usuário.');
                        }
                    }
            
                    $dados = [
                        'nome' => trim($_POST['nome'] ?? ''),
                        'data_nascimento' => $_POST['data_nascimento'] ?? null,
                        'sexo' => $_POST['sexo'] ?? null,
                        'email' => !empty($emailAtual) ? $emailAtual : null,
                        'telefone' => !empty($telefone) ? $telefone : null,
                        'matricula' => $_POST['matricula'] ?? $aluno['matricula'],
                        'nis' => !empty($_POST['nis']) ? preg_replace('/[^0-9]/', '', trim($_POST['nis'])) : null,
                        'responsavel_id' => !empty($_POST['responsavel_id']) ? $_POST['responsavel_id'] : null,
                        'escola_id' => !empty($_POST['escola_id']) ? $_POST['escola_id'] : null,
                'data_matricula' => $_POST['data_matricula'] ?? $aluno['data_matricula'],
                'situacao' => $_POST['situacao'] ?? 'MATRICULADO',
                'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1,
                'precisa_transporte' => isset($_POST['precisa_transporte']) ? 1 : 0,
                'distrito_transporte' => !empty($_POST['distrito_transporte']) ? trim($_POST['distrito_transporte']) : null,
                'localidade_transporte' => !empty($_POST['localidade_transporte']) ? trim($_POST['localidade_transporte']) : null,
                'nome_social' => !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null,
                'raca' => !empty($_POST['raca']) ? trim($_POST['raca']) : null,
                'is_pcd' => isset($_POST['is_pcd']) ? 1 : 0,
                'cids' => !empty($_POST['cids']) && is_array($_POST['cids']) ? $_POST['cids'] : []
            ];
            
            // Validar CIDs se is_pcd for marcado
            if ($dados['is_pcd'] && empty($dados['cids'])) {
                throw new Exception('É necessário informar pelo menos um CID quando o aluno é PCD.');
            }
            
            // Validar cada CID
            if ($dados['is_pcd'] && !empty($dados['cids'])) {
                foreach ($dados['cids'] as $index => $cid) {
                    if (empty($cid['codigo']) || trim($cid['codigo']) === '') {
                        throw new Exception("O código CID #" . ($index + 1) . " é obrigatório.");
                    }
                    // Validar formato do CID (letra seguida de números e ponto opcional)
                    $codigoCID = trim($cid['codigo']);
                    if (!preg_match('/^[A-Z][0-9]{1,2}(\.[0-9])?$/', $codigoCID)) {
                        throw new Exception("O código CID #" . ($index + 1) . " está em formato inválido. Use o formato: Letra + Números + Ponto opcional (ex: F84.0)");
                    }
                }
            }
            
            if (empty($dados['nome'])) {
                throw new Exception('Nome é obrigatório.');
            }
            if (empty($dados['data_nascimento'])) {
                throw new Exception('Data de nascimento é obrigatória.');
            }
            if (empty($dados['sexo'])) {
                throw new Exception('Sexo é obrigatório.');
            }
            
            if (!empty($dados['nis'])) {
                $nis = preg_replace('/[^0-9]/', '', $dados['nis']);
                if (strlen($nis) !== 11) {
                    throw new Exception('NIS inválido. Deve conter exatamente 11 dígitos.');
                }
                $dados['nis'] = $nis;
            }
            
            // Validar data de nascimento (não pode ser futura)
            if (!empty($dados['data_nascimento'])) {
                $dataNasc = new DateTime($dados['data_nascimento']);
                $hoje = new DateTime();
                if ($dataNasc > $hoje) {
                    throw new Exception('Data de nascimento não pode ser futura.');
                }
            }
            
            // Atualizar CPF se foi alterado
            if (!empty($cpfAtual) && $cpfAtual !== $aluno['cpf']) {
                $sqlUpdateCPF = "UPDATE pessoa SET cpf = :cpf WHERE id = :pessoa_id";
                $stmtUpdateCPF = $conn->prepare($sqlUpdateCPF);
                $stmtUpdateCPF->bindParam(':cpf', $cpfAtual);
                $stmtUpdateCPF->bindParam(':pessoa_id', $aluno['pessoa_id']);
                $stmtUpdateCPF->execute();
            }
            
            // Usar o model para atualizar o aluno
            $result = $alunoModel->atualizar($alunoId, $dados);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Aluno atualizado com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao atualizar aluno.'
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
    
    if ($_POST['acao'] === 'excluir_aluno') {
        try {
            $alunoId = $_POST['aluno_id'] ?? null;
            if (empty($alunoId)) {
                throw new Exception('ID do aluno não informado.');
            }
            
            // Verificar se o aluno existe
            $aluno = $alunoModel->buscarPorId($alunoId);
            if (!$aluno) {
                throw new Exception('Aluno não encontrado.');
            }
            
            // Verificar se o aluno está matriculado em alguma turma ativa
            $sqlTurmaAtiva = "SELECT COUNT(*) as total FROM aluno_turma WHERE aluno_id = :aluno_id AND fim IS NULL";
            $stmtTurma = $conn->prepare($sqlTurmaAtiva);
            $stmtTurma->bindParam(':aluno_id', $alunoId);
            $stmtTurma->execute();
            $resultTurma = $stmtTurma->fetch(PDO::FETCH_ASSOC);
            
            if ($resultTurma['total'] > 0) {
                throw new Exception('Não é possível excluir o aluno pois ele está matriculado em uma ou mais turmas ativas. Primeiro, transfira ou conclua a matrícula do aluno.');
            }
            
            // Usar o model para excluir (soft delete)
            $result = $alunoModel->excluir($alunoId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Aluno excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir aluno.');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    // Limpar qualquer output anterior
    if (ob_get_level()) { 
        ob_clean(); 
    }
    // Verificar se headers já foram enviados
    if (!headers_sent()) {
    header('Content-Type: application/json');
    }
    
    if ($_GET['acao'] === 'buscar_aluno') {
        $alunoId = $_GET['id'] ?? null;
        if (empty($alunoId)) {
            echo json_encode(['success' => false, 'message' => 'ID do aluno não informado']);
            exit;
        }
        
        $aluno = $alunoModel->buscarPorId($alunoId);
        if ($aluno && is_array($aluno)) {
            // Formatar CPF e telefone para exibição
            if (!empty($aluno['cpf']) && strlen($aluno['cpf']) === 11) {
                $aluno['cpf_formatado'] = substr($aluno['cpf'], 0, 3) . '.' . substr($aluno['cpf'], 3, 3) . '.' . substr($aluno['cpf'], 6, 3) . '-' . substr($aluno['cpf'], 9, 2);
            }
            if (!empty($aluno['telefone'])) {
                $tel = $aluno['telefone'];
                if (strlen($tel) === 11) {
                    $aluno['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                } elseif (strlen($tel) === 10) {
                    $aluno['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                }
            }
            echo json_encode(['success' => true, 'aluno' => $aluno]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Aluno não encontrado']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_localidades') {
        $distrito = $_GET['distrito'] ?? null;
        if (empty($distrito)) {
            echo json_encode(['success' => false, 'message' => 'Distrito não informado']);
            exit;
        }
        
        try {
            $sql = "SELECT DISTINCT localidade FROM distrito_localidade WHERE distrito = :distrito ORDER BY localidade ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':distrito', $distrito);
            $stmt->execute();
            $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'localidades' => $localidades]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar localidades: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'listar_alunos') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        
        // Buscar escola: primeiro do campo escola_id do aluno, depois da turma (se não tiver escola_id)
        $sql = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento,
                       COALESCE(e_direta.nome, e_turma.nome) as escola_nome,
                       COALESCE(e_direta.id, e_turma.id) as escola_id,
                       CASE WHEN at.turma_id IS NOT NULL OR a.situacao IN ('MATRICULADO', 'TRANSFERIDO') THEN 'Sim' ELSE 'Não' END as matriculado_turma
                FROM aluno a
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e_direta ON a.escola_id = e_direta.id
                LEFT JOIN (
                    SELECT at1.aluno_id, at1.turma_id, at1.status, at1.fim
                    FROM aluno_turma at1
                    INNER JOIN (
                        SELECT aluno_id, MAX(inicio) as max_inicio
                        FROM aluno_turma
                        GROUP BY aluno_id
                    ) at_max ON at1.aluno_id = at_max.aluno_id AND at1.inicio = at_max.max_inicio
                ) at ON a.id = at.aluno_id
                LEFT JOIN turma t ON at.turma_id = t.id
                LEFT JOIN escola e_turma ON t.escola_id = e_turma.id
                WHERE a.ativo = 1";
        
        $params = [];
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND (COALESCE(e_direta.id, e_turma.id) = :escola_id)";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR a.matricula LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        $sql .= " ORDER BY p.nome ASC LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'alunos' => $alunos]);
        exit;
    }
}

// Buscar alunos iniciais (apenas ativos)
try {
// Buscar escola: primeiro do campo escola_id do aluno, depois da turma (se não tiver escola_id)
$sqlAlunos = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento,
                     COALESCE(e_direta.nome, e_turma.nome) as escola_nome,
                     COALESCE(e_direta.id, e_turma.id) as escola_id,
                     CASE WHEN at.turma_id IS NOT NULL OR a.situacao IN ('MATRICULADO', 'TRANSFERIDO') THEN 'Sim' ELSE 'Não' END as matriculado_turma
              FROM aluno a
              INNER JOIN pessoa p ON a.pessoa_id = p.id
              LEFT JOIN escola e_direta ON a.escola_id = e_direta.id
              LEFT JOIN (
                  SELECT at1.aluno_id, at1.turma_id, at1.status, at1.fim
                  FROM aluno_turma at1
                  INNER JOIN (
                      SELECT aluno_id, MAX(inicio) as max_inicio
                      FROM aluno_turma
                      GROUP BY aluno_id
                  ) at_max ON at1.aluno_id = at_max.aluno_id AND at1.inicio = at_max.max_inicio
              ) at ON a.id = at.aluno_id
              LEFT JOIN turma t ON at.turma_id = t.id
              LEFT JOIN escola e_turma ON t.escola_id = e_turma.id
              WHERE a.ativo = 1
              ORDER BY p.nome ASC
              LIMIT 50";
$stmtAlunos = $conn->prepare($sqlAlunos);
$stmtAlunos->execute();
$alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao buscar alunos iniciais: " . $e->getMessage());
    $alunos = [];
}

// Limpar output buffer antes de enviar HTML
if (ob_get_level()) {
    ob_end_clean();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Gestão de Alunos') ?></title>
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
        .etapa-conteudo {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Autocomplete customizado */
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
        .autocomplete-item .distrito-nome {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Alunos</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Alunos</h2>
                        <p class="text-gray-600 mt-1">Cadastre, edite e exclua alunos do sistema</p>
                    </div>
                    <button onclick="abrirModalNovoAluno()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Aluno</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome, CPF ou Matrícula..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarAlunos()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarAlunos()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarAlunos()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Alunos -->
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
                            <tbody id="lista-alunos">
                                <?php if (empty($alunos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhum aluno encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($alunos as $aluno): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['matricula'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['cpf'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['escola_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarAluno(<?= $aluno['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirAluno(<?= $aluno['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal de Edição de Aluno -->
    <div id="modalEditarAluno" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Editar Aluno</h2>
                <button onclick="fecharModalEditarAluno()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formEditarAluno" class="space-y-6 max-w-6xl mx-auto">
                <div id="alertaErroEditar" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                <div id="alertaSucessoEditar" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                
                <input type="hidden" name="aluno_id" id="editar_aluno_id">
                
                <!-- Informações Pessoais -->
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                            <input type="text" name="nome" id="editar_nome" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                            <input type="text" name="cpf" id="editar_cpf" required maxlength="14"
                                   placeholder="000.000.000-00"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarCPF(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" id="editar_data_nascimento" required max="<?= date('Y-m-d') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                            <select name="sexo" id="editar_sexo" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Selecione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="editar_email"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                            <input type="text" name="telefone" id="editar_telefone" maxlength="15"
                                   placeholder="(00) 00000-0000"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarTelefone(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                            <input type="text" name="nome_social" id="editar_nome_social"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Nome pelo qual prefere ser chamado">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Raça/Cor</label>
                            <select name="raca" id="editar_raca"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                
                <!-- Informações Acadêmicas -->
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Acadêmicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                            <input type="text" name="matricula" id="editar_matricula"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIS (Número de Identificação Social)</label>
                            <input type="text" name="nis" id="editar_nis" maxlength="14"
                                   placeholder="000.00000.00-0"
                                   oninput="formatarNIS(this)"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select name="escola_id" id="editar_escola_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Selecione uma escola...</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Matrícula</label>
                            <input type="date" name="data_matricula" id="editar_data_matricula"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Situação</label>
                            <select name="situacao" id="editar_situacao"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="MATRICULADO">Matriculado</option>
                                <option value="TRANSFERIDO">Transferido</option>
                                <option value="EVADIDO">Evadido</option>
                                <option value="CONCLUIDO">Concluído</option>
                                <option value="CANCELADO">Cancelado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="ativo" id="editar_ativo"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Informações de PCD -->
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Deficiência</h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="checkbox" name="is_pcd" id="editar_is_pcd" value="1" 
                                   onchange="toggleCamposPCDEditar()"
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="editar_is_pcd" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Aluno é Pessoa com Deficiência (PCD)
                            </label>
                        </div>
                        <div id="container-cids-editar" class="hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <label class="block text-sm font-semibold text-gray-800 mb-3">CID (Código de Classificação Internacional de Doenças)</label>
                                <div id="lista-cids-editar" class="space-y-3 mb-4">
                                    <!-- CIDs serão adicionados dinamicamente aqui -->
                                </div>
                                <button type="button" onclick="adicionarCampoCIDEditar()" 
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
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Transporte Escolar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="precisa_transporte" id="editar_precisa_transporte" value="1" 
                                       onchange="toggleDistritoTransporteEditar()"
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Aluno precisa de transporte escolar</span>
                            </label>
                        </div>
                        <div id="container-distrito-transporte-editar" class="hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Distrito de Origem *</label>
                                <div class="autocomplete-container">
                                    <input type="text" name="editar_distrito_transporte" id="editar_distrito_transporte" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                           placeholder="Digite o distrito..." autocomplete="off">
                                    <div id="autocomplete-dropdown-transporte-editar" class="autocomplete-dropdown"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Selecione o distrito onde o aluno precisa de transporte</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Localidade (opcional)</label>
                                <select name="editar_localidade_transporte" id="editar_localidade_transporte"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Selecione uma localidade...</option>
                                    <option value="centro">Centro</option>
                                    <option value="zona_rural">Zona Rural</option>
                                    <option value="outra">Outra</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Selecione a localidade do endereço do aluno</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                </form>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalEditarAluno()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formEditarAluno" id="btnSalvarEdicao"
                        class="px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Alterações</span>
                    <svg id="spinnerSalvarEdicao" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Cadastro de Aluno -->
    <div id="modalNovoAluno" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <div>
                <h2 class="text-2xl font-bold text-gray-900">Cadastrar Novo Aluno</h2>
                    <!-- Indicador de Etapas -->
                    <div class="flex items-center space-x-4 mt-4">
                        <div class="flex items-center">
                            <div id="step-indicator-1" class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">1</div>
                            <span class="ml-2 text-sm font-medium text-gray-700">Dados do Aluno</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div id="step-indicator-2" class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-semibold">2</div>
                            <span class="ml-2 text-sm font-medium text-gray-500">Responsável (Opcional)</span>
                        </div>
                    </div>
                </div>
                <button onclick="fecharModalNovoAluno()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formNovoAluno" class="space-y-6 max-w-6xl mx-auto">
                <div id="alertaErro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                <div id="alertaSucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                
                <!-- ETAPA 1: Dados do Aluno -->
                <div id="etapa-aluno" class="etapa-conteudo">
                
                <!-- Informações Pessoais -->
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                            <input type="text" name="nome" id="nome" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                            <input type="text" name="cpf" id="cpf" required maxlength="14"
                                   placeholder="000.000.000-00"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarCPF(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" id="data_nascimento" required max="<?= date('Y-m-d') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                            <select name="sexo" id="sexo" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Selecione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="email"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                            <input type="text" name="telefone" id="telefone" maxlength="15"
                                   placeholder="(00) 00000-0000"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarTelefone(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                            <input type="text" name="nome_social" id="nome_social"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Nome pelo qual prefere ser chamado">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Raça/Cor</label>
                            <select name="raca" id="raca"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço</h3>
                    <div class="mb-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="endereco_mesmo_responsavel" id="endereco_mesmo_responsavel" value="1" 
                                   onchange="toggleEnderecoAluno()"
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Endereço é o mesmo do responsável</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-7">Se marcado, o endereço do responsável será usado para o aluno</p>
                    </div>
                    <div id="container-endereco-aluno" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                            <input type="text" name="endereco" id="endereco"
                                   placeholder="Rua, Avenida, etc."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                            <input type="text" name="numero" id="numero"
                                   placeholder="Número"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                            <input type="text" name="complemento" id="complemento"
                                   placeholder="Apartamento, bloco, etc."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                            <input type="text" name="bairro" id="bairro"
                                   placeholder="Bairro"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                            <input type="text" name="cidade" id="cidade"
                                   placeholder="Cidade"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="estado" id="estado"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                            <input type="text" name="cep" id="cep" maxlength="9"
                                   placeholder="00000-000"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarCEP(this)">
                        </div>
                    </div>
                </div>
                
                <!-- Informações Acadêmicas -->
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Acadêmicas</h3>
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
                                   oninput="formatarNIS(this)"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select name="escola_id" id="escola_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Selecione uma escola...</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Matrícula</label>
                            <input type="date" name="data_matricula" id="data_matricula"
                                   value="<?= date('Y-m-d') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Situação</label>
                            <select name="situacao" id="situacao"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="MATRICULADO" selected>Matriculado</option>
                                <option value="TRANSFERIDO">Transferido</option>
                                <option value="EVADIDO">Evadido</option>
                                <option value="CONCLUIDO">Concluído</option>
                                <option value="CANCELADO">Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Informações de PCD -->
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Deficiência</h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="checkbox" name="is_pcd" id="is_pcd" value="1" 
                                   onchange="toggleCamposPCD()"
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
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
                <div>
                    <h3 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Transporte Escolar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="precisa_transporte" id="precisa_transporte" value="1" 
                                       onchange="toggleDistritoTransporte()"
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Aluno precisa de transporte escolar</span>
                            </label>
                        </div>
                        <div id="container-distrito-transporte" class="hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Distrito de Origem *</label>
                                <div class="autocomplete-container">
                                    <input type="text" name="distrito_transporte" id="distrito_transporte" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                           placeholder="Digite o distrito..." autocomplete="off">
                                    <div id="autocomplete-dropdown-transporte" class="autocomplete-dropdown"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Selecione o distrito onde o aluno precisa de transporte</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Localidade (opcional) -> funcionalidade em desenvolvimento 🔧🔨</label>
                                <select name="localidade_transporte" id="localidade_transporte" disabled
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100 cursor-not-allowed">
                                    <option value="">Selecione uma localidade...</option>
                                    <option value="centro">Centro</option>
                                    <option value="zona_rural">Zona Rural</option>
                                    <option value="outra">Outra</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Selecione a localidade do endereço do aluno</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
                <!-- ETAPA 2: Dados do Responsável (Opcional) -->
                <div id="etapa-responsavel" class="etapa-conteudo hidden">
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Opcional:</strong> Você pode cadastrar um responsável para este aluno agora. Se preferir, pode fazer isso depois.
                        </p>
    </div>
    
                    <!-- Dados Pessoais do Responsável -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Dados Pessoais do Responsável</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo</label>
                                <input type="text" name="responsavel_nome" id="responsavel_nome"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                                <input type="text" name="responsavel_cpf" id="responsavel_cpf" maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                                <input type="date" name="responsavel_data_nascimento" id="responsavel_data_nascimento" max="<?= date('Y-m-d') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                                <select name="responsavel_sexo" id="responsavel_sexo"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                <input type="email" name="responsavel_email" id="responsavel_email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="responsavel_telefone" id="responsavel_telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="formatarTelefone(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Endereço do Responsável -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço do Responsável</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                <input type="text" name="responsavel_endereco" id="responsavel_endereco"
                                       placeholder="Rua, Avenida, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="copiarEnderecoParaAluno()">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" name="responsavel_numero" id="responsavel_numero"
                                       placeholder="Número"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="copiarEnderecoParaAluno()">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                <input type="text" name="responsavel_complemento" id="responsavel_complemento"
                                       placeholder="Apartamento, bloco, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="copiarEnderecoParaAluno()">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                <input type="text" name="responsavel_bairro" id="responsavel_bairro"
                                       placeholder="Bairro"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="copiarEnderecoParaAluno()">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                <input type="text" name="responsavel_cidade" id="responsavel_cidade"
                                       placeholder="Cidade"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="copiarEnderecoParaAluno()">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="responsavel_estado" id="responsavel_estado"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                <input type="text" name="responsavel_cep" id="responsavel_cep" maxlength="9"
                                       placeholder="00000-000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       oninput="formatarCEP(this); copiarEnderecoParaAluno();">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acesso ao Sistema -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Acesso ao Sistema</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                                <input type="password" name="responsavel_senha" id="responsavel_senha" minlength="6"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Mínimo 6 caracteres">
                                <p class="text-xs text-gray-500 mt-1">A senha deve ter no mínimo 6 caracteres</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parentesco</label>
                                <select name="responsavel_parentesco" id="responsavel_parentesco"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                </div>
                
                </form>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-between items-center p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalNovoAluno()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <div class="flex space-x-3">
                    <button type="button" id="btnVoltarEtapa" onclick="voltarEtapa()" 
                            class="hidden px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Voltar
                    </button>
                    <button type="button" id="btnAvancarEtapa" onclick="avancarEtapa()" 
                            class="px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors duration-200">
                        Avançar
                    </button>
                    <button type="submit" form="formNovoAluno" id="btnSalvarAluno"
                            class="hidden px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <span>Salvar Aluno</span>
                        <svg id="spinnerSalvar" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                </div>
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

        let etapaAtual = 1;
        const totalEtapas = 2;

        function abrirModalNovoAluno() {
            const modal = document.getElementById('modalNovoAluno');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Resetar para primeira etapa
                etapaAtual = 1;
                atualizarNavegacaoEtapas();
                // Gerar matrícula automática
                gerarMatriculaAutomatica();
                // Limpar formulário
                document.getElementById('formNovoAluno').reset();
                document.getElementById('data_matricula').value = new Date().toISOString().split('T')[0];
                // Limpar alertas
                document.getElementById('alertaErro').classList.add('hidden');
                document.getElementById('alertaSucesso').classList.add('hidden');
            }
        }
        
        function atualizarNavegacaoEtapas() {
            const etapaAluno = document.getElementById('etapa-aluno');
            const etapaResponsavel = document.getElementById('etapa-responsavel');
            const btnVoltar = document.getElementById('btnVoltarEtapa');
            const btnAvancar = document.getElementById('btnAvancarEtapa');
            const btnSalvar = document.getElementById('btnSalvarAluno');
            const stepIndicator1 = document.getElementById('step-indicator-1');
            const stepIndicator2 = document.getElementById('step-indicator-2');
            
            if (etapaAtual === 1) {
                etapaAluno.classList.remove('hidden');
                etapaResponsavel.classList.add('hidden');
                btnVoltar.classList.add('hidden');
                btnAvancar.classList.remove('hidden');
                btnSalvar.classList.add('hidden');
                stepIndicator1.classList.remove('bg-gray-300', 'text-gray-600');
                stepIndicator1.classList.add('bg-blue-600', 'text-white');
                stepIndicator2.classList.remove('bg-blue-600', 'text-white');
                stepIndicator2.classList.add('bg-gray-300', 'text-gray-600');
            } else if (etapaAtual === 2) {
                etapaAluno.classList.add('hidden');
                etapaResponsavel.classList.remove('hidden');
                btnVoltar.classList.remove('hidden');
                btnAvancar.classList.add('hidden');
                btnSalvar.classList.remove('hidden');
                stepIndicator1.classList.remove('bg-blue-600', 'text-white');
                stepIndicator1.classList.add('bg-green-500', 'text-white');
                stepIndicator2.classList.remove('bg-gray-300', 'text-gray-600');
                stepIndicator2.classList.add('bg-blue-600', 'text-white');
            }
        }
        
        function avancarEtapa() {
            // Validar campos obrigatórios da etapa 1
            const nome = document.getElementById('nome').value.trim();
            const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
            const dataNascimento = document.getElementById('data_nascimento').value;
            const sexo = document.getElementById('sexo').value;
            
            if (!nome || !cpf || cpf.length !== 11 || !dataNascimento || !sexo) {
                alert('Por favor, preencha todos os campos obrigatórios do aluno (Nome, CPF, Data de Nascimento e Sexo).');
                return;
            }
            
            etapaAtual = 2;
            atualizarNavegacaoEtapas();
        }
        
        function voltarEtapa() {
            etapaAtual = 1;
            atualizarNavegacaoEtapas();
        }
        
        function fecharModalNovoAluno() {
            const modal = document.getElementById('modalNovoAluno');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                // Resetar para primeira etapa
                etapaAtual = 1;
                atualizarNavegacaoEtapas();
                // Limpar CIDs
                document.getElementById('lista-cids').innerHTML = '';
                contadorCID = 0;
                document.getElementById('is_pcd').checked = false;
                document.getElementById('container-cids').classList.add('hidden');
                // Resetar checkbox de endereço e desbloquear campos
                const checkboxEndereco = document.getElementById('endereco_mesmo_responsavel');
                if (checkboxEndereco) {
                    checkboxEndereco.checked = false;
                    toggleEnderecoAluno();
                }
            }
        }
        
        // Funções para gerenciar campos PCD e CID
        let contadorCID = 0;
        
        function toggleCamposPCD() {
            const isPCD = document.getElementById('is_pcd').checked;
            const containerCids = document.getElementById('container-cids');
            
            if (isPCD) {
                containerCids.classList.remove('hidden');
                // Adicionar primeiro campo CID se não houver nenhum
                if (contadorCID === 0) {
                    adicionarCampoCID();
                }
            } else {
                containerCids.classList.add('hidden');
                // Limpar todos os CIDs
                document.getElementById('lista-cids').innerHTML = '';
                contadorCID = 0;
            }
        }
        
        function adicionarCampoCID() {
            const listaCids = document.getElementById('lista-cids');
            const cidIndex = contadorCID++;
            
            const cidDiv = document.createElement('div');
            cidDiv.className = 'bg-white rounded-lg border border-gray-300 p-4 shadow-sm';
            cidDiv.id = `cid-container-${cidIndex}`;
            cidDiv.innerHTML = `
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código CID *</label>
                            <input type="text" name="cids[${cidIndex}][codigo]" 
                                   id="cid-codigo-${cidIndex}"
                                   placeholder="Ex: F84.0" 
                                   maxlength="10"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarCID(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição (Opcional)</label>
                            <input type="text" name="cids[${cidIndex}][descricao]" 
                                   id="cid-descricao-${cidIndex}"
                                   placeholder="Descrição da deficiência" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <button type="button" onclick="removerCampoCID(${cidIndex})" 
                            class="mt-7 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm flex-shrink-0"
                            title="Remover CID">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            listaCids.appendChild(cidDiv);
        }
        
        function removerCampoCID(index) {
            const cidContainer = document.getElementById(`cid-container-${index}`);
            if (cidContainer) {
                cidContainer.remove();
            }
        }
        
        function formatarCID(input) {
            // Formato CID: letra seguida de números e ponto (ex: F84.0)
            let valor = input.value.toUpperCase().replace(/[^A-Z0-9.]/g, '');
            // Limitar a 10 caracteres
            if (valor.length > 10) {
                valor = valor.substring(0, 10);
            }
            input.value = valor;
        }
        
        // Funções para gerenciar campos PCD e CID no formulário de edição
        let contadorCIDEditar = 0;
        
        function toggleCamposPCDEditar() {
            const isPCD = document.getElementById('editar_is_pcd').checked;
            const containerCids = document.getElementById('container-cids-editar');
            
            if (isPCD) {
                containerCids.classList.remove('hidden');
                // Adicionar primeiro campo CID se não houver nenhum
                if (contadorCIDEditar === 0) {
                    adicionarCampoCIDEditar();
                }
            } else {
                containerCids.classList.add('hidden');
                // Limpar todos os CIDs
                document.getElementById('lista-cids-editar').innerHTML = '';
                contadorCIDEditar = 0;
            }
        }
        
        function adicionarCampoCIDEditar(codigoCID = '', descricaoCID = '') {
            const listaCids = document.getElementById('lista-cids-editar');
            const cidIndex = contadorCIDEditar++;
            
            const cidDiv = document.createElement('div');
            cidDiv.className = 'bg-white rounded-lg border border-gray-300 p-4 shadow-sm';
            cidDiv.id = `cid-container-editar-${cidIndex}`;
            cidDiv.innerHTML = `
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código CID *</label>
                            <input type="text" name="cids[${cidIndex}][codigo]" 
                                   id="cid-codigo-editar-${cidIndex}"
                                   value="${codigoCID}"
                                   placeholder="Ex: F84.0" 
                                   maxlength="10"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   oninput="formatarCID(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição (Opcional)</label>
                            <input type="text" name="cids[${cidIndex}][descricao]" 
                                   id="cid-descricao-editar-${cidIndex}"
                                   value="${descricaoCID}"
                                   placeholder="Descrição da deficiência" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <button type="button" onclick="removerCampoCIDEditar(${cidIndex})" 
                            class="mt-7 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm flex-shrink-0"
                            title="Remover CID">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            listaCids.appendChild(cidDiv);
        }
        
        function removerCampoCIDEditar(index) {
            const cidContainer = document.getElementById(`cid-container-editar-${index}`);
            if (cidContainer) {
                cidContainer.remove();
            }
        }
        
        function gerarMatriculaAutomatica() {
            const ano = new Date().getFullYear();
            const campoMatricula = document.getElementById('matricula');
            if (campoMatricula && !campoMatricula.value) {
                // A matrícula será gerada no backend, mas podemos mostrar um placeholder
                campoMatricula.placeholder = 'Será gerada automaticamente';
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
        function formatarNIS(input) {
            let value = input.value.replace(/\D/g, '');
            // Limitar a 11 dígitos
            value = value.slice(0, 11);
            
            // Aplicar máscara: 000.00000.00-0
            if (value.length > 0) {
                if (value.length <= 3) {
                    input.value = value;
                } else if (value.length <= 8) {
                    input.value = value.slice(0, 3) + '.' + value.slice(3);
                } else if (value.length <= 10) {
                    input.value = value.slice(0, 3) + '.' + value.slice(3, 8) + '.' + value.slice(8);
                } else {
                    input.value = value.slice(0, 3) + '.' + value.slice(3, 8) + '.' + value.slice(8, 10) + '-' + value.slice(10);
                }
            } else {
                input.value = '';
            }
        }
        
        function formatarCEP(input) {
            let value = input.value.replace(/\D/g, '');
            // Limitar a 8 dígitos
            value = value.slice(0, 8);
            
            // Aplicar máscara: 00000-000
            if (value.length > 0) {
                if (value.length <= 5) {
                    input.value = value;
                } else {
                    input.value = value.slice(0, 5) + '-' + value.slice(5);
                }
            } else {
                input.value = '';
            }
            
            // Buscar endereço quando CEP estiver completo (8 dígitos)
            if (value.length === 8) {
                buscarEnderecoPorCEP(value, input.id);
            }
        }
        
        function buscarEnderecoPorCEP(cep, inputId) {
            // Determinar se é CEP do aluno ou do responsável
            const isResponsavel = inputId.includes('responsavel');
            
            // Limpar campos antes de buscar
            if (isResponsavel) {
                document.getElementById('responsavel_endereco').value = '';
                document.getElementById('responsavel_bairro').value = '';
                document.getElementById('responsavel_cidade').value = '';
                document.getElementById('responsavel_estado').value = '';
            } else {
                document.getElementById('endereco').value = '';
                document.getElementById('bairro').value = '';
                document.getElementById('cidade').value = '';
                document.getElementById('estado').value = '';
            }
            
            // Buscar CEP na API ViaCEP
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        console.log('CEP não encontrado');
                        return;
                    }
                    
                    // Preencher campos de endereço
                    if (isResponsavel) {
                        document.getElementById('responsavel_endereco').value = data.logradouro || '';
                        document.getElementById('responsavel_bairro').value = data.bairro || '';
                        document.getElementById('responsavel_cidade').value = data.localidade || '';
                        document.getElementById('responsavel_estado').value = data.uf || '';
                        
                        // Copiar para aluno se checkbox estiver marcada
                        copiarEnderecoParaAluno();
                    } else {
                        document.getElementById('endereco').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                });
        }
        
        function toggleEnderecoAluno() {
            const checkbox = document.getElementById('endereco_mesmo_responsavel');
            const containerEndereco = document.getElementById('container-endereco-aluno');
            const camposEndereco = containerEndereco.querySelectorAll('input, select');
            
            if (checkbox.checked) {
                // Bloquear campos de endereço do aluno
                camposEndereco.forEach(campo => {
                    campo.disabled = true;
                    campo.classList.add('bg-gray-100', 'cursor-not-allowed');
                });
                
                // Copiar endereço do responsável para o aluno
                copiarEnderecoParaAluno();
            } else {
                // Desbloquear campos de endereço do aluno
                camposEndereco.forEach(campo => {
                    campo.disabled = false;
                    campo.classList.remove('bg-gray-100', 'cursor-not-allowed');
                });
            }
        }
        
        function copiarEnderecoParaAluno() {
            const checkbox = document.getElementById('endereco_mesmo_responsavel');
            
            if (checkbox && checkbox.checked) {
                // Copiar valores do responsável para o aluno
                document.getElementById('endereco').value = document.getElementById('responsavel_endereco').value || '';
                document.getElementById('numero').value = document.getElementById('responsavel_numero').value || '';
                document.getElementById('complemento').value = document.getElementById('responsavel_complemento').value || '';
                document.getElementById('bairro').value = document.getElementById('responsavel_bairro').value || '';
                document.getElementById('cidade').value = document.getElementById('responsavel_cidade').value || '';
                document.getElementById('estado').value = document.getElementById('responsavel_estado').value || '';
                document.getElementById('cep').value = document.getElementById('responsavel_cep').value || '';
            }
        }
        
        // Submissão do formulário
        document.getElementById('formNovoAluno').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarAluno');
            const spinner = document.getElementById('spinnerSalvar');
            const alertaErro = document.getElementById('alertaErro');
            const alertaSucesso = document.getElementById('alertaSucesso');
            const dn = document.getElementById('data_nascimento').value;
            const hojeStr = new Date().toISOString().split('T')[0];
            if (dn && dn > hojeStr) {
                alertaErro.textContent = 'Data de nascimento não pode ser futura.';
                alertaErro.classList.remove('hidden');
                return;
            }
            
            // Verificar se há dados do responsável preenchidos
            const responsavelNome = document.getElementById('responsavel_nome').value.trim();
            const responsavelCpf = document.getElementById('responsavel_cpf').value.replace(/\D/g, '');
            const responsavelSenha = document.getElementById('responsavel_senha').value;
            const responsavelParentesco = document.getElementById('responsavel_parentesco').value;
            
            let criarResponsavel = false;
            if (responsavelNome && responsavelCpf && responsavelCpf.length === 11 && responsavelSenha && responsavelSenha.length >= 6 && responsavelParentesco) {
                criarResponsavel = true;
            } else if (responsavelNome || responsavelCpf || responsavelSenha || responsavelParentesco) {
                // Se algum campo foi preenchido mas não todos obrigatórios
                alert('Para cadastrar o responsável, é necessário preencher: Nome, CPF, Senha (mínimo 6 caracteres) e Parentesco.');
                return;
            }
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'cadastrar_aluno');
            formData.append('criar_responsavel', criarResponsavel ? '1' : '0');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = data.message || 'Aluno cadastrado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
                    // Resetar para primeira etapa
                    etapaAtual = 1;
                    atualizarNavegacaoEtapas();
                    
                    // Limpar formulário
                    this.reset();
                    document.getElementById('data_matricula').value = new Date().toISOString().split('T')[0];
                    gerarMatriculaAutomatica();
                    
                    // Recarregar lista de alunos após 1.5 segundos
                    setTimeout(() => {
                        fecharModalNovoAluno();
                        filtrarAlunos();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao cadastrar aluno. Por favor, tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        });
        
        // Fechar modal ao clicar fora
        document.getElementById('modalNovoAluno')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalNovoAluno();
            }
        });

        async function editarAluno(id) {
            try {
                // Buscar dados do aluno
                const response = await fetch('?acao=buscar_aluno&id=' + id);
                const data = await response.json();
                
                if (!data.success || !data.aluno) {
                    alert('Erro ao carregar dados do aluno: ' + (data.message || 'Aluno não encontrado'));
                    return;
                }
                
                const aluno = data.aluno;
                
                // Preencher formulário
                document.getElementById('editar_aluno_id').value = aluno.id;
                document.getElementById('editar_nome').value = aluno.nome || '';
                document.getElementById('editar_cpf').value = aluno.cpf_formatado || aluno.cpf || '';
                document.getElementById('editar_data_nascimento').value = aluno.data_nascimento || '';
                document.getElementById('editar_sexo').value = aluno.sexo || '';
                document.getElementById('editar_email').value = aluno.email || '';
                document.getElementById('editar_telefone').value = aluno.telefone_formatado || aluno.telefone || '';
                document.getElementById('editar_nome_social').value = aluno.nome_social || '';
                document.getElementById('editar_raca').value = aluno.raca || '';
                document.getElementById('editar_matricula').value = aluno.matricula || '';
                // Formatar NIS para exibição se existir
                if (aluno.nis) {
                    const nisValue = aluno.nis.replace(/\D/g, '');
                    if (nisValue.length === 11) {
                        document.getElementById('editar_nis').value = nisValue.slice(0, 3) + '.' + nisValue.slice(3, 8) + '.' + nisValue.slice(8, 10) + '-' + nisValue.slice(10);
                    } else {
                        document.getElementById('editar_nis').value = aluno.nis || '';
                    }
                } else {
                    document.getElementById('editar_nis').value = '';
                }
                document.getElementById('editar_escola_id').value = aluno.escola_id || '';
                document.getElementById('editar_data_matricula').value = aluno.data_matricula || '';
                document.getElementById('editar_situacao').value = aluno.situacao || 'MATRICULADO';
                document.getElementById('editar_ativo').value = aluno.ativo !== undefined ? aluno.ativo : 1;
                
                // PCD e CIDs
                const isPCD = aluno.is_pcd == 1 || aluno.is_pcd === '1' || aluno.is_pcd === true;
                document.getElementById('editar_is_pcd').checked = isPCD;
                toggleCamposPCDEditar();
                
                // Limpar CIDs existentes
                document.getElementById('lista-cids-editar').innerHTML = '';
                contadorCIDEditar = 0;
                
                // Carregar CIDs se existirem
                if (isPCD && aluno.cids && Array.isArray(aluno.cids) && aluno.cids.length > 0) {
                    aluno.cids.forEach(function(cid) {
                        adicionarCampoCIDEditar(cid.cid, cid.descricao);
                    });
                }
                
                // Transporte
                const precisaTransporte = aluno.precisa_transporte == 1 || aluno.precisa_transporte === '1';
                document.getElementById('editar_precisa_transporte').checked = precisaTransporte;
                document.getElementById('editar_distrito_transporte').value = aluno.distrito_transporte || '';
                document.getElementById('editar_localidade_transporte').value = aluno.localidade_transporte || '';
                
                // Se tiver distrito, carregar localidades
                if (aluno.distrito_transporte) {
                    distritoSelecionadoEditar = aluno.distrito_transporte;
                    carregarLocalidadesEditar(aluno.distrito_transporte);
                }
                toggleDistritoTransporteEditar();
                
                // Abrir modal
                const modal = document.getElementById('modalEditarAluno');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    // Limpar alertas
                    document.getElementById('alertaErroEditar').classList.add('hidden');
                    document.getElementById('alertaSucessoEditar').classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar aluno:', error);
                alert('Erro ao carregar dados do aluno. Por favor, tente novamente.');
            }
        }
        
        function fecharModalEditarAluno() {
            const modal = document.getElementById('modalEditarAluno');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        // Submissão do formulário de edição
        document.getElementById('formEditarAluno').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarEdicao');
            const spinner = document.getElementById('spinnerSalvarEdicao');
            const alertaErro = document.getElementById('alertaErroEditar');
            const alertaSucesso = document.getElementById('alertaSucessoEditar');
            const dnEditar = document.getElementById('editar_data_nascimento').value;
            const hojeStrEditar = new Date().toISOString().split('T')[0];
            if (dnEditar && dnEditar > hojeStrEditar) {
                alertaErro.textContent = 'Data de nascimento não pode ser futura.';
                alertaErro.classList.remove('hidden');
                return;
            }
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'editar_aluno');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = 'Aluno atualizado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
                    // Recarregar lista de alunos após 1.5 segundos
                    setTimeout(() => {
                        fecharModalEditarAluno();
                        filtrarAlunos();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao atualizar aluno. Por favor, tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        });
        
        // Fechar modal de edição ao clicar fora
        document.getElementById('modalEditarAluno')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalEditarAluno();
            }
        });

        async function excluirAluno(id) {
            // Buscar nome do aluno para exibir na confirmação
            try {
                const response = await fetch('?acao=buscar_aluno&id=' + id);
                const data = await response.json();
                const nomeAluno = data.success && data.aluno ? data.aluno.nome : 'este aluno';
                
                // Modal de confirmação customizado
                if (confirm(`Tem certeza que deseja excluir o aluno "${nomeAluno}"?\n\nEsta ação não pode ser desfeita. O aluno será marcado como inativo no sistema.`)) {
                    // Mostrar loading
                    const btnExcluir = event.target;
                    const originalText = btnExcluir.textContent;
                    btnExcluir.disabled = true;
                    btnExcluir.textContent = 'Excluindo...';
                    
                    try {
                        const formData = new FormData();
                        formData.append('acao', 'excluir_aluno');
                        formData.append('aluno_id', id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert('Aluno excluído com sucesso!');
                            // Recarregar lista
                            filtrarAlunos();
                        } else {
                            alert('Erro ao excluir aluno: ' + (data.message || 'Erro desconhecido'));
                        }
                    } catch (error) {
                        console.error('Erro ao excluir aluno:', error);
                        alert('Erro ao processar requisição. Por favor, tente novamente.');
                    } finally {
                        btnExcluir.disabled = false;
                        btnExcluir.textContent = originalText;
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar dados do aluno:', error);
                // Se não conseguir buscar o nome, usar confirmação simples
                if (confirm('Tem certeza que deseja excluir este aluno?\n\nEsta ação não pode ser desfeita.')) {
                    const formData = new FormData();
                    formData.append('acao', 'excluir_aluno');
                    formData.append('aluno_id', id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Aluno excluído com sucesso!');
                            filtrarAlunos();
                        } else {
                            alert('Erro ao excluir aluno: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao processar requisição. Por favor, tente novamente.');
                    });
                }
            }
        }

        // Lista de distritos de Maranguape
        const distritosMaranguape = [
            'Amanari', 'Antônio Marques', 'Cachoeira', 'Itapebussu', 'Jubaia',
            'Ladeira Grande', 'Lages', 'Lagoa do Juvenal', 'Manoel Guedes',
            'Sede', 'Papara', 'Penedo', 'Sapupara', 'São João do Amanari',
            'Tanques', 'Umarizeiras', 'Vertentes do Lagedo'
        ];
        
        // Inicializar autocomplete para distrito de transporte (cadastro)
        function initAutocompleteDistrito(inputId, dropdownId) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            if (!input || !dropdown) return;
            
            let selectedIndex = -1;
            let filteredDistritos = [];
            
            input.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                selectedIndex = -1;
                
                if (query.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }
                
                // Filtrar distritos que contêm o texto digitado
                filteredDistritos = distritosMaranguape.filter(distrito => 
                    distrito.toLowerCase().includes(query)
                );
                
                if (filteredDistritos.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }
                
                // Renderizar dropdown
                renderDropdown();
                dropdown.classList.add('show');
            });
            
            // Navegação com teclado
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
                    if (selectedIndex >= 0 && filteredDistritos[selectedIndex]) {
                        selecionarDistritoAutocomplete(inputId, filteredDistritos[selectedIndex]);
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });
            
            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            
            function renderDropdown() {
                dropdown.innerHTML = filteredDistritos.map((distrito, index) => `
                    <div class="autocomplete-item ${index === selectedIndex ? 'selected' : ''}" 
                         data-index="${index}" 
                         onclick="selecionarDistritoAutocomplete('${inputId}', '${distrito}')">
                        <div class="distrito-nome">${distrito}</div>
                    </div>
                `).join('');
            }
            
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
        }
        
        window.selecionarDistritoAutocomplete = function(inputId, distrito) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(inputId === 'distrito_transporte' ? 'autocomplete-dropdown-transporte' : 'autocomplete-dropdown-transporte-editar');
            if (input) {
                input.value = distrito;
                if (dropdown) dropdown.classList.remove('show');
            }
        };
        
        function toggleDistritoTransporte() {
            const precisaTransporte = document.getElementById('precisa_transporte').checked;
            const container = document.getElementById('container-distrito-transporte');
            const inputDistrito = document.getElementById('distrito_transporte');
            const selectLocalidade = document.getElementById('localidade_transporte');
            
            if (precisaTransporte) {
                container.classList.remove('hidden');
                inputDistrito.required = true;
                selectLocalidade.required = true;
            } else {
                container.classList.add('hidden');
                inputDistrito.required = false;
                inputDistrito.value = '';
                selectLocalidade.value = '';
            }
        }
        
        function toggleDistritoTransporteEditar() {
            const precisaTransporte = document.getElementById('editar_precisa_transporte').checked;
            const container = document.getElementById('container-distrito-transporte-editar');
            const inputDistrito = document.getElementById('editar_distrito_transporte');
            const selectLocalidade = document.getElementById('editar_localidade_transporte');
            
            if (precisaTransporte) {
                container.classList.remove('hidden');
                inputDistrito.required = true;
                // Inicializar autocomplete quando aparecer
                setTimeout(() => initAutocompleteDistrito('editar_distrito_transporte', 'autocomplete-dropdown-transporte-editar'), 100);
            } else {
                container.classList.add('hidden');
                inputDistrito.required = false;
                inputDistrito.value = '';
                if (selectLocalidade) selectLocalidade.value = '';
            }
        }
        
        function filtrarAlunos() {
            const busca = document.getElementById('filtro-busca').value;
            const escolaId = document.getElementById('filtro-escola').value;
            
            let url = '?acao=listar_alunos';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (escolaId) url += '&escola_id=' + escolaId;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-alunos');
                        tbody.innerHTML = '';
                        
                        if (data.alunos.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhum aluno encontrado.</td></tr>';
                            return;
                        }
                        
                        data.alunos.forEach(aluno => {
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${aluno.nome}</td>
                                    <td class="py-3 px-4">${aluno.matricula || '-'}</td>
                                    <td class="py-3 px-4">${aluno.cpf || '-'}</td>
                                    <td class="py-3 px-4">${aluno.escola_nome || '-'}</td>
                                    <td class="py-3 px-4">${aluno.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarAluno(${aluno.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirAluno(${aluno.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
                    console.error('Erro ao filtrar alunos:', error);
                });
        }
        
        // Variáveis globais para transporte
        let distritoSelecionado = null;
        let localidadesDisponiveis = [];
        let distritoSelecionadoEditar = null;
        let localidadesDisponiveisEditar = [];
        
        // Funções para carregar localidades (cadastro)
        function carregarLocalidades(distrito) {
            if (!distrito) return;
            
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
        
        function selecionarLocalidade(localidade) {
            document.getElementById('localidade_transporte').value = localidade;
            document.getElementById('autocomplete-dropdown-localidade').classList.remove('show');
        }
        
        // Funções para carregar localidades (edição)
        function carregarLocalidadesEditar(distrito) {
            if (!distrito) return;
            
            fetch(`?acao=buscar_localidades&distrito=${encodeURIComponent(distrito)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.localidades && data.localidades.length > 0) {
                        localidadesDisponiveisEditar = data.localidades;
                        distritoSelecionadoEditar = distrito;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar localidades:', error);
                });
        }
        
        function buscarLocalidadesEditar(query) {
            const input = document.getElementById('editar_localidade_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-localidade-editar');
            if (!input || !dropdown || !distritoSelecionadoEditar) return;
            
            const queryLower = query.trim().toLowerCase();
            
            if (queryLower.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            const filteredLocalidades = localidadesDisponiveisEditar.filter(loc => 
                loc.localidade.toLowerCase().includes(queryLower)
            );
            
            if (filteredLocalidades.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            dropdown.innerHTML = filteredLocalidades.map((loc) => `
                <div class="autocomplete-item" onclick="selecionarLocalidadeEditar('${loc.localidade.replace(/'/g, "\\'")}')">
                    <div>${loc.localidade}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        function selecionarLocalidadeEditar(localidade) {
            document.getElementById('editar_localidade_transporte').value = localidade;
            document.getElementById('autocomplete-dropdown-localidade-editar').classList.remove('show');
        }
        
        // Função para buscar distritos (cadastro)
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
            carregarLocalidades(distrito);
        }
        
        // Função para buscar distritos (edição)
        function buscarDistritosEditar(query) {
            const input = document.getElementById('editar_distrito_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-transporte-editar');
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
                <div class="autocomplete-item" onclick="selecionarDistritoEditar('${distrito}')">
                    <div>${distrito}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        function selecionarDistritoEditar(distrito) {
            document.getElementById('editar_distrito_transporte').value = distrito;
            document.getElementById('autocomplete-dropdown-transporte-editar').classList.remove('show');
            distritoSelecionadoEditar = distrito;
            carregarLocalidadesEditar(distrito);
        }
    </script>
</body>
</html>

