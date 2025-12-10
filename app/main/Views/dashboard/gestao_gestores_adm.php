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
            $cor = !empty($_POST['cor']) ? $_POST['cor'] : null;
            
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, 
                         nome_social, cor, endereco, numero, complemento, bairro, cidade, estado, cep, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone,
                         :nome_social, :cor, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, 'GESTOR', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $nome);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimento);
            $stmtPessoa->bindParam(':sexo', $sexo);
            $stmtPessoa->bindParam(':email', $email);
            $stmtPessoa->bindParam(':telefone', $telefoneVal);
            $stmtPessoa->bindParam(':nome_social', $nomeSocial);
            $stmtPessoa->bindParam(':cor', $cor);
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
            $gestorId = $conn->lastInsertId();
            
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
            
            echo json_encode([
                'success' => true,
                'message' => 'Gestor cadastrado com sucesso!',
                'id' => $gestorId,
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
    
    if ($_POST['acao'] === 'editar_gestor') {
        try {
            $gestorId = $_POST['gestor_id'] ?? null;
            if (empty($gestorId)) {
                throw new Exception('ID do gestor não informado.');
            }
            
            // Buscar gestor existente
            $sqlGestor = "SELECT g.*, p.*, p.id as pessoa_id, p.endereco, p.numero, p.complemento, 
                         p.bairro, p.cidade, p.estado, p.cep
                         FROM gestor g INNER JOIN pessoa p ON g.pessoa_id = p.id WHERE g.id = :id";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':id', $gestorId);
            $stmtGestor->execute();
            $gestor = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestor) {
                throw new Exception('Gestor não encontrado.');
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
            
            // 1. Atualizar pessoa
            $nomeUpdate = trim($_POST['nome'] ?? '');
            $dataNascimentoUpdate = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
            $sexoUpdate = !empty($_POST['sexo']) ? $_POST['sexo'] : null;
            $emailUpdate = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $telefoneUpdate = !empty($telefone) ? $telefone : null;
            $pessoaId = $gestor['pessoa_id'];
            
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
                $stmtSenha->bindParam(':pessoa_id', $gestor['pessoa_id']);
                $stmtSenha->execute();
            }
            
            // 4. Atualizar lotação se informada
            if (!empty($_POST['escola_id'])) {
                // Verificar se já existe lotação ativa
                $sqlLotacaoAtual = "SELECT id FROM gestor_lotacao WHERE gestor_id = :gestor_id AND fim IS NULL LIMIT 1";
                $stmtLotacaoAtual = $conn->prepare($sqlLotacaoAtual);
                $stmtLotacaoAtual->bindParam(':gestor_id', $gestorId);
                $stmtLotacaoAtual->execute();
                $lotacaoAtual = $stmtLotacaoAtual->fetch(PDO::FETCH_ASSOC);
                
                if ($lotacaoAtual) {
                    // Finalizar lotação atual se a escola mudou
                    if ($_POST['escola_id'] != $gestor['escola_id']) {
                        $sqlFinalizar = "UPDATE gestor_lotacao SET fim = CURDATE() WHERE id = :id";
                        $stmtFinalizar = $conn->prepare($sqlFinalizar);
                        $stmtFinalizar->bindParam(':id', $lotacaoAtual['id']);
                        $stmtFinalizar->execute();
                        
                        // Criar nova lotação
                        $sqlNovaLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel, tipo, observacoes, criado_por)
                                         VALUES (:gestor_id, :escola_id, CURDATE(), :responsavel, :tipo, :observacoes, :criado_por)";
                        $stmtNovaLotacao = $conn->prepare($sqlNovaLotacao);
                        $stmtNovaLotacao->bindParam(':gestor_id', $gestorId);
                        $stmtNovaLotacao->bindParam(':escola_id', $_POST['escola_id']);
                        $stmtNovaLotacao->bindParam(':responsavel', $_POST['responsavel'] ?? 1);
                        $stmtNovaLotacao->bindParam(':tipo', !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null);
                        $stmtNovaLotacao->bindParam(':observacoes', !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null);
                        $stmtNovaLotacao->bindParam(':criado_por', $_SESSION['usuario_id']);
                        $stmtNovaLotacao->execute();
                    } else {
                        // Atualizar lotação existente
                        $sqlAtualizarLotacao = "UPDATE gestor_lotacao SET responsavel = :responsavel, tipo = :tipo, observacoes = :observacoes
                                               WHERE id = :id";
                        $stmtAtualizarLotacao = $conn->prepare($sqlAtualizarLotacao);
                        $stmtAtualizarLotacao->bindParam(':responsavel', $_POST['responsavel'] ?? 1);
                        $stmtAtualizarLotacao->bindParam(':tipo', !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null);
                        $stmtAtualizarLotacao->bindParam(':observacoes', !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null);
                        $stmtAtualizarLotacao->bindParam(':id', $lotacaoAtual['id']);
                        $stmtAtualizarLotacao->execute();
                    }
                } else {
                    // Criar nova lotação
                    $sqlNovaLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel, tipo, observacoes, criado_por)
                                     VALUES (:gestor_id, :escola_id, CURDATE(), :responsavel, :tipo, :observacoes, :criado_por)";
                    $stmtNovaLotacao = $conn->prepare($sqlNovaLotacao);
                    $stmtNovaLotacao->bindParam(':gestor_id', $gestorId);
                    $stmtNovaLotacao->bindParam(':escola_id', $_POST['escola_id']);
                    $stmtNovaLotacao->bindParam(':responsavel', $_POST['responsavel'] ?? 1);
                    $stmtNovaLotacao->bindParam(':tipo', !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null);
                    $stmtNovaLotacao->bindParam(':observacoes', !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null);
                    $stmtNovaLotacao->bindParam(':criado_por', $_SESSION['usuario_id']);
                    $stmtNovaLotacao->execute();
                }
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Gestor atualizado com sucesso!'
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
            $sqlLotacoes = "SELECT COUNT(*) as total FROM gestor_lotacao WHERE gestor_id = :gestor_id AND fim IS NULL";
            $stmtLotacoes = $conn->prepare($sqlLotacoes);
            $stmtLotacoes->bindParam(':gestor_id', $gestorId);
            $stmtLotacoes->execute();
            $lotacoes = $stmtLotacoes->fetch(PDO::FETCH_ASSOC);
            
            if ($lotacoes['total'] > 0) {
                // Finalizar todas as lotações ativas
                $sqlFinalizarLotacoes = "UPDATE gestor_lotacao SET fim = CURDATE() WHERE gestor_id = :gestor_id AND fim IS NULL";
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
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_gestor') {
        $gestorId = $_GET['id'] ?? null;
        if (empty($gestorId)) {
            echo json_encode(['success' => false, 'message' => 'ID do gestor não informado']);
            exit;
        }
        
        $sql = "SELECT g.*, p.*, gl.escola_id, gl.responsavel, gl.tipo as tipo_lotacao, gl.observacoes as observacao_lotacao
                FROM gestor g
                INNER JOIN pessoa p ON g.pessoa_id = p.id
                LEFT JOIN gestor_lotacao gl ON g.id = gl.gestor_id AND gl.fim IS NULL
                WHERE g.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $gestorId);
        $stmt->execute();
        $gestor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($gestor) {
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
            
            echo json_encode(['success' => true, 'gestor' => $gestor]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gestor não encontrado']);
        }
        exit;
        exit;
    }
    
    if ($_GET['acao'] === 'listar_gestores') {
        $filtros = [];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        
        $sql = "SELECT g.*, p.nome, p.cpf, p.email, p.telefone, e.nome as escola_nome
                FROM gestor g
                INNER JOIN pessoa p ON g.pessoa_id = p.id
                LEFT JOIN gestor_lotacao gl ON g.id = gl.gestor_id AND gl.fim IS NULL
                LEFT JOIN escola e ON gl.escola_id = e.id
                WHERE g.ativo = 1";
        
        $params = [];
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        $sql .= " GROUP BY g.id ORDER BY p.nome ASC LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $gestores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'gestores' => $gestores]);
        exit;
    }
}

$sqlGestores = "SELECT g.*, p.nome, p.cpf, p.email, p.telefone, e.nome as escola_nome
                FROM gestor g
                INNER JOIN pessoa p ON g.pessoa_id = p.id
                LEFT JOIN gestor_lotacao gl ON g.id = gl.gestor_id AND gl.fim IS NULL
                LEFT JOIN escola e ON gl.escola_id = e.id
                WHERE g.ativo = 1
                GROUP BY g.id
                ORDER BY p.nome ASC
                LIMIT 50";
$stmtGestores = $conn->prepare($sqlGestores);
$stmtGestores->execute();
$gestores = $stmtGestores->fetchAll(PDO::FETCH_ASSOC);
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
                                            <td class="py-3 px-4"><?= htmlspecialchars($gestor['escola_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($gestor['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarGestor(<?= $gestor['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
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
                
                if (data.success) {
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
                // Buscar dados do gestor
                const response = await fetch('?acao=buscar_gestor&id=' + id);
                const data = await response.json();
                
                if (!data.success || !data.gestor) {
                    alert('Erro ao carregar dados do gestor: ' + (data.message || 'Gestor não encontrado'));
                    return;
                }
                
                const gestor = data.gestor;
                
                // Preencher formulário
                document.getElementById('editar_gestor_id').value = gestor.id;
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
            formData.append('acao', 'editar_gestor');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
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
                console.error('Erro:', error);
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
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
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${gestor.nome}</td>
                                    <td class="py-3 px-4">${gestor.cpf || '-'}</td>
                                    <td class="py-3 px-4">${gestor.escola_nome || '-'}</td>
                                    <td class="py-3 px-4">${gestor.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarGestor(${gestor.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirGestor(${gestor.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
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

