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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Relatórios Nutricionais') ?></title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Relatórios Nutricionais</h1>
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
                    <h2 class="text-2xl font-bold text-gray-900">Relatórios e Pareceres Técnicos</h2>
                    <p class="text-gray-600 mt-1">Gere relatórios nutricionais e pareceres técnicos sobre cardápios e alimentação escolar</p>
                </div>
                
                <!-- Tipos de Relatórios -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="gerarRelatorio('cardapios')">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="p-3 bg-pink-100 rounded-xl">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Relatório de Cardápios</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Análise nutricional dos cardápios planejados e executados</p>
                        <button class="w-full bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="gerarRelatorio('consumo')">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="p-3 bg-blue-100 rounded-xl">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Relatório de Consumo</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Análise do consumo de alimentos e adequação nutricional</p>
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="gerarRelatorio('desperdicio')">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="p-3 bg-red-100 rounded-xl">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Relatório de Desperdício</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Análise de desperdício e sugestões de redução</p>
                        <button class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="gerarRelatorio('parecer')">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="p-3 bg-purple-100 rounded-xl">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Parecer Técnico</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Parecer técnico sobre adequação nutricional dos cardápios</p>
                        <button class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            Gerar Parecer
                        </button>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="gerarRelatorio('variedade')">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="p-3 bg-green-100 rounded-xl">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Relatório de Variedade</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Análise da variedade de alimentos nos cardápios</p>
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onclick="gerarRelatorio('sazonalidade')">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="p-3 bg-yellow-100 rounded-xl">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Análise de Sazonalidade</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Relatório sobre uso de alimentos sazonais</p>
                        <button class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>
                </div>
                
                <!-- Filtros para Relatórios -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Parâmetros do Relatório</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Período Inicial</label>
                            <input type="date" id="data-inicio" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Período Final</label>
                            <input type="date" id="data-fim" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?= date('Y-m-t') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="escola-relatorio" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Todas as escolas</option>
                                <?php
                                $sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
                                $stmtEscolas = $conn->prepare($sqlEscolas);
                                $stmtEscolas->execute();
                                $escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($escolas as $escola):
                                ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function gerarRelatorio(tipo) {
            const dataInicio = document.getElementById('data-inicio').value;
            const dataFim = document.getElementById('data-fim').value;
            const escolaId = document.getElementById('escola-relatorio').value;
            
            alert(`Funcionalidade de geração de relatório "${tipo}" será implementada em breve.\n\nParâmetros:\n- Período: ${dataInicio} a ${dataFim}\n- Escola: ${escolaId || 'Todas'}`);
        }
    </script>
</body>
</html>

