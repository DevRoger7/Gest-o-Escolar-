<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/DesperdicioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é ADM_MERENDA ou GESTAO (gestor da merenda)
$tipoUsuario = strtolower($_SESSION['tipo'] ?? '');
$ehAdmMerenda = ($tipoUsuario === 'adm_merenda');
$ehGestor = ($tipoUsuario === 'gestao');

if (!$ehAdmMerenda && !$ehGestor) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$desperdicioModel = new DesperdicioModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'registrar_desperdicio') {
        // Apenas ADM_MERENDA pode registrar
        if (!$ehAdmMerenda) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para registrar desperdício']);
            exit;
        }
        
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'data' => $_POST['data'] ?? date('Y-m-d'),
            'turno' => $_POST['turno'] ?? null,
            'produto_id' => $_POST['produto_id'] ?? null,
            'quantidade' => $_POST['quantidade'] ?? null,
            'unidade_medida' => $_POST['unidade_medida'] ?? null,
            'peso_kg' => $_POST['peso_kg'] ?? null,
            'motivo' => $_POST['motivo'] ?? 'OUTROS',
            'motivo_detalhado' => $_POST['motivo_detalhado'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];
        
        $resultado = $desperdicioModel->registrar($dados);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'adicionar_observacao') {
        // Gestor e ADM_MERENDA podem adicionar observações
        $desperdicioId = $_POST['desperdicio_id'] ?? null;
        $observacoes = $_POST['observacoes'] ?? null;
        
        if (!$desperdicioId) {
            echo json_encode(['success' => false, 'message' => 'ID do desperdício não informado']);
            exit;
        }
        
        try {
            $sql = "UPDATE desperdicio SET observacoes = :observacoes WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':id', $desperdicioId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Observação adicionada com sucesso']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar observação: ' . $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_desperdicio') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
        if (!empty($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
        if (!empty($_GET['motivo'])) $filtros['motivo'] = $_GET['motivo'];
        
        $desperdicios = $desperdicioModel->listar($filtros);
        echo json_encode(['success' => true, 'desperdicios' => $desperdicios]);
        exit;
    }
}

// Buscar desperdícios recentes
$desperdiciosRecentes = $desperdicioModel->listar(['data_inicio' => date('Y-m-d', strtotime('-30 days'))]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desperdício - SIGEA</title>
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
    <?php include 'components/sidebar_merenda.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Monitoramento de Desperdício</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Desperdício de Alimentos</h2>
                        <p class="text-gray-600 mt-1">Registre e monitore o desperdício de alimentos</p>
                    </div>
                    <?php if ($ehAdmMerenda): ?>
                    <button onclick="abrirModalRegistrarDesperdicio()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Registrar Desperdício</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarDesperdicio()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                            <input type="date" id="filtro-data-inicio" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarDesperdicio()" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="filtro-data-fim" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarDesperdicio()" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo</label>
                            <select id="filtro-motivo" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarDesperdicio()">
                                <option value="">Todos</option>
                                <option value="EXCESSO_PREPARO">Excesso de Preparo</option>
                                <option value="REJEICAO_ALUNOS">Rejeição dos Alunos</option>
                                <option value="VALIDADE_VENCIDA">Validade Vencida</option>
                                <option value="PREPARO_INCORRETO">Preparo Incorreto</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Produto</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Quantidade</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Peso (kg)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Motivo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Turno</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Observações</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-desperdicio">
                                <?php if (empty($desperdiciosRecentes)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-12 text-gray-600">
                                            Nenhum registro de desperdício encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($desperdiciosRecentes as $desp): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($desp['data'])) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($desp['escola_nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($desp['produto_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?php
                                                if ($desp['quantidade']) {
                                                    $unidade = strtoupper(trim($desp['unidade_medida'] ?? ''));
                                                    $permiteDecimal = in_array($unidade, ['ML', 'L', 'G', 'KG', 'LT', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS', 'GRAMA', 'GRAMAS', 'QUILO', 'QUILOS']);
                                                    $casasDecimais = $permiteDecimal ? 3 : 0;
                                                    echo number_format($desp['quantidade'], $casasDecimais, ',', '.') . ' ' . ($desp['unidade_medida'] ?? '');
                                                } else {
                                                    echo '-';
                                                }
                                            ?></td>
                                            <td class="py-3 px-4 font-medium"><?= $desp['peso_kg'] ? number_format($desp['peso_kg'], 2, ',', '.') . ' kg' : '-' ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs <?php
                                                    echo $desp['motivo'] === 'EXCESSO_PREPARO' ? 'bg-orange-100 text-orange-800' :
                                                        ($desp['motivo'] === 'REJEICAO_ALUNOS' ? 'bg-red-100 text-red-800' :
                                                        ($desp['motivo'] === 'VALIDADE_VENCIDA' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'));
                                                ?>">
                                                    <?= htmlspecialchars($desp['motivo'] ?? 'OUTROS') ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($desp['turno'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <?php if (!empty($desp['observacoes'])): ?>
                                                    <span class="text-sm text-gray-600" title="<?= htmlspecialchars($desp['observacoes']) ?>">
                                                        <?= strlen($desp['observacoes']) > 50 ? htmlspecialchars(substr($desp['observacoes'], 0, 50)) . '...' : htmlspecialchars($desp['observacoes']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-sm text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <button onclick="abrirModalObservacoes(<?= $desp['id'] ?>, '<?= htmlspecialchars(addslashes($desp['observacoes'] ?? '')) ?>')" 
                                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    <?= !empty($desp['observacoes']) ? 'Editar' : 'Adicionar' ?> Observação
                                                </button>
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
    
    <!-- Modal Registrar Desperdício -->
    <div id="modal-registrar-desperdicio" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <div class="bg-red-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Registrar Desperdício</h3>
            <button onclick="fecharModalRegistrarDesperdicio()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-desperdicio" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                            <select id="desperdicio-escola-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data *</label>
                            <input type="date" id="desperdicio-data" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <select id="desperdicio-produto-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione um produto</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?> (<?= $produto['unidade_medida'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
                            <select id="desperdicio-turno" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione</option>
                                <option value="MANHA">Manhã</option>
                                <option value="TARDE">Tarde</option>
                                <option value="NOITE">Noite</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                            <input type="number" step="0.001" min="0" id="desperdicio-quantidade" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Peso (kg) *</label>
                            <input type="number" step="0.01" min="0" id="desperdicio-peso" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo *</label>
                            <select id="desperdicio-motivo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="EXCESSO_PREPARO">Excesso de Preparo</option>
                                <option value="REJEICAO_ALUNOS">Rejeição dos Alunos</option>
                                <option value="VALIDADE_VENCIDA">Validade Vencida</option>
                                <option value="PREPARO_INCORRETO">Preparo Incorreto</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo Detalhado</label>
                        <textarea id="desperdicio-motivo-detalhado" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea id="desperdicio-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalRegistrarDesperdicio()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarDesperdicio()" class="flex-1 px-6 py-3 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors">
                    Salvar Registro
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Observações -->
    <div id="modal-observacoes" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Adicionar Observação</h3>
                    <button onclick="fecharModalObservacoes()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="form-observacoes" onsubmit="event.preventDefault(); salvarObservacao();">
                    <input type="hidden" id="observacao-desperdicio-id">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea id="observacao-texto" rows="6" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Digite suas observações sobre este registro de desperdício..."></textarea>
                        <p class="text-xs text-gray-500 mt-2">Você pode adicionar comentários, sugestões ou informações relevantes sobre este desperdício.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="fecharModalObservacoes()" 
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            Salvar Observação
                        </button>
                    </div>
                </form>
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
        // Função para formatar quantidade baseado na unidade de medida
        function formatarQuantidade(quantidade, unidadeMedida) {
            if (!quantidade && quantidade !== 0) return '0';
            
            const unidade = (unidadeMedida || '').toUpperCase().trim();
            // Unidades que permitem decimais (líquidas e de peso)
            const permiteDecimal = ['ML', 'L', 'G', 'KG', 'LT', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS', 'GRAMA', 'GRAMAS', 'QUILO', 'QUILOS'].includes(unidade);
            const casasDecimais = permiteDecimal ? 3 : 0;
            
            return parseFloat(quantidade).toLocaleString('pt-BR', {
                minimumFractionDigits: casasDecimais,
                maximumFractionDigits: casasDecimais
            });
        }
        
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

        function abrirModalRegistrarDesperdicio() {
            document.getElementById('modal-registrar-desperdicio').classList.remove('hidden');
            document.getElementById('form-desperdicio').reset();
            document.getElementById('desperdicio-data').value = '<?= date('Y-m-d') ?>';
        }

        function fecharModalRegistrarDesperdicio() {
            document.getElementById('modal-registrar-desperdicio').classList.add('hidden');
        }

        function salvarDesperdicio() {
            const formData = new FormData();
            formData.append('acao', 'registrar_desperdicio');
            formData.append('escola_id', document.getElementById('desperdicio-escola-id').value);
            formData.append('data', document.getElementById('desperdicio-data').value);
            formData.append('turno', document.getElementById('desperdicio-turno').value);
            formData.append('produto_id', document.getElementById('desperdicio-produto-id').value);
            formData.append('quantidade', document.getElementById('desperdicio-quantidade').value);
            formData.append('peso_kg', document.getElementById('desperdicio-peso').value);
            formData.append('motivo', document.getElementById('desperdicio-motivo').value);
            formData.append('motivo_detalhado', document.getElementById('desperdicio-motivo-detalhado').value);
            formData.append('observacoes', document.getElementById('desperdicio-observacoes').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
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
                    alert('Desperdício registrado com sucesso!');
                    fecharModalRegistrarDesperdicio();
                    filtrarDesperdicio();
                } else {
                    alert('Erro ao registrar desperdício: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar desperdício.');
            });
        }

        function filtrarDesperdicio() {
            const escolaId = document.getElementById('filtro-escola').value;
            const dataInicio = document.getElementById('filtro-data-inicio').value;
            const dataFim = document.getElementById('filtro-data-fim').value;
            const motivo = document.getElementById('filtro-motivo').value;
            
            let url = '?acao=listar_desperdicio';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (dataInicio) url += '&data_inicio=' + dataInicio;
            if (dataFim) url += '&data_fim=' + dataFim;
            if (motivo) url += '&motivo=' + motivo;
            
            fetch(url)
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
                        const tbody = document.getElementById('lista-desperdicio');
                        tbody.innerHTML = '';
                        
                        if (data.desperdicios.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-12 text-gray-600">Nenhum registro encontrado.</td></tr>';
                            return;
                        }
                        
                        data.desperdicios.forEach(desp => {
                            const motivoClass = desp.motivo === 'EXCESSO_PREPARO' ? 'bg-orange-100 text-orange-800' :
                                              (desp.motivo === 'REJEICAO_ALUNOS' ? 'bg-red-100 text-red-800' :
                                              (desp.motivo === 'VALIDADE_VENCIDA' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'));
                            const dataFormatada = new Date(desp.data).toLocaleDateString('pt-BR');
                            
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${dataFormatada}</td>
                                    <td class="py-3 px-4">${desp.escola_nome}</td>
                                    <td class="py-3 px-4">${desp.produto_nome || '-'}</td>
                                    <td class="py-3 px-4">${desp.quantidade ? formatarQuantidade(desp.quantidade, desp.unidade_medida) + ' ' + (desp.unidade_medida || '') : '-'}</td>
                                    <td class="py-3 px-4 font-medium">${desp.peso_kg ? parseFloat(desp.peso_kg).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg' : '-'}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs ${motivoClass}">
                                            ${desp.motivo || 'OUTROS'}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">${desp.turno || '-'}</td>
                                    <td class="py-3 px-4">
                                        ${desp.observacoes ? 
                                            `<span class="text-sm text-gray-600" title="${desp.observacoes.replace(/"/g, '&quot;')}">
                                                ${desp.observacoes.length > 50 ? desp.observacoes.substring(0, 50) + '...' : desp.observacoes}
                                            </span>` : 
                                            '<span class="text-sm text-gray-400">-</span>'
                                        }
                                    </td>
                                    <td class="py-3 px-4">
                                        <button onclick="abrirModalObservacoes(${desp.id}, '${(desp.observacoes || '').replace(/'/g, "\\'").replace(/"/g, '&quot;')}')" 
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            ${desp.observacoes ? 'Editar' : 'Adicionar'} Observação
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar desperdício:', error);
                });
        }
        
        function abrirModalObservacoes(desperdicioId, observacoesAtuais) {
            document.getElementById('observacao-desperdicio-id').value = desperdicioId;
            document.getElementById('observacao-texto').value = observacoesAtuais || '';
            document.getElementById('modal-observacoes').classList.remove('hidden');
            document.getElementById('modal-observacoes').style.display = 'flex';
        }
        
        function fecharModalObservacoes() {
            document.getElementById('modal-observacoes').classList.add('hidden');
            document.getElementById('modal-observacoes').style.display = 'none';
            document.getElementById('form-observacoes').reset();
        }
        
        async function salvarObservacao() {
            const desperdicioId = document.getElementById('observacao-desperdicio-id').value;
            const observacoes = document.getElementById('observacao-texto').value;
            
            if (!desperdicioId) {
                alert('Erro: ID do desperdício não encontrado');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'adicionar_observacao');
            formData.append('desperdicio_id', desperdicioId);
            formData.append('observacoes', observacoes);
            
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
                    alert('Observação salva com sucesso!');
                    fecharModalObservacoes();
                    filtrarDesperdicio();
                } else {
                    alert('Erro ao salvar observação: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar requisição. Por favor, tente novamente.');
            }
        }
    </script>
</body>
</html>

