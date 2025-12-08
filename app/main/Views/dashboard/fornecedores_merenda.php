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
        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'razao_social' => $_POST['razao_social'] ?? null,
            'cnpj' => $_POST['cnpj'] ?? null,
            'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
            'endereco' => $_POST['endereco'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'complemento' => $_POST['complemento'] ?? null,
            'bairro' => $_POST['bairro'] ?? null,
            'cidade' => $_POST['cidade'] ?? null,
            'estado' => $_POST['estado'] ?? null,
            'cep' => $_POST['cep'] ?? null,
            'telefone' => $_POST['telefone'] ?? null,
            'telefone_secundario' => $_POST['telefone_secundario'] ?? null,
            'email' => $_POST['email'] ?? null,
            'contato' => $_POST['contato'] ?? null,
            'tipo_fornecedor' => $_POST['tipo_fornecedor'] ?? 'ALIMENTOS',
            'observacoes' => $_POST['observacoes'] ?? null
        ];
        
        $resultado = $fornecedorModel->criar($dados);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'atualizar_fornecedor') {
        $id = $_POST['id'] ?? null;
        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'razao_social' => $_POST['razao_social'] ?? null,
            'cnpj' => $_POST['cnpj'] ?? null,
            'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
            'endereco' => $_POST['endereco'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'complemento' => $_POST['complemento'] ?? null,
            'bairro' => $_POST['bairro'] ?? null,
            'cidade' => $_POST['cidade'] ?? null,
            'estado' => $_POST['estado'] ?? null,
            'cep' => $_POST['cep'] ?? null,
            'telefone' => $_POST['telefone'] ?? null,
            'telefone_secundario' => $_POST['telefone_secundario'] ?? null,
            'email' => $_POST['email'] ?? null,
            'contato' => $_POST['contato'] ?? null,
            'tipo_fornecedor' => $_POST['tipo_fornecedor'] ?? 'ALIMENTOS',
            'observacoes' => $_POST['observacoes'] ?? null,
            'ativo' => $_POST['ativo'] ?? 1
        ];
        
        $resultado = $fornecedorModel->atualizar($id, $dados);
        echo json_encode(['success' => $resultado]);
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
                    <button onclick="abrirModalNovoFornecedor()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
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
                            <input type="text" id="filtro-busca" placeholder="Nome, CNPJ..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarFornecedores()">
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
    
    <!-- Modal Novo Fornecedor -->
    <div id="modal-novo-fornecedor" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <div class="bg-purple-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Novo Fornecedor</h3>
            <button onclick="fecharModalNovoFornecedor()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-fornecedor" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                            <input type="text" id="fornecedor-nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Razão Social</label>
                            <input type="text" id="fornecedor-razao-social" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CNPJ</label>
                            <input type="text" id="fornecedor-cnpj" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select id="fornecedor-tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="ALIMENTOS">Alimentos</option>
                                <option value="BEBIDAS">Bebidas</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                            <input type="text" id="fornecedor-telefone" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="fornecedor-email" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contato</label>
                            <input type="text" id="fornecedor-contato" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea id="fornecedor-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovoFornecedor()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarFornecedor()" class="flex-1 px-6 py-3 text-white bg-purple-600 hover:bg-purple-700 rounded-lg font-medium transition-colors">
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

        function abrirModalNovoFornecedor() {
            document.getElementById('modal-novo-fornecedor').classList.remove('hidden');
            document.getElementById('form-fornecedor').reset();
        }

        function fecharModalNovoFornecedor() {
            document.getElementById('modal-novo-fornecedor').classList.add('hidden');
        }

        function salvarFornecedor() {
            const formData = new FormData();
            formData.append('acao', 'criar_fornecedor');
            formData.append('nome', document.getElementById('fornecedor-nome').value);
            formData.append('razao_social', document.getElementById('fornecedor-razao-social').value);
            formData.append('cnpj', document.getElementById('fornecedor-cnpj').value);
            formData.append('tipo_fornecedor', document.getElementById('fornecedor-tipo').value);
            formData.append('telefone', document.getElementById('fornecedor-telefone').value);
            formData.append('email', document.getElementById('fornecedor-email').value);
            formData.append('contato', document.getElementById('fornecedor-contato').value);
            formData.append('observacoes', document.getElementById('fornecedor-observacoes').value);
            
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
            const busca = document.getElementById('filtro-busca').value;
            const tipo = document.getElementById('filtro-tipo').value;
            const status = document.getElementById('filtro-status').value;
            
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
            alert('Funcionalidade de edição em desenvolvimento. ID: ' + id);
        }
    </script>
</body>
</html>

