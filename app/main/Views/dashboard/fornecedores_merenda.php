<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/FornecedorModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Permitir acesso para ADM (geral) e ADM_MERENDA
if (!isset($_SESSION['tipo']) || (!eAdm() && strtolower($_SESSION['tipo']) !== 'adm_merenda')) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$fornecedorModel = new FornecedorModel();

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_fornecedor') {
        // Validar campos obrigatórios
        $nome = trim($_POST['nome'] ?? '');
        $cnpjCpf = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? ''); // Remover formatação
        $tipoFornecedor = trim($_POST['tipo_fornecedor'] ?? '');
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''); // Remover formatação
        $email = trim($_POST['email'] ?? '');
        
        if (empty($nome)) {
            echo json_encode(['success' => false, 'message' => 'O campo Nome é obrigatório.']);
            exit;
        }
        // Validar CNPJ/CPF apenas se preenchido (é opcional)
        if (!empty($cnpjCpf) && strlen($cnpjCpf) !== 11 && strlen($cnpjCpf) !== 14) {
            echo json_encode(['success' => false, 'message' => 'O CNPJ deve conter 14 dígitos ou o CPF deve conter 11 dígitos.']);
            exit;
        }
        if (empty($tipoFornecedor)) {
            echo json_encode(['success' => false, 'message' => 'O campo Tipo é obrigatório.']);
            exit;
        }
        if (empty($telefone) || (strlen($telefone) !== 10 && strlen($telefone) !== 11)) {
            echo json_encode(['success' => false, 'message' => 'O campo Telefone é obrigatório e deve conter 10 ou 11 dígitos.']);
            exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'O campo Email é obrigatório e deve ser um email válido.']);
            exit;
        }
        
        $dados = [
            'nome' => $nome,
            'razao_social' => trim($_POST['razao_social'] ?? '') ?: null,
            'cnpj' => $cnpjCpf ?: null,
            'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
            'endereco' => $_POST['endereco'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'complemento' => $_POST['complemento'] ?? null,
            'bairro' => $_POST['bairro'] ?? null,
            'cidade' => $_POST['cidade'] ?? null,
            'estado' => $_POST['estado'] ?? null,
            'cep' => $_POST['cep'] ?? null,
            'telefone' => $telefone,
            'telefone_secundario' => $_POST['telefone_secundario'] ?? null,
            'email' => $email,
            'contato' => null, // Removido - não é mais necessário
            'tipo_fornecedor' => $tipoFornecedor,
            'observacoes' => trim($_POST['observacoes'] ?? '') ?: null
        ];
        
        $resultado = $fornecedorModel->criar($dados);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'atualizar_fornecedor') {
        $id = $_POST['id'] ?? null;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID do fornecedor não informado.']);
            exit;
        }
        
        // Validar campos obrigatórios
        $nome = trim($_POST['nome'] ?? '');
        $cnpjCpf = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? ''); // Remover formatação
        $tipoFornecedor = trim($_POST['tipo_fornecedor'] ?? '');
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''); // Remover formatação
        $email = trim($_POST['email'] ?? '');
        
        if (empty($nome)) {
            echo json_encode(['success' => false, 'message' => 'O campo Nome é obrigatório.']);
            exit;
        }
        // Validar CNPJ/CPF apenas se preenchido (é opcional)
        if (!empty($cnpjCpf) && strlen($cnpjCpf) !== 11 && strlen($cnpjCpf) !== 14) {
            echo json_encode(['success' => false, 'message' => 'O CNPJ deve conter 14 dígitos ou o CPF deve conter 11 dígitos.']);
            exit;
        }
        if (empty($tipoFornecedor)) {
            echo json_encode(['success' => false, 'message' => 'O campo Tipo é obrigatório.']);
            exit;
        }
        if (empty($telefone) || (strlen($telefone) !== 10 && strlen($telefone) !== 11)) {
            echo json_encode(['success' => false, 'message' => 'O campo Telefone é obrigatório e deve conter 10 ou 11 dígitos.']);
            exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'O campo Email é obrigatório e deve ser um email válido.']);
            exit;
        }
        
        $dados = [
            'nome' => $nome,
            'razao_social' => trim($_POST['razao_social'] ?? '') ?: null,
            'cnpj' => $cnpjCpf ?: null,
            'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
            'endereco' => $_POST['endereco'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'complemento' => $_POST['complemento'] ?? null,
            'bairro' => $_POST['bairro'] ?? null,
            'cidade' => $_POST['cidade'] ?? null,
            'estado' => $_POST['estado'] ?? null,
            'cep' => $_POST['cep'] ?? null,
            'telefone' => $telefone,
            'telefone_secundario' => $_POST['telefone_secundario'] ?? null,
            'email' => $email,
            'contato' => null, // Removido - não é mais necessário
            'tipo_fornecedor' => $tipoFornecedor,
            'observacoes' => trim($_POST['observacoes'] ?? '') ?: null,
            'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1
        ];
        
        $resultado = $fornecedorModel->atualizar($id, $dados);
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Fornecedor atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar fornecedor.']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_fornecedores') {
        $filtros = [];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        if (!empty($_GET['tipo_fornecedor'])) $filtros['tipo_fornecedor'] = $_GET['tipo_fornecedor'];
        if (isset($_GET['ativo'])) $filtros['ativo'] = $_GET['ativo'];
        
        $fornecedores = $fornecedorModel->listar($filtros);
        echo json_encode(['success' => true, 'fornecedores' => $fornecedores]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_fornecedor') {
        $id = $_GET['id'] ?? null;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID do fornecedor não informado']);
            exit;
        }
        
        $fornecedor = $fornecedorModel->buscarPorId($id);
        if ($fornecedor) {
            // Formatar CNPJ/CPF e telefone para exibição
            if (!empty($fornecedor['cnpj'])) {
                $cnpj = $fornecedor['cnpj'];
                if (strlen($cnpj) === 11) {
                    // CPF
                    $fornecedor['cnpj_formatado'] = substr($cnpj, 0, 3) . '.' . substr($cnpj, 3, 3) . '.' . substr($cnpj, 6, 3) . '-' . substr($cnpj, 9, 2);
                } elseif (strlen($cnpj) === 14) {
                    // CNPJ
                    $fornecedor['cnpj_formatado'] = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
                }
            }
            if (!empty($fornecedor['telefone'])) {
                $tel = $fornecedor['telefone'];
                if (strlen($tel) === 11) {
                    $fornecedor['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                } elseif (strlen($tel) === 10) {
                    $fornecedor['telefone_formatado'] = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                }
            }
            echo json_encode(['success' => true, 'fornecedor' => $fornecedor]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado']);
        }
        exit;
    }
}

// Buscar fornecedores iniciais
$fornecedores = $fornecedorModel->listar(['ativo' => 1]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - SIGEA</title>
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
    </style>
</head>
<body class="bg-gray-50">
    <?php 
    // Mostrar sidebar correta baseada no tipo de usuário
    if (eAdm()) {
        include 'components/sidebar_adm.php';
    } else {
        include 'components/sidebar_merenda.php';
    }
    ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Fornecedores</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM' || $_SESSION['tipo'] === 'ADM_MERENDA') { ?>
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
                        <h2 class="text-2xl font-bold text-gray-900">Fornecedores</h2>
                        <p class="text-gray-600 mt-1">Cadastre e gerencie fornecedores de alimentos</p>
                    </div>
                    <button onclick="abrirModalNovoFornecedor()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Fornecedor</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome, CNPJ ou CPF..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" onkeyup="filtrarFornecedores()" maxlength="18">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select id="filtro-tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarFornecedores()">
                                <option value="">Todos</option>
                                <option value="ALIMENTOS">Alimentos</option>
                                <option value="BEBIDAS">Bebidas</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarFornecedores()">
                                <option value="1">Ativos</option>
                                <option value="0">Inativos</option>
                                <option value="">Todos</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Fornecedores -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div id="lista-fornecedores" class="space-y-4">
                        <?php if (empty($fornecedores)): ?>
                            <div class="text-center py-12">
                                <p class="text-gray-600">Nenhum fornecedor encontrado.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($fornecedor['nome']) ?></h3>
                                            <?php if ($fornecedor['razao_social']): ?>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($fornecedor['razao_social']) ?></p>
                                            <?php endif; ?>
                                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                                                <?php if ($fornecedor['cnpj']): ?>
                                                    <span>CNPJ: <?= htmlspecialchars($fornecedor['cnpj']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($fornecedor['telefone']): ?>
                                                    <span>Tel: <?= htmlspecialchars($fornecedor['telefone']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($fornecedor['email']): ?>
                                                    <span>Email: <?= htmlspecialchars($fornecedor['email']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $fornecedor['ativo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <?= $fornecedor['ativo'] ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                            <button onclick="editarFornecedor(<?= $fornecedor['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Editar Fornecedor -->
    <div id="modal-editar-fornecedor" class="fixed inset-0 bg-gray-100 z-[60] hidden flex flex-col">
        <div class="bg-white border-b border-gray-200 p-6 flex items-center justify-between shadow-sm">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Editar Fornecedor</h3>
                <p class="text-sm text-gray-500 mt-1">Atualize os dados do fornecedor</p>
            </div>
            <button onclick="fecharModalEditarFornecedor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-editar-fornecedor" class="space-y-6">
                    <input type="hidden" id="editar-fornecedor-id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome <span class="text-red-500">*</span></label>
                            <input type="text" id="editar-fornecedor-nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="Nome do fornecedor">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Razão Social <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="text" id="editar-fornecedor-razao-social" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="Razão social (se houver)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CNPJ/CPF <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="text" id="editar-fornecedor-cnpj" maxlength="18" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="00.000.000/0000-00 ou 000.000.000-00">
                            <p class="text-xs text-gray-500 mt-1">Informe CNPJ (14 dígitos) para empresa ou CPF (11 dígitos) para pessoa física</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo <span class="text-red-500">*</span></label>
                            <select id="editar-fornecedor-tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                <option value="">Selecione...</option>
                                <option value="ALIMENTOS">Alimentos</option>
                                <option value="BEBIDAS">Bebidas</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone <span class="text-red-500">*</span></label>
                            <input type="text" id="editar-fornecedor-telefone" required maxlength="15" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="(00) 00000-0000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="editar-fornecedor-email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="email@exemplo.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="editar-fornecedor-ativo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações <span class="text-gray-400 text-xs">(opcional)</span></label>
                        <textarea id="editar-fornecedor-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="Observações adicionais sobre o fornecedor"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-white border-t border-gray-200 p-6 shadow-sm">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalEditarFornecedor()" class="flex-1 px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEdicaoFornecedor()" class="flex-1 px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors">
                    Salvar Alterações
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Novo Fornecedor -->
    <div id="modal-novo-fornecedor" class="fixed inset-0 bg-gray-100 z-[60] hidden flex flex-col">
        <div class="bg-white border-b border-gray-200 p-6 flex items-center justify-between shadow-sm">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Novo Fornecedor</h3>
                <p class="text-sm text-gray-500 mt-1">Preencha os dados do fornecedor</p>
            </div>
            <button onclick="fecharModalNovoFornecedor()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-fornecedor" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome <span class="text-red-500">*</span></label>
                            <input type="text" id="fornecedor-nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="Nome do fornecedor">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Razão Social <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="text" id="fornecedor-razao-social" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="Razão social (se houver)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CNPJ/CPF <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="text" id="fornecedor-cnpj" maxlength="18" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="00.000.000/0000-00 ou 000.000.000-00">
                            <p class="text-xs text-gray-500 mt-1">Informe CNPJ (14 dígitos) para empresa ou CPF (11 dígitos) para pessoa física</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo <span class="text-red-500">*</span></label>
                            <select id="fornecedor-tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                <option value="">Selecione...</option>
                                <option value="ALIMENTOS">Alimentos</option>
                                <option value="BEBIDAS">Bebidas</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone <span class="text-red-500">*</span></label>
                            <input type="text" id="fornecedor-telefone" required maxlength="15" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="(00) 00000-0000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="fornecedor-email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="email@exemplo.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações <span class="text-gray-400 text-xs">(opcional)</span></label>
                        <textarea id="fornecedor-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors" placeholder="Observações adicionais sobre o fornecedor"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-white border-t border-gray-200 p-6 shadow-sm">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovoFornecedor()" class="flex-1 px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarFornecedor()" class="flex-1 px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors">
                    Salvar Fornecedor
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

        // Aplicar máscara no campo de busca quando digitar CNPJ/CPF
        document.addEventListener('DOMContentLoaded', function() {
            const buscaInput = document.getElementById('filtro-busca');
            if (buscaInput) {
                buscaInput.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, '');
                    // Se tiver apenas números e mais de 3 dígitos, aplicar máscara
                    if (value.length > 3 && value.length <= 14) {
                        // Se tiver 11 dígitos ou menos, aplicar máscara de CPF
                        if (value.length <= 11) {
                            value = value.replace(/^(\d{3})(\d)/, '$1.$2');
                            value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                            value = value.replace(/\.(\d{3})(\d)/, '.$1-$2');
                        } else {
                            // Se tiver mais de 11 dígitos, aplicar máscara de CNPJ
                            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                            value = value.replace(/(\d{4})(\d)/, '$1-$2');
                        }
                        this.value = value;
                    }
                });
            }
        });

        function fecharModalNovoFornecedor() {
            document.getElementById('modal-novo-fornecedor').classList.add('hidden');
        }

        // Máscara de CNPJ ou CPF
        function aplicarMascaraCNPJCPF(input) {
            let value = input.value.replace(/\D/g, '');
            // Se tiver 11 dígitos ou menos, aplicar máscara de CPF
            if (value.length <= 11) {
                value = value.replace(/^(\d{3})(\d)/, '$1.$2');
                value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1-$2');
            } else {
                // Se tiver mais de 11 dígitos, aplicar máscara de CNPJ
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            input.value = value;
        }

        // Máscara de Telefone
        function aplicarMascaraTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
            }
            input.value = value;
        }

        // Aplicar máscaras quando o modal abrir
        function abrirModalNovoFornecedor() {
            document.getElementById('modal-novo-fornecedor').classList.remove('hidden');
            document.getElementById('form-fornecedor').reset();
            
            // Aplicar máscaras nos campos
            const cnpjInput = document.getElementById('fornecedor-cnpj');
            const telefoneInput = document.getElementById('fornecedor-telefone');
            
            cnpjInput.addEventListener('input', function() {
                aplicarMascaraCNPJCPF(this);
            });
            
            telefoneInput.addEventListener('input', function() {
                aplicarMascaraTelefone(this);
            });
        }

        function salvarFornecedor() {
            // Validar campos obrigatórios
            const nome = document.getElementById('fornecedor-nome').value.trim();
            const cnpjCpf = document.getElementById('fornecedor-cnpj').value.replace(/\D/g, ''); // Remover formatação
            const tipo = document.getElementById('fornecedor-tipo').value;
            const telefone = document.getElementById('fornecedor-telefone').value.replace(/\D/g, ''); // Remover formatação
            const email = document.getElementById('fornecedor-email').value.trim();
            
            // Validação client-side
            if (!nome) {
                alert('Por favor, preencha o campo Nome.');
                document.getElementById('fornecedor-nome').focus();
                return;
            }
            // Validar CNPJ/CPF apenas se preenchido (é opcional)
            if (cnpjCpf && cnpjCpf.length !== 11 && cnpjCpf.length !== 14) {
                alert('Por favor, preencha o CNPJ (14 dígitos) ou CPF (11 dígitos) corretamente.');
                document.getElementById('fornecedor-cnpj').focus();
                return;
            }
            if (!tipo) {
                alert('Por favor, selecione o Tipo de fornecedor.');
                document.getElementById('fornecedor-tipo').focus();
                return;
            }
            if (!telefone || (telefone.length !== 10 && telefone.length !== 11)) {
                alert('Por favor, preencha o Telefone corretamente.');
                document.getElementById('fornecedor-telefone').focus();
                return;
            }
            if (!email) {
                alert('Por favor, preencha o campo Email.');
                document.getElementById('fornecedor-email').focus();
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'criar_fornecedor');
            formData.append('nome', nome);
            formData.append('razao_social', document.getElementById('fornecedor-razao-social').value.trim() || null);
            formData.append('cnpj', cnpjCpf || null);
            formData.append('tipo_fornecedor', tipo);
            formData.append('telefone', telefone);
            formData.append('email', email);
            formData.append('observacoes', document.getElementById('fornecedor-observacoes').value.trim() || null);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Fornecedor criado com sucesso!');
                    fecharModalNovoFornecedor();
                    filtrarFornecedores();
                } else {
                    alert('Erro ao criar fornecedor: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao criar fornecedor.');
            });
        }

        function filtrarFornecedores() {
            let busca = document.getElementById('filtro-busca').value;
            const tipo = document.getElementById('filtro-tipo').value;
            const status = document.getElementById('filtro-status').value;
            
            // Remover formatação de CNPJ/CPF para busca (manter apenas números)
            // Mas manter o texto se for nome
            const buscaNumeros = busca.replace(/\D/g, '');
            if (buscaNumeros.length >= 11) {
                // Se tiver muitos números, provavelmente é CNPJ/CPF, usar sem formatação
                busca = buscaNumeros;
            }
            
            let url = '?acao=listar_fornecedores';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (tipo) url += '&tipo_fornecedor=' + tipo;
            if (status !== '') url += '&ativo=' + status;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('lista-fornecedores');
                        container.innerHTML = '';
                        
                        if (data.fornecedores.length === 0) {
                            container.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">Nenhum fornecedor encontrado.</p></div>';
                            return;
                        }
                        
                        data.fornecedores.forEach(fornecedor => {
                            container.innerHTML += `
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900">${fornecedor.nome}</h3>
                                            ${fornecedor.razao_social ? `<p class="text-sm text-gray-600">${fornecedor.razao_social}</p>` : ''}
                                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-500">
                                                ${fornecedor.cnpj ? `<span>CNPJ: ${fornecedor.cnpj}</span>` : ''}
                                                ${fornecedor.telefone ? `<span>Tel: ${fornecedor.telefone}</span>` : ''}
                                                ${fornecedor.email ? `<span>Email: ${fornecedor.email}</span>` : ''}
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium ${fornecedor.ativo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                                ${fornecedor.ativo ? 'Ativo' : 'Inativo'}
                                            </span>
                                            <button onclick="editarFornecedor(${fornecedor.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar fornecedores:', error);
                });
        }

        function editarFornecedor(id) {
            fetch('?acao=buscar_fornecedor&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.fornecedor) {
                        const f = data.fornecedor;
                        
                        document.getElementById('editar-fornecedor-id').value = f.id;
                        document.getElementById('editar-fornecedor-nome').value = f.nome || '';
                        document.getElementById('editar-fornecedor-razao-social').value = f.razao_social || '';
                        document.getElementById('editar-fornecedor-cnpj').value = f.cnpj_formatado || f.cnpj || '';
                        document.getElementById('editar-fornecedor-tipo').value = f.tipo_fornecedor || '';
                        document.getElementById('editar-fornecedor-telefone').value = f.telefone_formatado || f.telefone || '';
                        document.getElementById('editar-fornecedor-email').value = f.email || '';
                        document.getElementById('editar-fornecedor-ativo').value = f.ativo || '1';
                        document.getElementById('editar-fornecedor-observacoes').value = f.observacoes || '';
                        
                        document.getElementById('modal-editar-fornecedor').classList.remove('hidden');
                        
                        setTimeout(() => {
                            const cnpjInputEdit = document.getElementById('editar-fornecedor-cnpj');
                            const telefoneInputEdit = document.getElementById('editar-fornecedor-telefone');
                            
                            if (cnpjInputEdit && !cnpjInputEdit.dataset.mascaraAplicada) {
                                cnpjInputEdit.addEventListener('input', function() {
                                    aplicarMascaraCNPJCPF(this);
                                });
                                cnpjInputEdit.dataset.mascaraAplicada = 'true';
                            }
                            
                            if (telefoneInputEdit && !telefoneInputEdit.dataset.mascaraAplicada) {
                                telefoneInputEdit.addEventListener('input', function() {
                                    aplicarMascaraTelefone(this);
                                });
                                telefoneInputEdit.dataset.mascaraAplicada = 'true';
                            }
                        }, 100);
                    } else {
                        alert('Erro ao carregar dados do fornecedor: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados do fornecedor.');
                });
        }
        
        function fecharModalEditarFornecedor() {
            document.getElementById('modal-editar-fornecedor').classList.add('hidden');
            document.getElementById('form-editar-fornecedor').reset();
            
            const cnpjInput = document.getElementById('editar-fornecedor-cnpj');
            const telefoneInput = document.getElementById('editar-fornecedor-telefone');
            if (cnpjInput) delete cnpjInput.dataset.mascaraAplicada;
            if (telefoneInput) delete telefoneInput.dataset.mascaraAplicada;
        }
        
        function salvarEdicaoFornecedor() {
            const id = document.getElementById('editar-fornecedor-id').value;
            if (!id) {
                alert('ID do fornecedor não encontrado.');
                return;
            }
            
            const nome = document.getElementById('editar-fornecedor-nome').value.trim();
            const cnpjCpf = document.getElementById('editar-fornecedor-cnpj').value.replace(/\D/g, ''); // Remover formatação
            const tipo = document.getElementById('editar-fornecedor-tipo').value;
            const telefone = document.getElementById('editar-fornecedor-telefone').value.replace(/\D/g, ''); // Remover formatação
            const email = document.getElementById('editar-fornecedor-email').value.trim();
            
            if (!nome) {
                alert('Por favor, preencha o campo Nome.');
                document.getElementById('editar-fornecedor-nome').focus();
                return;
            }
            if (cnpjCpf && cnpjCpf.length !== 11 && cnpjCpf.length !== 14) {
                alert('Por favor, preencha o CNPJ (14 dígitos) ou CPF (11 dígitos) corretamente.');
                document.getElementById('editar-fornecedor-cnpj').focus();
                return;
            }
            if (!tipo) {
                alert('Por favor, selecione o Tipo de fornecedor.');
                document.getElementById('editar-fornecedor-tipo').focus();
                return;
            }
            if (!telefone || (telefone.length !== 10 && telefone.length !== 11)) {
                alert('Por favor, preencha o Telefone corretamente.');
                document.getElementById('editar-fornecedor-telefone').focus();
                return;
            }
            if (!email) {
                alert('Por favor, preencha o campo Email.');
                document.getElementById('editar-fornecedor-email').focus();
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'atualizar_fornecedor');
            formData.append('id', id);
            formData.append('nome', nome);
            formData.append('razao_social', document.getElementById('editar-fornecedor-razao-social').value.trim() || null);
            formData.append('cnpj', cnpjCpf || null);
            formData.append('tipo_fornecedor', tipo);
            formData.append('telefone', telefone);
            formData.append('email', email);
            formData.append('ativo', document.getElementById('editar-fornecedor-ativo').value);
            formData.append('observacoes', document.getElementById('editar-fornecedor-observacoes').value.trim() || null);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Fornecedor atualizado com sucesso!');
                    fecharModalEditarFornecedor();
                    filtrarFornecedores();
                } else {
                    alert('Erro ao atualizar fornecedor: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar fornecedor.');
            });
        }
    </script>
</body>
</html>

