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

            const tabelaContainer = document.getElementById('tabela-container');
            const tabelaBody = document.getElementById('tabela-movimentacao-body');

            // Show the table container
            tabelaContainer.classList.remove('hidden');
            tabelaContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Sample data - replace with actual data from your backend
            const movimentos = [
                { data: '2023-01-15', descricao: 'Mensalidade', tipo: 'ENTRADA', valor: 1200.00 },
                { data: '2023-01-20', descricao: 'Material Didático', tipo: 'SAIDA', valor: 450.00 },
                { data: '2023-01-25', descricao: 'Mensalidade', tipo: 'ENTRADA', valor: 980.00 },
                { data: '2023-01-28', descricao: 'Manutenção', tipo: 'SAIDA', valor: 320.00 }
            ];

            // Filter by type if needed
            const movimentosFiltrados = tipo === 'TODOS' 
                ? movimentos 
                : movimentos.filter(m => m.tipo === tipo);

            // Clear previous table data
            tabelaBody.innerHTML = '';

            // Populate table with data
            movimentosFiltrados.forEach(movimento => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="py-4 px-6">${movimento.data}</td>
                    <td class="py-4 px-6">${movimento.descricao}</td>
                    <td class="py-4 px-6">${movimento.tipo}</td>
                    <td class="py-4 px-6">R$ ${movimento.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                `;
                tabelaBody.appendChild(row);
            });
        }

        function gerarTabelaMovimentacao() {
            const dataInicio = document.getElementById('data-inicio-financeira').value;
            const dataFim = document.getElementById('data-fim-financeira').value;
            const tipo = document.getElementById('tipo-movimentacao').value;
            
            // Sample data - replace with actual data from your backend
            const movimentos = [
                { data: '2023-01-15', descricao: 'Mensalidade', tipo: 'ENTRADA', valor: 1200.00 },
                { data: '2023-01-20', descricao: 'Material Didático', tipo: 'SAIDA', valor: 450.00 },
                { data: '2023-01-25', descricao: 'Mensalidade', tipo: 'ENTRADA', valor: 980.00 },
                { data: '2023-01-28', descricao: 'Manutenção', tipo: 'SAIDA', valor: 320.00 }
            ];
            
            // Filter by type if needed
            const movimentosFiltrados = tipo === 'TODOS' 
                ? movimentos 
                : movimentos.filter(m => m.tipo === tipo);
                
            return { movimentos: movimentosFiltrados, totalEntradas: 2180.00, totalSaidas: 770.00 };
        }

        // Geração de relatório em PDF com tabela
        function gerarPdfMovimentacao() {
            const dataInicio = document.getElementById('data-inicio-financeira').value;
            const dataFim = document.getElementById('data-fim-financeira').value;
            const tipo = document.getElementById('tipo-movimentacao').value;

            if (!dataInicio || !dataFim) {
                alert('Por favor, preencha as datas de início e fim');
                return;
            }

            try {
                // Obter dados da movimentação
                const { movimentos, totalEntradas, totalSaidas } = gerarTabelaMovimentacao();
                
                // Configuração do PDF
                const { jsPDF } = window.jspdf || {};
                if (!jsPDF) {
                    alert('Biblioteca jsPDF não carregada. Verifique a conexão com a CDN.');
                    return;
                }
                
                const pdf = new jsPDF({ unit: 'pt', format: 'a4' });
                const pageWidth = pdf.internal.pageSize.getWidth();
                const margin = 40;
                let cursorY = margin + 20;

                // Cabeçalho
                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(16);
                pdf.text('Relatório de Movimentação Financeira', margin, cursorY);

                // Informações do filtro
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(12);
                cursorY += 24;
                pdf.text(`Período: ${dataInicio} a ${dataFim}`, margin, cursorY);
                cursorY += 18;
                pdf.text(`Tipo de Movimentação: ${tipo === 'TODOS' ? 'Todos' : tipo}`, margin, cursorY);
                cursorY += 24;

                // Tabela de movimentações
                const headers = ['Data', 'Descrição', 'Tipo', 'Valor (R$)'];
                const colWidths = [80, 200, 80, 100];
                const rowHeight = 20;
                
                // Cabeçalho da tabela
                pdf.setFillColor(240, 240, 240);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight, 'F');
                
                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(12);
                headers.forEach((header, i) => {
                    pdf.text(header, margin + (i > 0 ? colWidths.slice(0, i).reduce((a, b) => a + b, 0) : 0), cursorY + 14);
                });
                
                cursorY += rowHeight;
                
                // Linhas da tabela
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(10);
                
                movimentos.forEach((mov, idx) => {
                    if (cursorY > pdf.internal.pageSize.getHeight() - 60) {
                        pdf.addPage();
                        cursorY = margin;
                    }
                    
                    const row = [
                        mov.data,
                        mov.descricao,
                        mov.tipo,
                        mov.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    ];
                    
                    // Alternar cores das linhas
                    if (idx % 2 === 0) {
                        pdf.setFillColor(250, 250, 250);
                        pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight, 'F');
                    }
                    
                    // Desenhar bordas da célula
                    pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight);
                    
                    // Adicionar texto das células
                    row.forEach((cell, i) => {
                        const x = margin + 5 + (i > 0 ? colWidths.slice(0, i).reduce((a, b) => a + b, 0) : 0);
                        const y = cursorY + 14;
                        
                        // Definir cor com base no tipo de movimentação
                        if (i === 2) { // Coluna de Tipo
                            pdf.setTextColor(mov.tipo === 'ENTRADA' ? 37 : 220, 99, mov.tipo === 'ENTRADA' ? 235 : 38);
                        } else if (i === 3) { // Coluna de Valor
                            pdf.setTextColor(mov.tipo === 'ENTRADA' ? 37 : 220, 99, mov.tipo === 'ENTRADA' ? 235 : 38);
                        } else {
                            pdf.setTextColor(0, 0, 0);
                        }
                        
                        pdf.text(cell, x, y);
                    });
                    
                    cursorY += rowHeight;
                });
                
                // Resetar cor do texto
                pdf.setTextColor(0, 0, 0);
                
                // Totais
                cursorY += 10;
                pdf.setFont('helvetica', 'bold');
                pdf.text('Totais:', margin, cursorY);
                
                cursorY += 20;
                pdf.setFillColor(245, 245, 245);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight, 'F');
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight);
                
                pdf.setFillColor(230, 255, 230);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin - 100, rowHeight, 'F');
                pdf.text('Total de Entradas:', margin + 5, cursorY + 14);
                pdf.text(`R$ ${totalEntradas.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, 
                        pageWidth - margin - 95, cursorY + 14);
                
                cursorY += rowHeight;
                pdf.setFillColor(255, 230, 230);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight, 'F');
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight);
                pdf.text('Total de Saídas:', margin + 5, cursorY + 14);
                pdf.text(`R$ ${totalSaidas.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, 
                        pageWidth - margin - 95, cursorY + 14);
                
                cursorY += rowHeight;
                pdf.setFillColor(240, 240, 255);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight, 'F');
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, rowHeight);
                pdf.text('Saldo Final:', margin + 5, cursorY + 14);
                const saldo = totalEntradas - totalSaidas;
                pdf.setTextColor(saldo >= 0 ? 37 : 220, 99, saldo >= 0 ? 235 : 38);
                pdf.text(`R$ ${Math.abs(saldo).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${saldo >= 0 ? '' : '-'}`, 
                        pageWidth - margin - 95, cursorY + 14);
                
                // Rodapé
                pdf.setTextColor(0, 0, 0);
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(10);
                const now = new Date();
                const footerText = `Gerado em: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`;
                pdf.text(footerText, margin, pdf.internal.pageSize.getHeight() - margin);

                // Salvar PDF
                const fileName = `relatorio_movimentacao_${dataInicio}_${dataFim}.pdf`;
                pdf.save(fileName);
                
            } catch (err) {
                console.error(err);
                alert('Erro ao gerar PDF. Verifique o console para mais detalhes.');
            }
        }

        function gerarRelatorioAlunos() {
            // Show loading indicator
            const loadingElement = document.createElement('div');
            loadingElement.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                            <span class="text-gray-700">Gerando relatório de alunos...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingElement);
            
            // Fetch student data
            fetch('gerar_relatorio.php?tipo=alunos')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao carregar dados dos alunos');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'Erro desconhecido ao processar os dados');
                    }
                    
                    // Generate PDF with the data
                    gerarPdfAlunos(data.data);
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao gerar relatório: ' + error.message);
                })
                .finally(() => {
                    // Remove loading indicator
                    document.body.removeChild(loadingElement);
                });
        }
        
        function formatarCPF(cpf) {
            if (!cpf) return '';
            cpf = cpf.replace(/\D/g, '');
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        
        function formatarTelefone(telefone) {
            if (!telefone) return '';
            telefone = telefone.replace(/\D/g, '');
            if (telefone.length === 11) {
                return telefone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (telefone.length === 10) {
                return telefone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            return telefone;
        }
        
        function formatarData(data) {
            if (!data) return '';
            const date = new Date(data);
            return date.toLocaleDateString('pt-BR');
        }
        
        function gerarPdfAlunos(alunos) {
            const { jsPDF } = window.jspdf || {};
            if (!jsPDF) {
                alert('Biblioteca jsPDF não carregada. Verifique a conexão com a CDN.');
                return;
            }
            
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const pageWidth = pdf.internal.pageSize.getWidth();
            const margin = 15;
            let cursorY = margin + 10;
            
            // Configurações iniciais
            pdf.setFont('helvetica');
            
            // Título do relatório
            pdf.setFontSize(16);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Relatório de Alunos', pageWidth / 2, cursorY, { align: 'center' });
            
            // Data de geração
            cursorY += 10;
            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'normal');
            pdf.text(`Gerado em: ${new Date().toLocaleString('pt-BR')}`, pageWidth - margin, cursorY, { align: 'right' });
            
            // Total de alunos
            cursorY += 8;
            pdf.setFont('helvetica', 'bold');
            pdf.text(`Total de Alunos: ${alunos.length}`, margin, cursorY);
            
            // Cabeçalho da tabela
            cursorY += 10;
            const headers = ['ID', 'Nome', 'CPF', 'Matrícula'];
            const colWidths = [15, 70, 50, 70]; // Nome 70mm, Matrícula 70mm
            const headerHeight = 10;
            
            // Desenhar cabeçalho
            pdf.setFillColor(240, 240, 240);
            pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight, 'F');
            pdf.setDrawColor(200, 200, 200);
            pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight);
            
            let x = margin;
            headers.forEach((header, i) => {
                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(9);
                pdf.text(header, x + 2, cursorY + 7);
                
                // Desenhar linhas verticais
                if (i > 0) {
                    pdf.line(x, cursorY, x, cursorY + headerHeight);
                }
                
                x += colWidths[i];
            });
            
            cursorY += headerHeight;
            
            // Dados dos alunos
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(9);
            
            alunos.forEach((aluno, index) => {
                // Verificar se precisa de uma nova página
                if (cursorY > pdf.internal.pageSize.getHeight() - 20) {
                    pdf.addPage();
                    cursorY = margin;
                    
                    // Desenhar cabeçalho novamente em novas páginas
                    pdf.setFillColor(240, 240, 240);
                    pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight, 'F');
                    pdf.setDrawColor(200, 200, 200);
                    pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight);
                    
                    let x = margin;
                    headers.forEach((header, i) => {
                        pdf.setFont('helvetica', 'bold');
                        pdf.text(header, x + 2, cursorY + 7);
                        if (i > 0) {
                            pdf.line(x, cursorY, x, cursorY + headerHeight);
                        }
                        x += colWidths[i];
                    });
                    
                    cursorY += headerHeight;
                    pdf.setFont('helvetica', 'normal');
                }
                
                // Linha com fundo alternado
                if (index % 2 === 0) {
                    pdf.setFillColor(250, 250, 250);
                    pdf.rect(margin, cursorY, pageWidth - 2 * margin, 8, 'F');
                }
                
                // Bordas da linha
                pdf.setDrawColor(220, 220, 220);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, 8);
                
                // Conteúdo das células (apenas ID, Nome, CPF e Matrícula)
                const rowData = [
                    String(aluno.id || ''),
                    String(aluno.nome || ''),
                    formatarCPF(aluno.cpf || ''),
                    String(aluno.matricula || '')
                ];
                
                // Posicionar texto nas células
                let xPos = margin;
                rowData.forEach((cell, i) => {
                    // Ajuste do alinhamento: ID centralizado, Nome, CPF e Matrícula alinhados à esquerda
                    const align = i === 0 ? 'center' : 'left';
                    const xOffset = i === 0 ? colWidths[i] / 2 : 2; // Centraliza apenas o ID
                    pdf.text(cell, xPos + xOffset, cursorY + 5, {
                        align: align,
                        maxWidth: colWidths[i] - 4
                    });
                    
                    // Desenhar linhas verticais
                    if (i > 0) {
                        pdf.line(xPos, cursorY, xPos, cursorY + 8);
                    }
                    
                    xPos += colWidths[i];
                });
                
                cursorY += 8;
            });
            
            // Rodapé
            cursorY = pdf.internal.pageSize.getHeight() - 10;
            pdf.setFontSize(8);
            pdf.setFont('helvetica', 'italic');
            pdf.text('SIGAE - Sistema de Gestão Acadêmica Escolar', pageWidth / 2, cursorY, { align: 'center' });
            
            // Salvar o PDF
            pdf.save(`relatorio_alunos_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function gerarRelatorioProfessores() {
            // Show loading indicator
            const loadingElement = document.createElement('div');
            loadingElement.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                            <span class="text-gray-700">Gerando relatório de professores...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingElement);
            
            // Fetch professor data
            fetch('gerar_relatorio.php?tipo=professores')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao buscar dados dos professores');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        gerarPdfProfessores(data.data);
                    } else {
                        throw new Error(data.error || 'Erro desconhecido ao gerar relatório');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao gerar relatório: ' + error.message);
                })
                .finally(() => {
                    document.body.removeChild(loadingElement);
                });
        }

        function gerarPdfProfessores(professores) {
            const { jsPDF } = window.jspdf || {};
            if (!jsPDF) {
                alert('Biblioteca jsPDF não carregada. Verifique a conexão com a CDN.');
                return;
            }

            try {
                // Configurações da página
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                const pageWidth = pdf.internal.pageSize.getWidth();
                const margin = 15;
                let cursorY = margin;

                // Adicionar título
                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(16);
                pdf.text('Relatório de Professores', pageWidth / 2, cursorY, { align: 'center' });
                
                // Data de geração
                cursorY += 10;
                pdf.setFontSize(10);
                pdf.setFont('helvetica', 'normal');
                pdf.text(`Gerado em: ${new Date().toLocaleString('pt-BR')}`, pageWidth - margin, cursorY, { align: 'right' });
                
                // Total de professores
                cursorY += 8;
                pdf.setFont('helvetica', 'bold');
                pdf.text(`Total de Professores: ${professores.length}`, margin, cursorY);
                
                // Cabeçalho da tabela
                cursorY += 10;
                const headers = ['ID', 'Nome', 'CPF', 'Matrícula'];
                const colWidths = [15, 90, 50, 50]; // Nome 90mm, Matrícula 50mm
                const headerHeight = 10;
                
                // Desenhar cabeçalho
                pdf.setFillColor(240, 240, 240);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight, 'F');
                pdf.setDrawColor(200, 200, 200);
                pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight);
                
                let x = margin;
                headers.forEach((header, i) => {
                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(9);
                    pdf.text(header, x + 2, cursorY + 7);
                    
                    // Desenhar linhas verticais
                    if (i > 0) {
                        pdf.line(x, cursorY, x, cursorY + headerHeight);
                    }
                    
                    x += colWidths[i];
                });
                
                cursorY += headerHeight;
                
                // Conteúdo da tabela
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(9);
                
                professores.forEach((professor, index) => {
                    // Verificar se precisa de uma nova página
                    if (cursorY > pdf.internal.pageSize.getHeight() - 20) {
                        pdf.addPage();
                        cursorY = margin;
                        
                        // Desenhar cabeçalho novamente na nova página
                        pdf.setFillColor(240, 240, 240);
                        pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight, 'F');
                        pdf.setDrawColor(200, 200, 200);
                        pdf.rect(margin, cursorY, pageWidth - 2 * margin, headerHeight);
                        
                        let x = margin;
                        headers.forEach((header, i) => {
                            pdf.setFont('helvetica', 'bold');
                            pdf.text(header, x + 2, cursorY + 7);
                            
                            if (i > 0) {
                                pdf.line(x, cursorY, x, cursorY + headerHeight);
                            }
                            
                            x += colWidths[i];
                        });
                        
                        cursorY += headerHeight;
                        pdf.setFont('helvetica', 'normal');
                    }
                    
                    // Linha com fundo alternado
                    if (index % 2 === 0) {
                        pdf.setFillColor(250, 250, 250);
                        pdf.rect(margin, cursorY, pageWidth - 2 * margin, 8, 'F');
                    }
                    
                    // Bordas da linha
                    pdf.setDrawColor(220, 220, 220);
                    pdf.rect(margin, cursorY, pageWidth - 2 * margin, 8);
                    
                    // Conteúdo das células
                    const rowData = [
                        String(professor.id || ''),
                        String(professor.nome || ''),
                        formatarCPF(professor.cpf || ''),
                        String(professor.matricula || '')
                    ];
                    
                    // Posicionar texto nas células
                    let xPos = margin;
                    rowData.forEach((cell, i) => {
                        // Ajuste do alinhamento: ID centralizado, Nome, CPF e Matrícula alinhados à esquerda
                        const align = i === 0 ? 'center' : 'left';
                        const xOffset = i === 0 ? colWidths[i] / 2 : 2;
                        pdf.text(cell, xPos + xOffset, cursorY + 5, {
                            align: align,
                            maxWidth: colWidths[i] - 4
                        });
                        
                        // Desenhar linhas verticais
                        if (i > 0) {
                            pdf.line(xPos, cursorY, xPos, cursorY + 8);
                        }
                        
                        xPos += colWidths[i];
                    });
                    
                    cursorY += 8;
                });
                
                // Rodapé
                const dataAtual = new Date().toLocaleDateString('pt-BR');
                const horaAtual = new Date().toLocaleTimeString('pt-BR');
                
                pdf.setFont('helvetica', 'italic');
                pdf.setFontSize(8);
                pdf.text(`Gerado em ${dataAtual} às ${horaAtual} - SIGAE`, 
                        pageWidth / 2, pdf.internal.pageSize.getHeight() - 10, 
                        { align: 'center' });
                
                // Salvar o PDF
                pdf.save(`relatorio_professores_${new Date().toISOString().split('T')[0]}.pdf`);
                
            } catch (error) {
                console.error('Erro ao gerar PDF:', error);
                alert('Erro ao gerar o PDF: ' + error.message);
            }
        }
    </script>
</body>
</html>

