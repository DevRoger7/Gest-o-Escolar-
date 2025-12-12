<?php
/**
 * Sidebar para usuários do tipo GESTAO
 * Este componente deve ser usado em todas as páginas acessíveis por gestores
 */

// Verificar se as variáveis necessárias estão definidas
$escolaGestorId = $escolaGestorId ?? null;
$escolaGestor = $escolaGestor ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Menu Overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>

<!-- Sidebar padrão para GESTAO -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile flex flex-col">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
            <div>
                <h1 class="text-lg font-bold text-gray-800">SIGEA</h1>
                <p class="text-xs text-gray-500">Gestão Escolar</p>
            </div>
        </div>
    </div>
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center space-x-3">
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
                        $iniciais = 'US';
                    }
                    echo $iniciais;
                    ?>
                </span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                <p class="text-xs text-gray-500"><?= $_SESSION['tipo'] ?? 'Gestão' ?></p>
            </div>
        </div>
    </div>
    <nav class="p-4 overflow-y-auto flex-1" style="max-height: calc(100vh - 200px);">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'dashboard.php' ? 'bg-primary-green text-white' : 'hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="gestao_escolar.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_escolar.php' ? 'bg-primary-green text-white' : 'hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span>Gestão Escolar</span>
                </a>
            </li>
            <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'GESTAO' && $escolaGestorId): ?>
            <li>
                <a href="cardapio_gestor.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'cardapio_gestor.php' ? 'bg-primary-green text-white' : 'hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Cardápio</span>
                </a>
            </li>
            <li>
                <a href="gestao_escolar.php?acao=abrir_desperdicio" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span>Registrar Desperdício</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="transferencias_pendentes.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'transferencias_pendentes.php' ? 'bg-primary-green text-white' : 'hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <span>Transferências</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout Button -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
        <button onclick="window.confirmLogout ? window.confirmLogout() : confirmLogout()" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Sair</span>
        </button>
    </div>
</aside>

