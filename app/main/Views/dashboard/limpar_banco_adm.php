<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/admin/DatabaseCleanupModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM geral pode acessar
if (!eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$cleanupModel = new DatabaseCleanupModel();
$estatisticas = $cleanupModel->getEstatisticas();

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'limpar_dados') {
        $tipo = $_POST['tipo'] ?? '';
        $categorias = $_POST['categorias'] ?? [];
        
        $resultado = [];
        
        switch ($tipo) {
            case 'academicos':
                $resultado = $cleanupModel->limparDadosAcademicos();
                break;
            case 'merenda':
                $resultado = $cleanupModel->limparDadosMerenda();
                break;
            case 'todos_mantem_usuarios':
                $resultado = $cleanupModel->limparTodosDadosMantemUsuarios();
                break;
            case 'tudo_incluindo_usuarios':
                $resultado = $cleanupModel->limparTudoIncluindoUsuarios();
                break;
            case 'especificos':
                $resultado = $cleanupModel->limparDadosEspecificos($categorias);
                break;
            default:
                $resultado = ['success' => false, 'message' => 'Tipo de limpeza inválido'];
        }
        
        echo json_encode($resultado);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar Banco de Dados - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Limpar Banco de Dados</h1>
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
                <!-- Aviso Importante -->
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-red-800 mb-2">⚠️ ATENÇÃO: Operação Irreversível</h3>
                            <p class="text-red-700 mb-2">Esta operação irá <strong>permanentemente excluir</strong> dados do banco de dados.</p>
                            <p class="text-red-700 mb-2"><strong>O usuário Administrador Geral nunca será excluído.</strong></p>
                            <p class="text-red-700">Certifique-se de ter um backup antes de prosseguir.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Estatísticas Atuais -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Estatísticas Atuais do Banco</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <?php foreach ($estatisticas as $tabela => $info): ?>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-primary-green"><?= $info['total'] ?></p>
                                <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($info['nome']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Opções de Limpeza -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Opções de Limpeza</h2>
                    
                    <div class="space-y-4">
                        <!-- Opção 1: Limpar Dados Acadêmicos -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-green transition-colors">
                            <div class="flex items-start space-x-4">
                                <input type="radio" name="tipo_limpeza" id="academicos" value="academicos" class="mt-1">
                                <div class="flex-1">
                                    <label for="academicos" class="font-semibold text-gray-900 cursor-pointer block mb-2">
                                        Limpar Dados Acadêmicos (Mantém Usuários)
                                    </label>
                                    <p class="text-sm text-gray-600 mb-2">
                                        Remove: Alunos, Turmas, Notas, Frequências, Matrículas, Séries, Disciplinas
                                    </p>
                                    <p class="text-xs text-green-600">✓ Usuários serão mantidos</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opção 2: Limpar Dados de Merenda -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-green transition-colors">
                            <div class="flex items-start space-x-4">
                                <input type="radio" name="tipo_limpeza" id="merenda" value="merenda" class="mt-1">
                                <div class="flex-1">
                                    <label for="merenda" class="font-semibold text-gray-900 cursor-pointer block mb-2">
                                        Limpar Dados de Merenda (Mantém Usuários)
                                    </label>
                                    <p class="text-sm text-gray-600 mb-2">
                                        Remove: Cardápios, Pedidos, Entregas, Consumo, Desperdício, Custos, Estoque, Fornecedores
                                    </p>
                                    <p class="text-xs text-green-600">✓ Usuários serão mantidos</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opção 3: Limpar Tudo (Mantém Usuários) -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-green transition-colors">
                            <div class="flex items-start space-x-4">
                                <input type="radio" name="tipo_limpeza" id="todos_mantem_usuarios" value="todos_mantem_usuarios" class="mt-1">
                                <div class="flex-1">
                                    <label for="todos_mantem_usuarios" class="font-semibold text-gray-900 cursor-pointer block mb-2">
                                        Limpar Todos os Dados (Mantém Usuários)
                                    </label>
                                    <p class="text-sm text-gray-600 mb-2">
                                        Remove: Todos os dados acadêmicos, de merenda, comunicados, eventos, produtos
                                    </p>
                                    <p class="text-xs text-green-600">✓ Usuários serão mantidos</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opção 4: Limpar Tudo Incluindo Usuários -->
                        <div class="border border-red-200 rounded-lg p-4 hover:border-red-400 transition-colors bg-red-50">
                            <div class="flex items-start space-x-4">
                                <input type="radio" name="tipo_limpeza" id="tudo_incluindo_usuarios" value="tudo_incluindo_usuarios" class="mt-1">
                                <div class="flex-1">
                                    <label for="tudo_incluindo_usuarios" class="font-semibold text-red-900 cursor-pointer block mb-2">
                                        ⚠️ Limpar TUDO Incluindo Usuários (Exceto ADM Geral)
                                    </label>
                                    <p class="text-sm text-red-700 mb-2">
                                        Remove: Todos os dados e usuários do sistema
                                    </p>
                                    <p class="text-xs text-red-600 font-semibold">⚠️ O usuário Administrador Geral será mantido</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opção 5: Limpeza Específica -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-green transition-colors">
                            <div class="flex items-start space-x-4">
                                <input type="radio" name="tipo_limpeza" id="especificos" value="especificos" class="mt-1">
                                <div class="flex-1">
                                    <label for="especificos" class="font-semibold text-gray-900 cursor-pointer block mb-2">
                                        Limpeza Específica (Selecione Categorias)
                                    </label>
                                    <div id="categorias-especificas" class="mt-3 space-y-2 hidden">
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="categorias[]" value="academicos" class="rounded">
                                            <span class="text-sm text-gray-700">Dados Acadêmicos</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="categorias[]" value="merenda" class="rounded">
                                            <span class="text-sm text-gray-700">Dados de Merenda</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="categorias[]" value="escolas" class="rounded">
                                            <span class="text-sm text-gray-700">Escolas</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="categorias[]" value="comunicados" class="rounded">
                                            <span class="text-sm text-gray-700">Comunicados</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="categorias[]" value="eventos" class="rounded">
                                            <span class="text-sm text-gray-700">Eventos do Calendário</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="categorias[]" value="usuarios" class="rounded">
                                            <span class="text-sm text-gray-700">Usuários (Exceto ADM Geral)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botão de Executar -->
                    <div class="mt-8 flex justify-end">
                        <button onclick="confirmarLimpeza()" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Executar Limpeza</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de Confirmação -->
    <div id="modal-confirmacao" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Confirmar Limpeza</h3>
                    <p class="text-sm text-gray-600">Esta ação é irreversível!</p>
                </div>
            </div>
            
            <div class="mb-4">
                <p class="text-gray-700 mb-2">Você está prestes a executar uma limpeza do banco de dados.</p>
                <p class="text-red-600 font-semibold mb-2" id="tipo-limpeza-texto"></p>
                <p class="text-sm text-gray-600">Digite <strong>"CONFIRMAR"</strong> para prosseguir:</p>
            </div>
            
            <form id="form-confirmacao" class="space-y-4">
                <input type="text" id="confirmacao-texto" placeholder="Digite CONFIRMAR" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" autocomplete="off">
                
                <div class="flex space-x-3">
                    <button type="button" onclick="fecharModalConfirmacao()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="executarLimpeza()" id="btn-executar" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors" disabled>
                        Executar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Resultado -->
    <div id="modal-resultado" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full">
            <div id="resultado-conteudo"></div>
            <div class="mt-4 flex justify-end">
                <button onclick="fecharModalResultado()" class="px-6 py-2 bg-primary-green text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                    Fechar
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
        
        // Mostrar/ocultar categorias específicas
        document.querySelectorAll('input[name="tipo_limpeza"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const categoriasDiv = document.getElementById('categorias-especificas');
                if (this.value === 'especificos') {
                    categoriasDiv.classList.remove('hidden');
                } else {
                    categoriasDiv.classList.add('hidden');
                    // Desmarcar checkboxes
                    categoriasDiv.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                }
            });
        });
        
        // Validação do campo de confirmação
        document.getElementById('confirmacao-texto').addEventListener('input', function() {
            const btnExecutar = document.getElementById('btn-executar');
            if (this.value.toUpperCase() === 'CONFIRMAR') {
                btnExecutar.disabled = false;
            } else {
                btnExecutar.disabled = true;
            }
        });
        
        function confirmarLimpeza() {
            const tipoSelecionado = document.querySelector('input[name="tipo_limpeza"]:checked');
            
            if (!tipoSelecionado) {
                alert('Por favor, selecione uma opção de limpeza.');
                return;
            }
            
            // Verificar se é limpeza específica e se tem categorias selecionadas
            if (tipoSelecionado.value === 'especificos') {
                const categoriasSelecionadas = Array.from(document.querySelectorAll('input[name="categorias[]"]:checked'));
                if (categoriasSelecionadas.length === 0) {
                    alert('Por favor, selecione pelo menos uma categoria para limpeza específica.');
                    return;
                }
            }
            
            // Definir texto do tipo de limpeza
            const textos = {
                'academicos': 'Limpeza de Dados Acadêmicos (mantém usuários)',
                'merenda': 'Limpeza de Dados de Merenda (mantém usuários)',
                'todos_mantem_usuarios': 'Limpeza de Todos os Dados (mantém usuários)',
                'tudo_incluindo_usuarios': 'Limpeza Completa (incluindo usuários, exceto ADM geral)',
                'especificos': 'Limpeza Específica'
            };
            
            document.getElementById('tipo-limpeza-texto').textContent = textos[tipoSelecionado.value] || 'Limpeza do Banco';
            document.getElementById('confirmacao-texto').value = '';
            document.getElementById('btn-executar').disabled = true;
            
            const modal = document.getElementById('modal-confirmacao');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
        
        function fecharModalConfirmacao() {
            const modal = document.getElementById('modal-confirmacao');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
        
        function executarLimpeza() {
            const tipoSelecionado = document.querySelector('input[name="tipo_limpeza"]:checked');
            const categorias = [];
            
            if (tipoSelecionado.value === 'especificos') {
                document.querySelectorAll('input[name="categorias[]"]:checked').forEach(cb => {
                    categorias.push(cb.value);
                });
            }
            
            const formData = new FormData();
            formData.append('acao', 'limpar_dados');
            formData.append('tipo', tipoSelecionado.value);
            if (categorias.length > 0) {
                categorias.forEach(cat => formData.append('categorias[]', cat));
            }
            
            const btn = document.getElementById('btn-executar');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span> Executando...';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                fecharModalConfirmacao();
                
                // Mostrar resultado
                const modalResultado = document.getElementById('modal-resultado');
                const conteudo = document.getElementById('resultado-conteudo');
                
                if (data.success) {
                    conteudo.innerHTML = `
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Limpeza Concluída</h3>
                                <p class="text-sm text-gray-600">Operação realizada com sucesso</p>
                            </div>
                        </div>
                        <p class="text-gray-700">${data.message}</p>
                    `;
                } else {
                    conteudo.innerHTML = `
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Erro na Limpeza</h3>
                                <p class="text-sm text-gray-600">Ocorreu um erro durante a operação</p>
                            </div>
                        </div>
                        <p class="text-red-700">${data.message}</p>
                    `;
                }
                
                modalResultado.classList.remove('hidden');
                modalResultado.style.display = 'flex';
                
                // Recarregar página após 2 segundos se sucesso
                if (data.success) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Erro:', error);
                alert('Erro ao executar limpeza: ' + error.message);
            });
        }
        
        function fecharModalResultado() {
            const modal = document.getElementById('modal-resultado');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>

