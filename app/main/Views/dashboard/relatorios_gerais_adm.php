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

    <!-- ADDED: Chart.js for in-page graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ADDED: jsPDF + html2canvas for client-side PDF generation (no server libs needed) -->
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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

                    <!-- UPDATED: Buttons (Relatório -> gráfico) + ADDED: Gerar PDF -->
                    <div class="mt-4 flex gap-3">
                        <button onclick="gerarGraficoMovimentacao()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Gerar Relatório
                        </button>
                        <button onclick="gerarPdfMovimentacao()" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Gerar PDF
                        </button>
                        <button onclick="cancelarFiltros()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>

                <!-- ADDED: Chart container for Movimentação Financeira -->
                <div id="grafico-container" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 hidden">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Gráfico - Movimentação Financeira</h3>
                    <canvas id="grafico-movimentacao-canvas" height="120"></canvas>
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
            // Hide chart when canceling filters
            const graficoContainer = document.getElementById('grafico-container');
            graficoContainer.classList.add('hidden');
        }

        // UPDATED: "Gerar Relatório" now renders a chart in-page
        function gerarGraficoMovimentacao() {
            const dataInicio = document.getElementById('data-inicio-financeira').value;
            const dataFim = document.getElementById('data-fim-financeira').value;
            const tipo = document.getElementById('tipo-movimentacao').value;

            if (!dataInicio || !dataFim) {
                alert('Por favor, preencha as datas de início e fim');
                return;
            }

            const graficoContainer = document.getElementById('grafico-container');
            const ctx = document.getElementById('grafico-movimentacao-canvas').getContext('2d');

            // Show the chart container
            graficoContainer.classList.remove('hidden');
            graficoContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Optional: If a chart already exists, destroy it before creating a new one
            if (window.graficoMovimentacao) {
                window.graficoMovimentacao.destroy();
            }

            // Simple sample dataset (replace with real data from your backend when available)
            const labels = ['Entradas', 'Saídas'];
            const values = tipo === 'ENTRADA' ? [12, 0] : (tipo === 'SAIDA' ? [0, 9] : [12, 9]);

            window.graficoMovimentacao = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: `Movimentação (${dataInicio} a ${dataFim})`,
                        data: values,
                        backgroundColor: ['#2563eb', '#dc2626'], // blue, red
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        // REPLACED: client-side PDF generation using jsPDF + canvas image
        function gerarPdfMovimentacao() {
            const dataInicio = document.getElementById('data-inicio-financeira').value;
            const dataFim = document.getElementById('data-fim-financeira').value;
            const tipo = document.getElementById('tipo-movimentacao').value;

            if (!dataInicio || !dataFim) {
                alert('Por favor, preencha as datas de início e fim');
                return;
            }

            // Ensure a chart exists for the current filters; if not, create it
            if (!window.graficoMovimentacao) {
                gerarGraficoMovimentacao();
            }

            // Slight delay to ensure Chart.js finished rendering before capturing
            setTimeout(() => {
                try {
                    const canvas = document.getElementById('grafico-movimentacao-canvas');
                    if (!canvas) {
                        alert('Não foi possível encontrar o gráfico para gerar o PDF.');
                        return;
                    }

                    // Get PDF instance
                    const { jsPDF } = window.jspdf || {};
                    if (!jsPDF) {
                        alert('Biblioteca jsPDF não carregada. Verifique a conexão com a CDN.');
                        return;
                    }
                    const pdf = new jsPDF({ unit: 'pt', format: 'a4' });

                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const margin = 40;
                    let cursorY = margin + 20;

                    // Title
                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(16);
                    pdf.text('Relatório de Movimentação Financeira', margin, cursorY);

                    // Filters info
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(12);
                    cursorY += 18;
                    pdf.text(`Período: ${dataInicio} a ${dataFim}`, margin, cursorY);
                    cursorY += 16;
                    pdf.text(`Tipo de Movimentação: ${tipo || 'Todos'}`, margin, cursorY);

                    // Chart image (from canvas)
                    const contentWidth = pageWidth - margin * 2;
                    const imgData = canvas.toDataURL('image/png', 1.0);
                    const canvasRatio = canvas.height / canvas.width;
                    const chartHeightPt = contentWidth * canvasRatio;

                    cursorY += 24;
                    pdf.addImage(imgData, 'PNG', margin, cursorY, contentWidth, chartHeightPt);
                    cursorY += chartHeightPt + 24;

                    // Summary values (from Chart dataset)
                    const dataset = window.graficoMovimentacao?.data?.datasets?.[0];
                    const labels = window.graficoMovimentacao?.data?.labels || ['Entradas', 'Saídas'];
                    const values = dataset?.data || [0, 0];

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(13);
                    pdf.text('Resumo', margin, cursorY);
                    cursorY += 16;

                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(12);

                    // Entradas line
                    pdf.setTextColor(37, 99, 235); // blue for Entradas
                    pdf.text(`${labels[0]}: ${values[0]}`, margin, cursorY);
                    cursorY += 16;

                    // Saídas line
                    pdf.setTextColor(220, 38, 38); // red for Saídas
                    pdf.text(`${labels[1]}: ${values[1]}`, margin, cursorY);

                    // Reset text color
                    pdf.setTextColor(0, 0, 0);

                    // Footer
                    const now = new Date();
                    const footerText = `Gerado em: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`;
                    pdf.setFontSize(10);
                    pdf.text(footerText, margin, pageHeight - margin);

                    // Save PDF file
                    const fileName = `relatorio_movimentacao_${dataInicio}_${dataFim}.pdf`;
                    pdf.save(fileName);
                } catch (err) {
                    console.error(err);
                    alert('Erro ao gerar PDF. Verifique o console para mais detalhes.');
                }
            }, 150);
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

