<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/PedidoCestaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$pedidoModel = new PedidoCestaModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'aprovar_pedido') {
        $id = $_POST['pedido_id'] ?? null;
        $observacoes = $_POST['observacoes'] ?? null;
        $resultado = $pedidoModel->aprovar($id, $observacoes);
        echo json_encode(['success' => $resultado]);
        exit;
    }
    
    if ($_POST['acao'] === 'rejeitar_pedido') {
        $id = $_POST['pedido_id'] ?? null;
        $motivo = $_POST['motivo_rejeicao'] ?? null;
        $resultado = $pedidoModel->rejeitar($id, $motivo);
        echo json_encode(['success' => $resultado]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_pedidos') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        
        $pedidos = $pedidoModel->listar($filtros);
        echo json_encode(['success' => true, 'pedidos' => $pedidos]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_pedido' && !empty($_GET['id'])) {
        $pedido = $pedidoModel->buscarPorId($_GET['id']);
        echo json_encode(['success' => true, 'pedido' => $pedido]);
        exit;
    }
}

// Buscar pedidos pendentes
$pedidosPendentes = $pedidoModel->listar(['status' => 'ENVIADO']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos de Compra - SIGEA</title>
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
    <?php include '../../components/sidebar_merenda.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Pedidos de Compra</h1>
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
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Aprovação de Pedidos</h2>
                    <p class="text-gray-600 mt-1">Aprove ou rejeite pedidos de compra de alimentos</p>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarPedidos()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarPedidos()">
                                <option value="">Todos</option>
                                <option value="ENVIADO" selected>Pendentes</option>
                                <option value="APROVADO">Aprovados</option>
                                <option value="REJEITADO">Rejeitados</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarPedidos()">
                                <option value="">Todos</option>
                                <?php 
                                $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>"><?= $meses[$i - 1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Pedidos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div id="lista-pedidos" class="space-y-4">
                        <?php if (empty($pedidosPendentes)): ?>
                            <div class="text-center py-12">
                                <p class="text-gray-600">Nenhum pedido pendente encontrado.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pedidosPendentes as $pedido): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($pedido['escola_nome']) ?></h3>
                                            <p class="text-sm text-gray-600">Mês: <?php 
                                            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                            echo $meses[$pedido['mes'] - 1] ?? $pedido['mes'];
                                            ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Enviado em: <?= date('d/m/Y H:i', strtotime($pedido['data_envio'] ?? $pedido['data_criacao'])) ?></p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php
                                                echo $pedido['status'] === 'APROVADO' ? 'bg-green-100 text-green-800' :
                                                    ($pedido['status'] === 'REJEITADO' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800');
                                            ?>">
                                                <?= htmlspecialchars($pedido['status'] ?? 'ENVIADO') ?>
                                            </span>
                                            <button onclick="verDetalhesPedido(<?= $pedido['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Ver Detalhes
                                            </button>
                                            <?php if ($pedido['status'] === 'ENVIADO'): ?>
                                                <button onclick="aprovarPedido(<?= $pedido['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                    Aprovar
                                                </button>
                                                <button onclick="rejeitarPedido(<?= $pedido['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                    Rejeitar
                                                </button>
                                            <?php endif; ?>
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

        function filtrarPedidos() {
            const escolaId = document.getElementById('filtro-escola').value;
            const status = document.getElementById('filtro-status').value;
            const mes = document.getElementById('filtro-mes').value;
            
            let url = '?acao=listar_pedidos';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (status) url += '&status=' + status;
            if (mes) url += '&mes=' + mes;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('lista-pedidos');
                        container.innerHTML = '';
                        
                        if (data.pedidos.length === 0) {
                            container.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">Nenhum pedido encontrado.</p></div>';
                            return;
                        }
                        
                        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                        
                        data.pedidos.forEach(pedido => {
                            const statusClass = pedido.status === 'APROVADO' ? 'bg-green-100 text-green-800' :
                                              (pedido.status === 'REJEITADO' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800');
                            const mesNome = meses[pedido.mes - 1] || pedido.mes;
                            const dataEnvio = pedido.data_envio ? new Date(pedido.data_envio).toLocaleString('pt-BR') : new Date(pedido.data_criacao).toLocaleString('pt-BR');
                            
                            container.innerHTML += `
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900">${pedido.escola_nome}</h3>
                                            <p class="text-sm text-gray-600">Mês: ${mesNome}</p>
                                            <p class="text-xs text-gray-500 mt-1">Enviado em: ${dataEnvio}</p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                                                ${pedido.status || 'ENVIADO'}
                                            </span>
                                            <button onclick="verDetalhesPedido(${pedido.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Ver Detalhes
                                            </button>
                                            ${pedido.status === 'ENVIADO' ? `
                                                <button onclick="aprovarPedido(${pedido.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                    Aprovar
                                                </button>
                                                <button onclick="rejeitarPedido(${pedido.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                    Rejeitar
                                                </button>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar pedidos:', error);
                });
        }

        function verDetalhesPedido(id) {
            fetch('?acao=buscar_pedido&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.pedido) {
                        const pedido = data.pedido;
                        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                        const mesNome = meses[pedido.mes - 1] || pedido.mes;
                        
                        let itensHtml = '';
                        if (pedido.itens && pedido.itens.length > 0) {
                            itensHtml = pedido.itens.map(item => 
                                `- ${item.produto_nome}: ${item.quantidade_solicitada} ${item.unidade_medida || ''}`
                            ).join('\n');
                        } else {
                            itensHtml = 'Nenhum item';
                        }
                        
                        alert(`Pedido #${pedido.id}\n\nEscola: ${pedido.escola_nome}\nMês: ${mesNome}\nStatus: ${pedido.status || 'ENVIADO'}\n\nItens:\n${itensHtml}`);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao buscar detalhes do pedido.');
                });
        }

        function aprovarPedido(id) {
            if (!confirm('Deseja aprovar este pedido?')) return;
            
            const observacoes = prompt('Observações (opcional):') || null;
            
            const formData = new FormData();
            formData.append('acao', 'aprovar_pedido');
            formData.append('pedido_id', id);
            formData.append('observacoes', observacoes);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pedido aprovado com sucesso!');
                    filtrarPedidos();
                } else {
                    alert('Erro ao aprovar pedido.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao aprovar pedido.');
            });
        }

        function rejeitarPedido(id) {
            const motivo = prompt('Motivo da rejeição:');
            if (!motivo) return;
            
            const formData = new FormData();
            formData.append('acao', 'rejeitar_pedido');
            formData.append('pedido_id', id);
            formData.append('motivo_rejeicao', motivo);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pedido rejeitado.');
                    filtrarPedidos();
                } else {
                    alert('Erro ao rejeitar pedido.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao rejeitar pedido.');
            });
        }
    </script>
</body>
</html>

