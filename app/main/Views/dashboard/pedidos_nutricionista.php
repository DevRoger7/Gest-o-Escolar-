<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');
require_once('../../Models/merenda/PedidoCestaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eNutricionista() && !eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$pedidoModel = new PedidoCestaModel();

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_pedido') {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        if ($dados['escola_id'] && !empty($dados['itens'])) {
            $resultado = $pedidoModel->criar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'enviar_pedido' && !empty($_POST['pedido_id'])) {
        $resultado = $pedidoModel->enviar($_POST['pedido_id']);
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
        $pedidosFiltrados = array_filter($pedidos, function($p) {
            return isset($p['nutricionista_id']) && $p['nutricionista_id'] == $_SESSION['usuario_id'];
        });
        
        echo json_encode(['success' => true, 'pedidos' => array_values($pedidosFiltrados)]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_pedido' && !empty($_GET['id'])) {
        $pedido = $pedidoModel->buscarPorId($_GET['id']);
        if ($pedido && isset($pedido['nutricionista_id']) && $pedido['nutricionista_id'] == $_SESSION['usuario_id']) {
            echo json_encode(['success' => true, 'pedido' => $pedido]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado ou sem permissão']);
        }
        exit;
    }
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

$sqlPedidos = "SELECT pc.*, e.nome as escola_nome 
               FROM pedido_cesta pc
               LEFT JOIN escola e ON pc.escola_id = e.id
               WHERE pc.nutricionista_id = :nutricionista_id
               ORDER BY pc.data_criacao DESC
               LIMIT 50";
$stmtPedidos = $conn->prepare($sqlPedidos);
$stmtPedidos->bindParam(':nutricionista_id', $_SESSION['usuario_id']);
$stmtPedidos->execute();
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Meus Pedidos') ?></title>
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
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_nutricionista.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Meus Pedidos</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Solicitações de Produtos</h2>
                        <p class="text-gray-600 mt-1">Solicite produtos e ingredientes ao administrador</p>
                    </div>
                    <?php if (isset($_SESSION['env_pedidos'])) { ?>
                    <button onclick="abrirModalNovoPedido()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Pedido</span>
                    </button>
                    <?php } ?>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Escola</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mês</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($pedidos)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum pedido encontrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <?php
                                        $statusClass = [
                                            'RASCUNHO' => 'bg-yellow-100 text-yellow-800',
                                            'ENVIADO' => 'bg-blue-100 text-blue-800',
                                            'APROVADO' => 'bg-green-100 text-green-800',
                                            'REJEITADO' => 'bg-red-100 text-red-800'
                                        ][$pedido['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($pedido['escola_nome'] ?? 'N/A') ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('F/Y', strtotime($pedido['mes'] . '-01')) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= $pedido['status'] ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y', strtotime($pedido['data_criacao'])) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="visualizarPedido(<?= $pedido['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Ver</button>
                                                <?php if ($pedido['status'] === 'RASCUNHO'): ?>
                                                    <button onclick="editarPedido(<?= $pedido['id'] ?>)" class="text-green-600 hover:text-green-900">Editar</button>
                                                <?php endif; ?>
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
    
    <!-- Modal Novo Pedido (Full Screen) -->
    <div id="modalNovoPedido" class="fixed inset-0 bg-white z-50 hidden overflow-y-auto">
        <div class="min-h-screen w-full">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 z-10 shadow-sm">
                <div class="max-w-7xl mx-auto px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900">Novo Pedido de Compra</h2>
                            <p class="text-sm text-gray-600 mt-1">Solicite produtos e ingredientes para a escola</p>
                        </div>
                        <button onclick="fecharModalNovoPedido()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Body -->
            <form id="formNovoPedido" class="max-w-7xl mx-auto px-6 py-8 space-y-8">
                <div id="mensagemErro" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"></div>
                <div id="mensagemSucesso" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"></div>
                
                <!-- Informações Básicas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                        <select id="pedido-escola-id" name="escola_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-base">
                            <option value="">Selecione uma escola</option>
                            <?php foreach ($escolas as $escola): ?>
                                <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mês *</label>
                        <select id="pedido-mes" name="mes" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-base">
                            <option value="">Selecione o mês</option>
                            <?php 
                            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                            for ($i = 1; $i <= 12; $i++): 
                                $selected = ($i == date('n')) ? 'selected' : '';
                            ?>
                                <option value="<?= $i ?>" <?= $selected ?>><?= $meses[$i - 1] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Itens do Pedido -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <label class="block text-lg font-semibold text-gray-900">Produtos *</label>
                            <p class="text-sm text-gray-600 mt-1">Adicione os produtos que deseja solicitar</p>
                        </div>
                        <button type="button" onclick="adicionarItemPedido()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center space-x-2 shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Adicionar Produto</span>
                        </button>
                    </div>
                    <div id="itens-pedido" class="space-y-4">
                        <!-- Itens serão adicionados aqui via JavaScript -->
                    </div>
                    <p id="aviso-sem-itens" class="text-sm text-gray-500 text-center py-8 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                        Clique em "Adicionar Produto" para começar
                    </p>
                </div>
                
                <!-- Observações -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea id="pedido-observacoes" name="observacoes" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-base" placeholder="Informações adicionais sobre o pedido..."></textarea>
                </div>
            </form>
            
            <!-- Footer -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 shadow-lg">
                <div class="max-w-7xl mx-auto px-6 py-4">
                    <div class="flex items-center justify-end space-x-3">
                        <button onclick="fecharModalNovoPedido()" class="px-8 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button onclick="salvarPedido()" class="px-8 py-3 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-medium transition-colors">
                            Salvar Pedido
                        </button>
                        <button onclick="salvarEEnviarPedido()" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                            Salvar e Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const produtos = <?= json_encode($produtos) ?>;
        let itemIndex = 0;
        
        function abrirModalNovoPedido() {
            document.getElementById('modalNovoPedido').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            limparFormularioPedido();
        }
        
        function fecharModalNovoPedido() {
            document.getElementById('modalNovoPedido').classList.add('hidden');
            document.body.style.overflow = 'auto';
            limparFormularioPedido();
        }
        
        function limparFormularioPedido() {
            document.getElementById('formNovoPedido').reset();
            document.getElementById('itens-pedido').innerHTML = '';
            document.getElementById('aviso-sem-itens').classList.remove('hidden');
            document.getElementById('mensagemErro').classList.add('hidden');
            document.getElementById('mensagemSucesso').classList.add('hidden');
            itemIndex = 0;
        }
        
        function adicionarItemPedido() {
            const container = document.getElementById('itens-pedido');
            const aviso = document.getElementById('aviso-sem-itens');
            
            aviso.classList.add('hidden');
            
            const div = document.createElement('div');
            div.id = `item-pedido-${itemIndex}`;
            div.className = 'flex items-center space-x-4 p-5 bg-white rounded-lg border-2 border-gray-200 shadow-sm hover:border-pink-300 transition-colors';
            div.innerHTML = `
                <select class="produto-select flex-1 px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-pink-500 focus:border-pink-500" data-item-index="${itemIndex}" required>
                    <option value="">Selecione um produto</option>
                    ${produtos.map(p => `<option value="${p.id}">${p.nome} (${p.unidade_medida})</option>`).join('')}
                </select>
                <input type="number" step="0.001" min="0" class="quantidade-input w-40 px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-pink-500 focus:border-pink-500" placeholder="Quantidade" data-item-index="${itemIndex}" required>
                <input type="text" class="observacao-input flex-1 px-4 py-3 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-pink-500 focus:border-pink-500" placeholder="Observação (opcional)" data-item-index="${itemIndex}">
                <button type="button" onclick="removerItemPedido(${itemIndex})" class="text-red-600 hover:text-red-700 hover:bg-red-50 p-3 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            itemIndex++;
        }
        
        function removerItemPedido(index) {
            const item = document.getElementById(`item-pedido-${index}`);
            if (item) {
                item.remove();
                const container = document.getElementById('itens-pedido');
                if (container.children.length === 0) {
                    document.getElementById('aviso-sem-itens').classList.remove('hidden');
                }
            }
        }
        
        function coletarItensPedido() {
            const itens = [];
            document.querySelectorAll('.produto-select').forEach(select => {
                const produtoId = select.value;
                const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${select.dataset.itemIndex}"]`);
                const observacaoInput = document.querySelector(`.observacao-input[data-item-index="${select.dataset.itemIndex}"]`);
                const quantidade = quantidadeInput ? quantidadeInput.value : '';
                const observacao = observacaoInput ? observacaoInput.value : '';
                
                if (produtoId && quantidade) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade_solicitada: quantidade,
                        obs: observacao || null
                    });
                }
            });
            return itens;
        }
        
        function salvarPedido() {
            const escolaId = document.getElementById('pedido-escola-id').value;
            const mes = document.getElementById('pedido-mes').value;
            const itens = coletarItensPedido();
            
            if (!escolaId || !mes || itens.length === 0) {
                mostrarErro('Por favor, preencha todos os campos obrigatórios e adicione pelo menos um produto.');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'criar_pedido');
            formData.append('escola_id', escolaId);
            formData.append('mes', mes);
            formData.append('itens', JSON.stringify(itens));
            
            fetch('pedidos_nutricionista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarSucesso('Pedido criado com sucesso!');
                    setTimeout(() => {
                        fecharModalNovoPedido();
                        location.reload();
                    }, 1500);
                } else {
                    mostrarErro(data.message || 'Erro ao criar pedido. Tente novamente.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarErro('Erro ao processar requisição. Tente novamente.');
            });
        }
        
        function salvarEEnviarPedido() {
            const escolaId = document.getElementById('pedido-escola-id').value;
            const mes = document.getElementById('pedido-mes').value;
            const itens = coletarItensPedido();
            
            if (!escolaId || !mes || itens.length === 0) {
                mostrarErro('Por favor, preencha todos os campos obrigatórios e adicione pelo menos um produto.');
                return;
            }
            
            // Primeiro criar o pedido
            const formData = new FormData();
            formData.append('acao', 'criar_pedido');
            formData.append('escola_id', escolaId);
            formData.append('mes', mes);
            formData.append('itens', JSON.stringify(itens));
            
            fetch('pedidos_nutricionista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Depois enviar o pedido
                    const formDataEnviar = new FormData();
                    formDataEnviar.append('acao', 'enviar_pedido');
                    formDataEnviar.append('pedido_id', data.id);
                    
                    return fetch('pedidos_nutricionista.php', {
                        method: 'POST',
                        body: formDataEnviar
                    });
                } else {
                    throw new Error(data.message || 'Erro ao criar pedido');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarSucesso('Pedido criado e enviado com sucesso!');
                    setTimeout(() => {
                        fecharModalNovoPedido();
                        location.reload();
                    }, 1500);
                } else {
                    mostrarErro('Pedido criado, mas houve erro ao enviar. Você pode enviar depois.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarErro('Erro ao processar requisição. Tente novamente.');
            });
        }
        
        function mostrarErro(mensagem) {
            const erroDiv = document.getElementById('mensagemErro');
            erroDiv.textContent = mensagem;
            erroDiv.classList.remove('hidden');
            erroDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function mostrarSucesso(mensagem) {
            const sucessoDiv = document.getElementById('mensagemSucesso');
            sucessoDiv.textContent = mensagem;
            sucessoDiv.classList.remove('hidden');
            sucessoDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function visualizarPedido(id) {
            window.location.href = `pedidos_nutricionista.php?acao=buscar_pedido&id=${id}`;
        }
        
        function editarPedido(id) {
            // Implementar edição de pedido se necessário
            alert('Funcionalidade de edição será implementada em breve.');
        }
    </script>
</body>
</html>

