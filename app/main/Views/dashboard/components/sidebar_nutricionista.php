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
        <button onclick="window.openUserProfile && window.openUserProfile()" class="w-full flex items-center space-x-3 hover:bg-gray-50 rounded-lg p-2 transition-colors cursor-pointer">
            <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center flex-shrink-0" style="aspect-ratio: 1; min-width: 2.5rem; min-height: 2.5rem; overflow: hidden;">
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
            <div class="flex-1 text-left">
                <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                <p class="text-xs text-gray-500">Nutricionista</p>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>

    <nav class="p-4">
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
            <li>
                <a href="cardapios_nutricionista.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= (basename($_SERVER['PHP_SELF']) === 'cardapios_nutricionista.php' || basename($_SERVER['PHP_SELF']) === 'cardapios_merenda.php') ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Cardápios</span>
                </a>
            </li>
            <!--
            <li>
                <a href="pedidos_merenda.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'pedidos_merenda.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span>Pedidos de Compra</span>
                </a>
            </li>
                -->
            <li>
                <a href="estoque_nutricionista.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'estoque_nutricionista.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Estoque da Escola</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout Button - Apenas no dashboard -->
    <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
        <button onclick="window.confirmLogout()" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-colors duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Sair</span>
        </button>
    </div>
    <?php endif; ?>
</aside>

