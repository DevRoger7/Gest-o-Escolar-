<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/CustoMerendaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$custoModel = new CustoMerendaModel();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $acao = $_GET['acao'];

    // Filtros comuns
    $filtros = [];
    if (!empty($_GET['mes'])) $filtros['mes'] = (int)$_GET['mes'];
    if (!empty($_GET['ano'])) $filtros['ano'] = (int)$_GET['ano'];

    if ($acao === 'buscar_custos') {
        // Ensure clean JSON output and proper content type
        // NEW: aggressively clear all output buffers to avoid BOM/previous output
        while (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $custos = $custoModel->listar($filtros);
        $totais = $custoModel->calcularTotal(null, $filtros['mes'] ?? null, $filtros['ano'] ?? null);
        $totalGeral = array_sum(array_column($totais, 'total_custos'));
        $media = count($custos) > 0 ? ($totalGeral / count($custos)) : 0;

        echo json_encode([
            'success' => true,
            'custos' => $custos,
            'totais' => $totais,
            'totalGeral' => $totalGeral,
            'totalRegistros' => count($custos),
            'media' => $media,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acao === 'exportar_csv') {
        // Exporta CSV respeitando filtros
        $custos = $custoModel->listar($filtros);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="relatorio_financeiro_merenda.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Data', 'Escola / Origem', 'Tipo', 'Descrição', 'Valor Total'], ';');
        foreach ($custos as $custo) {
            $escolaNome = $custo['escola_nome'] ?? ($custo['escola_id'] === null ? 'Compra Centralizada' : '-');
            fputcsv($out, [
                date('d/m/Y', strtotime($custo['data'])),
                $escolaNome,
                $custo['tipo'] ?? 'OUTROS',
                $custo['descricao'] ?? '-',
                number_format($custo['valor_total'] ?? 0, 2, ',', '.')
            ], ';');
        }
        fclose($out);
        exit;
    }
}

$custosMes = $custoModel->listar(['mes' => date('n'), 'ano' => date('Y')]);
$totaisMes = $custoModel->calcularTotal(null, date('n'), date('Y'));
$totalGeral = array_sum(array_column($totaisMes, 'total_custos'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Financeiros - Merenda Escolar - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <!-- jsPDF and AutoTable with proper version and initialization -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Initialize jsPDF in the global scope
        document.addEventListener('DOMContentLoaded', function() {
            window.jsPDF = window.jspdf.jsPDF;
            // Make sure autoTable is available
            if (window.jspdf && window.jspdf.jsPDF) {
                window.jsPDF.autoTable = window.jspdfAutoTable;
            }
        });
    </script>
    <!-- NEW: Chart.js for a simple bar chart summary -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- NEW: small CSS for loading state on "Gerar Relatório" button -->
    <style>
        .btn-loading { opacity: 0.6; pointer-events: none; }
    </style>
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
        /* Garantir que o conteúdo não fique por baixo da sidebar */
        main {
            position: relative;
            z-index: 10;
        }
        #resumo-bonito {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen relative z-10">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Relatórios Financeiros - Merenda Escolar</h1>
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
                    <h2 class="text-2xl font-bold text-gray-900">Relatórios Financeiros - Merenda Escolar</h2>
                    <p class="text-gray-600 mt-1">
                        Acompanhe os custos da merenda escolar. <strong>Compras são centralizadas</strong> (sem escola específica) 
                        e os produtos são <strong>distribuídos gratuitamente</strong> para as escolas.
                    </p>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300" id="card-total-mes">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-semibold">Total</span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" id="valor-total-mes">R$ <?= number_format($totalGeral, 2, ',', '.') ?></h3>
                        <p class="text-teal-100 text-sm font-medium">Total do Mês</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300" id="card-registros">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-semibold">Registros</span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" id="total-registros"><?= count($custosMes) ?></h3>
                        <p class="text-blue-100 text-sm font-medium">Registros do Mês</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 shadow-xl transform hover:scale-105 transition-all duration-300" id="card-media">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-semibold">Média</span>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" id="valor-media">R$ <?= number_format($totalGeral / max(count($custosMes), 1), 2, ',', '.') ?></h3>
                        <p class="text-green-100 text-sm font-medium">Média por Registro</p>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="buscarRelatorio()">
                                <option value="">Todos</option>
                                <?php 
                                $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('m') ? 'selected' : '' ?>><?= $meses[$i - 1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="buscarRelatorio()">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex items-end space-x-2">
                            <button onclick="gerarPdf(true)" class="w-full bg-indigo-100 hover:bg-indigo-200 text-indigo-800 px-4 py-2 rounded-lg font-medium border border-indigo-200">
                                Visualizar PDF
                            </button>
                            <button onclick="gerarPdf(false)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm">
                                Baixar PDF
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Custos com botão de visualização -->
                <div class="mt-6">
                    <button id="toggle-tabela" type="button" class="flex items-center text-sm text-indigo-600 hover:text-indigo-800 mb-2 font-medium">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span id="toggle-text">Mostrar Tabela Detalhada</span>
                    </button>
                    <div id="bloco-relatorio" style="display: none;" class="bg-white rounded-lg shadow p-4 mt-2">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola / Origem</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tipo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Descrição</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody id="lista-custos">
                                <?php if (empty($custosMes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhum registro de custo encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($custosMes as $custo): 
                                        $escolaNome = $custo['escola_nome'] ?? ($custo['escola_id'] === null ? 'Compra Centralizada' : '-');
                                        $tipoClass = ($custo['tipo'] === 'COMPRA_PRODUTOS' && $custo['escola_id'] === null) 
                                            ? 'bg-blue-100 text-blue-800' 
                                            : 'bg-gray-100 text-gray-800';
                                    ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($custo['data'])) ?></td>
                                            <td class="py-3 px-4">
                                                <?= htmlspecialchars($escolaNome) ?>
                                                <?php if ($custo['escola_id'] === null): ?>
                                                    <span class="ml-2 text-xs text-blue-600">(Central)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs <?= $tipoClass ?>">
                                                    <?= htmlspecialchars($custo['tipo'] ?? 'OUTROS') ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($custo['descricao'] ?? '-') ?></td>
                                            <td class="py-3 px-4 text-right font-medium">
                                                R$ <?= number_format($custo['valor_total'] ?? 0, 2, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Relatório Completo e Detalhado -->
                <div class="mt-8 space-y-6" id="resumo-bonito">
                    <!-- Cards de Estatísticas Avançadas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 shadow-lg text-white">
                            <div class="flex items-center justify-between mb-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-white/20 px-2 py-1 rounded">Maior</span>
                            </div>
                            <h3 class="text-2xl font-bold mb-1" id="maior-custo">R$ 0,00</h3>
                            <p class="text-blue-100 text-sm">Maior custo único</p>
                        </div>

                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 shadow-lg text-white">
                            <div class="flex items-center justify-between mb-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-white/20 px-2 py-1 rounded">Central</span>
                            </div>
                            <h3 class="text-2xl font-bold mb-1" id="total-centralizado">R$ 0,00</h3>
                        </div>
                    </div>

                    <!-- Análise Detalhada -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Gráfico Pizza por Tipo -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                    </svg>
                                    Distribuição por Tipo
                                </h3>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">%</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="grafico-tipos-pizza"></canvas>
                            </div>
                            <div id="legenda-tipos" class="mt-4 grid grid-cols-2 gap-2 text-xs"></div>
                        </div>

                        <!-- Gráfico Barras por Tipo -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Comparação por Tipo
                                </h3>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">R$</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="grafico-tipos-barras"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Análise Temporal e Detalhes -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Distribuição Temporal -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Distribuição Temporal
                                </h3>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Últimos 7 dias</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="grafico-temporal"></canvas>
                            </div>
                        </div>

                        <!-- Análise Detalhada por Tipo -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Detalhamento por Tipo
                                </h3>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Análise</span>
                            </div>
                            <div class="space-y-3" id="detalhamento-tipos">
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-sm">Gere o relatório para visualizar</p>
                                </div>
                            </div>
                        </div>
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

        // Helper to build an absolute URL for actions with filters and cache-busting
        function buildRelatorioUrl(acao) {
            // NEW: use an absolute URL based on the current location and clear any existing query
            const url = new URL(window.location.href);
            url.search = '';
            url.searchParams.set('acao', acao);

            const mes = document.getElementById('filtro-mes').value;
            const ano = document.getElementById('filtro-ano').value;
            if (mes) url.searchParams.set('mes', mes);
            if (ano) url.searchParams.set('ano', ano);

            // Avoid cached responses
            url.searchParams.set('_ts', Date.now());

            return url.toString();
        }

        // NEW: format YYYY-MM-DD safely without timezone shifts or invalid dates
        function formatPtBrDate(dateStr) {
            if (!dateStr || typeof dateStr !== 'string') return '-';
            const parts = dateStr.split('-'); // [YYYY, MM, DD]
            if (parts.length !== 3) return '-';
            const [y, m, d] = parts;
            if (!y || !m || !d) return '-';
            return `${d.padStart(2, '0')}/${m.padStart(2, '0')}/${y}`;
        }


        // Função completa para renderizar relatório detalhado e bonito
        function renderResumo(data) {
            const custos = Array.isArray(data?.custos) ? data.custos : [];
            const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Limpar dados se vazio
            if (custos.length === 0) {
                limparResumo();
                return;
            }

            // Calcular estatísticas básicas
            const valores = custos.map(c => Number.parseFloat(c.valor_total) || 0);
            const maiorCusto = Math.max(...valores, 0);
            const menorCusto = Math.min(...valores.filter(v => v > 0), 0);
            const totalGeral = valores.reduce((a, b) => a + b, 0);
            const media = valores.length > 0 ? totalGeral / valores.length : 0;

            // Separar centralizado
            let totalCentralizado = 0, registrosCentralizado = 0;

            const porOrigem = new Map();
            const porTipo = new Map();
            const porData = new Map();
            const porTipoDetalhado = new Map();

            custos.forEach(custo => {
                const origem = custo.escola_nome || (custo.escola_id === null ? 'Compra Centralizada' : '-');
                const tipo = custo.tipo || 'OUTROS';
                const valor = Number.parseFloat(custo.valor_total) || 0;
                const dataStr = custo.data || '';

                // Agrupar por origem
                const info = porOrigem.get(origem) || { total: 0, registros: 0 };
                info.total += valor;
                info.registros += 1;
                porOrigem.set(origem, info);

                // Agrupar por tipo
                const tipoInfo = porTipoDetalhado.get(tipo) || { total: 0, registros: 0 };
                tipoInfo.total += valor;
                tipoInfo.registros += 1;
                porTipoDetalhado.set(tipo, tipoInfo);
                porTipo.set(tipo, (porTipo.get(tipo) || 0) + valor);

                // Agrupar por data (últimos 7 dias)
                if (dataStr) {
                    const dataObj = new Date(dataStr + 'T00:00:00');
                    const hoje = new Date();
                    const diffTime = hoje - dataObj;
                    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays >= 0 && diffDays < 7) {
                        const dataKey = dataStr;
                        porData.set(dataKey, (porData.get(dataKey) || 0) + valor);
                    }
                }

                // Separar centralizado
                if (custo.escola_id === null) {
                    totalCentralizado += valor;
                    registrosCentralizado += 1;
                }
            });

            // Atualizar cards de estatísticas (com verificação de segurança)
            const maiorCustoEl = document.getElementById('maior-custo');
            const totalCentralizadoEl = document.getElementById('total-centralizado');
            
            if (maiorCustoEl) maiorCustoEl.innerText = 'R$ ' + formatCurrency(maiorCusto);
            if (totalCentralizadoEl) totalCentralizadoEl.innerText = 'R$ ' + formatCurrency(totalCentralizado);

            // Gráfico Pizza por Tipo
            const ctxPizza = document.getElementById('grafico-tipos-pizza');
            if (ctxPizza) {
                const labels = Array.from(porTipo.keys());
                const valores = labels.map(l => porTipo.get(l));
                const cores = ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444', '#06B6D4'];

                if (window.graficoTiposPizza) window.graficoTiposPizza.destroy();
                window.graficoTiposPizza = new Chart(ctxPizza, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: valores,
                            backgroundColor: cores.slice(0, labels.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = valores.reduce((a, b) => a + b, 0);
                                        const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: R$ ${formatCurrency(value)} (${percent}%)`;
                                    }
                                }
                            }
                        }
                    }
                });

                // Legenda customizada
                const legenda = document.getElementById('legenda-tipos');
                if (legenda) {
                    legenda.innerHTML = '';
                    const total = valores.reduce((a, b) => a + b, 0);
                    labels.forEach((label, i) => {
                        const valor = valores[i];
                        const percent = total > 0 ? ((valor / total) * 100).toFixed(1) : 0;
                        legenda.innerHTML += `
                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                                <div class="w-3 h-3 rounded-full" style="background-color: ${cores[i]}"></div>
                                <span class="text-xs font-medium text-gray-700 flex-1">${label}</span>
                                <span class="text-xs font-bold text-gray-900">${percent}%</span>
                            </div>
                        `;
                    });
                }
            }

            // Gráfico Barras por Tipo
            const ctxBarras = document.getElementById('grafico-tipos-barras');
            if (ctxBarras) {
                const labels = Array.from(porTipo.keys());
                const valores = labels.map(l => porTipo.get(l));
                const cores = ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444', '#06B6D4'];

                if (window.graficoTiposBarras) window.graficoTiposBarras.destroy();
                window.graficoTiposBarras = new Chart(ctxBarras, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total (R$)',
                            data: valores,
                            backgroundColor: cores.slice(0, labels.length),
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R$ ' + formatCurrency(context.parsed.y);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + formatCurrency(value);
                                    }
                                },
                                grid: { color: '#F3F4F6' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Gráfico Temporal (últimos 7 dias)
            const ctxTemporal = document.getElementById('grafico-temporal');
            if (ctxTemporal) {
                const hoje = new Date();
                const ultimos7Dias = [];
                const dadosTemporal = [];

                for (let i = 6; i >= 0; i--) {
                    const data = new Date(hoje);
                    data.setDate(data.getDate() - i);
                    const dataStr = data.toISOString().split('T')[0];
                    const dataFormatada = data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                    ultimos7Dias.push(dataFormatada);
                    dadosTemporal.push(porData.get(dataStr) || 0);
                }

                if (window.graficoTemporal) window.graficoTemporal.destroy();
                window.graficoTemporal = new Chart(ctxTemporal, {
                    type: 'line',
                    data: {
                        labels: ultimos7Dias,
                        datasets: [{
                            label: 'Gastos (R$)',
                            data: dadosTemporal,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#3B82F6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R$ ' + formatCurrency(context.parsed.y);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + formatCurrency(value);
                                    }
                                },
                                grid: { color: '#F3F4F6' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Detalhamento por Tipo
            const detalhamento = document.getElementById('detalhamento-tipos');
            if (detalhamento) {
                detalhamento.innerHTML = '';
                const tiposOrdenados = Array.from(porTipoDetalhado.entries())
                    .sort((a, b) => b[1].total - a[1].total);

                if (tiposOrdenados.length === 0) {
                    detalhamento.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">Sem dados</div>';
                } else {
                    tiposOrdenados.forEach(([tipo, info]) => {
                        const percent = totalGeral > 0 ? ((info.total / totalGeral) * 100).toFixed(1) : 0;
                        const coresTipo = {
                            'COMPRA_PRODUTOS': 'bg-blue-500',
                            'DISTRIBUICAO': 'bg-green-500',
                            'PREPARO': 'bg-purple-500',
                            'DESPERDICIO': 'bg-red-500',
                            'OUTROS': 'bg-gray-500'
                        };
                        const cor = coresTipo[tipo] || 'bg-gray-500';
                        detalhamento.innerHTML += `
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800">${tipo}</span>
                                    <span class="text-sm font-bold text-gray-600">${percent}%</span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 mb-2">R$ ${formatCurrency(info.total)}</div>
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="${cor} h-2 rounded-full transition-all duration-500" style="width: ${percent}%"></div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-600">
                                    <span>${info.registros} registro(s)</span>
                                    <span>Média: R$ ${formatCurrency(info.total / info.registros)}</span>
                                </div>
                            </div>
                        `;
                    });
                }
            }
        }

        function limparResumo() {
            const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Limpar com verificações de segurança
            const maiorCustoEl = document.getElementById('maior-custo');
            const totalCentralizadoEl = document.getElementById('total-centralizado');
            const detalhamentoTipos = document.getElementById('detalhamento-tipos');

            if (maiorCustoEl) maiorCustoEl.innerText = 'R$ 0,00';
            if (totalCentralizadoEl) totalCentralizadoEl.innerText = 'R$ 0,00';
            if (detalhamentoTipos) detalhamentoTipos.innerHTML = '<div class="text-center py-8 text-gray-500"><p class="text-sm">Sem dados para o período selecionado.</p></div>';
            
            // Limpar gráficos
            if (window.graficoTiposPizza) { window.graficoTiposPizza.destroy(); window.graficoTiposPizza = null; }
            if (window.graficoTiposBarras) { window.graficoTiposBarras.destroy(); window.graficoTiposBarras = null; }
            if (window.graficoTemporal) { window.graficoTemporal.destroy(); window.graficoTemporal = null; }
        }

        // Updated: robust response handling with absolute URL and visible error messages
        function buscarRelatorio() {
            const url = buildRelatorioUrl('buscar_custos');
            console.log('Relatório URL:', url);

            // Return a promise to handle async operations
            return new Promise((resolve, reject) => {
                // Show report section
                const reportSection = document.getElementById('bloco-relatorio');
                if (reportSection) {
                    reportSection.style.display = 'block';
                }

                // Make the AJAX request
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Process the data and update the UI
                        renderResumo(data);
                        
                        // Show the report section
                        if (reportSection) {
                            reportSection.style.display = 'block';
                        }
                        
                        // Resolve the promise with the data
                        resolve(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Show error message
                        if (reportSection) {
                            reportSection.innerHTML = `
                                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-red-700">
                                                Erro ao carregar os dados do relatório. Por favor, tente novamente.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Reject the promise with the error
                        reject(error);
                    });
            });
            const gerarBtn = document.querySelector('button[onclick="buscarRelatorio()"]');
            if (gerarBtn) {
                gerarBtn.classList.add('btn-loading');
                gerarBtn.textContent = 'Gerando...';
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            })
            .then(async (response) => {
                const ct = response.headers.get('content-type') || '';
                const text = await response.text();

                if (response.redirected || ct.includes('text/html')) {
                    throw new Error('Sessão expirada ou sem permissão (recebido HTML/redirect).');
                }
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + text.slice(0, 200));
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Resposta não JSON: ' + text.slice(0, 200));
                }
            })
            .then((data) => {
                if (!data || !data.success) {
                    throw new Error(data && data.error ? data.error : 'Falha ao carregar dados do relatório.');
                }

                const tbody = document.getElementById('lista-custos');
                tbody.innerHTML = '';

                if (!Array.isArray(data.custos) || data.custos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-600">Nenhum registro encontrado.</td></tr>';
                    document.getElementById('valor-total-mes').innerText = 'R$ 0,00';
                    document.getElementById('total-registros').innerText = '0';
                    document.getElementById('valor-media').innerText = 'R$ 0,00';
                    // NEW: clear summary if empty
                    renderResumo({ custos: [] });
                    return;
                }

                data.custos.forEach(custo => {
                    const dataFormatada = formatPtBrDate(custo.data);
                    const escolaNome = custo.escola_nome || (custo.escola_id === null ? 'Compra Centralizada' : '-');
                    const tipoClass = custo.tipo === 'COMPRA_PRODUTOS' && custo.escola_id === null
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-gray-100 text-gray-800';
                    const valorFmt = (Number.parseFloat(custo.valor_total) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    tbody.innerHTML += `
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">${dataFormatada}</td>
                            <td class="py-3 px-4">
                                ${escolaNome}
                                ${custo.escola_id === null ? '<span class="ml-2 text-xs text-blue-600">(Central)</span>' : ''}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded text-xs ${tipoClass}">
                                    ${custo.tipo || 'OUTROS'}
                                </span>
                            </td>
                            <td class="py-3 px-4">${custo.descricao || '-'}</td>
                            <td class="py-3 px-4 text-right font-medium">R$ ${valorFmt}</td>
                        </tr>
                    `;
                });

                const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('valor-total-mes').innerText = 'R$ ' + formatCurrency(data.totalGeral);
                document.getElementById('total-registros').innerText = data.totalRegistros;
                document.getElementById('valor-media').innerText = 'R$ ' + formatCurrency(data.media);

                // NEW: render the pretty summary section
                renderResumo(data);
            })
            .catch(error => {
                console.error('Erro ao buscar relatório:', error);
                alert('Não foi possível gerar o relatório: ' + error.message);
                // NEW: clear summary on error
                renderResumo({ custos: [] });
            })
            .finally(() => {
                if (gerarBtn) {
                    gerarBtn.classList.remove('btn-loading');
                    gerarBtn.textContent = 'Gerar Relatório';
                }
            });
        }

        // Optional: auto-generate on first load to show the pretty section immediately
        document.addEventListener('DOMContentLoaded', function () {
            // Pequeno delay para garantir que todos os elementos estejam carregados
            setTimeout(function() {
                // Keep initial PHP-rendered data visible; this call syncs the summary section with filters
                buscarRelatorio();
            }, 100);
        });

        function exportarCsv() {
            const mesSelect = document.getElementById('filtro-mes');
            const anoSelect = document.getElementById('filtro-ano');
            const mes = mesSelect.value || '';
            const ano = anoSelect.value || '';

            const url = buildRelatorioUrl('exportar_csv');
            window.location.href = url;
        }

        function gerarPdf(preview = false) {
            // Check if jsPDF is available
            if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                alert('Biblioteca de PDF não carregou. Por favor, atualize a página e tente novamente.');
                return;
            }
            
            // Create PDF instance in portrait mode
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'pt',
                format: 'a4'
            });
            
            // Set default font
            doc.setFont('helvetica');
            
            // Make sure autoTable is available
            if (typeof window.jspdfAutoTable !== 'undefined') {
                doc.autoTable = window.jspdfAutoTable;
            }

            // Show loading state
            const loadingMessage = preview ? 'Preparando visualização...' : 'Gerando PDF...';
            const loadingElement = document.createElement('div');
            loadingElement.style.position = 'fixed';
            loadingElement.style.top = '50%';
            loadingElement.style.left = '50%';
            loadingElement.style.transform = 'translate(-50%, -50%)';
            loadingElement.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            loadingElement.style.color = 'white';
            loadingElement.style.padding = '15px 30px';
            loadingElement.style.borderRadius = '5px';
            loadingElement.style.zIndex = '9999';
            loadingElement.textContent = loadingMessage;
            document.body.appendChild(loadingElement);

            // First ensure the report is generated
            const rows = Array.from(document.querySelectorAll('#lista-custos tr'));
            if (!rows.length || rows[0].querySelector('td[colspan]')) {
                // If no data, try to fetch it first
                buscarRelatorio().then(() => {
                    // After fetching, try generating PDF again
                    setTimeout(() => {
                        document.body.removeChild(loadingElement);
                        gerarPdf(preview);
                    }, 1000);
                }).catch(error => {
                    console.error('Error fetching data:', error);
                    document.body.removeChild(loadingElement);
                    alert('Erro ao buscar dados. Por favor, tente novamente.');
                });
                return;
            }

            // Check if AutoTable is available
            if (typeof doc.autoTable !== 'function') {
                alert('O plugin AutoTable não foi carregado corretamente. Por favor, atualize a página.');
                if (document.body.contains(loadingElement)) {
                    document.body.removeChild(loadingElement);
                }
                return;
            }

            const mesSelect = document.getElementById('filtro-mes');
            const anoSelect = document.getElementById('filtro-ano');
            const mesText = mesSelect.options[mesSelect.selectedIndex]?.text || 'Todos';
            const anoText = anoSelect.value || 'Todos';

            // Coletar dados principais
            const total = document.getElementById('valor-total-mes')?.innerText || 'R$ 0,00';
            const registros = document.getElementById('total-registros')?.innerText || '0';
            const media = document.getElementById('valor-media')?.innerText || 'R$ 0,00';

            // Coletar estatísticas avançadas
            const maiorCusto = document.getElementById('maior-custo')?.innerText || 'R$ 0,00';
            const totalCentralizado = document.getElementById('total-centralizado')?.innerText || 'R$ 0,00';

            // Document dimensions and styles
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const margem = 40;
            let yPos = margem;

            // Cores personalizadas
            const corPrimaria = [59, 130, 246]; // Azul
            const corSecundaria = [16, 185, 129]; // Verde
            const corDestaque = [139, 92, 246]; // Roxo
            
            // Cabeçalho compacto
            doc.setFontSize(14); // Aumentado de 12 para 14
            doc.setFont('helvetica', 'bold');
            doc.text('Relatório Financeiro - Merenda Escolar', pageWidth / 2, 30, { align: 'center' });
            
            // Data/hora e período lado a lado
            doc.setFontSize(10); // Aumentado de 8 para 10
            doc.setFont('helvetica', 'normal');
            const now = new Date();
            const options = { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            };
            const dataHora = now.toLocaleString('pt-BR', options);
            
            doc.text(`Período: ${mesText}/${anoText}`, margem, 45);
            doc.text(`Gerado em: ${dataHora}`, pageWidth - margem, 45, { align: 'right' });
            
            // Linha divisória fina
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.3);
            doc.line(margem, 52, pageWidth - margem, 52);
            
            yPos = 70; // Ajustado para acomodar fontes maiores

            

            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            const statsWidth = (pageWidth - (margem * 2)) / 3;
            const statsX = [margem, margem + statsWidth, margem + (statsWidth * 2)];

            // Tabela principal de custos
            doc.setFontSize(14); // Aumentado de 12 para 14
            doc.setFont(undefined, 'bold');
            doc.text('Detalhamento de Custos', margem, yPos);
            yPos += 20; // Aumentado o espaçamento

            const head = [['Data', 'Escola / Origem', 'Tipo', 'Descrição', 'Valor Total']];
            const body = rows.map(tr => {
                const tds = Array.from(tr.querySelectorAll('td'));
                if (tds.length >= 5) {
                    return [
                        tds[0]?.innerText.trim() || '',
                        tds[1]?.innerText.trim() || '',
                        tds[2]?.innerText.trim() || '',
                        tds[3]?.innerText.trim() || '',
                        tds[4]?.innerText.trim() || ''
                    ];
                }
                return null;
            }).filter(row => row !== null);

            // Configurações da tabela de detalhamento de custos
            doc.autoTable({
                head,
                body,
                startY: yPos,
                margin: { 
                    left: 15, // Reduzido para 15 (era margem)
                    right: 15, // Reduzido para 15 (era margem)
                    top: 10,
                    bottom: 20
                },
                tableWidth: 'wrap', // Permite que a tabela seja maior que a página
                styles: { 
                    fontSize: 9,
                    overflow: 'linebreak',
                    cellPadding: 2, // Reduzido para 2 para melhorar o espaço
                    lineWidth: 0.1,
                    minCellHeight: 8,
                    cellWidth: 'auto',
                    valign: 'middle'
                },
                headStyles: { 
                    fillColor: corPrimaria, 
                    textColor: [255, 255, 255], 
                    fontStyle: 'bold',
                    fontSize: 9,
                    cellPadding: 3,
                    lineWidth: 0.1,
                    halign: 'center'
                },
                alternateRowStyles: { 
                    fillColor: [245, 247, 250],
                    textColor: [0, 0, 0],
                    fontSize: 9,
                    cellPadding: 2
                },
                columnStyles: { 
                    0: { 
                        cellWidth: 55,   // Data
                        halign: 'center',
                        minCellWidth: 50
                    },
                    1: { 
                        cellWidth: 120,  // Origem
                        minCellWidth: 100
                    },
                    2: { 
                        cellWidth: 80,   // Tipo
                        halign: 'center',
                        minCellWidth: 60
                    },
                    3: { 
                        cellWidth: 180,  // Descrição
                        minCellWidth: 150
                    },
                    4: { 
                        cellWidth: 120,  // Valor Total - Aumentado para 120
                        halign: 'right',
                        fontStyle: 'bold',
                        cellPadding: { right: 15 }, // Aumentado o espaço à direita
                        minCellWidth: 100
                    }
                },
                theme: 'grid',
                tableLineColor: [200, 200, 200],
                tableLineWidth: 0.2,
                showHead: 'everyPage', // Mostra o cabeçalho em todas as páginas
                didDrawPage: function(data) {
                    // Adiciona o cabeçalho da seção em cada nova página
                    if (data.pageNumber > 1) {
                        doc.setFontSize(14);
                        doc.setFont(undefined, 'bold');
                        doc.text('Detalhamento de Custos (continuação)', margem, 30);
                    }
                }
            });
            
            // Atualiza a posição Y após a tabela
            yPos = doc.lastAutoTable.finalY + 10;

            // Posiciona os cards no final do documento, antes do rodapé
            let cardsY = doc.lastAutoTable.finalY + 40; // Aumentado o espaçamento
            
            // Verifica se tem espaço suficiente para os cards + rodapé (150 = altura dos cards + título + margem)
            if (cardsY > pageHeight - 150) {
                doc.addPage();
                cardsY = 40;
            }

            // Título da seção de resumo
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('Resumo Financeiro', margem, cardsY);
            cardsY += 25;

            // Cards de estatísticas
            const cardHeight = 70;
            const cardMargin = 15; // Aumentado o espaçamento entre os cards
            const cardWidth = (pageWidth - (margem * 2) - (cardMargin * 2)) / 3;
            
            // Card 1: Total Geral
            doc.setFillColor(16, 185, 129);
            doc.rect(margem, cardsY, cardWidth, cardHeight, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('TOTAL GERAL', margem + (cardWidth / 2), cardsY + 15, { align: 'center' });
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text(total, margem + (cardWidth / 2), cardsY + 40, { align: 'center' });

            // Card 2: Maior Custo
            doc.setFillColor(59, 130, 246);
            doc.rect(margem + cardWidth + cardMargin, cardsY, cardWidth, cardHeight, 'F');
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('MAIOR CUSTO', margem + cardWidth + (cardWidth / 2) + cardMargin, cardsY + 15, { align: 'center' });
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text(maiorCusto, margem + cardWidth + (cardWidth / 2) + cardMargin, cardsY + 40, { align: 'center' });

            // Card 3: Total Centralizado
            doc.setFillColor(139, 92, 246);
            doc.rect(margem + (cardWidth * 2) + (cardMargin * 2), cardsY, cardWidth, cardHeight, 'F');
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('TOTAL CENTRALIZADO', margem + (cardWidth * 2) + (cardWidth / 2) + (cardMargin * 2), cardsY + 15, { align: 'center' });
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text(totalCentralizado, margem + (cardWidth * 2) + (cardWidth / 2) + (cardMargin * 2), cardsY + 40, { align: 'center' });

            // Atualiza a posição Y para o rodapé
            cardsY += cardHeight + 30; // Aumentado o espaçamento após os cards

            // Adiciona o rodapé
            const footerY = Math.max(cardsY, pageHeight - 40);
            doc.setFontSize(8);
            doc.setTextColor(128, 128, 128);
            doc.text(`Total de Registros: ${registros} | Média: ${media}`, 
                     pageWidth / 2, footerY, { align: 'center' });
            doc.text('SIGEA - Sistema de Gestão e Alimentação Escolar - Secretaria Municipal da Educação de Maranguape', 
                     pageWidth / 2, footerY + 10, { align: 'center' });

            // Remove loading message
            if (document.body.contains(loadingElement)) {
                document.body.removeChild(loadingElement);
            }

            // Gerar ou visualizar PDF
            try {
                if (preview) {
                    // Para visualização
                    doc.output('dataurlnewwindow');
                } else {
                    // Para download
                    const fileName = `relatorio_financeiro_merenda_${mesText}_${anoText}_${new Date().getTime()}.pdf`;
                    doc.save(fileName);
                }
                
                // Remove loading message after a short delay
                setTimeout(() => {
                    if (document.body.contains(loadingElement)) {
                        document.body.removeChild(loadingElement);
                    }
                }, 500);
                
            } catch (error) {
                console.error('Erro ao gerar PDF:', error);
                
                // Remove loading message on error
                if (document.body.contains(loadingElement)) {
                    document.body.removeChild(loadingElement);
                }
                
                alert('Ocorreu um erro ao gerar o PDF. Por favor, tente novamente.');
                
                // Se for preview, tenta abrir em uma nova aba como fallback
                if (preview) {
                    try {
                        const pdfData = doc.output('datauristring');
                        const newWindow = window.open();
                        newWindow.document.write(`<iframe width='100%' height='100%' src='${pdfData}'></iframe>`);
                    } catch (e) {
                        console.error('Fallback preview failed:', e);
                    }
                }
            }
        }

        // Toggle da visibilidade da tabela
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos da interface
            const toggleBtn = document.getElementById('toggle-tabela');
            const tabela = document.getElementById('bloco-relatorio');
            const eyeIcon = document.getElementById('eye-icon');
            const toggleText = document.getElementById('toggle-text');
            
            // Garante que a tabela comece oculta
            if (tabela) {
                tabela.style.display = 'none';
            }
            
            if (toggleBtn && tabela && eyeIcon && toggleText) {
                toggleBtn.addEventListener('click', function() {
                    // Alterna a visibilidade da tabela
                    const isHidden = tabela.style.display === 'none';
                    tabela.style.display = isHidden ? 'block' : 'none';
                    
                    // Atualiza o ícone e o texto do botão
                    if (isHidden) {
                        // Mostrar tabela
                        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
                        toggleText.textContent = 'Ocultar Tabela Detalhada';
                    } else {
                        // Esconder tabela
                        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                        toggleText.textContent = 'Mostrar Tabela Detalhada';
                    }
                });
            }
        });

        function mostrarRelatorio() {
            const tabela = document.getElementById("bloco-relatorio");
            const toggleBtn = document.getElementById('toggle-tabela');
            if (tabela && toggleBtn) {
                tabela.style.display = "block";
                // Atualiza o ícone para mostrar que a tabela está visível
                const olhoIcon = toggleBtn.querySelector('svg');
                olhoIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
                toggleBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">' + olhoIcon.innerHTML + '</svg> Ocultar Tabela Detalhada';
            }
        }


    </script>
</body>
</html>
