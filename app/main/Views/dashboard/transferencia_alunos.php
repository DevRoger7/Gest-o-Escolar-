<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/AlunoModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$alunoModel = new AlunoModel();

$mensagem = '';
$tipoMensagem = '';

// Processar transferência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'transferir') {
    $alunoId = $_POST['aluno_id'] ?? null;
    $escolaOrigemId = $_POST['escola_origem_id'] ?? null;
    $escolaDestinoId = $_POST['escola_destino_id'] ?? null;
    $dataTransferencia = $_POST['data_transferencia'] ?? date('Y-m-d');
    
    if ($alunoId && $escolaOrigemId && $escolaDestinoId) {
        if ($escolaOrigemId == $escolaDestinoId) {
            $mensagem = 'A escola de origem e destino não podem ser a mesma!';
            $tipoMensagem = 'error';
        } else {
            $resultado = $alunoModel->transferirEscola($alunoId, $escolaOrigemId, $escolaDestinoId, $dataTransferencia);
            
            if ($resultado['success']) {
                $mensagem = $resultado['message'];
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'];
                $tipoMensagem = 'error';
            }
        }
    } else {
        $mensagem = 'Dados incompletos para realizar a transferência!';
        $tipoMensagem = 'error';
    }
}

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Não precisamos carregar alunos aqui, será feito via AJAX
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferência de Alunos - SIGAE</title>
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
        .aluno-item:first-child { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
        .aluno-item:last-child { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; border-bottom: none; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include('components/sidebar_adm.php'); ?>
    
    <!-- Mobile Menu Button -->
    <button onclick="window.toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-50 bg-white p-2 rounded-lg shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Main Content -->
    <main class="lg:ml-64 content-transition min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Transferência de Alunos</h1>
                        <p class="mt-1 text-sm text-gray-500">Transfira alunos entre escolas do sistema</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($mensagem): ?>
            <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <div class="flex items-center">
                    <?php if ($tipoMensagem === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($mensagem) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Formulário de Transferência -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <form method="POST" id="form-transferencia" class="space-y-6">
                    <input type="hidden" name="acao" value="transferir">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Escola de Origem -->
                        <div>
                            <label for="escola_origem_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Escola de Origem <span class="text-red-500">*</span>
                            </label>
                            <select name="escola_origem_id" id="escola_origem_id" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                    onchange="carregarAlunos(this.value, 'origem')">
                                <option value="">Selecione a escola de origem...</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>">
                                        <?= htmlspecialchars($escola['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Escola de Destino -->
                        <div>
                            <label for="escola_destino_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Escola de Destino <span class="text-red-500">*</span>
                            </label>
                            <select name="escola_destino_id" id="escola_destino_id" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                <option value="">Selecione a escola de destino...</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>">
                                        <?= htmlspecialchars($escola['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Aluno -->
                    <div class="relative">
                        <label for="busca_aluno" class="block text-sm font-medium text-gray-700 mb-2">
                            Buscar Aluno <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="busca_aluno" 
                                   name="busca_aluno"
                                   placeholder="Digite o nome, CPF ou matrícula do aluno..."
                                   class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                   autocomplete="off"
                                   disabled>
                            <input type="hidden" name="aluno_id" id="aluno_id" required>
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <!-- Lista de sugestões -->
                        <div id="sugestoes-alunos" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <!-- Sugestões serão inseridas aqui via JavaScript -->
                        </div>
                        <p id="mensagem-busca" class="mt-1 text-sm text-gray-500 hidden"></p>
                    </div>

                    <!-- Informações do Aluno -->
                    <div id="info-aluno" class="bg-gray-50 rounded-lg p-4 hidden">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Informações do Aluno</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Nome</p>
                                <p class="text-sm font-medium text-gray-900" id="info-nome">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">CPF</p>
                                <p class="text-sm font-medium text-gray-900" id="info-cpf">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Matrícula</p>
                                <p class="text-sm font-medium text-gray-900" id="info-matricula">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data de Transferência -->
                    <div>
                        <label for="data_transferencia" class="block text-sm font-medium text-gray-700 mb-2">
                            Data de Transferência <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="data_transferencia" id="data_transferencia" 
                               value="<?= date('Y-m-d') ?>" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                    </div>

                    <!-- Botões -->
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <button type="button" onclick="window.location.reload()" 
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                            Transferir Aluno
                        </button>
                    </div>
                </form>
            </div>

            <!-- Instruções -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">Como transferir um aluno:</h3>
                <ol class="list-decimal list-inside space-y-2 text-blue-800">
                    <li>Selecione a escola de origem do aluno</li>
                    <li>Digite o nome, CPF ou matrícula do aluno no campo de busca</li>
                    <li>Selecione o aluno da lista de sugestões que aparecerá</li>
                    <li>Escolha a escola de destino</li>
                    <li>Confirme a data de transferência</li>
                    <li>Clique em "Transferir Aluno" para concluir</li>
                </ol>
                <p class="mt-4 text-sm text-blue-700">
                    <strong>Dica:</strong> Você pode usar as setas do teclado (↑↓) para navegar pelas sugestões e pressionar Enter para selecionar.
                </p>
                <p class="mt-2 text-sm text-blue-700">
                    <strong>Observação:</strong> Ao transferir um aluno, ele será automaticamente desvinculado da turma atual na escola de origem e sua situação será alterada para "TRANSFERIDO".
                </p>
            </div>
        </div>
    </main>

    <!-- Logout Confirmation Modal -->
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
        // Função para toggle do sidebar (mobile)
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };

        // Funções de logout
        window.confirmLogout = function() {
            let modal = document.getElementById('logoutModal');
            if (!modal) {
                modal = document.querySelector('#logoutModal');
            }
            if (!modal) {
                modal = document.querySelector('[id="logoutModal"]');
            }
            if (!modal) {
                modal = document.body.querySelector('#logoutModal');
            }
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

        let alunosDisponiveis = [];
        let escolaOrigemId = null;
        let timeoutBusca = null;
        let indiceSugestaoAtiva = -1;

        function carregarAlunos(escolaId, tipo) {
            const inputBusca = document.getElementById('busca_aluno');
            const inputAlunoId = document.getElementById('aluno_id');
            const infoAluno = document.getElementById('info-aluno');
            const sugestoesDiv = document.getElementById('sugestoes-alunos');
            const mensagemBusca = document.getElementById('mensagem-busca');
            
            escolaOrigemId = escolaId;
            
            if (!escolaId) {
                inputBusca.value = '';
                inputBusca.disabled = true;
                inputBusca.placeholder = 'Primeiro selecione a escola de origem...';
                inputAlunoId.value = '';
                infoAluno.classList.add('hidden');
                sugestoesDiv.classList.add('hidden');
                alunosDisponiveis = [];
                return;
            }

            // Habilitar campo de busca
            inputBusca.disabled = false;
            inputBusca.placeholder = 'Digite o nome, CPF ou matrícula do aluno...';
            inputBusca.value = '';
            inputAlunoId.value = '';
            infoAluno.classList.add('hidden');
            sugestoesDiv.classList.add('hidden');
            alunosDisponiveis = [];
            mensagemBusca.classList.add('hidden');

            // Carregar todos os alunos da escola para busca local
            fetch(`../../Controllers/academico/AlunoController.php?action=buscar_por_escola&escola_id=${escolaId}&situacao=MATRICULADO`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        alunosDisponiveis = data.data;
                        mensagemBusca.textContent = `${alunosDisponiveis.length} aluno(s) encontrado(s) nesta escola. Digite para buscar...`;
                        mensagemBusca.classList.remove('hidden');
                    } else {
                        mensagemBusca.textContent = 'Nenhum aluno encontrado nesta escola.';
                        mensagemBusca.classList.remove('hidden');
                        inputBusca.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar alunos:', error);
                    mensagemBusca.textContent = 'Erro ao carregar alunos.';
                    mensagemBusca.classList.remove('hidden');
                });
        }

        function buscarAlunos(termo) {
            const sugestoesDiv = document.getElementById('sugestoes-alunos');
            const inputAlunoId = document.getElementById('aluno_id');
            const infoAluno = document.getElementById('info-aluno');
            
            if (!termo || termo.length < 2) {
                sugestoesDiv.classList.add('hidden');
                inputAlunoId.value = '';
                infoAluno.classList.add('hidden');
                return;
            }

            termo = termo.toLowerCase();
            const resultados = alunosDisponiveis.filter(aluno => {
                const nome = (aluno.nome || '').toLowerCase();
                const cpf = (aluno.cpf || '').replace(/\D/g, '');
                const matricula = (aluno.matricula || '').toLowerCase();
                const termoLimpo = termo.replace(/\D/g, '');
                
                return nome.includes(termo) || 
                       cpf.includes(termoLimpo) || 
                       matricula.includes(termo);
            });

            if (resultados.length === 0) {
                sugestoesDiv.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                sugestoesDiv.classList.remove('hidden');
                inputAlunoId.value = '';
                infoAluno.classList.add('hidden');
                return;
            }

            // Limitar a 10 resultados
            const resultadosLimitados = resultados.slice(0, 10);
            
            sugestoesDiv.innerHTML = '';
            resultadosLimitados.forEach((aluno, index) => {
                const item = document.createElement('div');
                item.className = 'p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 aluno-item';
                item.dataset.alunoId = aluno.id;
                item.dataset.index = index;
                
                const nome = aluno.nome || 'Sem nome';
                const matricula = aluno.matricula ? ` - Matrícula: ${aluno.matricula}` : '';
                const cpf = aluno.cpf ? ` - CPF: ${aluno.cpf}` : '';
                
                item.innerHTML = `
                    <div class="font-medium text-gray-900">${nome}</div>
                    <div class="text-sm text-gray-500">${matricula}${cpf}</div>
                `;
                
                item.addEventListener('click', () => selecionarAluno(aluno));
                item.addEventListener('mouseenter', () => {
                    indiceSugestaoAtiva = index;
                    atualizarDestaqueSugestoes();
                });
                
                sugestoesDiv.appendChild(item);
            });
            
            sugestoesDiv.classList.remove('hidden');
            indiceSugestaoAtiva = -1;
        }

        function selecionarAluno(aluno) {
            const inputBusca = document.getElementById('busca_aluno');
            const inputAlunoId = document.getElementById('aluno_id');
            const sugestoesDiv = document.getElementById('sugestoes-alunos');
            const infoAluno = document.getElementById('info-aluno');
            
            inputBusca.value = aluno.nome + (aluno.matricula ? ` - Matrícula: ${aluno.matricula}` : '');
            inputAlunoId.value = aluno.id;
            
            document.getElementById('info-nome').textContent = aluno.nome || '-';
            document.getElementById('info-cpf').textContent = aluno.cpf || '-';
            document.getElementById('info-matricula').textContent = aluno.matricula || '-';
            
            sugestoesDiv.classList.add('hidden');
            infoAluno.classList.remove('hidden');
        }

        function atualizarDestaqueSugestoes() {
            const itens = document.querySelectorAll('.aluno-item');
            itens.forEach((item, index) => {
                if (index === indiceSugestaoAtiva) {
                    item.classList.add('bg-gray-100');
                } else {
                    item.classList.remove('bg-gray-100');
                }
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const inputBusca = document.getElementById('busca_aluno');
            
            // Busca ao digitar
            inputBusca.addEventListener('input', function(e) {
                clearTimeout(timeoutBusca);
                const termo = e.target.value;
                
                timeoutBusca = setTimeout(() => {
                    buscarAlunos(termo);
                }, 300);
            });

            // Navegação com teclado
            inputBusca.addEventListener('keydown', function(e) {
                const sugestoesDiv = document.getElementById('sugestoes-alunos');
                const itens = document.querySelectorAll('.aluno-item');
                
                if (!sugestoesDiv.classList.contains('hidden') && itens.length > 0) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        indiceSugestaoAtiva = Math.min(indiceSugestaoAtiva + 1, itens.length - 1);
                        atualizarDestaqueSugestoes();
                        itens[indiceSugestaoAtiva].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        indiceSugestaoAtiva = Math.max(indiceSugestaoAtiva - 1, -1);
                        atualizarDestaqueSugestoes();
                    } else if (e.key === 'Enter' && indiceSugestaoAtiva >= 0) {
                        e.preventDefault();
                        const alunoId = itens[indiceSugestaoAtiva].dataset.alunoId;
                        const aluno = alunosDisponiveis.find(a => a.id == alunoId);
                        if (aluno) {
                            selecionarAluno(aluno);
                        }
                    } else if (e.key === 'Escape') {
                        sugestoesDiv.classList.add('hidden');
                        indiceSugestaoAtiva = -1;
                    }
                }
            });

            // Fechar sugestões ao clicar fora
            document.addEventListener('click', function(e) {
                const sugestoesDiv = document.getElementById('sugestoes-alunos');
                const inputBusca = document.getElementById('busca_aluno');
                
                if (!sugestoesDiv.contains(e.target) && e.target !== inputBusca) {
                    sugestoesDiv.classList.add('hidden');
                }
            });
        });

        // Validação do formulário
        document.getElementById('form-transferencia').addEventListener('submit', function(e) {
            const escolaOrigem = document.getElementById('escola_origem_id').value;
            const escolaDestino = document.getElementById('escola_destino_id').value;
            const aluno = document.getElementById('aluno_id').value;

            if (escolaOrigem === escolaDestino) {
                e.preventDefault();
                alert('A escola de origem e destino não podem ser a mesma!');
                return false;
            }

            if (!aluno) {
                e.preventDefault();
                alert('Por favor, selecione um aluno para transferir!');
                return false;
            }

            if (!confirm('Tem certeza que deseja transferir este aluno? Esta ação não pode ser desfeita facilmente.')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>

