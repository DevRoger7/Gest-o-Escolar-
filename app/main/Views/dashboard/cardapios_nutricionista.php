<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');
require_once('../../Models/merenda/CardapioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é NUTRICIONISTA
if (!eNutricionista() && !eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$cardapioModel = new CardapioModel();

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_cardapio') {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'ano' => $_POST['ano'] ?? date('Y'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        if ($dados['escola_id'] && !empty($dados['itens'])) {
            $resultado = $cardapioModel->criar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'editar_cardapio' && !empty($_POST['cardapio_id'])) {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'ano' => $_POST['ano'] ?? date('Y'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        $resultado = $cardapioModel->atualizar($_POST['cardapio_id'], $dados);
        echo json_encode($resultado);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_cardapios') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        if (!empty($_GET['ano'])) $filtros['ano'] = $_GET['ano'];
        if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
        
        $cardapios = $cardapioModel->listar($filtros);
        echo json_encode(['success' => true, 'cardapios' => $cardapios]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_cardapio' && !empty($_GET['id'])) {
        $cardapio = $cardapioModel->buscarPorId($_GET['id']);
        echo json_encode(['success' => true, 'cardapio' => $cardapio]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_pacote_escola' && !empty($_GET['escola_id'])) {
        $escolaId = $_GET['escola_id'];
        
        // Buscar o pacote mais recente da escola
        $sqlPacote = "SELECT pe.*, e.nome as escola_nome
                      FROM pacote_escola pe
                      INNER JOIN escola e ON pe.escola_id = e.id
                      WHERE pe.escola_id = :escola_id
                      ORDER BY pe.criado_em DESC
                      LIMIT 1";
        $stmtPacote = $conn->prepare($sqlPacote);
        $stmtPacote->bindParam(':escola_id', $escolaId);
        $stmtPacote->execute();
        $pacote = $stmtPacote->fetch(PDO::FETCH_ASSOC);
        
        if ($pacote) {
            // Buscar itens do pacote
            $sqlItens = "SELECT pei.*, pr.nome as produto_nome, pr.unidade_medida
                        FROM pacote_escola_item pei
                        INNER JOIN produto pr ON pei.produto_id = pr.id
                        WHERE pei.pacote_id = :pacote_id
                        ORDER BY pr.nome ASC";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':pacote_id', $pacote['id']);
            $stmtItens->execute();
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'pacote' => $pacote,
                'itens' => $itens
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Nenhum pacote encontrado para esta escola'
            ]);
        }
        exit;
    }
}

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos para cardápio
$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Buscar cardápios para exibição inicial
$filtrosInicial = ['ano' => date('Y')];
$cardapios = $cardapioModel->listar($filtrosInicial);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Cardápios Nutricionais') ?></title>
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
            background: linear-gradient(90deg, rgba(236, 72, 153, 0.12) 0%, rgba(236, 72, 153, 0.06) 100%);
            border-right: 3px solid #ec4899;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(236, 72, 153, 0.08) 0%, rgba(236, 72, 153, 0.04) 100%);
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
                        <h1 class="text-xl font-semibold text-gray-800">Cardápios Nutricionais</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Gestão de Cardápios</h2>
                        <p class="text-gray-600 mt-1">Crie e gerencie cardápios conforme normas nutricionais</p>
                    </div>
                    <?php if (isset($_SESSION['adc_cardapio'])) { ?>
                    <button onclick="abrirModalNovoCardapio()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Cardápio</span>
                    </button>
                    <?php } ?>
                </div>
                
                <!-- Informações sobre Normas Nutricionais -->
                <div class="bg-gradient-to-r from-pink-50 to-purple-50 rounded-2xl p-6 shadow-lg mb-6 border border-pink-200">
                    <div class="flex items-start space-x-4">
                        <div class="p-3 bg-pink-100 rounded-xl">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Normas Nutricionais</h3>
                            <p class="text-sm text-gray-600 mb-2">Ao criar cardápios, certifique-se de seguir as diretrizes do PNAE (Programa Nacional de Alimentação Escolar):</p>
                            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                <li>Variedade de alimentos ao longo da semana</li>
                                <li>Valor nutricional adequado para cada faixa etária</li>
                                <li>Respeito à sazonalidade dos alimentos</li>
                                <li>Consideração de necessidades especiais (alergias, restrições)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todos os meses</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="<?= date('Y') - 1 ?>"><?= date('Y') - 1 ?></option>
                                <option value="<?= date('Y') ?>" selected><?= date('Y') ?></option>
                                <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todos</option>
                                <option value="RASCUNHO">Rascunho</option>
                                <option value="APROVADO">Aprovado</option>
                                <option value="PUBLICADO">Publicado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Cardápios -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Itens</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-cardapios" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Carregando cardápios...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const produtos = <?= json_encode($produtos) ?>;
        let itemIndex = 0;
        
        function filtrarCardapios() {
            const escolaId = document.getElementById('filtro-escola').value;
            const mes = document.getElementById('filtro-mes').value;
            const ano = document.getElementById('filtro-ano').value;
            const status = document.getElementById('filtro-status').value;
            
            const params = new URLSearchParams();
            if (escolaId) params.append('escola_id', escolaId);
            if (mes) params.append('mes', mes);
            if (ano) params.append('ano', ano);
            if (status) params.append('status', status);
            params.append('acao', 'listar_cardapios');
            
            fetch('cardapios_nutricionista.php?' + params.toString())
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Resposta não é JSON:', text.substring(0, 200));
                        throw new Error('Resposta do servidor não é válida.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderizarCardapios(data.cardapios);
                    }
                })
                .catch(error => console.error('Erro:', error));
        }
        
        function renderizarCardapios(cardapios) {
            const tbody = document.getElementById('lista-cardapios');
            if (cardapios.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum cardápio encontrado.</td></tr>';
                return;
            }
            
            tbody.innerHTML = cardapios.map(c => {
                const statusClass = {
                    'RASCUNHO': 'bg-yellow-100 text-yellow-800',
                    'APROVADO': 'bg-green-100 text-green-800',
                    'PUBLICADO': 'bg-blue-100 text-blue-800'
                }[c.status] || 'bg-gray-100 text-gray-800';
                
                return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${c.escola_nome || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${c.mes}/${c.ano}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${c.status}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${c.total_itens || 0} itens</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="visualizarCardapio(${c.id})" class="text-blue-600 hover:text-blue-900 mr-3">Ver</button>
                            ${c.status === 'RASCUNHO' ? `<button onclick="editarCardapio(${c.id})" class="text-green-600 hover:text-green-900 mr-3">Editar</button>` : ''}
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function abrirModalNovoCardapio() {
            document.getElementById('modalNovoCardapio').classList.remove('hidden');
            document.getElementById('modalNovoCardapio').style.display = 'flex';
            // Limpar formulário
            document.getElementById('formNovoCardapio').reset();
            document.getElementById('mes').value = new Date().getMonth() + 1;
            document.getElementById('ano').value = new Date().getFullYear();
            document.getElementById('pacote-info').classList.add('hidden');
            document.getElementById('produtos-pacote').innerHTML = '';
            document.getElementById('itens-cardapio').innerHTML = '';
            itemIndex = 0;
        }
        
        function fecharModalNovoCardapio() {
            document.getElementById('modalNovoCardapio').classList.add('hidden');
            document.getElementById('modalNovoCardapio').style.display = 'none';
        }
        
        async function buscarPacoteEscola() {
            const escolaId = document.getElementById('escola_id').value;
            const pacoteInfo = document.getElementById('pacote-info');
            const produtosPacote = document.getElementById('produtos-pacote');
            
            if (!escolaId) {
                pacoteInfo.classList.add('hidden');
                produtosPacote.innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`?acao=buscar_pacote_escola&escola_id=${escolaId}`);
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida.');
                }
                
                const data = await response.json();
                
                if (data.success && data.pacote && data.itens) {
                    pacoteInfo.classList.remove('hidden');
                    document.getElementById('pacote-nome').textContent = data.pacote.descricao || 'Pacote da Escola';
                    document.getElementById('pacote-data').textContent = new Date(data.pacote.data_envio).toLocaleDateString('pt-BR');
                    
                    // Exibir produtos do pacote
                    produtosPacote.innerHTML = data.itens.map(item => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">${item.produto_nome}</p>
                                <p class="text-sm text-gray-500">${item.quantidade} ${item.unidade_medida || ''}</p>
                            </div>
                            <button onclick="adicionarProdutoAoCardapio(${item.produto_id}, '${item.produto_nome.replace(/'/g, "\\'")}', '${item.unidade_medida || ''}')" 
                                    class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Adicionar
                            </button>
                        </div>
                    `).join('');
                } else {
                    pacoteInfo.classList.add('hidden');
                    produtosPacote.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhum pacote encontrado para esta escola.</p>';
                }
            } catch (error) {
                console.error('Erro ao buscar pacote:', error);
                pacoteInfo.classList.add('hidden');
                produtosPacote.innerHTML = '<p class="text-red-500 text-center py-4">Erro ao buscar pacote da escola.</p>';
            }
        }
        
        function adicionarProdutoAoCardapio(produtoId, produtoNome, unidadeMedida) {
            const itensCardapio = document.getElementById('itens-cardapio');
            const novoItem = document.createElement('div');
            novoItem.className = 'flex items-center space-x-4 p-4 bg-white border border-gray-200 rounded-lg mb-3';
            novoItem.innerHTML = `
                <input type="hidden" name="itens[${itemIndex}][produto_id]" value="${produtoId}">
                <div class="flex-1">
                    <p class="font-medium text-gray-900">${produtoNome}</p>
                    <p class="text-sm text-gray-500">${unidadeMedida}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-700">Quantidade:</label>
                    <input type="number" name="itens[${itemIndex}][quantidade]" 
                           step="0.001" min="0" value="1" 
                           class="w-24 px-3 py-2 border border-gray-300 rounded-lg" required>
                    <span class="text-sm text-gray-500">${unidadeMedida}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" 
                        class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            itensCardapio.appendChild(novoItem);
            itemIndex++;
        }
        
        function adicionarItemManual() {
            const produtoSelect = document.getElementById('produto-manual');
            const produtoId = produtoSelect.value;
            const produtoOption = produtoSelect.options[produtoSelect.selectedIndex];
            const produtoNome = produtoOption.text;
            const unidadeMedida = produtoOption.getAttribute('data-unidade') || '';
            
            if (!produtoId) {
                alert('Selecione um produto');
                return;
            }
            
            adicionarProdutoAoCardapio(produtoId, produtoNome, unidadeMedida);
            produtoSelect.value = '';
        }
        
        async function salvarCardapio() {
            const form = document.getElementById('formNovoCardapio');
            const formData = new FormData(form);
            
            // Coletar itens do cardápio
            const itens = [];
            const itemInputs = form.querySelectorAll('input[name^="itens["]');
            const itemGroups = {};
            
            itemInputs.forEach(input => {
                const name = input.name;
                const match = name.match(/itens\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const index = match[1];
                    const field = match[2];
                    if (!itemGroups[index]) {
                        itemGroups[index] = {};
                    }
                    itemGroups[index][field] = input.value;
                }
            });
            
            Object.values(itemGroups).forEach(item => {
                if (item.produto_id && item.quantidade) {
                    itens.push({
                        produto_id: item.produto_id,
                        quantidade: parseFloat(item.quantidade)
                    });
                }
            });
            
            if (itens.length === 0) {
                alert('Adicione pelo menos um item ao cardápio');
                return;
            }
            
            formData.append('acao', 'criar_cardapio');
            formData.append('itens', JSON.stringify(itens));
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Cardápio criado com sucesso!');
                    fecharModalNovoCardapio();
                    filtrarCardapios();
                } else {
                    alert('Erro ao criar cardápio: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar requisição. Por favor, tente novamente.');
            }
        }
        
        function visualizarCardapio(id) {
            window.location.href = `cardapios_nutricionista.php?acao=buscar_cardapio&id=${id}`;
        }
        
        function editarCardapio(id) {
            window.location.href = `cardapios_nutricionista.php?acao=editar_cardapio&id=${id}`;
        }
        
        // Carregar cardápios ao iniciar
        filtrarCardapios();
    </script>
    
    <!-- Modal Novo Cardápio -->
    <div id="modalNovoCardapio" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Novo Cardápio</h3>
                    <button onclick="fecharModalNovoCardapio()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="formNovoCardapio" onsubmit="event.preventDefault(); salvarCardapio();">
                    <div class="space-y-6">
                        <!-- Seleção de Escola -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                            <select id="escola_id" name="escola_id" onchange="buscarPacoteEscola()" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Informações do Pacote -->
                        <div id="pacote-info" class="hidden bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl p-4 border border-pink-200">
                            <div class="flex items-center space-x-3 mb-3">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Pacote da Escola</h4>
                                    <p class="text-sm text-gray-600" id="pacote-nome"></p>
                                    <p class="text-xs text-gray-500">Data de envio: <span id="pacote-data"></span></p>
                                </div>
                            </div>
                            
                            <!-- Produtos do Pacote -->
                            <div class="mt-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-3">Produtos Disponíveis no Pacote:</h5>
                                <div id="produtos-pacote" class="space-y-2 max-h-60 overflow-y-auto">
                                    <!-- Produtos serão carregados aqui -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Período -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mês *</label>
                                <select id="mes" name="mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ano *</label>
                                <input type="number" id="ano" name="ano" 
                                       value="<?= date('Y') ?>" min="2020" max="2100"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                            </div>
                        </div>
                        
                        <!-- Adicionar Produto Manualmente -->
                        <div class="border-t pt-4">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Adicionar Produto Manualmente</h4>
                            <div class="flex space-x-2">
                                <select id="produto-manual" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Selecione um produto</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= $produto['id'] ?>" data-unidade="<?= htmlspecialchars($produto['unidade_medida'] ?? '') ?>">
                                            <?= htmlspecialchars($produto['nome']) ?> 
                                            <?= $produto['unidade_medida'] ? '(' . htmlspecialchars($produto['unidade_medida']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="adicionarItemManual()" 
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium">
                                    Adicionar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Itens do Cardápio -->
                        <div class="border-t pt-4">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Itens do Cardápio</h4>
                            <div id="itens-cardapio" class="space-y-3">
                                <!-- Itens serão adicionados aqui -->
                                <p class="text-gray-500 text-center py-4">Nenhum item adicionado ainda. Use os produtos do pacote ou adicione manualmente.</p>
                            </div>
                        </div>
                        
                        <!-- Botões -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <button type="button" onclick="fecharModalNovoCardapio()" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-medium">
                                Salvar Cardápio
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

