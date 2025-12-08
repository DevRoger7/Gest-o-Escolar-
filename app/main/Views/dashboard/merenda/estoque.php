<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedor WHERE ativo = 1 ORDER BY nome ASC";
$stmtFornecedores = $conn->prepare($sqlFornecedores);
$stmtFornecedores->execute();
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$sqlProdutos = "SELECT id, nome, unidade_medida, estoque_minimo FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'registrar_entrada') {
        try {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO estoque_central (produto_id, quantidade, lote, fornecedor_id, nota_fiscal, 
                    valor_unitario, valor_total, validade, criado_em)
                    VALUES (:produto_id, :quantidade, :lote, :fornecedor_id, :nota_fiscal, 
                    :valor_unitario, :valor_total, :validade, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':produto_id', $_POST['produto_id']);
            $stmt->bindParam(':quantidade', $_POST['quantidade']);
            $stmt->bindParam(':lote', $_POST['lote'] ?? null);
            $stmt->bindParam(':fornecedor_id', $_POST['fornecedor_id'] ?? null);
            $stmt->bindParam(':nota_fiscal', $_POST['nota_fiscal'] ?? null);
            $stmt->bindParam(':valor_unitario', $_POST['valor_unitario'] ?? null);
            $valorTotal = ($_POST['quantidade'] ?? 0) * ($_POST['valor_unitario'] ?? 0);
            $stmt->bindParam(':valor_total', $valorTotal);
            $stmt->bindParam(':validade', $_POST['validade'] ?? null);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_estoque') {
        $sql = "SELECT ec.*, p.nome as produto_nome, p.unidade_medida, p.estoque_minimo, f.nome as fornecedor_nome
                FROM estoque_central ec
                INNER JOIN produto p ON ec.produto_id = p.id
                LEFT JOIN fornecedor f ON ec.fornecedor_id = f.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($_GET['produto_id'])) {
            $sql .= " AND ec.produto_id = :produto_id";
            $params[':produto_id'] = $_GET['produto_id'];
        }
        
        if (!empty($_GET['fornecedor_id'])) {
            $sql .= " AND ec.fornecedor_id = :fornecedor_id";
            $params[':fornecedor_id'] = $_GET['fornecedor_id'];
        }
        
        $sql .= " ORDER BY ec.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular totais por produto
        $sqlTotal = "SELECT produto_id, SUM(quantidade) as total_quantidade
                     FROM estoque_central
                     GROUP BY produto_id";
        $stmtTotal = $conn->prepare($sqlTotal);
        $stmtTotal->execute();
        $totais = [];
        while ($row = $stmtTotal->fetch(PDO::FETCH_ASSOC)) {
            $totais[$row['produto_id']] = $row['total_quantidade'];
        }
        
        foreach ($estoque as &$item) {
            $item['total_produto'] = $totais[$item['produto_id']] ?? 0;
        }
        
        echo json_encode(['success' => true, 'estoque' => $estoque]);
        exit;
    }
}

// Buscar estoque inicial
$sqlEstoque = "SELECT ec.*, p.nome as produto_nome, p.unidade_medida, p.estoque_minimo, f.nome as fornecedor_nome
               FROM estoque_central ec
               INNER JOIN produto p ON ec.produto_id = p.id
               LEFT JOIN fornecedor f ON ec.fornecedor_id = f.id
               ORDER BY ec.criado_em DESC
               LIMIT 50";
$stmtEstoque = $conn->prepare($sqlEstoque);
$stmtEstoque->execute();
$estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - SIGEA</title>
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
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Controle de Estoque</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Estoque Central</h2>
                        <p class="text-gray-600 mt-1">Gerencie entradas e saídas de produtos</p>
                    </div>
                    <button onclick="abrirModalNovaEntrada()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Entrada</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <select id="filtro-produto" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos os produtos</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                            <select id="filtro-fornecedor" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos os fornecedores</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos</option>
                                <option value="baixo">Estoque Baixo</option>
                                <option value="normal">Estoque Normal</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Estoque -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Produto</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Quantidade</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Lote</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Fornecedor</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Validade</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody id="lista-estoque">
                                <?php if (empty($estoque)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhum registro de estoque encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($estoque as $item): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($item['produto_nome']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($item['unidade_medida']) ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium"><?= number_format($item['quantidade'], 2, ',', '.') ?></span>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-600"><?= htmlspecialchars($item['lote'] ?? '-') ?></td>
                                            <td class="py-3 px-4 text-sm text-gray-600"><?= htmlspecialchars($item['fornecedor_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4 text-sm text-gray-600">
                                                <?= $item['validade'] ? date('d/m/Y', strtotime($item['validade'])) : '-' ?>
                                            </td>
                                            <td class="py-3 px-4 text-right font-medium">
                                                R$ <?= number_format($item['valor_total'] ?? 0, 2, ',', '.') ?>
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
    
    <!-- Modal Nova Entrada -->
    <div id="modal-nova-entrada" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <div class="bg-indigo-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Nova Entrada de Estoque</h3>
            <button onclick="fecharModalNovaEntrada()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-entrada" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto *</label>
                            <select id="entrada-produto-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione um produto</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?> (<?= $produto['unidade_medida'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                            <input type="number" step="0.001" min="0" id="entrada-quantidade" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lote</label>
                            <input type="text" id="entrada-lote" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                            <select id="entrada-fornecedor-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione um fornecedor</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nota Fiscal</label>
                            <input type="text" id="entrada-nota-fiscal" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valor Unitário (R$)</label>
                            <input type="number" step="0.01" min="0" id="entrada-valor-unitario" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Validade</label>
                            <input type="date" id="entrada-validade" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovaEntrada()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEntrada()" class="flex-1 px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg font-medium transition-colors">
                    Salvar Entrada
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

        function abrirModalNovaEntrada() {
            document.getElementById('modal-nova-entrada').classList.remove('hidden');
            document.getElementById('form-entrada').reset();
        }

        function fecharModalNovaEntrada() {
            document.getElementById('modal-nova-entrada').classList.add('hidden');
        }

        function salvarEntrada() {
            const formData = new FormData();
            formData.append('acao', 'registrar_entrada');
            formData.append('produto_id', document.getElementById('entrada-produto-id').value);
            formData.append('quantidade', document.getElementById('entrada-quantidade').value);
            formData.append('lote', document.getElementById('entrada-lote').value);
            formData.append('fornecedor_id', document.getElementById('entrada-fornecedor-id').value);
            formData.append('nota_fiscal', document.getElementById('entrada-nota-fiscal').value);
            formData.append('valor_unitario', document.getElementById('entrada-valor-unitario').value);
            formData.append('validade', document.getElementById('entrada-validade').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Entrada registrada com sucesso!');
                    fecharModalNovaEntrada();
                    filtrarEstoque();
                } else {
                    alert('Erro ao registrar entrada: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar entrada.');
            });
        }

        function filtrarEstoque() {
            const produtoId = document.getElementById('filtro-produto').value;
            const fornecedorId = document.getElementById('filtro-fornecedor').value;
            
            let url = '?acao=listar_estoque';
            if (produtoId) url += '&produto_id=' + produtoId;
            if (fornecedorId) url += '&fornecedor_id=' + fornecedorId;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-estoque');
                        tbody.innerHTML = '';
                        
                        if (data.estoque.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhum registro encontrado.</td></tr>';
                            return;
                        }
                        
                        data.estoque.forEach(item => {
                            const validade = item.validade ? new Date(item.validade).toLocaleDateString('pt-BR') : '-';
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900">${item.produto_nome}</div>
                                        <div class="text-sm text-gray-500">${item.unidade_medida}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium">${parseFloat(item.quantidade).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600">${item.lote || '-'}</td>
                                    <td class="py-3 px-4 text-sm text-gray-600">${item.fornecedor_nome || '-'}</td>
                                    <td class="py-3 px-4 text-sm text-gray-600">${validade}</td>
                                    <td class="py-3 px-4 text-right font-medium">
                                        R$ ${parseFloat(item.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar estoque:', error);
                });
        }
    </script>
</body>
</html>

