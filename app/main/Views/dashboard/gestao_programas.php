<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/ProgramaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$programaModel = new ProgramaModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_programa') {
        try {
            // Validar nome obrigatório
            if (empty(trim($_POST['nome'] ?? ''))) {
                throw new Exception('Nome do programa é obrigatório.');
            }
            
            $dados = [
                'nome' => trim($_POST['nome']),
                'descricao' => !empty($_POST['descricao']) ? trim($_POST['descricao']) : null,
                'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1
            ];
            
            $resultado = $programaModel->criar($dados);
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'editar_programa') {
        try {
            // Validar nome obrigatório
            if (empty(trim($_POST['nome'] ?? ''))) {
                throw new Exception('Nome do programa é obrigatório.');
            }
            
            $id = (int)$_POST['id'];
            $dados = [
                'nome' => trim($_POST['nome']),
                'descricao' => !empty($_POST['descricao']) ? trim($_POST['descricao']) : null,
                'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1
            ];
            
            $resultado = $programaModel->atualizar($id, $dados);
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_programa') {
        $id = (int)$_POST['id'];
        $resultado = $programaModel->excluir($id);
        echo json_encode($resultado);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_programas') {
        $filtros = [];
        if (!empty($_GET['busca'])) {
            $filtros['busca'] = $_GET['busca'];
        }
        $programas = $programaModel->listar($filtros);
        echo json_encode(['success' => true, 'programas' => $programas]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_programa' && isset($_GET['id'])) {
        $programa = $programaModel->buscarPorId($_GET['id']);
        if ($programa) {
            echo json_encode(['success' => true, 'programa' => $programa]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Programa não encontrado.']);
        }
        exit;
    }
}

$programas = $programaModel->listar();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Programas - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Programas</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="hidden lg:block">
                            <div class="text-right px-4 py-2">
                                <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                <p class="text-xs text-gray-500">Órgão Central</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Programas Educacionais</h2>
                        <p class="text-gray-600 mt-1">Crie, edite e gerencie programas educacionais que as escolas participam ou podem participar</p>
                    </div>
                    <button onclick="abrirModalNovoPrograma()" class="bg-primary-green hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Criar Programa</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome ou descrição..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarProgramas()">
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarProgramas()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Descrição</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Criado em</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-programas">
                                <?php if (empty($programas)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhum programa encontrado. Clique em "Criar Programa" para adicionar um novo.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($programas as $programa): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars($programa['nome']) ?></td>
                                            <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars(substr($programa['descricao'] ?? '-', 0, 100)) ?><?= strlen($programa['descricao'] ?? '') > 100 ? '...' : '' ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs <?= $programa['ativo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                    <?= $programa['ativo'] ? 'Ativo' : 'Inativo' ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-500">
                                                <?= date('d/m/Y', strtotime($programa['criado_em'])) ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarPrograma(<?= $programa['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirPrograma(<?= $programa['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal de Novo Programa -->
    <div id="modalNovoPrograma" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">Criar Programa</h3>
                <button onclick="fecharModalNovoPrograma()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="formNovoPrograma" class="p-6 space-y-6">
                <div>
                    <label for="nova_nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Programa <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nova_nome" name="nome" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                           placeholder="Ex: Programa Mais Educação">
                </div>
                
                <div>
                    <label for="nova_descricao" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea id="nova_descricao" name="descricao" rows="4"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                           placeholder="Descreva o programa educacional..."></textarea>
                </div>
                
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="nova_ativo" name="ativo" value="1" checked
                               class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                        <span class="text-sm font-medium text-gray-700">Programa Ativo</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Desmarque para criar o programa como inativo</p>
                </div>
                
                <div id="mensagem-nova" class="hidden"></div>
                
                <div class="flex space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="fecharModalNovoPrograma()"
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 text-white bg-primary-green hover:bg-green-700 rounded-lg font-medium transition-colors duration-200">
                        Criar Programa
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Edição de Programa -->
    <div id="modalEditarPrograma" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">Editar Programa</h3>
                <button onclick="fecharModalEditarPrograma()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="formEditarPrograma" class="p-6 space-y-6">
                <input type="hidden" id="edit_programa_id" name="id">
                
                <div>
                    <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Programa <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_nome" name="nome" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                           placeholder="Ex: Programa Mais Educação">
                </div>
                
                <div>
                    <label for="edit_descricao" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea id="edit_descricao" name="descricao" rows="4"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                           placeholder="Descreva o programa educacional..."></textarea>
                </div>
                
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="edit_ativo" name="ativo" value="1" checked
                               class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                        <span class="text-sm font-medium text-gray-700">Programa Ativo</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Desmarque para desativar o programa</p>
                </div>
                
                <div id="mensagem-edicao" class="hidden"></div>
                
                <div class="flex space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="fecharModalEditarPrograma()"
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 text-white bg-primary-green hover:bg-green-700 rounded-lg font-medium transition-colors duration-200">
                        Salvar Alterações
                    </button>
                </div>
            </form>
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

        function abrirModalNovoPrograma() {
            document.getElementById('formNovoPrograma').reset();
            const mensagemDiv = document.getElementById('mensagem-nova');
            mensagemDiv.classList.add('hidden');
            mensagemDiv.innerHTML = '';
            
            const modal = document.getElementById('modalNovoPrograma');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('nova_nome').focus();
            }, 100);
        }
        
        function fecharModalNovoPrograma() {
            const modal = document.getElementById('modalNovoPrograma');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            document.getElementById('formNovoPrograma').reset();
            document.getElementById('mensagem-nova').classList.add('hidden');
        }
        
        document.getElementById('formNovoPrograma').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'criar_programa');
            
            const ativo = document.getElementById('nova_ativo').checked ? 1 : 0;
            formData.set('ativo', ativo);
            
            const mensagemDiv = document.getElementById('mensagem-nova');
            mensagemDiv.classList.remove('hidden');
            mensagemDiv.innerHTML = '<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">Criando programa...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensagemDiv.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">Programa criado com sucesso!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">' + (data.message || 'Erro ao criar programa. Tente novamente.') + '</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">Erro ao criar programa. Tente novamente.</div>';
            });
        });

        function editarPrograma(id) {
            fetch(`?acao=buscar_programa&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.programa) {
                        const programa = data.programa;
                        
                        document.getElementById('edit_programa_id').value = programa.id;
                        document.getElementById('edit_nome').value = programa.nome || '';
                        document.getElementById('edit_descricao').value = programa.descricao || '';
                        document.getElementById('edit_ativo').checked = programa.ativo == 1;
                        
                        const mensagemDiv = document.getElementById('mensagem-edicao');
                        mensagemDiv.classList.add('hidden');
                        mensagemDiv.innerHTML = '';
                        
                        const modal = document.getElementById('modalEditarPrograma');
                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                    } else {
                        alert('Erro ao carregar dados do programa: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar programa:', error);
                    alert('Erro ao carregar dados do programa. Tente novamente.');
                });
        }
        
        function fecharModalEditarPrograma() {
            const modal = document.getElementById('modalEditarPrograma');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            document.getElementById('formEditarPrograma').reset();
            document.getElementById('mensagem-edicao').classList.add('hidden');
        }
        
        document.getElementById('formEditarPrograma').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar_programa');
            
            const ativo = document.getElementById('edit_ativo').checked ? 1 : 0;
            formData.set('ativo', ativo);
            
            const mensagemDiv = document.getElementById('mensagem-edicao');
            mensagemDiv.classList.remove('hidden');
            mensagemDiv.innerHTML = '<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">Salvando alterações...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensagemDiv.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">Programa atualizado com sucesso!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">' + (data.message || 'Erro ao atualizar programa. Tente novamente.') + '</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">Erro ao atualizar programa. Tente novamente.</div>';
            });
        });

        function excluirPrograma(id) {
            if (confirm('Tem certeza que deseja excluir este programa?')) {
                const formData = new FormData();
                formData.append('acao', 'excluir_programa');
                formData.append('id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Programa excluído com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir programa.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir programa.');
                });
            }
        }

        function filtrarProgramas() {
            const busca = document.getElementById('filtro-busca').value;
            
            let url = '?acao=listar_programas';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-programas');
                        tbody.innerHTML = '';
                        
                        if (data.programas.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-600">Nenhum programa encontrado.</td></tr>';
                            return;
                        }
                        
                        data.programas.forEach(programa => {
                            const descricao = programa.descricao || '-';
                            const descricaoResumida = descricao.length > 100 ? descricao.substring(0, 100) + '...' : descricao;
                            const dataCriacao = new Date(programa.criado_em).toLocaleDateString('pt-BR');
                            
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium">${programa.nome}</td>
                                    <td class="py-3 px-4 text-gray-600">${descricaoResumida}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs ${programa.ativo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                            ${programa.ativo ? 'Ativo' : 'Inativo'}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-500">${dataCriacao}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarPrograma(${programa.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirPrograma(${programa.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
                    console.error('Erro ao filtrar programas:', error);
                });
        }
    </script>
</body>
</html>


