<?php
// Iniciar output buffering para evitar problemas com headers
if (!ob_get_level()) {
    ob_start();
}
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/merenda/CardapioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO
if ($_SESSION['tipo'] !== 'GESTAO' && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

require_once('../../config/Database.php');

$cardapioModel = new CardapioModel();

// Buscar escola do gestor logado
$db = Database::getInstance();
$conn = $db->getConnection();
$escolaGestor = null;
$escolaGestorId = null;

if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
    try {
        $pessoaId = $_SESSION['pessoa_id'] ?? null;
        if ($pessoaId) {
            // Primeiro, buscar o ID do gestor usando pessoa_id
            $sqlBuscarGestor = "SELECT g.id as gestor_id
                      FROM gestor g
                      WHERE g.pessoa_id = :pessoa_id
                      LIMIT 1";
            $stmtBuscarGestor = $conn->prepare($sqlBuscarGestor);
            $stmtBuscarGestor->bindParam(':pessoa_id', $pessoaId);
            $stmtBuscarGestor->execute();
            $gestorData = $stmtBuscarGestor->fetch(PDO::FETCH_ASSOC);
        } else {
            $gestorData = null;
        }

        if ($gestorData && isset($gestorData['gestor_id'])) {
            $gestorId = (int)$gestorData['gestor_id'];

            // Buscar a primeira escola ativa do gestor
            $sqlEscola = "SELECT DISTINCT e.id as escola_id, e.nome as escola_nome
                         FROM escola e
                         INNER JOIN gestor_lotacao gl ON e.id = gl.escola_id
                         WHERE gl.gestor_id = :gestor_id
                         AND gl.fim IS NULL
                         AND e.ativo = 1
                         ORDER BY e.nome ASC
                         LIMIT 1";
            $stmtEscola = $conn->prepare($sqlEscola);
            $stmtEscola->bindParam(':gestor_id', $gestorId);
            $stmtEscola->execute();
            $escolaData = $stmtEscola->fetch(PDO::FETCH_ASSOC);

            if ($escolaData) {
                $escolaGestorId = (int)$escolaData['escola_id'];
                $escolaGestor = $escolaData['escola_nome'];
            }
        }
    } catch (Exception $e) {
        error_log("ERRO ao buscar escola do gestor: " . $e->getMessage());
        $escolaGestorId = null;
        $escolaGestor = null;
    }
}

// Processar ações AJAX
if (!empty($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_cardapios' && !empty($_GET['escola_id'])) {
        if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId && $_GET['escola_id'] == $escolaGestorId) {
            $filtros = [
                'escola_id' => $escolaGestorId,
                'mes' => $_GET['mes'] ?? null,
                'ano' => $_GET['ano'] ?? null,
                'status' => $_GET['status'] ?? null
            ];
            
            $cardapios = $cardapioModel->listar($filtros);
            echo json_encode(['success' => true, 'cardapios' => $cardapios]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_cardapio' && !empty($_GET['id'])) {
        if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorId) {
            $cardapio = $cardapioModel->buscarPorId($_GET['id']);
            
            // Verificar se o cardápio pertence à escola do gestor
            if ($cardapio && $cardapio['escola_id'] == $escolaGestorId) {
                echo json_encode(['success' => true, 'cardapio' => $cardapio]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cardápio não encontrado ou acesso não autorizado']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
        }
        exit;
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global-theme.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include('components/sidebar_gestao.php'); ?>
    
    <div class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
            <div class="flex items-center justify-between px-4 py-4 lg:px-8">
                <div class="flex items-center space-x-4">
                    <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-800">Cardápio da Escola</h1>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="p-4 lg:p-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Cardápio da Escola</h2>
                        <p class="text-sm text-gray-600 mt-1">Visualize o cardápio da sua escola</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                        <select id="filtro-mes-cardapio" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="carregarCardapios()">
                            <option value="">Todos</option>
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Março</option>
                            <option value="4">Abril</option>
                            <option value="5">Maio</option>
                            <option value="6">Junho</option>
                            <option value="7">Julho</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                        <input type="number" id="filtro-ano-cardapio" value="<?= date('Y') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="carregarCardapios()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="filtro-status-cardapio" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="carregarCardapios()">
                            <option value="">Todos</option>
                            <option value="RASCUNHO">Rascunho</option>
                            <option value="ENVIADO">Enviado</option>
                            <option value="APROVADO">Aprovado</option>
                            <option value="REJEITADO">Rejeitado</option>
                        </select>
                    </div>
                </div>

                <!-- Lista de Cardápios -->
                <div id="lista-cardapios" class="space-y-4">
                    <div class="text-center py-8 text-gray-500">Carregando cardápios...</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Controle do menu mobile
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-mobile');
                sidebar.classList.toggle('open');
                if (mobileOverlay) {
                    mobileOverlay.classList.toggle('hidden');
                }
            });
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                mobileOverlay.classList.add('hidden');
            });
        }

        // Função para carregar cardápios
        function carregarCardapios() {
            const mes = document.getElementById('filtro-mes-cardapio').value;
            const ano = document.getElementById('filtro-ano-cardapio').value || new Date().getFullYear();
            const status = document.getElementById('filtro-status-cardapio').value;
            
            const lista = document.getElementById('lista-cardapios');
            lista.innerHTML = '<div class="text-center py-8 text-gray-500">Carregando...</div>';
            
            let url = `?acao=buscar_cardapios&escola_id=<?= $escolaGestorId ?>`;
            if (mes) url += `&mes=${mes}`;
            if (ano) url += `&ano=${ano}`;
            if (status) url += `&status=${status}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapios && data.cardapios.length > 0) {
                        renderizarCardapios(data.cardapios);
                    } else {
                        lista.innerHTML = '<div class="text-center py-8 text-gray-500">Nenhum cardápio encontrado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    lista.innerHTML = '<div class="text-center py-8 text-red-500">Erro ao carregar cardápios</div>';
                });
        }
        
        function renderizarCardapios(cardapios) {
            const lista = document.getElementById('lista-cardapios');
            const meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            
            lista.innerHTML = cardapios.map(cardapio => {
                const statusClass = {
                    'RASCUNHO': 'bg-yellow-100 text-yellow-800',
                    'ENVIADO': 'bg-blue-100 text-blue-800',
                    'APROVADO': 'bg-green-100 text-green-800',
                    'REJEITADO': 'bg-red-100 text-red-800'
                }[cardapio.status] || 'bg-gray-100 text-gray-800';
                
                return `
                    <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">${meses[cardapio.mes] || cardapio.mes}/${cardapio.ano}</h3>
                                <p class="text-sm text-gray-600 mt-1">${cardapio.escola_nome || 'Escola'}</p>
                                <p class="text-xs text-gray-500 mt-1">Criado por: ${cardapio.criado_por_nome || 'N/A'}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                                    ${cardapio.status || 'RASCUNHO'}
                                </span>
                                <button onclick="verDetalhesCardapio(${cardapio.id})" class="text-primary-green hover:text-green-700 font-medium text-sm">
                                    Ver Detalhes
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function verDetalhesCardapio(id) {
            fetch(`?acao=buscar_cardapio&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapio) {
                        mostrarModalDetalhesCardapio(data.cardapio);
                    } else {
                        alert('Erro ao carregar detalhes do cardápio');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar detalhes do cardápio');
                });
        }
        
        function mostrarModalDetalhesCardapio(cardapio) {
            const meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            const statusClass = {
                'RASCUNHO': 'bg-yellow-100 text-yellow-800',
                'ENVIADO': 'bg-blue-100 text-blue-800',
                'APROVADO': 'bg-green-100 text-green-800',
                'REJEITADO': 'bg-red-100 text-red-800'
            }[cardapio.status] || 'bg-gray-100 text-gray-800';
            
            const itensHtml = cardapio.itens && cardapio.itens.length > 0 
                ? cardapio.itens.map(item => `
                    <tr class="border-b border-gray-200">
                        <td class="px-4 py-3">${item.produto_nome || '-'}</td>
                        <td class="px-4 py-3">${item.quantidade || '-'} ${item.unidade_medida || ''}</td>
                        <td class="px-4 py-3">${item.observacoes || '-'}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="3" class="px-4 py-3 text-center text-gray-500">Nenhum item cadastrado</td></tr>';
            
            const modalHtml = `
                <div id="modalDetalhesCardapio" class="fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                        <div class="bg-primary-green text-white p-6 flex justify-between items-center sticky top-0">
                            <h3 class="text-2xl font-bold">Detalhes do Cardápio</h3>
                            <button onclick="fecharModalDetalhesCardapio()" class="text-white hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Período</label>
                                        <p class="text-gray-900">${meses[cardapio.mes] || cardapio.mes}/${cardapio.ano}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Status</label>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                                            ${cardapio.status || 'RASCUNHO'}
                                        </span>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Escola</label>
                                        <p class="text-gray-900">${cardapio.escola_nome || '-'}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Criado por</label>
                                        <p class="text-gray-900">${cardapio.criado_por_nome || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold mb-4">Itens do Cardápio</h4>
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Produto</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Quantidade</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Observações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itensHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-6 flex justify-end">
                            <button onclick="fecharModalDetalhesCardapio()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        
        function fecharModalDetalhesCardapio() {
            const modal = document.getElementById('modalDetalhesCardapio');
            if (modal) {
                modal.remove();
            }
        }
        
        // Carregar cardápios ao carregar a página
        window.addEventListener('load', function() {
            carregarCardapios();
        });
    </script>
</body>
</html>

