<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');
require_once('../../Models/pessoas/FuncionarioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$funcionarioModel = new FuncionarioModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'cadastrar_funcionario') {
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
            
            // Preparar dados para o model
            $dados = [
                'cpf' => $cpf,
                'nome' => $nome,
                'data_nascimento' => $_POST['data_nascimento'] ?? null,
                'sexo' => $_POST['sexo'] ?? null,
                'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'telefone' => !empty($telefone) ? $telefone : null,
                'matricula' => !empty($_POST['matricula']) ? trim($_POST['matricula']) : null,
                'cargo' => trim($_POST['cargo'] ?? ''),
                'setor' => !empty($_POST['setor']) ? trim($_POST['setor']) : null,
                'data_admissao' => $_POST['data_admissao'] ?? date('Y-m-d')
            ];
            
            // 1. Criar pessoa
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone, 'FUNCIONARIO', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $dataNascimentoParam = $_POST['data_nascimento'] ?? null;
            $sexoParam = $_POST['sexo'] ?? null;
            $emailParam = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $telefoneParam = !empty($telefone) ? $telefone : null;
            $criadoPorParam = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $nome);
            $stmtPessoa->bindParam(':data_nascimento', $dataNascimentoParam);
            $stmtPessoa->bindParam(':sexo', $sexoParam);
            $stmtPessoa->bindParam(':email', $emailParam);
            $stmtPessoa->bindParam(':telefone', $telefoneParam);
            $stmtPessoa->bindParam(':criado_por', $criadoPorParam);
            $stmtPessoa->execute();
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar funcionário
            $sqlFunc = "INSERT INTO funcionario (pessoa_id, matricula, cargo, setor, data_admissao, ativo, criado_por)
                       VALUES (:pessoa_id, :matricula, :cargo, :setor, :data_admissao, 1, :criado_por)";
            $stmtFunc = $conn->prepare($sqlFunc);
            $matriculaParam = !empty($_POST['matricula']) ? trim($_POST['matricula']) : null;
            $cargoParam = trim($_POST['cargo'] ?? '');
            $setorParam = !empty($_POST['setor']) ? trim($_POST['setor']) : null;
            $dataAdmissaoParam = $_POST['data_admissao'] ?? date('Y-m-d');
            $criadoPorFuncParam = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
            $stmtFunc->bindParam(':pessoa_id', $pessoaId);
            $stmtFunc->bindParam(':matricula', $matriculaParam);
            $stmtFunc->bindParam(':cargo', $cargoParam);
            $stmtFunc->bindParam(':setor', $setorParam);
            $stmtFunc->bindParam(':data_admissao', $dataAdmissaoParam);
            $stmtFunc->bindParam(':criado_por', $criadoPorFuncParam);
            $stmtFunc->execute();
            $funcionarioId = $conn->lastInsertId();
            
            // 3. Criar usuário
            $sqlUsuario = "INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo)
                          VALUES (:pessoa_id, :username, :senha_hash, 'FUNCIONARIO', 1)";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $pessoaId);
            $stmtUsuario->bindParam(':username', $username);
            $stmtUsuario->bindParam(':senha_hash', $senhaHash);
            $stmtUsuario->execute();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Funcionário cadastrado com sucesso!',
                'id' => $funcionarioId,
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
    
    if ($_POST['acao'] === 'editar_funcionario') {
        try {
            $funcionarioId = $_POST['funcionario_id'] ?? null;
            if (empty($funcionarioId)) {
                throw new Exception('ID do funcionário não informado.');
            }
            
            // Buscar funcionário existente
            $funcionario = $funcionarioModel->buscarPorId($funcionarioId);
            if (!$funcionario) {
                throw new Exception('Funcionário não encontrado.');
            }
            
            // Preparar dados
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            
            // Validar CPF (se foi alterado)
            $cpfAtual = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            if (!empty($cpfAtual) && strlen($cpfAtual) !== 11) {
                throw new Exception('CPF inválido. Deve conter 11 dígitos.');
            }
            
            // Verificar se CPF já existe em outro funcionário
            if (!empty($cpfAtual) && $cpfAtual !== $funcionario['cpf']) {
                $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id";
                $stmtVerificar = $conn->prepare($sqlVerificarCPF);
                $stmtVerificar->bindParam(':cpf', $cpfAtual);
                $stmtVerificar->bindParam(':pessoa_id', $funcionario['pessoa_id']);
                $stmtVerificar->execute();
                if ($stmtVerificar->fetch()) {
                    throw new Exception('CPF já cadastrado para outro funcionário.');
                }
            }
            
            // Validar cargo (obrigatório)
            if (empty(trim($_POST['cargo'] ?? ''))) {
                throw new Exception('Cargo é obrigatório.');
            }
            
            // Preparar dados para atualização
            $dados = [
                'nome' => trim($_POST['nome'] ?? ''),
                'data_nascimento' => $_POST['data_nascimento'] ?? null,
                'sexo' => $_POST['sexo'] ?? null,
                'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'telefone' => !empty($telefone) ? $telefone : null,
                'matricula' => !empty($_POST['matricula']) ? trim($_POST['matricula']) : null,
                'cargo' => trim($_POST['cargo'] ?? ''),
                'setor' => !empty($_POST['setor']) ? trim($_POST['setor']) : null,
                'data_admissao' => $_POST['data_admissao'] ?? $funcionario['data_admissao'],
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
            
            // Atualizar CPF se foi alterado
            if (!empty($cpfAtual) && $cpfAtual !== $funcionario['cpf']) {
                $sqlUpdateCPF = "UPDATE pessoa SET cpf = :cpf WHERE id = :pessoa_id";
                $stmtUpdateCPF = $conn->prepare($sqlUpdateCPF);
                $stmtUpdateCPF->bindParam(':cpf', $cpfAtual);
                $stmtUpdateCPF->bindParam(':pessoa_id', $funcionario['pessoa_id']);
                $stmtUpdateCPF->execute();
            }
            
            // Usar o model para atualizar o funcionário
            $result = $funcionarioModel->atualizar($funcionarioId, $dados);
            
            if ($result['success']) {
                // Atualizar senha se fornecida
                if (!empty($_POST['senha']) && $_POST['senha'] !== '123456') {
                    $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                    $sqlSenha = "UPDATE usuario SET senha_hash = :senha_hash WHERE pessoa_id = :pessoa_id";
                    $stmtSenha = $conn->prepare($sqlSenha);
                    $stmtSenha->bindParam(':senha_hash', $senhaHash);
                    $stmtSenha->bindParam(':pessoa_id', $funcionario['pessoa_id']);
                    $stmtSenha->execute();
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Funcionário atualizado com sucesso!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao atualizar funcionário.'
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
    
    if ($_POST['acao'] === 'excluir_funcionario') {
        try {
            $funcionarioId = $_POST['funcionario_id'] ?? null;
            if (empty($funcionarioId)) {
                throw new Exception('ID do funcionário não informado.');
            }
            
            // Verificar se o funcionário existe
            $funcionario = $funcionarioModel->buscarPorId($funcionarioId);
            if (!$funcionario) {
                throw new Exception('Funcionário não encontrado.');
            }
            
            // Soft delete usando o model
            $sqlExcluir = "UPDATE funcionario SET ativo = 0 WHERE id = :id";
            $stmtExcluir = $conn->prepare($sqlExcluir);
            $stmtExcluir->bindParam(':id', $funcionarioId);
            $result = $stmtExcluir->execute();
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Funcionário excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao excluir funcionário.');
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
    
    if ($_GET['acao'] === 'buscar_funcionario') {
        $funcionarioId = $_GET['id'] ?? null;
        if (empty($funcionarioId)) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário não informado']);
            exit;
        }
        
        $funcionario = $funcionarioModel->buscarPorId($funcionarioId);
        if ($funcionario) {
            // Formatar CPF e telefone para exibição
            if (!empty($funcionario['cpf']) && strlen($funcionario['cpf']) === 11) {
                $funcionario['cpf_formatado'] = substr($funcionario['cpf'], 0, 3) . '.' . substr($funcionario['cpf'], 3, 3) . '.' . substr($funcionario['cpf'], 6, 3) . '-' . substr($funcionario['cpf'], 9, 2);
            }
            if (!empty($funcionario['telefone'])) {
                $tel = $funcionario['telefone'];
                if (strlen($tel) === 11) {
                    $funcionario['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                } elseif (strlen($tel) === 10) {
                    $funcionario['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                }
            }
            
            // Buscar username do usuário
            $sqlUsuario = "SELECT username FROM usuario WHERE pessoa_id = :pessoa_id LIMIT 1";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $funcionario['pessoa_id']);
            $stmtUsuario->execute();
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                $funcionario['username'] = $usuario['username'];
            }
            
            echo json_encode(['success' => true, 'funcionario' => $funcionario]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'listar_funcionarios') {
        $filtros = [];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        if (!empty($_GET['cargo'])) $filtros['cargo'] = $_GET['cargo'];
        
        $funcionarios = $funcionarioModel->listar($filtros);
        echo json_encode(['success' => true, 'funcionarios' => $funcionarios]);
        exit;
    }
}

$funcionarios = $funcionarioModel->listar(['ativo' => 1]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Gestão de Funcionários') ?></title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Funcionários</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Funcionários</h2>
                        <p class="text-gray-600 mt-1">Cadastre, edite e exclua funcionários do sistema</p>
                    </div>
                    <button onclick="abrirModalNovoFuncionario()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Funcionário</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome, CPF ou Matrícula..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarFuncionarios()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                            <input type="text" id="filtro-cargo" placeholder="Cargo..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarFuncionarios()">
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarFuncionarios()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Cargo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-funcionarios">
                                <?php if (empty($funcionarios)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhum funcionário encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($func['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($func['matricula'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($func['cpf'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($func['cargo'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($func['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarFuncionario(<?= $func['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirFuncionario(<?= $func['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal de Edição de Funcionário -->
    <div id="modalEditarFuncionario" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Editar Funcionário</h2>
                <button onclick="fecharModalEditarFuncionario()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formEditarFuncionario" class="space-y-6 max-w-6xl mx-auto">
                    <div id="alertaErroEditar" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucessoEditar" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <input type="hidden" name="funcionario_id" id="editar_funcionario_id">
                    
                    <!-- Informações Pessoais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" name="nome" id="editar_nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" name="cpf" id="editar_cpf" required maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" id="editar_data_nascimento" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                                <select name="sexo" id="editar_sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="editar_email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="editar_telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
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
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cargo *</label>
                                <input type="text" name="cargo" id="editar_cargo" required placeholder="Ex: Secretário, Auxiliar Administrativo"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Setor</label>
                                <input type="text" name="setor" id="editar_setor" placeholder="Ex: Secretaria, Administração"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                                <input type="date" name="data_admissao" id="editar_data_admissao"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="ativo" id="editar_ativo"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
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
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
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
                <button type="button" onclick="fecharModalEditarFuncionario()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formEditarFuncionario" id="btnSalvarEdicao"
                        class="px-6 py-3 text-white bg-teal-600 hover:bg-teal-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Alterações</span>
                    <svg id="spinnerSalvarEdicao" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Cadastro de Funcionário -->
    <div id="modalNovoFuncionario" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Cadastrar Novo Funcionário</h2>
                <button onclick="fecharModalNovoFuncionario()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formNovoFuncionario" class="space-y-6 max-w-6xl mx-auto">
                    <div id="alertaErro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <!-- Informações Pessoais -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" name="nome" id="nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" name="cpf" id="cpf" required maxlength="14"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                       oninput="formatarCPF(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" id="data_nascimento" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                                <select name="sexo" id="sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="telefone" maxlength="15"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
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
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cargo *</label>
                                <input type="text" name="cargo" id="cargo" required placeholder="Ex: Secretário, Auxiliar Administrativo"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Setor</label>
                                <input type="text" name="setor" id="setor" placeholder="Ex: Secretaria, Administração"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                                <input type="date" name="data_admissao" id="data_admissao"
                                       value="<?= date('Y-m-d') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
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
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Senha padrão: 123456 (pode ser alterada pelo funcionário após o primeiro login)</p>
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
                <button type="button" onclick="fecharModalNovoFuncionario()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formNovoFuncionario" id="btnSalvarFuncionario"
                        class="px-6 py-3 text-white bg-teal-600 hover:bg-teal-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Funcionário</span>
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

        function abrirModalNovoFuncionario() {
            const modal = document.getElementById('modalNovoFuncionario');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Limpar formulário
                document.getElementById('formNovoFuncionario').reset();
                document.getElementById('data_admissao').value = new Date().toISOString().split('T')[0];
                document.getElementById('senha').value = '123456';
                // Limpar alertas
                document.getElementById('alertaErro').classList.add('hidden');
                document.getElementById('alertaSucesso').classList.add('hidden');
                // Atualizar preview do username
                atualizarPreviewUsername();
            }
        }
        
        function fecharModalNovoFuncionario() {
            const modal = document.getElementById('modalNovoFuncionario');
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
        document.getElementById('formNovoFuncionario').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvarFuncionario');
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
            formData.append('acao', 'cadastrar_funcionario');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = `Funcionário cadastrado com sucesso! Username: ${data.username || 'gerado automaticamente'}`;
                    alertaSucesso.classList.remove('hidden');
                    
                    // Limpar formulário
                    this.reset();
                    document.getElementById('data_admissao').value = new Date().toISOString().split('T')[0];
                    document.getElementById('senha').value = '123456';
                    atualizarPreviewUsername();
                    
                    // Recarregar lista de funcionários após 1.5 segundos
                    setTimeout(() => {
                        fecharModalNovoFuncionario();
                        filtrarFuncionarios();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao cadastrar funcionário. Por favor, tente novamente.';
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
        document.getElementById('modalNovoFuncionario')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalNovoFuncionario();
            }
        });

        async function editarFuncionario(id) {
            try {
                // Buscar dados do funcionário
                const response = await fetch('?acao=buscar_funcionario&id=' + id);
                const data = await response.json();
                
                if (!data.success || !data.funcionario) {
                    alert('Erro ao carregar dados do funcionário: ' + (data.message || 'Funcionário não encontrado'));
                    return;
                }
                
                const func = data.funcionario;
                
                // Preencher formulário
                document.getElementById('editar_funcionario_id').value = func.id;
                document.getElementById('editar_nome').value = func.nome || '';
                document.getElementById('editar_cpf').value = func.cpf_formatado || func.cpf || '';
                document.getElementById('editar_data_nascimento').value = func.data_nascimento || '';
                document.getElementById('editar_sexo').value = func.sexo || '';
                document.getElementById('editar_email').value = func.email || '';
                document.getElementById('editar_telefone').value = func.telefone_formatado || func.telefone || '';
                document.getElementById('editar_matricula').value = func.matricula || '';
                document.getElementById('editar_cargo').value = func.cargo || '';
                document.getElementById('editar_setor').value = func.setor || '';
                document.getElementById('editar_data_admissao').value = func.data_admissao || '';
                document.getElementById('editar_ativo').value = func.ativo !== undefined ? func.ativo : 1;
                document.getElementById('editar_username_preview').value = func.username || '';
                
                // Abrir modal
                const modal = document.getElementById('modalEditarFuncionario');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    // Limpar alertas
                    document.getElementById('alertaErroEditar').classList.add('hidden');
                    document.getElementById('alertaSucessoEditar').classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar funcionário:', error);
                alert('Erro ao carregar dados do funcionário. Por favor, tente novamente.');
            }
        }
        
        function fecharModalEditarFuncionario() {
            const modal = document.getElementById('modalEditarFuncionario');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        // Submissão do formulário de edição
        document.getElementById('formEditarFuncionario').addEventListener('submit', async function(e) {
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
            formData.append('acao', 'editar_funcionario');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = 'Funcionário atualizado com sucesso!';
                    alertaSucesso.classList.remove('hidden');
                    
                    // Recarregar lista de funcionários após 1.5 segundos
                    setTimeout(() => {
                        fecharModalEditarFuncionario();
                        filtrarFuncionarios();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao atualizar funcionário. Por favor, tente novamente.';
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
        document.getElementById('modalEditarFuncionario')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalEditarFuncionario();
            }
        });

        async function excluirFuncionario(id) {
            // Buscar nome do funcionário para exibir na confirmação
            try {
                const response = await fetch('?acao=buscar_funcionario&id=' + id);
                const data = await response.json();
                const nomeFuncionario = data.success && data.funcionario ? data.funcionario.nome : 'este funcionário';
                
                // Modal de confirmação customizado
                if (confirm(`Tem certeza que deseja excluir o funcionário "${nomeFuncionario}"?\n\nEsta ação não pode ser desfeita. O funcionário será marcado como inativo no sistema.`)) {
                    // Mostrar loading
                    const btnExcluir = event.target;
                    const originalText = btnExcluir.textContent;
                    btnExcluir.disabled = true;
                    btnExcluir.textContent = 'Excluindo...';
                    
                    try {
                        const formData = new FormData();
                        formData.append('acao', 'excluir_funcionario');
                        formData.append('funcionario_id', id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert('Funcionário excluído com sucesso!');
                            // Recarregar lista
                            filtrarFuncionarios();
                        } else {
                            alert('Erro ao excluir funcionário: ' + (data.message || 'Erro desconhecido'));
                        }
                    } catch (error) {
                        console.error('Erro ao excluir funcionário:', error);
                        alert('Erro ao processar requisição. Por favor, tente novamente.');
                    } finally {
                        btnExcluir.disabled = false;
                        btnExcluir.textContent = originalText;
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar dados do funcionário:', error);
                // Se não conseguir buscar o nome, usar confirmação simples
                if (confirm('Tem certeza que deseja excluir este funcionário?\n\nEsta ação não pode ser desfeita.')) {
                    const formData = new FormData();
                    formData.append('acao', 'excluir_funcionario');
                    formData.append('funcionario_id', id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Funcionário excluído com sucesso!');
                            filtrarFuncionarios();
                        } else {
                            alert('Erro ao excluir funcionário: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao processar requisição. Por favor, tente novamente.');
                    });
                }
            }
        }

        function filtrarFuncionarios() {
            const busca = document.getElementById('filtro-busca').value;
            const cargo = document.getElementById('filtro-cargo').value;
            
            let url = '?acao=listar_funcionarios';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (cargo) url += '&cargo=' + encodeURIComponent(cargo);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-funcionarios');
                        tbody.innerHTML = '';
                        
                        if (data.funcionarios.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhum funcionário encontrado.</td></tr>';
                            return;
                        }
                        
                        data.funcionarios.forEach(func => {
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${func.nome}</td>
                                    <td class="py-3 px-4">${func.matricula || '-'}</td>
                                    <td class="py-3 px-4">${func.cpf || '-'}</td>
                                    <td class="py-3 px-4">${func.cargo || '-'}</td>
                                    <td class="py-3 px-4">${func.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarFuncionario(${func.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirFuncionario(${func.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
                    console.error('Erro ao filtrar funcionários:', error);
                });
        }
    </script>
</body>
</html>

