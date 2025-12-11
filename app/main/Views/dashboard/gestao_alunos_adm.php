
<?php
// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na tela, apenas log
ini_set('log_errors', 1);

try {
    require_once('../../Models/sessao/sessions.php');
    require_once('../../config/permissions_helper.php');
    require_once('../../config/Database.php');
    require_once('../../config/system_helper.php');
    require_once('../../Models/academico/AlunoModel.php');

    $session = new sessions();
    $session->autenticar_session();
    $session->tempo_session();

    // Verificar se é ADM
    if (!eAdm()) {
        header('Location: ../auth/login.php?erro=sem_permissao');
        exit;
    }
} catch (Exception $e) {
    error_log("Erro em gestao_alunos_adm.php: " . $e->getMessage());
    http_response_code(500);
    die("Erro ao carregar a página. Por favor, tente novamente mais tarde.");
}

$db = Database::getInstance();
$conn = $db->getConnection();
$alunoModel = new AlunoModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if (ob_get_level()) { ob_clean(); }
    header('Content-Type: application/json');
    
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
            
            // Verificar se matrícula já existe
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
            
            // Preparar dados para o model
                    $dados = [
                        'cpf' => $cpf,
                        'nome' => trim($_POST['nome'] ?? ''),
                        'data_nascimento' => $_POST['data_nascimento'] ?? null,
                        'sexo' => $_POST['sexo'] ?? null,
                        'email' => !empty($emailInformado) ? $emailInformado : null,
                        'telefone' => !empty($telefone) ? $telefone : null,
                        'matricula' => $matricula,
                        'nis' => !empty($_POST['nis']) ? preg_replace('/[^0-9]/', '', trim($_POST['nis'])) : null,
                        'responsavel_id' => !empty($_POST['responsavel_id']) ? $_POST['responsavel_id'] : null,
                        'escola_id' => !empty($_POST['escola_id']) ? $_POST['escola_id'] : null,
                'data_matricula' => $_POST['data_matricula'] ?? date('Y-m-d'),
                'situacao' => $_POST['situacao'] ?? 'MATRICULADO'
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
                echo json_encode([
                    'success' => true,
                    'message' => 'Aluno cadastrado com sucesso!',
                    'id' => $result['id'] ?? null,
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
            
            // Preparar dados para atualização
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
                'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1
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
            
            // Validar NIS (se fornecido, deve ter 11 dígitos)
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
    header('Content-Type: application/json');
    
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
    
    if ($_GET['acao'] === 'listar_alunos') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        
        $sql = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, e.nome as escola_nome
                FROM aluno a
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON a.escola_id = e.id
                WHERE a.ativo = 1";
        
        $params = [];
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND a.escola_id = :escola_id";
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
$sqlAlunos = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, e.nome as escola_nome
              FROM aluno a
              INNER JOIN pessoa p ON a.pessoa_id = p.id
              LEFT JOIN escola e ON a.escola_id = e.id
              WHERE a.ativo = 1
              ORDER BY p.nome ASC
              LIMIT 50";
$stmtAlunos = $conn->prepare($sqlAlunos);
$stmtAlunos->execute();
$alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
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
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
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
                    </div>
                </div>
                
                <!-- Informações Acadêmicas -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Acadêmicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                            <input type="text" name="matricula" id="editar_matricula"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIS (Número de Identificação Social)</label>
                            <input type="text" name="nis" id="editar_nis" maxlength="11"
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
                <h2 class="text-2xl font-bold text-gray-900">Cadastrar Novo Aluno</h2>
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
                
                <!-- Informações Pessoais -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
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
                    </div>
                </div>
                
                <!-- Informações Acadêmicas -->
                <div>
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
                            <input type="text" name="nis" id="nis" maxlength="11"
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
                
                </form>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalNovoAluno()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formNovoAluno" id="btnSalvarAluno"
                        class="px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Aluno</span>
                    <svg id="spinnerSalvar" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
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

        function abrirModalNovoAluno() {
            const modal = document.getElementById('modalNovoAluno');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
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
        
        function fecharModalNovoAluno() {
            const modal = document.getElementById('modalNovoAluno');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
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
            input.value = value.slice(0, 11);
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
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            formData.append('acao', 'cadastrar_aluno');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = 'Aluno cadastrado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
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
                document.getElementById('editar_matricula').value = aluno.matricula || '';
                document.getElementById('editar_nis').value = aluno.nis || '';
                document.getElementById('editar_escola_id').value = aluno.escola_id || '';
                document.getElementById('editar_data_matricula').value = aluno.data_matricula || '';
                document.getElementById('editar_situacao').value = aluno.situacao || 'MATRICULADO';
                document.getElementById('editar_ativo').value = aluno.ativo !== undefined ? aluno.ativo : 1;
                
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
    </script>
</body>
</html>

