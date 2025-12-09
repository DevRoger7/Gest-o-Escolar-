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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Gerais - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global-theme.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include('components/sidebar_adm.php'); ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-xl font-bold text-gray-900">Relatórios Gerais</h1>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Cards de Relatórios -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Movimentação Financeira -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Movimentação Financeira</h3>
                        <p class="text-sm text-gray-600 mb-4">Gere relatório completo de movimentações financeiras do sistema</p>
                        <button onclick="gerarMovimentacaoFinanceira()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>

                    <!-- Relatório de Alunos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Relatório de Alunos</h3>
                        <p class="text-sm text-gray-600 mb-4">Relatório completo de alunos cadastrados no sistema</p>
                        <button onclick="gerarRelatorioAlunos()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>

                    <!-- Relatório de Professores -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Relatório de Professores</h3>
                        <p class="text-sm text-gray-600 mb-4">Relatório completo de professores cadastrados</p>
                        <button onclick="gerarRelatorioProfessores()" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                    </div>
                </div>

                <!-- Filtros para Movimentação Financeira -->
                <div id="filtros-movimentacao" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 hidden">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros - Movimentação Financeira</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                            <input type="date" id="data-inicio-financeira" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="data-fim-financeira" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimentação</label>
                            <select id="tipo-movimentacao" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos</option>
                                <option value="ENTRADA">Entrada</option>
                                <option value="SAIDA">Saída</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-3">
                        <button onclick="confirmarGerarMovimentacao()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                        <button onclick="cancelarFiltros()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function gerarMovimentacaoFinanceira() {
            const filtrosDiv = document.getElementById('filtros-movimentacao');
            filtrosDiv.classList.remove('hidden');
            filtrosDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function cancelarFiltros() {
            document.getElementById('filtros-movimentacao').classList.add('hidden');
        }

        function confirmarGerarMovimentacao() {
            const dataInicio = document.getElementById('data-inicio-financeira').value;
            const dataFim = document.getElementById('data-fim-financeira').value;
            const tipo = document.getElementById('tipo-movimentacao').value;

            if (!dataInicio || !dataFim) {
                alert('Por favor, preencha as datas de início e fim');
                return;
            }

            // Gerar relatório (implementar lógica de geração)
            const params = new URLSearchParams({
                tipo: 'movimentacao_financeira',
                data_inicio: dataInicio,
                data_fim: dataFim,
                tipo_movimentacao: tipo || ''
            });

            window.open(`gerar_relatorio.php?${params.toString()}`, '_blank');
        }

        function gerarRelatorioAlunos() {
            window.open('gerar_relatorio.php?tipo=alunos', '_blank');
        }

        function gerarRelatorioProfessores() {
            window.open('gerar_relatorio.php?tipo=professores', '_blank');
        }
    </script>
</body>
</html>

