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
    
    if ($_POST['acao'] === 'atualizar_configuracao') {
        $chave = $_POST['chave'] ?? null;
        $valor = $_POST['valor'] ?? null;
        
        // Implementar atualização de configurações
        echo json_encode(['success' => false, 'message' => 'Funcionalidade em desenvolvimento']);
        exit;
    }
}

// Buscar configurações do sistema
$sqlConfig = "SELECT * FROM configuracao_sistema LIMIT 1";
$stmtConfig = $conn->prepare($sqlConfig);
$stmtConfig->execute();
$config = $stmtConfig->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações e Segurança - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Configurações e Segurança</h1>
                    </div>
                    <div class="w-10"></div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Configurações do Sistema</h2>
                    <p class="text-gray-600 mt-1">Gerencie configurações gerais e segurança do sistema</p>
                </div>
                
                <!-- Seções de Configuração -->
                <div class="space-y-6">
                    <!-- Configurações Gerais -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configurações Gerais</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Sistema</label>
                                <input type="text" value="SIGEA - Sistema de Gestão e Alimentação Escolar" class="w-full px-4 py-2 border border-gray-300 rounded-lg" disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo Atual</label>
                                <input type="number" value="<?= date('Y') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segurança -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Segurança</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">Sessão Automática</h4>
                                    <p class="text-sm text-gray-600">Tempo de expiração da sessão</p>
                                </div>
                                <input type="number" value="30" class="w-20 px-3 py-2 border border-gray-300 rounded-lg" placeholder="minutos">
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">Tentativas de Login</h4>
                                    <p class="text-sm text-gray-600">Máximo de tentativas antes de bloquear</p>
                                </div>
                                <input type="number" value="5" class="w-20 px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Backup -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Backup e Manutenção</h3>
                        <div class="space-y-4">
                            <button class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                                Gerar Backup do Banco de Dados
                            </button>
                            <button class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                                Limpar Cache do Sistema
                            </button>
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
    </script>
</body>
</html>

