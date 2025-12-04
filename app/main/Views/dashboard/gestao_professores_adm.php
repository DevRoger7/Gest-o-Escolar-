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
    
    if ($_POST['acao'] === 'cadastrar_professor') {
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
            
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone, 'PROFESSOR', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $nome);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimento);
            $stmtPessoa->bindParam(':sexo', $sexo);
            $stmtPessoa->bindParam(':email', $email);
            $stmtPessoa->bindParam(':telefone', $telefoneVal);
            $stmtPessoa->bindParam(':criado_por', $criadoPor);
            $stmtPessoa->execute();
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar professor
            $matricula = !empty($_POST['matricula']) ? trim($_POST['matricula']) : null;
            $formacao = !empty($_POST['formacao']) ? trim($_POST['formacao']) : null;
            $especializacao = !empty($_POST['especializacao']) ? trim($_POST['especializacao']) : null;
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
                $sqlLotacao = "INSERT INTO professor_lotacao (professor_id, escola_id, inicio, carga_horaria, observacao, criado_em)
                              VALUES (:professor_id, :escola_id, CURDATE(), :carga_horaria, :observacao, NOW())";
                $stmtLotacao = $conn->prepare($sqlLotacao);
                $stmtLotacao->bindParam(':professor_id', $professorId);
                $stmtLotacao->bindParam(':escola_id', $_POST['escola_id']);
                $stmtLotacao->bindParam(':carga_horaria', !empty($_POST['carga_horaria']) ? $_POST['carga_horaria'] : null);
                $stmtLotacao->bindParam(':observacao', !empty($_POST['observacao_lotacao']) ? trim($_POST['observacao_lotacao']) : null);
                $stmtLotacao->execute();
            }
            
            $conn->commit();
            
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
            $sqlBuscar = "SELECT pr.*, p.id as pessoa_id, p.cpf, u.username
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
            
            $sqlPessoa = "UPDATE pessoa SET nome = :nome, data_nascimento = :data_nascimento, 
                         sexo = :sexo, email = :email, telefone = :telefone
                         WHERE id = :pessoa_id";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':nome', $nomeUpdate);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimentoUpdate);
            $stmtPessoa->bindParam(':sexo', $sexoUpdate);
            $stmtPessoa->bindParam(':email', $emailUpdate);
            $stmtPessoa->bindParam(':telefone', $telefoneUpdate);
            $stmtPessoa->bindParam(':pessoa_id', $professor['pessoa_id']);
            $stmtPessoa->execute();
            
            // 3. Atualizar professor
            $matriculaUpdate = !empty($_POST['matricula']) ? trim($_POST['matricula']) : null;
            $formacaoUpdate = !empty($_POST['formacao']) ? trim($_POST['formacao']) : null;
            $especializacaoUpdate = !empty($_POST['especializacao']) ? trim($_POST['especializacao']) : null;
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
            
            // Reverter soft delete (ativar novamente)
            $sqlReverter = "UPDATE professor SET ativo = 1 WHERE id = :id";
            $stmtReverter = $conn->prepare($sqlReverter);
            $stmtReverter->bindParam(':id', $professorId);
            $result = $stmtReverter->execute();
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Exclusão revertida com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao reverter exclusão do professor.');
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
    
    if ($_GET['acao'] === 'buscar_professor') {
        $professorId = $_GET['id'] ?? null;
        if (empty($professorId)) {
            echo json_encode(['success' => false, 'message' => 'ID do professor não informado']);
            exit;
        }
        
        $sql = "SELECT pr.*, p.id as pessoa_id, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, p.sexo, u.username
                FROM professor pr
                INNER JOIN pessoa p ON pr.pessoa_id = p.id
                LEFT JOIN usuario u ON u.pessoa_id = p.id
                WHERE pr.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $professorId);
        $stmt->execute();
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($professor) {
            // Formatar CPF e telefone para exibição
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
            
            // Buscar lotação atual
            $sqlLotacao = "SELECT pl.*, e.nome as escola_nome
                          FROM professor_lotacao pl
                          LEFT JOIN escola e ON pl.escola_id = e.id
                          WHERE pl.professor_id = :professor_id AND pl.fim IS NULL
                          LIMIT 1";
            $stmtLotacao = $conn->prepare($sqlLotacao);
            $stmtLotacao->bindParam(':professor_id', $professorId);
            $stmtLotacao->execute();
            $lotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
            
            if ($lotacao) {
                $professor['lotacao_escola_id'] = $lotacao['escola_id'];
                $professor['lotacao_carga_horaria'] = $lotacao['carga_horaria'];
                $professor['lotacao_observacao'] = $lotacao['observacao'];
            }
            
            echo json_encode(['success' => true, 'professor' => $professor]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Professor não encontrado']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'listar_professores') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        
        $sql = "SELECT pr.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, e.nome as escola_nome
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
        
        echo json_encode(['success' => true, 'professores' => $professores]);
        exit;
    }
}

// Buscar professores iniciais (apenas ativos)
$sqlProfessores = "SELECT pr.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, e.nome as escola_nome
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
                    <div class="w-10"></div>
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
                                            <td class="py-3 px-4"><?= htmlspecialchars($prof['escola_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($prof['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarProfessor(<?= $prof['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formação</label>
                                <input type="text" name="formacao" id="editar_formacao" placeholder="Ex: Licenciatura em Matemática"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Especialização</label>
                                <input type="text" name="especializacao" id="editar_especializacao" placeholder="Ex: Educação Especial"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional</label>
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formação</label>
                                <input type="text" name="formacao" id="formacao" placeholder="Ex: Licenciatura em Matemática"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Especialização</label>
                                <input type="text" name="especializacao" id="especializacao" placeholder="Ex: Educação Especial"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registro Profissional</label>
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

        function abrirModalNovoProfessor() {
            const modal = document.getElementById('modalNovoProfessor');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Limpar formulário
                document.getElementById('formNovoProfessor').reset();
                document.getElementById('data_admissao').value = new Date().toISOString().split('T')[0];
                document.getElementById('senha').value = '123456';
                // Limpar alertas
                document.getElementById('alertaErro').classList.add('hidden');
                document.getElementById('alertaSucesso').classList.add('hidden');
                // Atualizar preview do username
                atualizarPreviewUsername();
            }
        }
        
        function fecharModalNovoProfessor() {
            const modal = document.getElementById('modalNovoProfessor');
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
        document.getElementById('formNovoProfessor').addEventListener('submit', async function(e) {
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
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        });
        
        // Fechar modal ao clicar fora
        document.getElementById('modalNovoProfessor')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalNovoProfessor();
            }
        });

        async function editarProfessor(id) {
            try {
                // Buscar dados do professor
                const response = await fetch('?acao=buscar_professor&id=' + id);
                const data = await response.json();
                
                if (!data.success || !data.professor) {
                    alert('Erro ao carregar dados do professor: ' + (data.message || 'Professor não encontrado'));
                    return;
                }
                
                const prof = data.professor;
                
                // Preencher formulário
                document.getElementById('editar_professor_id').value = prof.id;
                document.getElementById('editar_nome').value = prof.nome || '';
                document.getElementById('editar_cpf').value = prof.cpf_formatado || prof.cpf || '';
                document.getElementById('editar_data_nascimento').value = prof.data_nascimento || '';
                document.getElementById('editar_sexo').value = prof.sexo || '';
                document.getElementById('editar_email').value = prof.email || '';
                document.getElementById('editar_telefone').value = prof.telefone_formatado || prof.telefone || '';
                document.getElementById('editar_matricula').value = prof.matricula || '';
                document.getElementById('editar_formacao').value = prof.formacao || '';
                document.getElementById('editar_especializacao').value = prof.especializacao || '';
                document.getElementById('editar_registro_profissional').value = prof.registro_profissional || '';
                document.getElementById('editar_data_admissao').value = prof.data_admissao || '';
                document.getElementById('editar_ativo').value = prof.ativo !== undefined ? prof.ativo : 1;
                document.getElementById('editar_username_preview').value = prof.username || '';
                
                // Abrir modal
                const modal = document.getElementById('modalEditarProfessor');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    // Limpar alertas
                    document.getElementById('alertaErroEditar').classList.add('hidden');
                    document.getElementById('alertaSucessoEditar').classList.add('hidden');
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
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
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


        function filtrarProfessores() {
            const busca = document.getElementById('filtro-busca').value;
            const escolaId = document.getElementById('filtro-escola').value;
            
            let url = '?acao=listar_professores';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (escolaId) url += '&escola_id=' + escolaId;
            
            fetch(url)
                .then(response => response.json())
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
                                    <td class="py-3 px-4">${prof.escola_nome || '-'}</td>
                                    <td class="py-3 px-4">${prof.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarProfessor(${prof.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
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
    
    <script>
        let professorIdExcluido = null;
        let timerContagem = null;
        let tempoRestante = 5;
        
        function abrirModalConfirmarExclusao(id, nome) {
            professorIdExcluido = id;
            document.getElementById('textoConfirmacaoExclusao').textContent = 
                `Tem certeza que deseja excluir o professor "${nome}"?\n\nEsta ação pode ser revertida nos próximos 5 segundos após a exclusão.`;
            
            const modal = document.getElementById('modalConfirmarExclusao');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
        }
        
        function fecharModalConfirmarExclusao() {
            const modal = document.getElementById('modalConfirmarExclusao');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            professorIdExcluido = null;
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
            .then(response => response.json())
            .then(data => {
                fecharModalConfirmarExclusao();
                
                if (data.success) {
                    // Abrir modal de notificação com contagem regressiva
                    abrirModalNotificacaoExclusao();
                } else {
                    // Exibir modal de erro estilizado
                    abrirModalErroExclusao(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro ao excluir professor:', error);
                abrirModalErroExclusao('Erro ao processar requisição. Por favor, tente novamente.');
                fecharModalConfirmarExclusao();
            });
        }
        
        function abrirModalNotificacaoExclusao() {
            tempoRestante = 5;
            const modal = document.getElementById('modalNotificacaoExclusao');
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
            modal.style.display = 'none';
            modal.classList.add('hidden');
            
            // Recarregar lista
            filtrarProfessores();
            professorIdExcluido = null;
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
        
        function reverterExclusaoProfessor() {
            if (!professorIdExcluido) return;
            
            // Parar contagem
            if (timerContagem) {
                clearInterval(timerContagem);
                timerContagem = null;
            }
            
            const formData = new FormData();
            formData.append('acao', 'reverter_exclusao_professor');
            formData.append('professor_id', professorIdExcluido);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                fecharModalNotificacaoExclusao();
                
                if (data.success) {
                    alert('Exclusão revertida com sucesso!');
                    filtrarProfessores();
                } else {
                    abrirModalErroExclusao('Erro ao reverter exclusão: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro ao reverter exclusão:', error);
                abrirModalErroExclusao('Erro ao processar requisição. Por favor, tente novamente.');
            });
        }
        
        // Substituir função excluirProfessor existente
        async function excluirProfessor(id) {
            try {
                const response = await fetch('?acao=buscar_professor&id=' + id);
                const data = await response.json();
                const nomeProfessor = data.success && data.professor ? data.professor.nome : 'este professor';
                
                abrirModalConfirmarExclusao(id, nomeProfessor);
            } catch (error) {
                console.error('Erro ao buscar dados do professor:', error);
                alert('Erro ao carregar dados do professor. Tente novamente.');
            }
        }
    </script>
</body>
</html>

