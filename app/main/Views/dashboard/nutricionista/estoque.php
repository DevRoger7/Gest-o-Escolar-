<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eNutricionista() && !eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sqlEstoque = "SELECT p.id, p.nome, p.unidade_medida, 
               COALESCE(SUM(ec.quantidade), 0) as quantidade_total
               FROM produto p
               LEFT JOIN estoque_central ec ON p.id = ec.produto_id
               WHERE p.ativo = 1
               GROUP BY p.id, p.nome, p.unidade_medida
               ORDER BY p.nome ASC";
$stmtEstoque = $conn->prepare($sqlEstoque);
$stmtEstoque->execute();
$estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);

$sqlConsumo = "SELECT DATE(cd.data) as data, 
               SUM(ci.quantidade) as quantidade_total,
               COUNT(DISTINCT cd.escola_id) as escolas
               FROM consumo_diario cd
               LEFT JOIN consumo_item ci ON cd.id = ci.consumo_diario_id
               WHERE cd.data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
               GROUP BY DATE(cd.data)
               ORDER BY cd.data DESC
               LIMIT 30";
$stmtConsumo = $conn->prepare($sqlConsumo);
$stmtConsumo->execute();
$consumo = $stmtConsumo->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Avaliação de Estoque e Consumo') ?></title>
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
    <?php include '../../components/sidebar_nutricionista.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Avaliação de Estoque e Consumo</h1>
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
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Estoque e Consumo</h2>
                    <p class="text-gray-600 mt-1">Visualize o estoque atual e o consumo registrado para planejar cardápios adequados</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Estoque Atual</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($estoque as $item): ?>
                                    <?php 
                                    $statusClass = $item['quantidade_total'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                    $statusText = $item['quantidade_total'] > 0 ? 'Disponível' : 'Indisponível';
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nome']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['unidade_medida']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($item['quantidade_total'], 2, ',', '.') ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= $statusText ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Consumo Recente -->
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Consumo dos Últimos 30 Dias</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($consumo)): ?>
                            <p class="text-gray-500 text-center">Nenhum consumo registrado nos últimos 30 dias.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade Total</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Escolas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($consumo as $c): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d/m/Y', strtotime($c['data'])) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($c['quantidade_total'], 2, ',', '.') ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $c['escolas'] ?> escola(s)</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

