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
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone, 'GESTOR', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $nome);
            $stmtPessoa->bindParam(':data_nascimento', $_POST['data_nascimento'] ?? null);
            $stmtPessoa->bindParam(':sexo', $_POST['sexo'] ?? null);
            $stmtPessoa->bindParam(':email', !empty($_POST['email']) ? trim($_POST['email']) : null);
            $stmtPessoa->bindParam(':telefone', !empty($telefone) ? $telefone : null);
            $stmtPessoa->bindParam(':criado_por', $_SESSION['usuario_id']);
            $stmtPessoa->execute();
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar gestor
            $sqlGestor = "INSERT INTO gestor (pessoa_id, cargo, formacao, registro_profissional, observacoes, ativo, criado_por)
                         VALUES (:pessoa_id, :cargo, :formacao, :registro_profissional, :observacoes, 1, :criado_por)";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':pessoa_id', $pessoaId);
            $stmtGestor->bindParam(':cargo', trim($_POST['cargo'] ?? ''));
            $stmtGestor->bindParam(':formacao', !empty($_POST['formacao']) ? trim($_POST['formacao']) : null);
            $stmtGestor->bindParam(':registro_profissional', !empty($_POST['registro_profissional']) ? trim($_POST['registro_profissional']) : null);
            $stmtGestor->bindParam(':observacoes', !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null);
            $stmtGestor->bindParam(':criado_por', $_SESSION['usuario_id']);
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
                $sqlLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel, tipo, observacoes, criado_por)
                              VALUES (:gestor_id, :escola_id, CURDATE(), :responsavel, :tipo, :observacoes, :criado_por)";
                $stmtLotacao = $conn->prepare($sqlLotacao);
                $stmtLotacao->bindParam(':gestor_id', $gestorId);
                $stmtLotacao->bindParam(':escola_id', $_POST['escola_id']);
                $stmtLotacao->bindParam(':responsavel', $_POST['responsavel'] ?? 1);
                $stmtLotacao->bindParam(':tipo', !empty($_POST['tipo_lotacao']) ? $_POST['tipo_lotacao'] : null);
                $stmtLotacao->bindParam(':observacoes', !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null);
                $stmtLotacao->bindParam(':criado_por', $_SESSION['usuario_id']);
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
            $sqlGestor = "SELECT g.*, p.* FROM gestor g INNER JOIN pessoa p ON g.pessoa_id = p.id WHERE g.id = :id";
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
            
            // 1. Atualizar pessoa
            $sqlPessoa = "UPDATE pessoa SET nome = :nome, data_nascimento = :data_nascimento, 
                          sexo = :sexo, email = :email, telefone = :telefone
                          WHERE id = :pessoa_id";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':nome', trim($_POST['nome'] ?? ''));
            $stmtPessoa->bindParam(':data_nascimento', $_POST['data_nascimento'] ?? null);
            $stmtPessoa->bindParam(':sexo', $_POST['sexo'] ?? null);
            $stmtPessoa->bindParam(':email', !empty($_POST['email']) ? trim($_POST['email']) : null);
            $stmtPessoa->bindParam(':telefone', !empty($telefone) ? $telefone : null);
            $stmtPessoa->bindParam(':pessoa_id', $gestor['pessoa_id']);
            $stmtPessoa->execute();
            
            // Atualizar CPF se foi alterado
            if (!empty($cpfAtual) && $cpfAtual !== $gestor['cpf']) {
                $sqlUpdateCPF = "UPDATE pessoa SET cpf = :cpf WHERE id = :pessoa_id";
                $stmtUpdateCPF = $conn->prepare($sqlUpdateCPF);
                $stmtUpdateCPF->bindParam(':cpf', $cpfAtual);
                $stmtUpdateCPF->bindParam(':pessoa_id', $gestor['pessoa_id']);
                $stmtUpdateCPF->execute();
            }
            
            // 2. Atualizar gestor
            $sqlGestorUpdate = "UPDATE gestor SET cargo = :cargo, formacao = :formacao, 
                               registro_profissional = :registro_profissional, observacoes = :observacoes, ativo = :ativo
                               WHERE id = :id";
            $stmtGestorUpdate = $conn->prepare($sqlGestorUpdate);
            $stmtGestorUpdate->bindParam(':cargo', trim($_POST['cargo'] ?? ''));
            $stmtGestorUpdate->bindParam(':formacao', !empty($_POST['formacao']) ? trim($_POST['formacao']) : null);
            $stmtGestorUpdate->bindParam(':registro_profissional', !empty($_POST['registro_profissional']) ? trim($_POST['registro_profissional']) : null);
            $stmtGestorUpdate->bindParam(':observacoes', !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null);
            $stmtGestorUpdate->bindParam(':ativo', isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1);
            $stmtGestorUpdate->bindParam(':id', $gestorId);
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
                    <div class="w-10"></div>
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formação</label>
                                <input type="text" name="formacao" id="editar_formacao" placeholder="Ex: Licenciatura em Pedagogia"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional</label>
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
                                <select name="escola_id" id="editar_escola_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione uma escola...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                <input type="password" name="senha" id="editar_senha"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formação</label>
                                <input type="text" name="formacao" id="formacao" placeholder="Ex: Licenciatura em Pedagogia"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional</label>
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
                                <select name="escola_id" id="escola_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Selecione uma escola...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                <input type="password" name="senha" id="senha" value="123456"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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

        function abrirModalNovoGestor() {
            const modal = document.getElementById('modalNovoGestor');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Limpar formulário
                document.getElementById('formNovoGestor').reset();
                document.getElementById('senha').value = '123456';
                document.getElementById('responsavel').value = '1';
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
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'cadastrar_gestor');
            
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
                    document.getElementById('senha').value = '123456';
                    document.getElementById('responsavel').value = '1';
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
                document.getElementById('editar_cargo').value = gestor.cargo || '';
                document.getElementById('editar_formacao').value = gestor.formacao || '';
                document.getElementById('editar_registro_profissional').value = gestor.registro_profissional || '';
                document.getElementById('editar_observacoes').value = gestor.observacoes || '';
                document.getElementById('editar_ativo').value = gestor.ativo !== undefined ? gestor.ativo : 1;
                document.getElementById('editar_escola_id').value = gestor.escola_id || '';
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
    </script>
</body>
</html>

