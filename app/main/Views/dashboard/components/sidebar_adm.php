<?php
// Componente reutilizável de sidebar para Administrador Geral
require_once(__DIR__ . '/../../../config/system_helper.php');
$currentPage = basename($_SERVER['PHP_SELF']);
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
                <p class="text-xs text-gray-500">Administrador Geral</p>
            </div>
        </div>
    </div>

    <!-- User Info -->
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
                        $iniciais = 'AD';
                    }
                    echo $iniciais;
                    ?>
                </span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Administrador' ?></p>
                <p class="text-xs text-gray-500">Administrador Geral</p>
            </div>
        </div>
    </div>

    <nav class="p-4 overflow-y-auto sidebar-nav" style="max-height: calc(100vh - 200px); scroll-behavior: smooth; padding-bottom: 80px;">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Gestão de Pessoas -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Gestão de Pessoas</p>
            </li>
            <li>
                <a href="gestao_alunos_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_alunos_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Alunos</span>
                </a>
            </li>
            <li>
                <a href="gestao_professores_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_professores_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>Professores</span>
                </a>
            </li>
            <li>
                <a href="gestao_funcionarios_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_funcionarios_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Funcionários</span>
                </a>
            </li>
            <li>
                <a href="gestao_gestores_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_gestores_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span>Gestores</span>
                </a>
            </li>
            <li>
                <a href="gestao_usuarios.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_usuarios.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Usuários</span>
                </a>
            </li>
            
            <!-- Gestão Acadêmica -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Gestão Acadêmica</p>
            </li>
            <li>
                <a href="gestao_escolas.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_escolas.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span>Escolas</span>
                </a>
            </li>
            <li>
                <a href="gestao_turmas_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_turmas_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Turmas</span>
                </a>
            </li>
            <li>
                <a href="gestao_series_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_series_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Séries</span>
                </a>
            </li>
            <li>
                <a href="gestao_disciplinas_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_disciplinas_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span>Disciplinas</span>
                </a>
            </li>
            <li>
                <a href="gestao_escolar.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'gestao_escolar.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span>Gestão Escolar</span>
                </a>
            </li>
            
            <!-- Supervisão -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Supervisão</p>
            </li>
            <li>
                <a href="supervisao_academica_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'supervisao_academica_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    <span>Supervisão Acadêmica</span>
                </a>
            </li>
            <li>
                <a href="supervisao_alimentacao_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'supervisao_alimentacao_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    <span>Supervisão Alimentação</span>
                </a>
            </li>
            <li>
                <a href="validacao_lancamentos_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'validacao_lancamentos_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Validação de Lançamentos</span>
                </a>
            </li>
            
            <!-- Relatórios -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Relatórios</p>
            </li>
            <li>
                <a href="relatorios_financeiros_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'relatorios_financeiros_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Relatórios Financeiros</span>
                </a>
            </li>
            <li>
                <a href="relatorios_pedagogicos_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'relatorios_pedagogicos_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Relatórios Pedagógicos</span>
                </a>
            </li>
            
            <!-- Configurações -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Configurações</p>
            </li>
            <li>
                <a href="permissoes_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'permissoes_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span>Permissões</span>
                </a>
            </li>
            <li>
                <a href="configuracoes_seguranca_adm.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= $currentPage === 'configuracoes_seguranca_adm.php' ? 'active' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Configurações e Segurança</span>
                </a>
            </li>
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
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('hidden');
        }
    };
    
    // Fechar sidebar ao clicar no overlay
    document.getElementById('mobileOverlay')?.addEventListener('click', function() {
        window.toggleSidebar();
    });
    
    // Manter posição do scroll do sidebar ao navegar
    (function() {
        const sidebarNav = document.querySelector('nav');
        if (!sidebarNav) return;
        
        // Salvar posição do scroll antes de navegar
        const sidebarLinks = sidebarNav.querySelectorAll('a[href]');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Salvar posição do scroll no sessionStorage
                sessionStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
            });
        });
        
        // Restaurar posição do scroll após carregar a página
        window.addEventListener('load', function() {
            const savedScroll = sessionStorage.getItem('sidebarScroll');
            if (savedScroll !== null) {
                sidebarNav.scrollTop = parseInt(savedScroll, 10);
            }
        });
        
        // Também restaurar no DOMContentLoaded para ser mais rápido
        document.addEventListener('DOMContentLoaded', function() {
            const savedScroll = sessionStorage.getItem('sidebarScroll');
            if (savedScroll !== null) {
                sidebarNav.scrollTop = parseInt(savedScroll, 10);
            }
        });
    })();
</script>

<style>
    .sidebar-transition { transition: all 0.3s ease-in-out; }
    .content-transition { transition: margin-left 0.3s ease-in-out; }
    .bg-primary-green {
        background-color: #2D5A27;
    }
    .menu-item.active {
        background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
        border-right: 3px solid #2D5A27;
        color: #2D5A27;
    }
    .menu-item.active svg {
        color: #2D5A27;
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
    
    /* Estilos para tema escuro */
    [data-theme="dark"] #sidebar {
        background-color: #1f2937;
        border-right: 1px solid #374151;
    }
    
    [data-theme="dark"] #sidebar .border-gray-200 {
        border-color: #374151;
    }
    
    [data-theme="dark"] #sidebar .text-gray-800 {
        color: #f3f4f6;
    }
    
    [data-theme="dark"] #sidebar .text-gray-500 {
        color: #9ca3af;
    }
    
    [data-theme="dark"] #sidebar .bg-white {
        background-color: #1f2937;
    }
    
    [data-theme="dark"] #sidebar button.text-red-600 {
        color: #f87171;
    }
    
    [data-theme="dark"] #sidebar button:hover.bg-red-50 {
        background-color: rgba(239, 68, 68, 0.1);
    }
</style>

