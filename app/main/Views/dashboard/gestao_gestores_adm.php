<?php
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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'cadastrar_gestor') {
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
            
            // Validar cargo (obrigatório)
            if (empty(trim($_POST['cargo'] ?? ''))) {
                throw new Exception('Cargo é obrigatório.');
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
            $nomeSocial = !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null;
            
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, 
                         nome_social, endereco, numero, complemento, bairro, cidade, estado, cep, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone,
                         :nome_social, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, 'GESTOR', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $nome);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimento);
            $stmtPessoa->bindParam(':sexo', $sexo);
            $stmtPessoa->bindParam(':email', $email);
            $stmtPessoa->bindParam(':telefone', $telefoneVal);
            $stmtPessoa->bindParam(':nome_social', $nomeSocial);
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
            error_log("DEBUG CADASTRO GESTOR - Pessoa criada, ID: " . $pessoaId);
            
            // 2. Criar gestor
            $cargo = trim($_POST['cargo'] ?? '');
            
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
            $observacoes = !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
            
            // Verificar se a tabela gestor tem campo especializacao
            try {
                $stmtCheck = $conn->query("SHOW COLUMNS FROM gestor LIKE 'especializacao'");
                $temEspecializacao = $stmtCheck->rowCount() > 0;
            } catch (Exception $e) {
                $temEspecializacao = false;
            }
            
            if ($temEspecializacao) {
                $sqlGestor = "INSERT INTO gestor (pessoa_id, cargo, formacao, especializacao, registro_profissional, observacoes, ativo, criado_por)
                             VALUES (:pessoa_id, :cargo, :formacao, :especializacao, :registro_profissional, :observacoes, 1, :criado_por)";
                $stmtGestor = $conn->prepare($sqlGestor);
                $stmtGestor->bindParam(':pessoa_id', $pessoaId);
                $stmtGestor->bindParam(':cargo', $cargo);
                $stmtGestor->bindParam(':formacao', $formacao);
                $stmtGestor->bindParam(':especializacao', $especializacao);
                $stmtGestor->bindParam(':registro_profissional', $registroProfissional);
                $stmtGestor->bindParam(':observacoes', $observacoes);
                $stmtGestor->bindParam(':criado_por', $criadoPor);
            } else {
                $sqlGestor = "INSERT INTO gestor (pessoa_id, cargo, formacao, registro_profissional, observacoes, ativo, criado_por)
                             VALUES (:pessoa_id, :cargo, :formacao, :registro_profissional, :observacoes, 1, :criado_por)";
                $stmtGestor = $conn->prepare($sqlGestor);
                $stmtGestor->bindParam(':pessoa_id', $pessoaId);
                $stmtGestor->bindParam(':cargo', $cargo);
                $stmtGestor->bindParam(':formacao', $formacao);
                $stmtGestor->bindParam(':registro_profissional', $registroProfissional);
                $stmtGestor->bindParam(':observacoes', $observacoes);
                $stmtGestor->bindParam(':criado_por', $criadoPor);
            }
            $stmtGestor->execute();
            
            // IMPORTANTE: Buscar o ID do gestor diretamente pela pessoa_id, pois lastInsertId() pode retornar ID de outra tabela
            // após inserções subsequentes
            // Usar ORDER BY id DESC para pegar o mais recente (caso existam múltiplos gestores para a mesma pessoa)
            $sqlBuscarGestorId = "SELECT id, pessoa_id FROM gestor WHERE pessoa_id = :pessoa_id ORDER BY id DESC LIMIT 1";
            $stmtBuscarGestorId = $conn->prepare($sqlBuscarGestorId);
            $stmtBuscarGestorId->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmtBuscarGestorId->execute();
            $gestorEncontrado = $stmtBuscarGestorId->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestorEncontrado || !isset($gestorEncontrado['id'])) {
                error_log("DEBUG CADASTRO GESTOR - ERRO: Gestor não encontrado após inserção! pessoa_id: " . $pessoaId);
                throw new Exception('Erro ao criar gestor: não foi possível recuperar o ID do gestor criado.');
            }
            
            $gestorId = (int)$gestorEncontrado['id'];
            
            // VALIDAÇÃO IMEDIATA: Garantir que o ID não seja o da pessoa
            if ($gestorId == $pessoaId) {
                error_log("DEBUG CADASTRO GESTOR - ERRO: ID do gestor (" . $gestorId . ") é igual ao ID da pessoa (" . $pessoaId . ")!");
                error_log("DEBUG CADASTRO GESTOR - Buscando todos os gestores para esta pessoa...");
                
                // Buscar todos os gestores para esta pessoa
                $sqlTodosGestores = "SELECT id, pessoa_id FROM gestor WHERE pessoa_id = :pessoa_id ORDER BY id DESC";
                $stmtTodosGestores = $conn->prepare($sqlTodosGestores);
                $stmtTodosGestores->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmtTodosGestores->execute();
                $todosGestores = $stmtTodosGestores->fetchAll(PDO::FETCH_ASSOC);
                error_log("DEBUG CADASTRO GESTOR - Todos os gestores encontrados: " . json_encode($todosGestores));
                
                // Procurar um gestor com ID diferente da pessoa
                $gestorIdEncontrado = false;
                foreach ($todosGestores as $g) {
                    if ((int)$g['id'] != $pessoaId) {
                        $gestorId = (int)$g['id'];
                        $gestorIdEncontrado = true;
                        error_log("DEBUG CADASTRO GESTOR - ID CORRIGIDO (antes do commit): " . $gestorId);
                        break;
                    }
                }
                
                if (!$gestorIdEncontrado) {
                    error_log("DEBUG CADASTRO GESTOR - ERRO: Não foi possível encontrar um ID de gestor válido antes do commit!");
                    throw new Exception('Erro ao criar gestor: ID incorreto. Por favor, tente novamente.');
                }
            }
            
            error_log("DEBUG CADASTRO GESTOR - Gestor criado com sucesso! ID: " . $gestorId . ", pessoa_id: " . $pessoaId);
            
            // Verificar imediatamente se o gestor foi criado corretamente
            $sqlVerificarImediato = "SELECT id, pessoa_id, ativo FROM gestor WHERE id = :id";
            $stmtVerificarImediato = $conn->prepare($sqlVerificarImediato);
            $stmtVerificarImediato->bindParam(':id', $gestorId, PDO::PARAM_INT);
            $stmtVerificarImediato->execute();
            $gestorVerificadoImediato = $stmtVerificarImediato->fetch(PDO::FETCH_ASSOC);
            error_log("DEBUG CADASTRO GESTOR - Verificação imediata (antes do commit): " . json_encode($gestorVerificadoImediato));
            
            if (!$gestorVerificadoImediato) {
                error_log("DEBUG CADASTRO GESTOR - ERRO CRÍTICO: Gestor não encontrado após verificação! ID: " . $gestorId);
                throw new Exception('Erro ao criar gestor: gestor não encontrado após criação.');
            }
            
            // Validação adicional: garantir que pessoa_id corresponde
            if ($gestorVerificadoImediato['pessoa_id'] != $pessoaId) {
                error_log("DEBUG CADASTRO GESTOR - ERRO: pessoa_id do gestor (" . $gestorVerificadoImediato['pessoa_id'] . ") não corresponde à pessoa criada (" . $pessoaId . ")!");
                throw new Exception('Erro ao criar gestor: inconsistência nos dados.');
            }
            
            // 3. Criar usuário
            $sqlUsuario = "INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo)
                          VALUES (:pessoa_id, :username, :senha_hash, 'GESTAO', 1)";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $pessoaId);
            $stmtUsuario->bindParam(':username', $username);
            $stmtUsuario->bindParam(':senha_hash', $senhaHash);
            $stmtUsuario->execute();
            
            // 4. Lotar gestor na escola (se informado)
            if (!empty($_POST['escola_id'])) {
                $escolaId = $_POST['escola_id'];
                $responsavel = !empty($_POST['responsavel']) ? (int)$_POST['responsavel'] : 1;
                $tipoLotacao = !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null;
                $observacaoLotacao = !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null;
                
                $sqlLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel, tipo, observacoes, criado_por)
                              VALUES (:gestor_id, :escola_id, CURDATE(), :responsavel, :tipo, :observacoes, :criado_por)";
                $stmtLotacao = $conn->prepare($sqlLotacao);
                $stmtLotacao->bindParam(':gestor_id', $gestorId);
                $stmtLotacao->bindParam(':escola_id', $escolaId);
                $stmtLotacao->bindParam(':responsavel', $responsavel);
                $stmtLotacao->bindParam(':tipo', $tipoLotacao);
                $stmtLotacao->bindParam(':observacoes', $observacaoLotacao);
                $stmtLotacao->bindParam(':criado_por', $criadoPor);
                $stmtLotacao->execute();
            }
            
            $conn->commit();
            
            error_log("DEBUG CADASTRO GESTOR - Commit realizado. Buscando ID do gestor criado...");
            
            // CRÍTICO: Após o commit, buscar o ID do gestor NOVAMENTE pela pessoa_id
            // Isso garante que temos o ID correto, mesmo que algo tenha dado errado antes
            $sqlBuscarGestorFinal = "SELECT id, pessoa_id, ativo FROM gestor WHERE pessoa_id = :pessoa_id ORDER BY id DESC LIMIT 1";
            $stmtBuscarGestorFinal = $conn->prepare($sqlBuscarGestorFinal);
            $stmtBuscarGestorFinal->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmtBuscarGestorFinal->execute();
            $gestorFinal = $stmtBuscarGestorFinal->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestorFinal || !isset($gestorFinal['id'])) {
                error_log("DEBUG CADASTRO GESTOR - ERRO CRÍTICO: Gestor não encontrado após commit! pessoa_id: " . $pessoaId);
                throw new Exception('Erro ao criar gestor: não foi possível recuperar o ID do gestor criado.');
            }
            
            // USAR O ID BUSCADO APÓS O COMMIT - este é o ID correto e garantido
            $gestorId = (int)$gestorFinal['id'];
            
            error_log("DEBUG CADASTRO GESTOR - ID do gestor após commit: " . $gestorId . " (pessoa_id: " . $pessoaId . ")");
            
            // VALIDAÇÃO CRÍTICA: Garantir que o ID não seja o mesmo da pessoa
            if ($gestorId == $pessoaId) {
                error_log("DEBUG CADASTRO GESTOR - ERRO CRÍTICO: O ID do gestor (" . $gestorId . ") é igual ao ID da pessoa (" . $pessoaId . ")!");
                error_log("DEBUG CADASTRO GESTOR - Isso indica um erro grave no banco de dados ou na lógica!");
                
                // Tentar buscar todos os gestores para essa pessoa
                $sqlTodosGestores = "SELECT id, pessoa_id, ativo FROM gestor WHERE pessoa_id = :pessoa_id ORDER BY id DESC";
                $stmtTodosGestores = $conn->prepare($sqlTodosGestores);
                $stmtTodosGestores->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmtTodosGestores->execute();
                $todosGestores = $stmtTodosGestores->fetchAll(PDO::FETCH_ASSOC);
                error_log("DEBUG CADASTRO GESTOR - Todos os gestores para pessoa_id " . $pessoaId . ": " . json_encode($todosGestores));
                
                // Procurar um gestor com ID diferente da pessoa
                foreach ($todosGestores as $g) {
                    if ($g['id'] != $pessoaId) {
                        $gestorId = (int)$g['id'];
                        error_log("DEBUG CADASTRO GESTOR - ID CORRIGIDO: " . $gestorId . " (era " . $pessoaId . ")");
                        break;
                    }
                }
                
                // Se ainda for igual, lançar erro
                if ($gestorId == $pessoaId) {
                    error_log("DEBUG CADASTRO GESTOR - ERRO: Não foi possível encontrar um ID de gestor válido!");
                    throw new Exception('Erro ao criar gestor: ID incorreto retornado. Por favor, tente novamente.');
                }
            }
            
            // Verificar se o gestor realmente existe e está correto
            $sqlVerificarFinal = "SELECT id, pessoa_id FROM gestor WHERE id = :id";
            $stmtVerificarFinal = $conn->prepare($sqlVerificarFinal);
            $stmtVerificarFinal->bindParam(':id', $gestorId, PDO::PARAM_INT);
            $stmtVerificarFinal->execute();
            $verificacaoFinal = $stmtVerificarFinal->fetch(PDO::FETCH_ASSOC);
            
            if (!$verificacaoFinal) {
                error_log("DEBUG CADASTRO GESTOR - ERRO: Gestor com ID " . $gestorId . " não encontrado na verificação final!");
                throw new Exception('Erro ao criar gestor: gestor não encontrado após criação.');
            }
            
            if ($verificacaoFinal['pessoa_id'] != $pessoaId) {
                error_log("DEBUG CADASTRO GESTOR - ERRO: pessoa_id do gestor (" . $verificacaoFinal['pessoa_id'] . ") não corresponde à pessoa criada (" . $pessoaId . ")!");
                throw new Exception('Erro ao criar gestor: inconsistência nos dados.');
            }
            
            error_log("DEBUG CADASTRO GESTOR - Gestor criado com ID FINAL: " . $gestorId . " (pessoa_id: " . $pessoaId . ")");
            error_log("DEBUG CADASTRO GESTOR - Verificação final: " . json_encode($verificacaoFinal));
            
            // ÚLTIMA VERIFICAÇÃO: Buscar o gestor UMA ÚLTIMA VEZ para garantir que temos o ID correto
            $sqlUltimaVerificacao = "SELECT id, pessoa_id FROM gestor WHERE pessoa_id = :pessoa_id AND id != :pessoa_id ORDER BY id DESC LIMIT 1";
            $stmtUltimaVerificacao = $conn->prepare($sqlUltimaVerificacao);
            $stmtUltimaVerificacao->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmtUltimaVerificacao->execute();
            $ultimaVerificacao = $stmtUltimaVerificacao->fetch(PDO::FETCH_ASSOC);
            
            if ($ultimaVerificacao && $ultimaVerificacao['id'] != $pessoaId) {
                $gestorId = (int)$ultimaVerificacao['id'];
                error_log("DEBUG CADASTRO GESTOR - ID confirmado na última verificação: " . $gestorId);
            }
            
            // Garantir que estamos retornando o ID do GESTOR, não da PESSOA
            $idRetornado = (int)$gestorId;
            
            // Registrar log de criação de gestor
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
            require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logCriarGestor($usuarioLogadoId, $gestorId, $nome);
            
            // Validação final ABSOLUTA: garantir que o ID retornado não seja o da pessoa
            if ($idRetornado == $pessoaId) {
                error_log("DEBUG CADASTRO GESTOR - ERRO FINAL CRÍTICO: Tentando retornar ID da pessoa (" . $pessoaId . ") em vez do gestor!");
                error_log("DEBUG CADASTRO GESTOR - Buscando TODOS os gestores para esta pessoa...");
                
                // Buscar TODOS os gestores para esta pessoa
                $sqlTodosGestoresFinal = "SELECT id, pessoa_id FROM gestor WHERE pessoa_id = :pessoa_id ORDER BY id DESC";
                $stmtTodosGestoresFinal = $conn->prepare($sqlTodosGestoresFinal);
                $stmtTodosGestoresFinal->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmtTodosGestoresFinal->execute();
                $todosGestoresFinal = $stmtTodosGestoresFinal->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("DEBUG CADASTRO GESTOR - Todos os gestores encontrados: " . json_encode($todosGestoresFinal));
                
                // Procurar um gestor com ID diferente da pessoa
                $idEncontrado = false;
                foreach ($todosGestoresFinal as $g) {
                    if ((int)$g['id'] != $pessoaId) {
                        $idRetornado = (int)$g['id'];
                        $idEncontrado = true;
                        error_log("DEBUG CADASTRO GESTOR - ID CORRIGIDO NA ÚLTIMA HORA: " . $idRetornado);
                        break;
                    }
                }
                
                if (!$idEncontrado) {
                    error_log("DEBUG CADASTRO GESTOR - ERRO: Não foi possível encontrar um ID de gestor válido!");
                    throw new Exception('Erro ao criar gestor: ID incorreto. Por favor, tente novamente.');
                }
            }
            
            // Verificação final: garantir que o ID retornado existe na tabela gestor
            $sqlVerificarIdFinal = "SELECT id FROM gestor WHERE id = :id";
            $stmtVerificarIdFinal = $conn->prepare($sqlVerificarIdFinal);
            $stmtVerificarIdFinal->bindParam(':id', $idRetornado, PDO::PARAM_INT);
            $stmtVerificarIdFinal->execute();
            $verificarIdFinal = $stmtVerificarIdFinal->fetch(PDO::FETCH_ASSOC);
            
            if (!$verificarIdFinal) {
                error_log("DEBUG CADASTRO GESTOR - ERRO: ID " . $idRetornado . " não existe na tabela gestor!");
                throw new Exception('Erro ao criar gestor: ID inválido. Por favor, tente novamente.');
            }
            
            error_log("DEBUG CADASTRO GESTOR - ID que será retornado no JSON: " . $idRetornado . " (pessoa_id: " . $pessoaId . ")");
            error_log("DEBUG CADASTRO GESTOR - Validação: ID do gestor (" . $idRetornado . ") é diferente do ID da pessoa (" . $pessoaId . ") - OK!");
            
            echo json_encode([
                'success' => true,
                'message' => 'Gestor cadastrado com sucesso!',
                'id' => $idRetornado, // ID do GESTOR, não da pessoa
                'gestor_id' => $idRetornado, // Adicionar também como gestor_id para deixar claro
                'pessoa_id' => (int)$pessoaId, // Incluir pessoa_id para referência
                'username' => $username,
                'verificado' => true,
                'debug' => [
                    'gestor_id' => $idRetornado,
                    'pessoa_id' => (int)$pessoaId,
                    'encontrado' => true
                ]
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
    
    if ($_POST['acao'] === 'editar_gestor') {
        // Garantir que não há output antes do JSON
        ob_clean();
        
        try {
            error_log("DEBUG EDITAR GESTOR - Iniciando edição...");
            error_log("DEBUG EDITAR GESTOR - POST data: " . json_encode($_POST));
            
            $gestorId = $_POST['gestor_id'] ?? null;
            if (empty($gestorId)) {
                error_log("DEBUG EDITAR GESTOR - ERRO: ID do gestor não informado");
                throw new Exception('ID do gestor não informado.');
            }
            
            // Garantir que o ID seja um inteiro
            $gestorId = (int)$gestorId;
            if ($gestorId <= 0) {
                error_log("DEBUG EDITAR GESTOR - ERRO: ID do gestor inválido: " . $gestorId);
                throw new Exception('ID do gestor inválido.');
            }
            
            // Buscar gestor existente
            error_log("DEBUG EDITAR GESTOR - Buscando gestor com ID: " . $gestorId . " (tipo: " . gettype($gestorId) . ")");
            
            // Primeiro, buscar o gestor básico (sem restrição de ativo para permitir edição)
            // Tentar primeiro com JOIN
            $sqlGestor = "SELECT g.*, p.*, p.id as pessoa_id, p.endereco, p.numero, p.complemento, 
                         p.bairro, p.cidade, p.estado, p.cep
                         FROM gestor g 
                         INNER JOIN pessoa p ON g.pessoa_id = p.id 
                         WHERE g.id = :id";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':id', $gestorId, PDO::PARAM_INT);
            $stmtGestor->execute();
            $gestor = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG EDITAR GESTOR - ID recebido: " . var_export($gestorId, true));
            error_log("DEBUG EDITAR GESTOR - Tipo do ID: " . gettype($gestorId));
            error_log("DEBUG EDITAR GESTOR - Resultado da busca com JOIN: " . ($gestor ? 'ENCONTRADO' : 'NÃO ENCONTRADO'));
            
            // Se não encontrou com JOIN, tentar buscar o gestor diretamente
            if (!$gestor) {
                error_log("DEBUG EDITAR GESTOR - Tentando buscar gestor diretamente na tabela gestor");
                $sqlTeste = "SELECT * FROM gestor WHERE id = :id";
                $stmtTeste = $conn->prepare($sqlTeste);
                $stmtTeste->bindParam(':id', $gestorId, PDO::PARAM_INT);
                $stmtTeste->execute();
                $gestorTeste = $stmtTeste->fetch(PDO::FETCH_ASSOC);
                
                if ($gestorTeste) {
                    error_log("DEBUG EDITAR GESTOR - Gestor encontrado diretamente, pessoa_id: " . $gestorTeste['pessoa_id']);
                    // Buscar pessoa separadamente
                    $sqlPessoa = "SELECT * FROM pessoa WHERE id = :pessoa_id";
                    $stmtPessoa = $conn->prepare($sqlPessoa);
                    $stmtPessoa->bindParam(':pessoa_id', $gestorTeste['pessoa_id'], PDO::PARAM_INT);
                    $stmtPessoa->execute();
                    $pessoa = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                    
                    if ($pessoa) {
                        // Combinar dados
                        $gestor = array_merge($gestorTeste, $pessoa);
                        $gestor['pessoa_id'] = $pessoa['id'];
                        error_log("DEBUG EDITAR GESTOR - Gestor e pessoa combinados com sucesso");
                    } else {
                        error_log("DEBUG EDITAR GESTOR - Pessoa não encontrada para pessoa_id: " . $gestorTeste['pessoa_id']);
                        throw new Exception('Pessoa associada ao gestor não encontrada. Gestor ID: ' . $gestorId);
                    }
                } else {
                    // Verificar se existe algum gestor no banco
                    $sqlTodos = "SELECT g.id, g.pessoa_id, g.ativo, p.nome FROM gestor g INNER JOIN pessoa p ON g.pessoa_id = p.id ORDER BY g.id DESC LIMIT 10";
                    $stmtTodos = $conn->query($sqlTodos);
                    $todos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);
                    error_log("DEBUG EDITAR GESTOR - Últimos 10 gestores no banco: " . json_encode($todos));
                    
                    // Verificar se existe gestor com ID específico usando query direta
                    $sqlDireto = "SELECT COUNT(*) as total FROM gestor WHERE id = " . (int)$gestorId;
                    $stmtDireto = $conn->query($sqlDireto);
                    $resultadoDireto = $stmtDireto->fetch(PDO::FETCH_ASSOC);
                    error_log("DEBUG EDITAR GESTOR - Query direta COUNT para ID " . $gestorId . ": " . $resultadoDireto['total']);
                    
                    // Listar todos os IDs disponíveis (apenas IDs de gestor, não de outras tabelas)
                    $idsDisponiveis = array_column($todos, 'id');
                    error_log("DEBUG EDITAR GESTOR - IDs de gestores disponíveis no banco: " . implode(', ', $idsDisponiveis));
                    
                    // Verificar se o ID pode ser de outra tabela (pessoa, usuario, etc)
                    $sqlPessoa = "SELECT id FROM pessoa WHERE id = " . (int)$gestorId;
                    $stmtPessoa = $conn->query($sqlPessoa);
                    $pessoaEncontrada = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                    if ($pessoaEncontrada) {
                        error_log("DEBUG EDITAR GESTOR - ATENÇÃO: O ID " . $gestorId . " existe na tabela pessoa, mas não na tabela gestor!");
                        error_log("DEBUG EDITAR GESTOR - Buscando gestor pela pessoa_id...");
                        // Tentar buscar gestor pela pessoa_id
                        $sqlGestorPorPessoa = "SELECT g.*, p.*, p.id as pessoa_id, p.endereco, p.numero, p.complemento, 
                                               p.bairro, p.cidade, p.estado, p.cep
                                               FROM gestor g 
                                               INNER JOIN pessoa p ON g.pessoa_id = p.id 
                                               WHERE g.pessoa_id = :pessoa_id
                                               ORDER BY g.id DESC LIMIT 1";
                        $stmtGestorPorPessoa = $conn->prepare($sqlGestorPorPessoa);
                        $stmtGestorPorPessoa->bindParam(':pessoa_id', $gestorId, PDO::PARAM_INT);
                        $stmtGestorPorPessoa->execute();
                        $gestorPorPessoa = $stmtGestorPorPessoa->fetch(PDO::FETCH_ASSOC);
                        if ($gestorPorPessoa) {
                            error_log("DEBUG EDITAR GESTOR - Gestor encontrado pela pessoa_id! ID correto do gestor: " . $gestorPorPessoa['id']);
                            // Usar o gestor encontrado e corrigir o ID
                            $gestor = $gestorPorPessoa;
                            $gestorId = (int)$gestorPorPessoa['id']; // Corrigir o ID para o ID do gestor
                            error_log("DEBUG EDITAR GESTOR - ID corrigido de " . $_POST['gestor_id'] . " (pessoa) para " . $gestorId . " (gestor)");
                        } else {
                            error_log("DEBUG EDITAR GESTOR - Pessoa encontrada, mas não há gestor associado!");
                            throw new Exception('ID fornecido é de uma pessoa, mas não há gestor associado a esta pessoa.');
                        }
                    }
                    
                    // Tentar buscar com LIKE (caso seja problema de tipo)
                    $sqlLike = "SELECT id, pessoa_id, ativo FROM gestor WHERE id LIKE :id";
                    $stmtLike = $conn->prepare($sqlLike);
                    $idLike = '%' . $gestorId . '%';
                    $stmtLike->bindParam(':id', $idLike);
                    $stmtLike->execute();
                    $resultadoLike = $stmtLike->fetchAll(PDO::FETCH_ASSOC);
                    error_log("DEBUG EDITAR GESTOR - Busca com LIKE: " . json_encode($resultadoLike));
                    
                    error_log("DEBUG EDITAR GESTOR - Gestor não encontrado com ID: " . $gestorId . " (tipo: " . gettype($gestorId) . ")");
                    throw new Exception('Gestor não encontrado. ID buscado: ' . $gestorId . '. IDs disponíveis no banco: ' . implode(', ', $idsDisponiveis));
                }
            }
            
            error_log("DEBUG EDITAR GESTOR - Gestor encontrado: ID=" . $gestor['id'] . ", Pessoa_ID=" . ($gestor['pessoa_id'] ?? 'NULL') . ", Ativo=" . ($gestor['ativo'] ?? 'NULL'));
            
            // Buscar lotação ativa separadamente
            $sqlLotacao = "SELECT gl.escola_id, gl.responsavel, gl.tipo as tipo_lotacao, gl.observacoes as observacao_lotacao, gl.id as lotacao_id
                          FROM gestor_lotacao gl
                          WHERE gl.gestor_id = :gestor_id 
                          AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')
                          ORDER BY gl.responsavel DESC, gl.inicio DESC
                          LIMIT 1";
            $stmtLotacao = $conn->prepare($sqlLotacao);
            $stmtLotacao->bindParam(':gestor_id', $gestorId);
            $stmtLotacao->execute();
            $lotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG EDITAR GESTOR - Lotação encontrada: " . json_encode($lotacao));
            
            // Adicionar dados da lotação ao array do gestor
            if ($lotacao) {
                $gestor['escola_id'] = $lotacao['escola_id'];
                $gestor['responsavel'] = $lotacao['responsavel'];
                $gestor['tipo_lotacao'] = $lotacao['tipo_lotacao'];
                $gestor['observacao_lotacao'] = $lotacao['observacao_lotacao'];
                $gestor['lotacao_id'] = $lotacao['lotacao_id'];
            } else {
                $gestor['escola_id'] = null;
                $gestor['responsavel'] = null;
                $gestor['tipo_lotacao'] = null;
                $gestor['observacao_lotacao'] = null;
                $gestor['lotacao_id'] = null;
            }
            
            // Preparar dados
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            
            // Validar CPF (se foi alterado)
            $cpfAtual = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            if (!empty($cpfAtual) && strlen($cpfAtual) !== 11) {
                throw new Exception('CPF inválido. Deve conter 11 dígitos.');
            }
            
            // Verificar se CPF já existe em outro gestor
            if (!empty($cpfAtual) && $cpfAtual !== $gestor['cpf']) {
                $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id";
                $stmtVerificar = $conn->prepare($sqlVerificarCPF);
                $stmtVerificar->bindParam(':cpf', $cpfAtual);
                $stmtVerificar->bindParam(':pessoa_id', $gestor['pessoa_id']);
                $stmtVerificar->execute();
                if ($stmtVerificar->fetch()) {
                    throw new Exception('CPF já cadastrado para outro gestor.');
                }
            }
            
            // Validar cargo (obrigatório)
            if (empty(trim($_POST['cargo'] ?? ''))) {
                throw new Exception('Cargo é obrigatório.');
            }
            
            $conn->beginTransaction();
            
            // Validar data de nascimento (não pode ser futura)
            if (!empty($_POST['data_nascimento'])) {
                $dataNasc = new DateTime($_POST['data_nascimento']);
                $hoje = new DateTime();
                if ($dataNasc > $hoje) {
                    throw new Exception('Data de nascimento não pode ser futura.');
                }
            }
            
            // Verificar se gestor foi encontrado
            if (!isset($gestor) || empty($gestor)) {
                error_log("DEBUG EDITAR GESTOR - ERRO: Gestor não encontrado antes de atualizar!");
                throw new Exception('Gestor não encontrado. Por favor, recarregue a página e tente novamente.');
            }
            
            if (!isset($gestor['pessoa_id']) || empty($gestor['pessoa_id'])) {
                error_log("DEBUG EDITAR GESTOR - ERRO: pessoa_id não encontrado no gestor!");
                error_log("DEBUG EDITAR GESTOR - Dados do gestor: " . json_encode($gestor));
                throw new Exception('Dados do gestor incompletos. Por favor, recarregue a página e tente novamente.');
            }
            
            // 1. Atualizar pessoa
            $nomeUpdate = trim($_POST['nome'] ?? '');
            $dataNascimentoUpdate = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
            $sexoUpdate = !empty($_POST['sexo']) ? $_POST['sexo'] : null;
            $emailUpdate = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $telefoneUpdate = !empty($telefone) ? $telefone : null;
            $pessoaId = (int)$gestor['pessoa_id'];
            
            error_log("DEBUG EDITAR GESTOR - pessoa_id obtido: " . $pessoaId);
            
            // Preparar dados de endereço
            $endereco = !empty($_POST['endereco']) ? trim($_POST['endereco']) : null;
            $numero = !empty($_POST['numero']) ? trim($_POST['numero']) : null;
            $complemento = !empty($_POST['complemento']) ? trim($_POST['complemento']) : null;
            $bairro = !empty($_POST['bairro']) ? trim($_POST['bairro']) : null;
            $cidade = !empty($_POST['cidade']) ? trim($_POST['cidade']) : null;
            $estado = 'CE'; // Sempre Ceará (Maranguape/CE)
            $cep = !empty($_POST['cep']) ? preg_replace('/[^0-9]/', '', $_POST['cep']) : null;
            $nomeSocial = !empty($_POST['nome_social']) ? trim($_POST['nome_social']) : null;
            $cor = !empty($_POST['cor']) ? $_POST['cor'] : null;
            
            // Verificar se os campos nome_social e cor existem na tabela
            try {
                $stmtCheck = $conn->query("SHOW COLUMNS FROM pessoa LIKE 'nome_social'");
                $temNomeSocial = $stmtCheck->rowCount() > 0;
                
                $stmtCheck = $conn->query("SHOW COLUMNS FROM pessoa LIKE 'cor'");
                $temCor = $stmtCheck->rowCount() > 0;
            } catch (Exception $e) {
                $temNomeSocial = false;
                $temCor = false;
            }
            
            $camposExtras = '';
            if ($temNomeSocial) {
                $camposExtras .= ', nome_social = :nome_social';
            }
            if ($temCor) {
                $camposExtras .= ', cor = :cor';
            }
            
            $sqlPessoa = "UPDATE pessoa SET nome = :nome, data_nascimento = :data_nascimento, 
                          sexo = :sexo, email = :email, telefone = :telefone,
                          endereco = :endereco, numero = :numero, complemento = :complemento,
                          bairro = :bairro, cidade = :cidade, estado = :estado, cep = :cep{$camposExtras}
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
            if ($temNomeSocial) {
                $stmtPessoa->bindParam(':nome_social', $nomeSocial);
            }
            if ($temCor) {
                $stmtPessoa->bindParam(':cor', $cor);
            }
            $stmtPessoa->bindParam(':pessoa_id', $pessoaId);
            $stmtPessoa->execute();
            
            // Atualizar CPF se foi alterado
            if (!empty($cpfAtual) && $cpfAtual !== $gestor['cpf']) {
                $sqlUpdateCPF = "UPDATE pessoa SET cpf = :cpf WHERE id = :pessoa_id";
                $stmtUpdateCPF = $conn->prepare($sqlUpdateCPF);
                $stmtUpdateCPF->bindParam(':cpf', $cpfAtual);
                $stmtUpdateCPF->bindParam(':pessoa_id', $pessoaId);
                $stmtUpdateCPF->execute();
            }
            
            // 2. Atualizar gestor
            $cargoUpdate = trim($_POST['cargo'] ?? '');
            
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
            $observacoesUpdate = !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
            $ativoUpdate = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
            
            // Verificar se a tabela gestor tem campo especializacao
            try {
                $stmtCheck = $conn->query("SHOW COLUMNS FROM gestor LIKE 'especializacao'");
                $temEspecializacao = $stmtCheck->rowCount() > 0;
            } catch (Exception $e) {
                $temEspecializacao = false;
            }
            
            if ($temEspecializacao) {
                $sqlGestorUpdate = "UPDATE gestor SET cargo = :cargo, formacao = :formacao, 
                                   especializacao = :especializacao, registro_profissional = :registro_profissional, 
                                   observacoes = :observacoes, ativo = :ativo
                                   WHERE id = :id";
                $stmtGestorUpdate = $conn->prepare($sqlGestorUpdate);
                $stmtGestorUpdate->bindParam(':cargo', $cargoUpdate);
                $stmtGestorUpdate->bindParam(':formacao', $formacaoUpdate);
                $stmtGestorUpdate->bindParam(':especializacao', $especializacaoUpdate);
                $stmtGestorUpdate->bindParam(':registro_profissional', $registroProfissionalUpdate);
                $stmtGestorUpdate->bindParam(':observacoes', $observacoesUpdate);
                $stmtGestorUpdate->bindParam(':ativo', $ativoUpdate);
                $stmtGestorUpdate->bindParam(':id', $gestorId);
            } else {
                $sqlGestorUpdate = "UPDATE gestor SET cargo = :cargo, formacao = :formacao, 
                                   registro_profissional = :registro_profissional, observacoes = :observacoes, ativo = :ativo
                                   WHERE id = :id";
                $stmtGestorUpdate = $conn->prepare($sqlGestorUpdate);
                $stmtGestorUpdate->bindParam(':cargo', $cargoUpdate);
                $stmtGestorUpdate->bindParam(':formacao', $formacaoUpdate);
                $stmtGestorUpdate->bindParam(':registro_profissional', $registroProfissionalUpdate);
                $stmtGestorUpdate->bindParam(':observacoes', $observacoesUpdate);
                $stmtGestorUpdate->bindParam(':ativo', $ativoUpdate);
                $stmtGestorUpdate->bindParam(':id', $gestorId);
            }
            $stmtGestorUpdate->execute();
            
            // 3. Atualizar senha se fornecida
            if (!empty($_POST['senha']) && $_POST['senha'] !== '123456') {
                $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                $sqlSenha = "UPDATE usuario SET senha_hash = :senha_hash WHERE pessoa_id = :pessoa_id";
                $stmtSenha = $conn->prepare($sqlSenha);
                $stmtSenha->bindParam(':senha_hash', $senhaHash);
                $stmtSenha->bindParam(':pessoa_id', $pessoaId);
                $stmtSenha->execute();
                error_log("DEBUG EDITAR GESTOR - Senha atualizada para pessoa_id: " . $pessoaId);
            }
            
            // 4. Atualizar lotação se informada
            $escolaIdPost = !empty($_POST['escola_id']) ? trim($_POST['escola_id']) : null;
            
            if (!empty($escolaIdPost) && $escolaIdPost !== '') {
                $escolaIdNova = (int)$escolaIdPost;
                error_log("DEBUG EDITAR GESTOR - Escola ID recebida no POST: " . $escolaIdPost . " (convertido: " . $escolaIdNova . ")");
                
                // Verificar se já existe lotação ativa (considerando NULL, vazio ou '0000-00-00' como ativa)
                $sqlLotacaoAtual = "SELECT id, escola_id FROM gestor_lotacao 
                                   WHERE gestor_id = :gestor_id 
                                   AND (fim IS NULL OR fim = '' OR fim = '0000-00-00')
                                   ORDER BY responsavel DESC, inicio DESC
                                   LIMIT 1";
                $stmtLotacaoAtual = $conn->prepare($sqlLotacaoAtual);
                $stmtLotacaoAtual->bindParam(':gestor_id', $gestorId);
                $stmtLotacaoAtual->execute();
                $lotacaoAtual = $stmtLotacaoAtual->fetch(PDO::FETCH_ASSOC);
                
                error_log("DEBUG EDITAR GESTOR - Lotação atual encontrada: " . json_encode($lotacaoAtual));
                
                if ($lotacaoAtual) {
                    // Finalizar lotação atual se a escola mudou
                    $escolaIdAtual = !empty($lotacaoAtual['escola_id']) ? (int)$lotacaoAtual['escola_id'] : null;
                    error_log("DEBUG EDITAR GESTOR - Comparação: Atual=$escolaIdAtual, Nova=$escolaIdNova");
                    
                    if ($escolaIdNova != $escolaIdAtual) {
                        error_log("DEBUG EDITAR GESTOR - Escola mudou, finalizando lotação atual (ID: " . $lotacaoAtual['id'] . ") e criando nova");
                        $sqlFinalizar = "UPDATE gestor_lotacao SET fim = CURDATE() WHERE id = :id";
                        $stmtFinalizar = $conn->prepare($sqlFinalizar);
                        $stmtFinalizar->bindParam(':id', $lotacaoAtual['id']);
                        $stmtFinalizar->execute();
                        
                        // Preparar variáveis para bindParam (não pode passar expressões diretamente)
                        $responsavel = !empty($_POST['responsavel']) ? (int)$_POST['responsavel'] : 1;
                        $tipoLotacao = !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null;
                        $observacaoLotacao = !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null;
                        $criadoPor = $_SESSION['usuario_id'];
                        
                        // Criar nova lotação
                        $sqlNovaLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel, tipo, observacoes, criado_por)
                                         VALUES (:gestor_id, :escola_id, CURDATE(), :responsavel, :tipo, :observacoes, :criado_por)";
                        $stmtNovaLotacao = $conn->prepare($sqlNovaLotacao);
                        $stmtNovaLotacao->bindParam(':gestor_id', $gestorId);
                        $stmtNovaLotacao->bindParam(':escola_id', $escolaIdNova);
                        $stmtNovaLotacao->bindParam(':responsavel', $responsavel);
                        $stmtNovaLotacao->bindParam(':tipo', $tipoLotacao);
                        $stmtNovaLotacao->bindParam(':observacoes', $observacaoLotacao);
                        $stmtNovaLotacao->bindParam(':criado_por', $criadoPor);
                        $stmtNovaLotacao->execute();
                        error_log("DEBUG EDITAR GESTOR - Nova lotação criada com sucesso para escola ID: " . $escolaIdNova);
                    } else {
                        // Preparar variáveis para bindParam (não pode passar expressões diretamente)
                        $responsavel = !empty($_POST['responsavel']) ? (int)$_POST['responsavel'] : 1;
                        $tipoLotacao = !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null;
                        $observacaoLotacao = !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null;
                        
                        // Atualizar lotação existente (escola não mudou, mas pode ter mudado outros campos)
                        error_log("DEBUG EDITAR GESTOR - Escola não mudou, atualizando lotação existente (ID: " . $lotacaoAtual['id'] . ")");
                        $sqlAtualizarLotacao = "UPDATE gestor_lotacao SET responsavel = :responsavel, tipo = :tipo, observacoes = :observacoes
                                               WHERE id = :id";
                        $stmtAtualizarLotacao = $conn->prepare($sqlAtualizarLotacao);
                        $stmtAtualizarLotacao->bindParam(':responsavel', $responsavel);
                        $stmtAtualizarLotacao->bindParam(':tipo', $tipoLotacao);
                        $stmtAtualizarLotacao->bindParam(':observacoes', $observacaoLotacao);
                        $stmtAtualizarLotacao->bindParam(':id', $lotacaoAtual['id']);
                        $stmtAtualizarLotacao->execute();
                        error_log("DEBUG EDITAR GESTOR - Lotação atualizada com sucesso");
                    }
                } else {
                    // Preparar variáveis para bindParam (não pode passar expressões diretamente)
                    $responsavel = !empty($_POST['responsavel']) ? (int)$_POST['responsavel'] : 1;
                    $tipoLotacao = !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null;
                    $observacaoLotacao = !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null;
                    $criadoPor = $_SESSION['usuario_id'];
                    
                    // Criar nova lotação (gestor não tinha lotação ativa)
                    error_log("DEBUG EDITAR GESTOR - Nenhuma lotação ativa encontrada, criando nova para escola ID: " . $escolaIdNova);
                    $sqlNovaLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel, tipo, observacoes, criado_por)
                                     VALUES (:gestor_id, :escola_id, CURDATE(), :responsavel, :tipo, :observacoes, :criado_por)";
                    $stmtNovaLotacao = $conn->prepare($sqlNovaLotacao);
                    $stmtNovaLotacao->bindParam(':gestor_id', $gestorId);
                    $stmtNovaLotacao->bindParam(':escola_id', $escolaIdNova);
                    $stmtNovaLotacao->bindParam(':responsavel', $responsavel);
                    $stmtNovaLotacao->bindParam(':tipo', $tipoLotacao);
                    $stmtNovaLotacao->bindParam(':observacoes', $observacaoLotacao);
                    $stmtNovaLotacao->bindParam(':criado_por', $criadoPor);
                    $stmtNovaLotacao->execute();
                    error_log("DEBUG EDITAR GESTOR - Nova lotação criada (sem lotação anterior)");
                }
            } else {
                error_log("DEBUG EDITAR GESTOR - escola_id não informado no POST ou vazio, mantendo lotação existente");
                // Se não foi informada escola_id, manter a lotação existente
            }
            
            $conn->commit();
            
            error_log("DEBUG EDITAR GESTOR - Commit realizado com sucesso");
            
            // Registrar log de edição de gestor
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
            require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logEditarGestor($usuarioLogadoId, $gestorId, $nomeUpdate);
            
            $response = [
                'success' => true,
                'message' => 'Gestor atualizado com sucesso!'
            ];
            
            error_log("DEBUG EDITAR GESTOR - Resposta JSON: " . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("DEBUG EDITAR GESTOR - ERRO: " . $e->getMessage());
            error_log("DEBUG EDITAR GESTOR - Stack trace: " . $e->getTraceAsString());
            
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            error_log("DEBUG EDITAR GESTOR - Resposta de erro JSON: " . json_encode($response));
            echo json_encode($response);
        } catch (Error $e) {
            error_log("DEBUG EDITAR GESTOR - ERRO FATAL: " . $e->getMessage());
            error_log("DEBUG EDITAR GESTOR - Stack trace: " . $e->getTraceAsString());
            
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            $response = [
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ];
            
            echo json_encode($response);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_gestor') {
        try {
            $gestorId = $_POST['gestor_id'] ?? null;
            if (empty($gestorId)) {
                throw new Exception('ID do gestor não informado.');
            }
            
            // Verificar se o gestor existe
            $sqlGestor = "SELECT g.*, p.nome FROM gestor g INNER JOIN pessoa p ON g.pessoa_id = p.id WHERE g.id = :id";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':id', $gestorId);
            $stmtGestor->execute();
            $gestor = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestor) {
                throw new Exception('Gestor não encontrado.');
            }
            
            // Verificar se o gestor tem lotações ativas
            $sqlLotacoes = "SELECT COUNT(*) as total FROM gestor_lotacao 
                           WHERE gestor_id = :gestor_id 
                           AND (fim IS NULL OR fim = '' OR fim = '0000-00-00')";
            $stmtLotacoes = $conn->prepare($sqlLotacoes);
            $stmtLotacoes->bindParam(':gestor_id', $gestorId);
            $stmtLotacoes->execute();
            $lotacoes = $stmtLotacoes->fetch(PDO::FETCH_ASSOC);
            
            if ($lotacoes['total'] > 0) {
                // Finalizar todas as lotações ativas
                $sqlFinalizarLotacoes = "UPDATE gestor_lotacao SET fim = CURDATE() 
                                        WHERE gestor_id = :gestor_id 
                                        AND (fim IS NULL OR fim = '' OR fim = '0000-00-00')";
                $stmtFinalizarLotacoes = $conn->prepare($sqlFinalizarLotacoes);
                $stmtFinalizarLotacoes->bindParam(':gestor_id', $gestorId);
                $stmtFinalizarLotacoes->execute();
            }
            
            // Soft delete
            $sqlExcluir = "UPDATE gestor SET ativo = 0 WHERE id = :id";
            $stmtExcluir = $conn->prepare($sqlExcluir);
            $stmtExcluir->bindParam(':id', $gestorId);
            $result = $stmtExcluir->execute();
            
            if ($result) {
                // Registrar log de exclusão/desativação de gestor
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
                require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
                $logger = SystemLogger::getInstance();
                $logger->logExcluirGestor($usuarioLogadoId, $gestorId, $gestor['nome']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Gestor excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir gestor.');
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
    
    if ($_GET['acao'] === 'buscar_gestor') {
        try {
            $gestorId = $_GET['id'] ?? null;
            if (empty($gestorId)) {
                echo json_encode(['success' => false, 'message' => 'ID do gestor não informado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Garantir que o ID seja um inteiro
            $gestorId = (int)$gestorId;
            if ($gestorId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID do gestor inválido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Buscar gestor básico primeiro (sem restrição de ativo para permitir visualização)
            // IMPORTANTE: g.id AS id garante que o id seja sempre do gestor, não da pessoa
            $sql = "SELECT g.id, g.pessoa_id, g.cargo, g.formacao, g.registro_profissional, 
                           g.observacoes, g.ativo, g.criado_por, g.criado_em,
                           p.id AS pessoa_id_explicit, p.cpf, p.nome, p.data_nascimento, p.sexo, p.email, 
                           p.telefone, p.nome_social, p.endereco, p.numero, p.complemento, 
                           p.bairro, p.cidade, p.estado, p.cep
                    FROM gestor g
                    INNER JOIN pessoa p ON g.pessoa_id = p.id
                    WHERE g.id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $gestorId, PDO::PARAM_INT);
            $stmt->execute();
            $gestor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Garantir que pessoa_id está definido corretamente
        if ($gestor && isset($gestor['pessoa_id_explicit'])) {
            $gestor['pessoa_id'] = $gestor['pessoa_id_explicit'];
            unset($gestor['pessoa_id_explicit']);
        }
        
        error_log("DEBUG BUSCAR GESTOR - ID: " . var_export($gestorId, true) . ", Encontrado: " . ($gestor ? 'SIM' : 'NÃO'));
        
        // Se não encontrou com JOIN, tentar buscar separadamente
        if (!$gestor) {
            error_log("DEBUG BUSCAR GESTOR - Tentando buscar gestor diretamente");
            $sqlGestor = "SELECT * FROM gestor WHERE id = :id";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':id', $gestorId, PDO::PARAM_INT);
            $stmtGestor->execute();
            $gestorTemp = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            if ($gestorTemp) {
                error_log("DEBUG BUSCAR GESTOR - Gestor encontrado diretamente, buscando pessoa");
                $sqlPessoa = "SELECT * FROM pessoa WHERE id = :pessoa_id";
                $stmtPessoa = $conn->prepare($sqlPessoa);
                $stmtPessoa->bindParam(':pessoa_id', $gestorTemp['pessoa_id'], PDO::PARAM_INT);
                $stmtPessoa->execute();
                $pessoa = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                
                if ($pessoa) {
                    $gestor = array_merge($gestorTemp, $pessoa);
                    $gestor['pessoa_id'] = $pessoa['id'];
                    error_log("DEBUG BUSCAR GESTOR - Gestor e pessoa combinados");
                }
            } else {
                // Se não encontrou gestor, verificar se o ID é de uma pessoa
                error_log("DEBUG BUSCAR GESTOR - Gestor não encontrado, verificando se ID é de pessoa");
                $sqlVerificarPessoa = "SELECT id FROM pessoa WHERE id = :id";
                $stmtVerificarPessoa = $conn->prepare($sqlVerificarPessoa);
                $stmtVerificarPessoa->bindParam(':id', $gestorId, PDO::PARAM_INT);
                $stmtVerificarPessoa->execute();
                $pessoaEncontrada = $stmtVerificarPessoa->fetch(PDO::FETCH_ASSOC);
                
                if ($pessoaEncontrada) {
                    error_log("DEBUG BUSCAR GESTOR - ID é de uma pessoa! Buscando gestor pela pessoa_id...");
                    // Buscar gestor pela pessoa_id
                    // IMPORTANTE: g.id AS id garante que o id seja sempre do gestor
                    $sqlGestorPorPessoa = "SELECT g.id, g.pessoa_id, g.cargo, g.formacao, g.registro_profissional, 
                                                 g.observacoes, g.ativo, g.criado_por, g.criado_em,
                                                 p.id AS pessoa_id_explicit, p.cpf, p.nome, p.data_nascimento, p.sexo, p.email, 
                                                 p.telefone, p.nome_social, p.endereco, p.numero, p.complemento, 
                                                 p.bairro, p.cidade, p.estado, p.cep
                                          FROM gestor g
                                          INNER JOIN pessoa p ON g.pessoa_id = p.id
                                          WHERE g.pessoa_id = :pessoa_id
                                          ORDER BY g.id DESC LIMIT 1";
                    $stmtGestorPorPessoa = $conn->prepare($sqlGestorPorPessoa);
                    $stmtGestorPorPessoa->bindParam(':pessoa_id', $gestorId, PDO::PARAM_INT);
                    $stmtGestorPorPessoa->execute();
                    $gestorPorPessoa = $stmtGestorPorPessoa->fetch(PDO::FETCH_ASSOC);
                    
                    if ($gestorPorPessoa) {
                        error_log("DEBUG BUSCAR GESTOR - Gestor encontrado pela pessoa_id! ID do gestor: " . $gestorPorPessoa['id']);
                        // Garantir que pessoa_id está definido corretamente
                        if (isset($gestorPorPessoa['pessoa_id_explicit'])) {
                            $gestorPorPessoa['pessoa_id'] = $gestorPorPessoa['pessoa_id_explicit'];
                            unset($gestorPorPessoa['pessoa_id_explicit']);
                        }
                        $gestor = $gestorPorPessoa;
                        // Atualizar gestorId para o ID correto do gestor
                        $gestorId = (int)$gestorPorPessoa['id'];
                        error_log("DEBUG BUSCAR GESTOR - ID corrigido para: " . $gestorId);
                    }
                }
            }
        }
        
        if ($gestor) {
            // Usar o ID do gestor (pode ter sido corrigido se o ID original era de pessoa)
            $gestorIdFinal = (int)$gestor['id'];
            error_log("DEBUG BUSCAR GESTOR - Usando gestor_id: " . $gestorIdFinal);
            
            // Buscar lotação ativa separadamente
            $sqlLotacao = "SELECT gl.escola_id, gl.responsavel, gl.tipo as tipo_lotacao, gl.observacoes as observacao_lotacao
                          FROM gestor_lotacao gl
                          WHERE gl.gestor_id = :gestor_id 
                          AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')
                          ORDER BY gl.responsavel DESC, gl.inicio DESC
                          LIMIT 1";
            $stmtLotacao = $conn->prepare($sqlLotacao);
            $stmtLotacao->bindParam(':gestor_id', $gestorIdFinal);
            $stmtLotacao->execute();
            $lotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
            
            // Adicionar dados da lotação
            if ($lotacao) {
                $gestor['escola_id'] = $lotacao['escola_id'];
                $gestor['responsavel'] = $lotacao['responsavel'];
                $gestor['tipo_lotacao'] = $lotacao['tipo_lotacao'];
                $gestor['observacao_lotacao'] = $lotacao['observacao_lotacao'];
            } else {
                $gestor['escola_id'] = null;
                $gestor['responsavel'] = null;
                $gestor['tipo_lotacao'] = null;
                $gestor['observacao_lotacao'] = null;
            }
            // Formatar CPF e telefone para exibição
            if (!empty($gestor['cpf']) && strlen($gestor['cpf']) === 11) {
                $gestor['cpf_formatado'] = substr($gestor['cpf'], 0, 3) . '.' . substr($gestor['cpf'], 3, 3) . '.' . substr($gestor['cpf'], 6, 3) . '-' . substr($gestor['cpf'], 9, 2);
            }
            if (!empty($gestor['telefone'])) {
                $tel = $gestor['telefone'];
                if (strlen($tel) === 11) {
                    $gestor['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                } elseif (strlen($tel) === 10) {
                    $gestor['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                }
            }
            
            // Buscar username do usuário
            $sqlUsuario = "SELECT username FROM usuario WHERE pessoa_id = :pessoa_id LIMIT 1";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $gestor['pessoa_id']);
            $stmtUsuario->execute();
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                $gestor['username'] = $usuario['username'];
            }
            
            echo json_encode(['success' => true, 'gestor' => $gestor], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gestor não encontrado'], JSON_UNESCAPED_UNICODE);
        }
        } catch (Exception $e) {
            error_log("Erro ao buscar gestor: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar gestor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Error $e) {
            error_log("Erro fatal ao buscar gestor: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Erro fatal ao buscar gestor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'listar_gestores') {
        $filtros = [];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        
        $sql = "SELECT g.*, p.nome, p.cpf, p.email, p.telefone
                FROM gestor g
                INNER JOIN pessoa p ON g.pessoa_id = p.id
                WHERE g.ativo = 1";
        
        $params = [];
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        $sql .= " ORDER BY p.nome ASC LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $gestores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar todas as escolas de cada gestor
        foreach ($gestores as &$gestor) {
            $sqlEscolas = "SELECT e.id, e.nome, gl.responsavel, gl.tipo
                          FROM gestor_lotacao gl
                          INNER JOIN escola e ON gl.escola_id = e.id
                          WHERE gl.gestor_id = :gestor_id 
                          AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')
                          AND e.ativo = 1
                          ORDER BY gl.responsavel DESC, gl.inicio DESC";
            $stmtEscolas = $conn->prepare($sqlEscolas);
            $stmtEscolas->bindParam(':gestor_id', $gestor['id'], PDO::PARAM_INT);
            $stmtEscolas->execute();
            $gestor['escolas'] = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($gestor);
        
        echo json_encode(['success' => true, 'gestores' => $gestores], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$sqlGestores = "SELECT g.*, p.nome, p.cpf, p.email, p.telefone
                FROM gestor g
                INNER JOIN pessoa p ON g.pessoa_id = p.id
                WHERE g.ativo = 1
                ORDER BY p.nome ASC
                LIMIT 50";
$stmtGestores = $conn->prepare($sqlGestores);
$stmtGestores->execute();
$gestores = $stmtGestores->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as escolas de cada gestor
foreach ($gestores as &$gestor) {
    $sqlEscolas = "SELECT e.id, e.nome, gl.responsavel, gl.tipo
                  FROM gestor_lotacao gl
                  INNER JOIN escola e ON gl.escola_id = e.id
                  WHERE gl.gestor_id = :gestor_id 
                  AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')
                  AND e.ativo = 1
                  ORDER BY gl.responsavel DESC, gl.inicio DESC";
    $stmtEscolas = $conn->prepare($sqlEscolas);
    $stmtEscolas->bindParam(':gestor_id', $gestor['id'], PDO::PARAM_INT);
    $stmtEscolas->execute();
    $gestor['escolas'] = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
}
unset($gestor);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Gestão de Gestores') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Gestores</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Gestores</h2>
                        <p class="text-gray-600 mt-1">Cadastre, edite e exclua gestores do sistema</p>
                    </div>
                    <button onclick="abrirModalNovoGestor()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Gestor</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome ou CPF..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarGestores()">
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarGestores()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">CPF</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-gestores">
                                <?php if (empty($gestores)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhum gestor encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($gestores as $gestor): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($gestor['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($gestor['cpf'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <?php if (!empty($gestor['escolas']) && count($gestor['escolas']) > 0): ?>
                                                    <?php if (count($gestor['escolas']) == 1): ?>
                                                        <span><?= htmlspecialchars($gestor['escolas'][0]['nome']) ?></span>
                                                    <?php else: ?>
                                                        <button onclick="mostrarEscolasModal(<?= htmlspecialchars(json_encode($gestor['escolas'], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>, '<?= htmlspecialchars($gestor['nome'], ENT_QUOTES) ?>')" class="text-indigo-600 hover:text-indigo-700 font-medium underline cursor-pointer">
                                                            <?= count($gestor['escolas']) ?> escolas
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($gestor['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarGestor(<?= (int)$gestor['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm" data-gestor-id="<?= (int)$gestor['id'] ?>">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirGestor(<?= $gestor['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal de Edição de Gestor -->
    <div id="modalEditarGestor" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Editar Gestor</h2>
                <button onclick="fecharModalEditarGestor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formEditarGestor" class="space-y-6 max-w-6xl mx-auto">
                    <div id="alertaErroEditar" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucessoEditar" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <input type="hidden" name="gestor_id" id="editar_gestor_id">
                    
                    <!-- Informações Pessoais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" name="nome" id="editar_nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" name="cpf" id="editar_cpf" required maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" id="editar_data_nascimento" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                                <select name="sexo" id="editar_sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="editar_email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="editar_telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       oninput="formatarTelefone(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                                <input type="text" name="nome_social" id="editar_nome_social"
                                       placeholder="Nome social (se houver)"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cor/Raça</label>
                                <select name="cor" id="editar_cor"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="BRANCA">Branca</option>
                                    <option value="PRETA">Preta</option>
                                    <option value="PARDA">Parda</option>
                                    <option value="AMARELA">Amarela</option>
                                    <option value="INDIGENA">Indígena</option>
                                    <option value="NAO_DECLARADA">Não Declarada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Endereço -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                <input type="text" name="endereco" id="editar_endereco"
                                       placeholder="Rua, Avenida, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" name="numero" id="editar_numero"
                                       placeholder="Número"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                <input type="text" name="complemento" id="editar_complemento"
                                       placeholder="Apto, Bloco, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                <input type="text" name="bairro" id="editar_bairro"
                                       placeholder="Bairro"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                <input type="text" name="cidade" id="editar_cidade"
                                       placeholder="Cidade"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <input type="text" name="estado" id="editar_estado" value="CE" readonly
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                       placeholder="Ceará">
                                <input type="hidden" name="estado" value="CE">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                <input type="text" name="cep" id="editar_cep" maxlength="9"
                                       placeholder="00000-000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       oninput="formatarCEP(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações Profissionais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Profissionais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cargo *</label>
                                <input type="text" name="cargo" id="editar_cargo" required placeholder="Ex: Diretor, Vice-Diretor, Coordenador"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formações</label>
                                <div id="editar-formacoes-container" class="space-y-2 mb-2">
                                    <!-- Formações serão adicionadas aqui dinamicamente -->
                                </div>
                                <button type="button" onclick="adicionarFormacaoEdicao()" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center space-x-1">
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
                                <button type="button" onclick="adicionarEspecializacaoEdicao()" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Adicionar Especialização</span>
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional (Opcional)</label>
                                <input type="text" name="registro_profissional" id="editar_registro_profissional" placeholder="Ex: CREA, CREF, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="ativo" id="editar_ativo"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                <textarea name="observacoes" id="editar_observacoes" rows="2"
                                          placeholder="Observações sobre o gestor..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lotação (Opcional) -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Lotação (Opcional)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                                <div class="relative">
                                    <input type="text" id="editar_escola_search" placeholder="Digite para pesquisar escola..."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                           autocomplete="off">
                                    <input type="hidden" name="escola_id" id="editar_escola_id" value="">
                                    <div id="editar_escola_dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        <!-- Opções serão preenchidas via JavaScript -->
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Digite o nome da escola para pesquisar</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Lotação</label>
                                <select name="tipo_lotacao" id="editar_tipo_lotacao"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="Diretor">Diretor</option>
                                    <option value="Vice-Diretor">Vice-Diretor</option>
                                    <option value="Coordenador Pedagógico">Coordenador Pedagógico</option>
                                    <option value="Secretário Escolar">Secretário Escolar</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Responsável</label>
                                <select name="responsavel" id="editar_responsavel"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observação da Lotação</label>
                                <textarea name="observacao_lotacao" id="editar_observacao_lotacao" rows="2"
                                          placeholder="Observações sobre a lotação..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de Acesso -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Acesso</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha (deixe em branco para manter a atual)</label>
                                <div class="relative">
                                    <input type="password" name="senha" id="editar_senha"
                                           class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <button type="button" onclick="toggleSenha('editar_senha', 'editar_senhaIcon')" 
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <svg id="editar_senhaIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
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
                <button type="button" onclick="fecharModalEditarGestor()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formEditarGestor" id="btnSalvarEdicao"
                        class="px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Alterações</span>
                    <svg id="spinnerSalvarEdicao" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Cadastro de Gestor -->
    <div id="modalNovoGestor" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Cadastrar Novo Gestor</h2>
                <button onclick="fecharModalNovoGestor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formNovoGestor" class="space-y-6 max-w-6xl mx-auto">
                    <div id="alertaErro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <!-- Informações Pessoais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" name="nome" id="nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" name="cpf" id="cpf" required maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" id="data_nascimento" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                                <select name="sexo" id="sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       oninput="formatarTelefone(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Social</label>
                                <input type="text" name="nome_social" id="nome_social"
                                       placeholder="Nome social (se houver)"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cor/Raça</label>
                                <select name="cor" id="cor"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="BRANCA">Branca</option>
                                    <option value="PRETA">Preta</option>
                                    <option value="PARDA">Parda</option>
                                    <option value="AMARELA">Amarela</option>
                                    <option value="INDIGENA">Indígena</option>
                                    <option value="NAO_DECLARADA">Não Declarada</option>
                                </select>
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
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" name="numero" id="numero"
                                       placeholder="Número"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                <input type="text" name="complemento" id="complemento"
                                       placeholder="Apto, Bloco, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                <input type="text" name="bairro" id="bairro"
                                       placeholder="Bairro"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                <input type="text" name="cidade" id="cidade"
                                       placeholder="Cidade"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       oninput="formatarCEP(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações Profissionais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Profissionais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cargo *</label>
                                <input type="text" name="cargo" id="cargo" required placeholder="Ex: Diretor, Vice-Diretor, Coordenador"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formações</label>
                                <div id="formacoes-container" class="space-y-2 mb-2">
                                    <!-- Formações serão adicionadas aqui dinamicamente -->
                                </div>
                                <button type="button" onclick="adicionarFormacao()" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center space-x-1">
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
                                <button type="button" onclick="adicionarEspecializacao()" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Adicionar Especialização</span>
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional (Opcional)</label>
                                <input type="text" name="registro_profissional" id="registro_profissional" placeholder="Ex: CREA, CREF, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                <textarea name="observacoes" id="observacoes" rows="2"
                                          placeholder="Observações sobre o gestor..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lotação (Opcional) -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Lotação (Opcional)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                                <div class="relative">
                                    <input type="text" id="escola_search" placeholder="Digite para pesquisar escola..."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                           autocomplete="off">
                                    <input type="hidden" name="escola_id" id="escola_id" value="">
                                    <div id="escola_dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        <!-- Opções serão preenchidas via JavaScript -->
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Digite o nome da escola para pesquisar</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Lotação</label>
                                <select name="tipo_lotacao" id="tipo_lotacao"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="Diretor">Diretor</option>
                                    <option value="Vice-Diretor">Vice-Diretor</option>
                                    <option value="Coordenador Pedagógico">Coordenador Pedagógico</option>
                                    <option value="Secretário Escolar">Secretário Escolar</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Responsável</label>
                                <select name="responsavel" id="responsavel"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="1" selected>Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observação da Lotação</label>
                                <textarea name="observacao_lotacao" id="observacao_lotacao" rows="2"
                                          placeholder="Observações sobre a lotação..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de Acesso -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações de Acesso</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha Padrão</label>
                                <div class="relative">
                                    <input type="password" name="senha" id="senha" value="123456"
                                           class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <button type="button" onclick="toggleSenha('senha', 'senhaIcon')" 
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <svg id="senhaIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Senha padrão: 123456 (pode ser alterada pelo gestor após o primeiro login)</p>
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
                <button type="button" onclick="fecharModalNovoGestor()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formNovoGestor" id="btnSalvarGestor"
                        class="px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Gestor</span>
                    <svg id="spinnerSalvar" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
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

        // Array de escolas para pesquisa
        const escolas = <?= json_encode($escolas) ?>;
        
        // Função para mostrar/ocultar senha
        function toggleSenha(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (!input || !icon) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        // Função de pesquisa de escolas (genérica para ambos os modais)
        function initEscolaSearchField(searchInputId, dropdownId, hiddenInputId) {
            const searchInput = document.getElementById(searchInputId);
            const dropdown = document.getElementById(dropdownId);
            const hiddenInput = document.getElementById(hiddenInputId);
            
            if (!searchInput || !dropdown || !hiddenInput) return;
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length === 0) {
                    dropdown.classList.add('hidden');
                    hiddenInput.value = '';
                    return;
                }
                
                // Filtrar escolas
                const filtered = escolas.filter(escola => 
                    escola.nome.toLowerCase().includes(searchTerm)
                );
                
                if (filtered.length === 0) {
                    dropdown.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">Nenhuma escola encontrada</div>';
                    dropdown.classList.remove('hidden');
                    return;
                }
                
                // Criar opções
                dropdown.innerHTML = filtered.map(escola => {
                    const nomeEscapado = escola.nome.replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, ' ');
                    return `
                    <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0" 
                         onclick="selectEscola('${searchInputId}', '${dropdownId}', '${hiddenInputId}', ${escola.id}, '${nomeEscapado}')">
                        ${escola.nome}
                    </div>
                `;
                }).join('');
                
                dropdown.classList.remove('hidden');
            });
            
            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }
        
        // Função de pesquisa de escolas (inicializa ambos)
        function initEscolaSearch() {
            // Modal de cadastro
            initEscolaSearchField('escola_search', 'escola_dropdown', 'escola_id');
            // Modal de edição
            initEscolaSearchField('editar_escola_search', 'editar_escola_dropdown', 'editar_escola_id');
        }
        
        // Função para selecionar escola
        window.selectEscola = function(searchInputId, dropdownId, hiddenInputId, id, nome) {
            const searchInput = document.getElementById(searchInputId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const dropdown = document.getElementById(dropdownId);
            
            if (searchInput && hiddenInput && dropdown) {
                searchInput.value = nome;
                hiddenInput.value = id;
                dropdown.classList.add('hidden');
            }
        };
        
        // Limpar pesquisa de escola ao abrir modal
        function limparPesquisaEscola() {
            const searchInput = document.getElementById('escola_search');
            const hiddenInput = document.getElementById('escola_id');
            const dropdown = document.getElementById('escola_dropdown');
            
            if (searchInput) searchInput.value = '';
            if (hiddenInput) hiddenInput.value = '';
            if (dropdown) dropdown.classList.add('hidden');
        }
        
        // Limpar pesquisa de escola no modal de edição
        function limparPesquisaEscolaEdicao() {
            const searchInput = document.getElementById('editar_escola_search');
            const hiddenInput = document.getElementById('editar_escola_id');
            const dropdown = document.getElementById('editar_escola_dropdown');
            
            if (searchInput) searchInput.value = '';
            if (hiddenInput) hiddenInput.value = '';
            if (dropdown) dropdown.classList.add('hidden');
        }

        function abrirModalNovoGestor() {
            const modal = document.getElementById('modalNovoGestor');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Limpar formulário
                document.getElementById('formNovoGestor').reset();
                const senhaInput = document.getElementById('senha');
                senhaInput.value = '123456';
                senhaInput.type = 'password'; // Garantir que volta para password
                // Resetar ícone do olho mágico
                const senhaIcon = document.getElementById('senhaIcon');
                if (senhaIcon) {
                    senhaIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    `;
                }
                document.getElementById('responsavel').value = '1';
                // Limpar containers de formações e especializações
                document.getElementById('formacoes-container').innerHTML = '';
                document.getElementById('especializacoes-container').innerHTML = '';
                formacaoCount = 0;
                especializacaoCount = 0;
                // Limpar pesquisa de escola
                limparPesquisaEscola();
                // Limpar alertas
                document.getElementById('alertaErro').classList.add('hidden');
                document.getElementById('alertaSucesso').classList.add('hidden');
                // Atualizar preview do username
                atualizarPreviewUsername();
            }
        }
        
        function fecharModalNovoGestor() {
            const modal = document.getElementById('modalNovoGestor');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
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
        let formacaoEdicaoCount = 0;
        let especializacaoCount = 0;
        let especializacaoEdicaoCount = 0;
        
        function adicionarFormacao() {
            const container = document.getElementById('formacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `formacao-${formacaoCount}`;
            div.innerHTML = `
                <input type="text" name="formacoes[]" placeholder="Ex: Licenciatura em Pedagogia"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
        
        function adicionarFormacaoEdicao() {
            const container = document.getElementById('editar-formacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `editar-formacao-${formacaoEdicaoCount}`;
            div.innerHTML = `
                <input type="text" name="formacoes[]" placeholder="Ex: Licenciatura em Pedagogia"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
        
        function adicionarEspecializacao() {
            const container = document.getElementById('especializacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `especializacao-${especializacaoCount}`;
            div.innerHTML = `
                <input type="text" name="especializacoes[]" placeholder="Ex: Gestão Escolar"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
        
        function adicionarEspecializacaoEdicao() {
            const container = document.getElementById('editar-especializacoes-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.id = `editar-especializacao-${especializacaoEdicaoCount}`;
            div.innerHTML = `
                <input type="text" name="especializacoes[]" placeholder="Ex: Gestão Escolar"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
        
        // Atualizar preview do username quando o nome mudar
        document.getElementById('nome')?.addEventListener('input', atualizarPreviewUsername);
        
        // Submissão do formulário
        document.getElementById('formNovoGestor').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarGestor');
            const spinner = document.getElementById('spinnerSalvar');
            const alertaErro = document.getElementById('alertaErro');
            const alertaSucesso = document.getElementById('alertaSucesso');
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Validar campos obrigatórios
            const nome = document.getElementById('nome').value.trim();
            const cpf = document.getElementById('cpf').value.trim();
            const dataNascimento = document.getElementById('data_nascimento').value;
            const sexo = document.getElementById('sexo').value;
            const cargo = document.getElementById('cargo').value.trim();
            
            if (!nome || !cpf || !dataNascimento || !sexo || !cargo) {
                alertaErro.textContent = 'Por favor, preencha todos os campos obrigatórios (*).';
                alertaErro.classList.remove('hidden');
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
                return;
            }
            
            // Verificar se escola foi selecionada (se o campo de pesquisa tem valor mas não tem ID)
            const escolaSearch = document.getElementById('escola_search').value;
            const escolaId = document.getElementById('escola_id').value;
            if (escolaSearch && !escolaId) {
                alertaErro.textContent = 'Por favor, selecione uma escola válida da lista de resultados.';
                alertaErro.classList.remove('hidden');
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
                return;
            }
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'cadastrar_gestor');
            
            // Garantir que escola_id está sendo enviado corretamente
            // Se não houver escola selecionada, garantir que o campo está vazio
            if (!escolaId || escolaId === '') {
                formData.set('escola_id', ''); // Garantir que está vazio
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                console.log('DEBUG JS CADASTRO - Resposta do servidor:', data);
                
                if (data.success) {
                    console.log('DEBUG JS CADASTRO - Resposta completa:', data);
                    console.log('DEBUG JS CADASTRO - ID retornado:', data.id);
                    console.log('DEBUG JS CADASTRO - gestor_id retornado:', data.gestor_id);
                    console.log('DEBUG JS CADASTRO - pessoa_id retornado:', data.pessoa_id);
                    console.log('DEBUG JS CADASTRO - Verificado:', data.verificado);
                    console.log('DEBUG JS CADASTRO - Debug info:', data.debug);
                    
                    // IMPORTANTE: Usar gestor_id se disponível, caso contrário usar id
                    const gestorIdCorreto = data.gestor_id || data.id;
                    console.log('DEBUG JS CADASTRO - ID do gestor que será usado:', gestorIdCorreto);
                    
                    // Verificar se o ID não é da pessoa
                    if (data.pessoa_id && data.pessoa_id == data.id) {
                        console.error('DEBUG JS CADASTRO - ATENÇÃO: O ID retornado parece ser da pessoa, não do gestor!');
                        console.error('DEBUG JS CADASTRO - pessoa_id:', data.pessoa_id, 'id retornado:', data.id);
                        if (data.gestor_id) {
                            console.log('DEBUG JS CADASTRO - Usando gestor_id correto:', data.gestor_id);
                        }
                    }
                    
                    alertaSucesso.textContent = `Gestor cadastrado com sucesso! Username: ${data.username || 'gerado automaticamente'}`;
                    alertaSucesso.classList.remove('hidden');
                    
                    // Limpar formulário
                    this.reset();
                    const senhaInput = document.getElementById('senha');
                    senhaInput.value = '123456';
                    senhaInput.type = 'password'; // Garantir que volta para password
                    // Resetar ícone do olho mágico
                    const senhaIcon = document.getElementById('senhaIcon');
                    if (senhaIcon) {
                        senhaIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        `;
                    }
                    document.getElementById('responsavel').value = '1';
                    limparPesquisaEscola(); // Limpar pesquisa de escola
                    atualizarPreviewUsername();
                    
                    // Recarregar lista de gestores após 1.5 segundos
                    setTimeout(() => {
                        fecharModalNovoGestor();
                        console.log('DEBUG JS CADASTRO - Recarregando lista de gestores');
                        filtrarGestores();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao cadastrar gestor. Por favor, tente novamente.';
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
        document.getElementById('modalNovoGestor')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalNovoGestor();
            }
        });

        async function editarGestor(id) {
            try {
                // Garantir que o ID seja um número
                const gestorId = parseInt(id);
                if (isNaN(gestorId) || gestorId <= 0) {
                    alert('ID do gestor inválido: ' + id);
                    console.error('ID inválido recebido:', id, 'Tipo:', typeof id);
                    return;
                }
                
                console.log('DEBUG JS - Buscando gestor com ID:', gestorId, 'Tipo:', typeof gestorId);
                
                // Buscar dados do gestor
                const response = await fetch('?acao=buscar_gestor&id=' + gestorId);
                
                // Verificar se a resposta é JSON válido
                const contentType = response.headers.get('content-type') || '';
                const textResponse = await response.text();
                
                let data;
                try {
                    if (!contentType.includes('application/json')) {
                        console.error('DEBUG JS - Resposta não é JSON:', textResponse.substring(0, 200));
                        throw new Error('Resposta do servidor não é JSON válido. Verifique o console para mais detalhes.');
                    }
                    data = JSON.parse(textResponse);
                } catch (parseError) {
                    console.error('DEBUG JS - Erro ao fazer parse do JSON:', parseError);
                    console.error('DEBUG JS - Texto recebido:', textResponse.substring(0, 500));
                    alert('Erro ao processar resposta do servidor. Verifique o console para mais detalhes.');
                    return;
                }
                
                console.log('DEBUG JS - Resposta da busca:', data);
                
                if (!data.success || !data.gestor) {
                    console.error('DEBUG JS - Erro na busca:', data);
                    alert('Erro ao carregar dados do gestor: ' + (data.message || 'Gestor não encontrado') + ' (ID: ' + gestorId + ')');
                    return;
                }
                
                const gestor = data.gestor;
                
                // IMPORTANTE: Garantir que estamos usando o ID do gestor, não da pessoa
                // O backend já corrige isso automaticamente, então confiamos no backend
                const gestorIdCorreto = parseInt(gestor.id);
                
                console.log('DEBUG JS - ID do gestor recebido:', gestorIdCorreto);
                console.log('DEBUG JS - pessoa_id do gestor:', gestor.pessoa_id);
                console.log('DEBUG JS - Dados completos do gestor:', gestor);
                
                // Verificar se o ID é válido
                if (isNaN(gestorIdCorreto) || gestorIdCorreto <= 0) {
                    console.error('DEBUG JS - ERRO: ID do gestor inválido!', gestorIdCorreto);
                    alert('Erro: ID do gestor inválido. Por favor, recarregue a página e tente novamente.');
                    return;
                }
                
                // Verificação opcional: só alertar se ambos existirem e forem iguais
                // Mas não bloquear, pois o backend já corrigiu
                if (gestor.pessoa_id) {
                    const pessoaId = parseInt(gestor.pessoa_id);
                    if (!isNaN(pessoaId) && gestorIdCorreto === pessoaId) {
                        console.warn('DEBUG JS - AVISO: ID do gestor é igual ao pessoa_id! O backend deveria ter corrigido isso.');
                        // Não bloquear, apenas logar o aviso
                    }
                }
                
                console.log('DEBUG JS - ID do gestor validado:', gestorIdCorreto);
                
                // Preencher formulário
                document.getElementById('editar_gestor_id').value = gestorIdCorreto;
                document.getElementById('editar_nome').value = gestor.nome || '';
                document.getElementById('editar_cpf').value = gestor.cpf_formatado || gestor.cpf || '';
                document.getElementById('editar_data_nascimento').value = gestor.data_nascimento || '';
                document.getElementById('editar_sexo').value = gestor.sexo || '';
                document.getElementById('editar_email').value = gestor.email || '';
                document.getElementById('editar_telefone').value = gestor.telefone_formatado || gestor.telefone || '';
                document.getElementById('editar_nome_social').value = gestor.nome_social || '';
                document.getElementById('editar_cor').value = gestor.cor || '';
                document.getElementById('editar_cargo').value = gestor.cargo || '';
                
                // Carregar formações (JSON)
                const formacoesContainer = document.getElementById('editar-formacoes-container');
                formacoesContainer.innerHTML = '';
                formacaoEdicaoCount = 0;
                if (gestor.formacao) {
                    try {
                        const formacoes = JSON.parse(gestor.formacao);
                        if (Array.isArray(formacoes)) {
                            formacoes.forEach(form => {
                                if (form && form.trim()) {
                                    const div = document.createElement('div');
                                    div.className = 'flex items-center space-x-2';
                                    div.id = `editar-formacao-${formacaoEdicaoCount}`;
                                    div.innerHTML = `
                                        <input type="text" name="formacoes[]" value="${form.replace(/"/g, '&quot;')}"
                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                        if (gestor.formacao.trim()) {
                            const div = document.createElement('div');
                            div.className = 'flex items-center space-x-2';
                            div.id = `editar-formacao-${formacaoEdicaoCount}`;
                            div.innerHTML = `
                                <input type="text" name="formacoes[]" value="${gestor.formacao.replace(/"/g, '&quot;')}"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                
                // Carregar especializações (JSON)
                const especializacoesContainer = document.getElementById('editar-especializacoes-container');
                especializacoesContainer.innerHTML = '';
                especializacaoEdicaoCount = 0;
                if (gestor.especializacao) {
                    try {
                        const especializacoes = JSON.parse(gestor.especializacao);
                        if (Array.isArray(especializacoes)) {
                            especializacoes.forEach(esp => {
                                if (esp && esp.trim()) {
                                    const div = document.createElement('div');
                                    div.className = 'flex items-center space-x-2';
                                    div.id = `editar-especializacao-${especializacaoEdicaoCount}`;
                                    div.innerHTML = `
                                        <input type="text" name="especializacoes[]" value="${esp.replace(/"/g, '&quot;')}"
                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                        if (gestor.especializacao.trim()) {
                            const div = document.createElement('div');
                            div.className = 'flex items-center space-x-2';
                            div.id = `editar-especializacao-${especializacaoEdicaoCount}`;
                            div.innerHTML = `
                                <input type="text" name="especializacoes[]" value="${gestor.especializacao.replace(/"/g, '&quot;')}"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                
                document.getElementById('editar_registro_profissional').value = gestor.registro_profissional || '';
                document.getElementById('editar_observacoes').value = gestor.observacoes || '';
                document.getElementById('editar_ativo').value = gestor.ativo !== undefined ? gestor.ativo : 1;
                
                // Preencher endereço
                document.getElementById('editar_endereco').value = gestor.endereco || '';
                document.getElementById('editar_numero').value = gestor.numero || '';
                document.getElementById('editar_complemento').value = gestor.complemento || '';
                document.getElementById('editar_bairro').value = gestor.bairro || '';
                document.getElementById('editar_cidade').value = gestor.cidade || '';
                document.getElementById('editar_estado').value = gestor.estado || 'CE';
                if (gestor.cep) {
                    const cep = gestor.cep.replace(/\D/g, '');
                    if (cep.length === 8) {
                        document.getElementById('editar_cep').value = cep.slice(0, 5) + '-' + cep.slice(5);
                    } else {
                        document.getElementById('editar_cep').value = gestor.cep;
                    }
                }
                // Preencher escola (pesquisa)
                if (gestor.escola_id) {
                    const escola = escolas.find(e => e.id == gestor.escola_id);
                    if (escola) {
                        document.getElementById('editar_escola_search').value = escola.nome;
                        document.getElementById('editar_escola_id').value = escola.id;
                    } else {
                        document.getElementById('editar_escola_search').value = '';
                        document.getElementById('editar_escola_id').value = '';
                    }
                } else {
                    limparPesquisaEscolaEdicao();
                }
                
                document.getElementById('editar_tipo_lotacao').value = gestor.tipo_lotacao || '';
                document.getElementById('editar_responsavel').value = gestor.responsavel !== undefined ? gestor.responsavel : 1;
                document.getElementById('editar_observacao_lotacao').value = gestor.observacao_lotacao || '';
                document.getElementById('editar_username_preview').value = gestor.username || '';
                
                // Abrir modal
                const modal = document.getElementById('modalEditarGestor');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    // Limpar alertas
                    document.getElementById('alertaErroEditar').classList.add('hidden');
                    document.getElementById('alertaSucessoEditar').classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar gestor:', error);
                alert('Erro ao carregar dados do gestor. Por favor, tente novamente.');
            }
        }
        
        function fecharModalEditarGestor() {
            const modal = document.getElementById('modalEditarGestor');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        // Submissão do formulário de edição
        document.getElementById('formEditarGestor').addEventListener('submit', async function(e) {
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
            
            // Validar gestor_id
            const gestorIdValue = document.getElementById('editar_gestor_id').value;
            if (!gestorIdValue || gestorIdValue === '' || parseInt(gestorIdValue) <= 0) {
                alert('Erro: ID do gestor não encontrado. Por favor, recarregue a página e tente novamente.');
                console.error('DEBUG JS - gestor_id inválido:', gestorIdValue);
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
                return;
            }
            
            formData.append('acao', 'editar_gestor');
            formData.set('gestor_id', gestorIdValue); // Garantir que o gestor_id seja enviado
            
            // Log para debug
            const escolaIdValue = document.getElementById('editar_escola_id').value;
            console.log('DEBUG JS - gestor_id:', gestorIdValue, 'Tipo:', typeof gestorIdValue);
            console.log('DEBUG JS - escola_id antes do envio:', escolaIdValue);
            
            // Garantir que escola_id seja enviado se tiver valor
            if (escolaIdValue && escolaIdValue !== '') {
                formData.set('escola_id', escolaIdValue); // Usar set para garantir que o valor seja atualizado
            } else {
                console.log('DEBUG JS - escola_id está vazio, não será enviado');
            }
            
            // Log de todos os dados que serão enviados
            console.log('DEBUG JS - Dados do FormData:');
            for (let pair of formData.entries()) {
                console.log('  ' + pair[0] + ': ' + pair[1]);
            }
            
            try {
                console.log('DEBUG JS - Enviando requisição de edição...');
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('DEBUG JS - Status da resposta:', response.status, response.statusText);
                console.log('DEBUG JS - Content-Type:', response.headers.get('content-type'));
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('DEBUG JS - Resposta não é JSON:', textResponse);
                    alertaErro.textContent = 'Erro: Resposta inválida do servidor. Verifique o console para mais detalhes.';
                    alertaErro.classList.remove('hidden');
                    return;
                }
                
                const data = await response.json();
                console.log('DEBUG JS - Resposta do servidor:', data);
                
                if (data.success) {
                    alertaSucesso.textContent = 'Gestor atualizado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
                    // Recarregar lista de gestores após 1.5 segundos
                    setTimeout(() => {
                        fecharModalEditarGestor();
                        filtrarGestores();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao atualizar gestor. Por favor, tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('DEBUG JS - Erro completo:', error);
                console.error('DEBUG JS - Stack trace:', error.stack);
                alertaErro.textContent = 'Erro ao processar requisição: ' + (error.message || 'Erro desconhecido') + '. Verifique o console para mais detalhes.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        });
        
        // Fechar modal de edição ao clicar fora
        document.getElementById('modalEditarGestor')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalEditarGestor();
            }
        });

        async function excluirGestor(id) {
            // Buscar nome do gestor para exibir na confirmação
            try {
                const response = await fetch('?acao=buscar_gestor&id=' + id);
                const data = await response.json();
                const nomeGestor = data.success && data.gestor ? data.gestor.nome : 'este gestor';
                
                // Modal de confirmação customizado
                if (confirm(`Tem certeza que deseja excluir o gestor "${nomeGestor}"?\n\nEsta ação não pode ser desfeita. O gestor será marcado como inativo no sistema e todas as lotações ativas serão finalizadas.`)) {
                    // Mostrar loading
                    const btnExcluir = event.target;
                    const originalText = btnExcluir.textContent;
                    btnExcluir.disabled = true;
                    btnExcluir.textContent = 'Excluindo...';
                    
                    try {
                        const formData = new FormData();
                        formData.append('acao', 'excluir_gestor');
                        formData.append('gestor_id', id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert('Gestor excluído com sucesso!');
                            // Recarregar lista
                            filtrarGestores();
                        } else {
                            alert('Erro ao excluir gestor: ' + (data.message || 'Erro desconhecido'));
                        }
                    } catch (error) {
                        console.error('Erro ao excluir gestor:', error);
                        alert('Erro ao processar requisição. Por favor, tente novamente.');
                    } finally {
                        btnExcluir.disabled = false;
                        btnExcluir.textContent = originalText;
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar dados do gestor:', error);
                // Se não conseguir buscar o nome, usar confirmação simples
                if (confirm('Tem certeza que deseja excluir este gestor?\n\nEsta ação não pode ser desfeita.')) {
                    const formData = new FormData();
                    formData.append('acao', 'excluir_gestor');
                    formData.append('gestor_id', id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Gestor excluído com sucesso!');
                            filtrarGestores();
                        } else {
                            alert('Erro ao excluir gestor: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao processar requisição. Por favor, tente novamente.');
                    });
                }
            }
        }

        // Função para escapar HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Função para mostrar modal de escolas
        function mostrarEscolasModal(escolas, gestorNome) {
            if (!escolas || escolas.length === 0) {
                alert('Nenhuma escola encontrada para este gestor.');
                return;
            }
            
            // Criar ou atualizar modal
            let modal = document.getElementById('modalEscolasGestor');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalEscolasGestor';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center';
                modal.innerHTML = `
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Escolas do Gestor</h3>
                            <button onclick="fecharModalEscolas()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-4 overflow-y-auto">
                            <p class="text-sm text-gray-600 mb-4">Gestor: <strong>${escapeHtml(gestorNome)}</strong></p>
                            <ul id="lista-escolas-modal" class="space-y-2">
                            </ul>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Fechar ao clicar fora
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        fecharModalEscolas();
                    }
                });
            }
            
            // Preencher lista de escolas
            const listaEscolas = document.getElementById('lista-escolas-modal');
            listaEscolas.innerHTML = '';
            
            escolas.forEach((escola, index) => {
                const item = document.createElement('li');
                item.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
                let badges = '';
                if (escola.responsavel == 1 || escola.responsavel === '1') {
                    badges += '<span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded mr-2">Responsável</span>';
                }
                if (escola.tipo) {
                    badges += `<span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">${escapeHtml(escola.tipo)}</span>`;
                }
                item.innerHTML = `
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">${escapeHtml(escola.nome)}</p>
                            ${badges ? `<div class="mt-2">${badges}</div>` : ''}
                        </div>
                    </div>
                `;
                listaEscolas.appendChild(item);
            });
            
            modal.style.display = 'flex';
        }
        
        // Função para fechar modal de escolas
        function fecharModalEscolas() {
            const modal = document.getElementById('modalEscolasGestor');
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        function filtrarGestores() {
            const busca = document.getElementById('filtro-busca').value;
            
            let url = '?acao=listar_gestores';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-gestores');
                        tbody.innerHTML = '';
                        
                        if (data.gestores.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-600">Nenhum gestor encontrado.</td></tr>';
                            return;
                        }
                        
                        data.gestores.forEach(gestor => {
                            // Garantir que o ID seja um número válido
                            const gestorId = parseInt(gestor.id);
                            if (isNaN(gestorId) || gestorId <= 0) {
                                console.error('ID inválido na listagem:', gestor.id, gestor);
                                return;
                            }
                            
                            // Preparar HTML das escolas
                            let escolasHtml = '<span class="text-gray-400">-</span>';
                            if (gestor.escolas && gestor.escolas.length > 0) {
                                if (gestor.escolas.length === 1) {
                                    escolasHtml = `<span>${escapeHtml(gestor.escolas[0].nome)}</span>`;
                                } else {
                                    const escolasJson = JSON.stringify(gestor.escolas);
                                    const gestorNome = escapeHtml(gestor.nome);
                                    escolasHtml = `<button onclick="mostrarEscolasModal(${escolasJson.replace(/"/g, '&quot;')}, '${gestorNome.replace(/'/g, "&#39;")}')" class="text-indigo-600 hover:text-indigo-700 font-medium underline cursor-pointer">${gestor.escolas.length} escolas</button>`;
                                }
                            }
                            
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${gestor.nome || '-'}</td>
                                    <td class="py-3 px-4">${gestor.cpf || '-'}</td>
                                    <td class="py-3 px-4">${escolasHtml}</td>
                                    <td class="py-3 px-4">${gestor.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarGestor(${gestorId})" class="text-blue-600 hover:text-blue-700 font-medium text-sm" data-gestor-id="${gestorId}">
                                                Editar
                                            </button>
                                            <button onclick="excluirGestor(${gestorId})" class="text-red-600 hover:text-red-700 font-medium text-sm" data-gestor-id="${gestorId}">
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
                    console.error('Erro ao filtrar gestores:', error);
                });
        }
        // Inicializar pesquisa de escolas quando a página carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initEscolaSearch);
        } else {
            initEscolaSearch();
        }
    </script>
</body>
</html>

