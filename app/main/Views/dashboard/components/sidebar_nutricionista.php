<?php
// Componente reutilizável de sidebar para Nutricionista
require_once(__DIR__ . '/../../../config/system_helper.php');
$nomeSistema = getNomeSistemaCurto();
?>
<!-- Mobile Menu Overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile">
    <!-- Logo e Header -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
            <div>
                <h1 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($nomeSistema) ?></h1>
                <p class="text-xs text-gray-500">Maranguape</p>
            </div>
        </div>
    </div>

    <!-- User Info -->
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center flex-shrink-0" style="aspect-ratio: 1; min-width: 2.5rem; min-height: 2.5rem; overflow: hidden;">
                <span class="text-sm font-bold text-white">
                    <?php
                    $nome = $_SESSION['nome'] ?? '';
                    $iniciais = '';
                    if (strlen($nome) >= 2) {
                        $iniciais = strtoupper(substr($nome, 0, 2));
                    } elseif (strlen($nome) == 1) {
                        $iniciais = strtoupper($nome);
                    } else {
                        $iniciais = 'NU';
                    }
                    echo $iniciais;
                    ?>
                </span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                <p class="text-xs text-gray-500">Nutricionista</p>
            </div>
        </div>
    </div>

    <nav class="p-4 overflow-y-auto" style="max-height: calc(100vh - 280px);">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if (isset($_SESSION['adc_cardapio']) || isset($_SESSION['editar_cardapio']) || isset($_SESSION['visualizar_cardapios'])) { ?>
            <li>
                <a href="cardapios_nutricionista.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'cardapios_nutricionista.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Cardápios</span>
                </a>
            </li>
            <?php } ?>
            <?php if (isset($_SESSION['lista_insumos']) || isset($_SESSION['visualizar_insumos'])) { ?>
            <li>
                <a href="avaliacao_estoque_nutricionista.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'avaliacao_estoque_nutricionista.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Avaliar Estoque</span>
                </a>
            </li>
            <?php } ?>
            <li>
                <a href="substituicoes_nutricionista.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'substituicoes_nutricionista.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <span>Substituições</span>
                </a>
            </li>
            <li>
                <a href="indicadores_nutricionais.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'indicadores_nutricionais.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Indicadores Nutricionais</span>
                </a>
            </li>
            <li>
                <a href="relatorios_nutricionais.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'relatorios_nutricionais.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Relatórios Nutricionais</span>
                </a>
            </li>
            <?php if (isset($_SESSION['env_pedidos'])) { ?>
            <li>
                <a href="pedidos_nutricionista.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'pedidos_nutricionista.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span>Meus Pedidos</span>
                </a>
            </li>
            <?php } ?>
        </ul>
    </nav>

    <!-- Logout Button -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
        <button onclick="window.confirmLogout()" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-colors duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Sair</span>
        </button>
    </div>
</aside>

<script>
    // Função para toggle sidebar (mobile)
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('hidden');
    };

    // Fechar sidebar ao clicar no overlay
    document.getElementById('mobileOverlay').addEventListener('click', function() {
        window.toggleSidebar();
    });

    // Função de logout
    window.confirmLogout = function() {
        if (confirm('Deseja realmente sair do sistema?')) {
            window.location.href = '../auth/logout.php';
        }
    };
</script>

