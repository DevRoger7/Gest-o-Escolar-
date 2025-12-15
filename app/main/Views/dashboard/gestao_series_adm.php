<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_serie') {
        try {
            // Validar campos obrigatórios
            if (empty(trim($_POST['nome'] ?? ''))) {
                throw new Exception('Nome da série é obrigatório.');
            }
            if (empty($_POST['ordem'])) {
                throw new Exception('Ordem da série é obrigatória.');
            }
            
            $nome = trim($_POST['nome']);
            $ordem = (int)$_POST['ordem'];
            $nivelEnsino = !empty($_POST['nivel_ensino']) ? $_POST['nivel_ensino'] : 'ENSINO_FUNDAMENTAL';
            $codigo = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
            $idadeMinima = !empty($_POST['idade_minima']) ? (int)$_POST['idade_minima'] : null;
            $idadeMaxima = !empty($_POST['idade_maxima']) ? (int)$_POST['idade_maxima'] : null;
            $descricao = !empty($_POST['descricao']) ? trim($_POST['descricao']) : null;
            
            $sql = "INSERT INTO serie (nome, codigo, nivel_ensino, ordem, idade_minima, idade_maxima, descricao, ativo, criado_em) 
                    VALUES (:nome, :codigo, :nivel_ensino, :ordem, :idade_minima, :idade_maxima, :descricao, 1, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':nivel_ensino', $nivelEnsino);
            $stmt->bindParam(':ordem', $ordem);
            $stmt->bindParam(':idade_minima', $idadeMinima);
            $stmt->bindParam(':idade_maxima', $idadeMaxima);
            $stmt->bindParam(':descricao', $descricao);
            $resultado = $stmt->execute();
            echo json_encode(['success' => $resultado, 'id' => $conn->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'editar_serie') {
        try {
            // Validar campos obrigatórios
            if (empty(trim($_POST['nome'] ?? ''))) {
                throw new Exception('Nome da série é obrigatório.');
            }
            if (empty($_POST['ordem'])) {
                throw new Exception('Ordem da série é obrigatória.');
            }
            
            $id = (int)$_POST['id'];
            $nome = trim($_POST['nome']);
            $ordem = (int)$_POST['ordem'];
            $nivelEnsino = !empty($_POST['nivel_ensino']) ? $_POST['nivel_ensino'] : 'ENSINO_FUNDAMENTAL';
            $codigo = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
            $idadeMinima = !empty($_POST['idade_minima']) ? (int)$_POST['idade_minima'] : null;
            $idadeMaxima = !empty($_POST['idade_maxima']) ? (int)$_POST['idade_maxima'] : null;
            $descricao = !empty($_POST['descricao']) ? trim($_POST['descricao']) : null;
            $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
            
            $sql = "UPDATE serie SET nome = :nome, codigo = :codigo, nivel_ensino = :nivel_ensino, ordem = :ordem, idade_minima = :idade_minima, idade_maxima = :idade_maxima, descricao = :descricao, ativo = :ativo WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':nivel_ensino', $nivelEnsino);
            $stmt->bindParam(':ordem', $ordem);
            $stmt->bindParam(':idade_minima', $idadeMinima);
            $stmt->bindParam(':idade_maxima', $idadeMaxima);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':ativo', $ativo);
            $resultado = $stmt->execute();
            echo json_encode(['success' => $resultado]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_serie') {
        $sql = "UPDATE serie SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $_POST['id']);
        $resultado = $stmt->execute();
        echo json_encode(['success' => $resultado]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_series') {
        $sql = "SELECT * FROM serie WHERE 1=1";
        $params = [];
        
        if (!empty($_GET['nivel_ensino'])) {
            $sql .= " AND nivel_ensino = :nivel_ensino";
            $params[':nivel_ensino'] = $_GET['nivel_ensino'];
        }
        
        $sql .= " ORDER BY ordem ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'series' => $series]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_serie') {
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM serie WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $serie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($serie) {
            echo json_encode(['success' => true, 'serie' => $serie]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Série não encontrada']);
        }
        exit;
    }
}

$sqlSeries = "SELECT * FROM serie ORDER BY nivel_ensino ASC, ordem ASC";
$stmtSeries = $conn->prepare($sqlSeries);
$stmtSeries->execute();
$series = $stmtSeries->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Séries - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Séries</h1>
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
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Séries</h2>
                        <p class="text-gray-600 mt-1">Crie, edite e exclua séries do sistema</p>
                    </div>
                    <button onclick="abrirModalNovaSerie()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Série</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ordem</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nível</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-series">
                                <?php if (empty($series)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhuma série encontrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($series as $serie): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars($serie['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($serie['ordem']) ?></td>
                                            <td class="py-3 px-4">
                                                <?php
                                                $nivel = $serie['nivel_ensino'] ?? 'ENSINO_FUNDAMENTAL';
                                                $nivelTexto = [
                                                    'EDUCACAO_INFANTIL' => 'Educação Infantil',
                                                    'ENSINO_FUNDAMENTAL' => 'Ensino Fundamental',
                                                    'ENSINO_MEDIO' => 'Ensino Médio',
                                                    'EJA' => 'EJA'
                                                ];
                                                echo htmlspecialchars($nivelTexto[$nivel] ?? $nivel);
                                                ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs <?= $serie['ativo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                    <?= $serie['ativo'] ? 'Ativa' : 'Inativa' ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarSerie(<?= $serie['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirSerie(<?= $serie['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal de Sucesso - Atualização -->
    <div id="modalSucessoAtualizacao" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Série Atualizada</h3>
                        <p class="text-sm text-gray-600">Operação concluída com sucesso</p>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-gray-700 mb-4" id="textoSucessoAtualizacao">
                        Série atualizada com sucesso!
                    </p>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong class="font-medium">Status:</strong> As alterações foram salvas e a série está atualizada no sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <button onclick="fecharModalSucessoAtualizacao()" 
                        class="w-full px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors duration-200">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Série -->
    <div id="modal-editar-serie" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Editar Série</h3>
                <button onclick="fecharModalEditarSerie()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="form-editar-serie" class="space-y-4">
                <input type="hidden" id="editar-serie-id" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome <span class="text-red-500">*</span></label>
                        <input type="text" id="editar-serie-nome" name="nome" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código</label>
                        <input type="text" id="editar-serie-codigo" name="codigo"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nível de Ensino <span class="text-red-500">*</span></label>
                        <select id="editar-serie-nivel-ensino" name="nivel_ensino" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="EDUCACAO_INFANTIL">Educação Infantil</option>
                            <option value="ENSINO_FUNDAMENTAL">Ensino Fundamental</option>
                            <option value="ENSINO_MEDIO">Ensino Médio</option>
                            <option value="EJA">EJA</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ordem <span class="text-red-500">*</span></label>
                        <input type="number" id="editar-serie-ordem" name="ordem" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Idade Mínima</label>
                        <input type="number" id="editar-serie-idade-minima" name="idade_minima" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Idade Máxima</label>
                        <input type="number" id="editar-serie-idade-maxima" name="idade_maxima" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea id="editar-serie-descricao" name="descricao" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"></textarea>
                </div>
                
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="editar-serie-ativo" name="ativo" value="1" checked
                               class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                        <span class="text-sm font-medium text-gray-700">Série Ativa</span>
                    </label>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="fecharModalEditarSerie()" 
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg font-medium transition-colors duration-200">
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

        function abrirModalNovaSerie() {
            const nome = prompt('Nome da série:');
            if (!nome) return;
            
            const ordem = prompt('Ordem (número):');
            if (!ordem) return;
            
            const nivelEnsino = prompt('Nível de Ensino:\n1 - Educação Infantil\n2 - Ensino Fundamental\n3 - Ensino Médio\n4 - EJA\n\nDigite o número:', '2');
            const niveis = {
                '1': 'EDUCACAO_INFANTIL',
                '2': 'ENSINO_FUNDAMENTAL',
                '3': 'ENSINO_MEDIO',
                '4': 'EJA'
            };
            const nivelSelecionado = niveis[nivelEnsino] || 'ENSINO_FUNDAMENTAL';
            
            const codigo = prompt('Código (opcional):', '');
            const idadeMinima = prompt('Idade Mínima (opcional):', '');
            const idadeMaxima = prompt('Idade Máxima (opcional):', '');
            const descricao = prompt('Descrição (opcional):', '');
            
            const formData = new FormData();
            formData.append('acao', 'criar_serie');
            formData.append('nome', nome);
            formData.append('ordem', ordem);
            formData.append('nivel_ensino', nivelSelecionado);
            if (codigo) formData.append('codigo', codigo);
            if (idadeMinima) formData.append('idade_minima', idadeMinima);
            if (idadeMaxima) formData.append('idade_maxima', idadeMaxima);
            if (descricao) formData.append('descricao', descricao);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Série criada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao criar série: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao criar série.');
            });
        }

        function editarSerie(id) {
            // Buscar dados da série
            fetch('?acao=buscar_serie&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.serie) {
                        const serie = data.serie;
                        
                        // Preencher formulário
                        document.getElementById('editar-serie-id').value = serie.id;
                        document.getElementById('editar-serie-nome').value = serie.nome || '';
                        document.getElementById('editar-serie-codigo').value = serie.codigo || '';
                        document.getElementById('editar-serie-nivel-ensino').value = serie.nivel_ensino || 'ENSINO_FUNDAMENTAL';
                        document.getElementById('editar-serie-ordem').value = serie.ordem || '';
                        document.getElementById('editar-serie-idade-minima').value = serie.idade_minima || '';
                        document.getElementById('editar-serie-idade-maxima').value = serie.idade_maxima || '';
                        document.getElementById('editar-serie-descricao').value = serie.descricao || '';
                        document.getElementById('editar-serie-ativo').checked = serie.ativo == 1;
                        
                        // Abrir modal
                        const modal = document.getElementById('modal-editar-serie');
                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                    } else {
                        alert('Erro ao buscar dados da série.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao buscar dados da série.');
                });
        }
        
        function fecharModalEditarSerie() {
            const modal = document.getElementById('modal-editar-serie');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }
        
        function abrirModalSucessoAtualizacao() {
            const modal = document.getElementById('modalSucessoAtualizacao');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        }
        
        function fecharModalSucessoAtualizacao() {
            const modal = document.getElementById('modalSucessoAtualizacao');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
            // Recarregar página após fechar o modal
            location.reload();
        }
        
        // Submeter formulário de edição
        document.getElementById('form-editar-serie').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar_serie');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fecharModalEditarSerie();
                    abrirModalSucessoAtualizacao();
                } else {
                    alert('Erro ao atualizar série: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar série.');
            });
        });

        function excluirSerie(id) {
            if (confirm('Tem certeza que deseja excluir esta série?')) {
                const formData = new FormData();
                formData.append('acao', 'excluir_serie');
                formData.append('id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Série excluída com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir série.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir série.');
                });
            }
        }
    </script>
</body>
</html>

