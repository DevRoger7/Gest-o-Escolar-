<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/validacao/ValidacaoModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$validacaoModel = new ValidacaoModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'aprovar_lancamento') {
        $id = $_POST['id'] ?? null;
        $observacoes = $_POST['observacoes'] ?? null;
        $resultado = $validacaoModel->aprovar($id, $observacoes);
        echo json_encode(['success' => $resultado]);
        exit;
    }
    
    if ($_POST['acao'] === 'rejeitar_lancamento') {
        $id = $_POST['id'] ?? null;
        $motivo = $_POST['motivo'] ?? null;
        $resultado = $validacaoModel->rejeitar($id, $motivo);
        echo json_encode(['success' => $resultado]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_validacoes') {
        $filtros = [];
        if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
        
        $validacoes = $validacaoModel->listarPendentes();
        echo json_encode(['success' => true, 'validacoes' => $validacoes]);
        exit;
    }
}

$validacoesPendentes = $validacaoModel->listarPendentes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Lançamentos - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Validação de Lançamentos</h1>
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
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Validação de Informações</h2>
                    <p class="text-gray-600 mt-1">Aprove ou rejeite lançamentos pendentes de validação</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select id="filtro-tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarValidacoes()">
                                <option value="">Todos</option>
                                <option value="NOTA">Notas</option>
                                <option value="FREQUENCIA">Frequências</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarValidacoes()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tipo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Detalhes</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-validacoes">
                                <?php if (empty($validacoesPendentes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-12 text-gray-600">
                                            Nenhuma validação pendente encontrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($validacoesPendentes as $validacao): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs <?= $validacao['tipo_registro'] === 'NOTA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                                    <?= htmlspecialchars($validacao['tipo_registro']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($validacao['observacoes'] ?? 'Sem detalhes') ?></td>
                                            <td class="py-3 px-4"><?= date('d/m/Y H:i', strtotime($validacao['criado_em'])) ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="aprovarValidacao(<?= $validacao['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                        Aprovar
                                                    </button>
                                                    <button onclick="rejeitarValidacao(<?= $validacao['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                        Rejeitar
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

        function aprovarValidacao(id) {
            if (!confirm('Deseja aprovar esta validação?')) return;
            
            const formData = new FormData();
            formData.append('acao', 'aprovar_lancamento');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Validação aprovada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao aprovar validação.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao aprovar validação.');
            });
        }

        function rejeitarValidacao(id) {
            const motivo = prompt('Motivo da rejeição:');
            if (!motivo) return;
            
            const formData = new FormData();
            formData.append('acao', 'rejeitar_lancamento');
            formData.append('id', id);
            formData.append('motivo', motivo);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Validação rejeitada.');
                    location.reload();
                } else {
                    alert('Erro ao rejeitar validação.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao rejeitar validação.');
            });
        }

        function filtrarValidacoes() {
            const tipo = document.getElementById('filtro-tipo').value;
            
            let url = '?acao=listar_validacoes';
            if (tipo) url += '&tipo=' + tipo;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-validacoes');
                        tbody.innerHTML = '';
                        
                        if (data.validacoes.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-gray-600">Nenhuma validação encontrada.</td></tr>';
                            return;
                        }
                        
                        data.validacoes.forEach(validacao => {
                            const tipoClass = validacao.tipo_registro === 'NOTA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
                            const dataFormatada = new Date(validacao.criado_em).toLocaleString('pt-BR');
                            
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs ${tipoClass}">
                                            ${validacao.tipo_registro}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">${validacao.observacoes || 'Sem detalhes'}</td>
                                    <td class="py-3 px-4">${dataFormatada}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="aprovarValidacao(${validacao.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                Aprovar
                                            </button>
                                            <button onclick="rejeitarValidacao(${validacao.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                Rejeitar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar validações:', error);
                });
        }
    </script>
</body>
</html>

