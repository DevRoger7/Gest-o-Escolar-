<?php
// Verificar se DashboardStats está disponível
if (!class_exists('DashboardStats')) {
    require_once('../../../Models/dashboard/DashboardStats.php');
}
$stats = new DashboardStats();

// Buscar dados do usuário
$usuarioId = $_SESSION['usuario_id'] ?? null;
$dadosUsuario = $usuarioId ? $stats->getDadosUsuario($usuarioId) : null;
$estatisticasUsuario = $usuarioId ? $stats->getEstatisticasUsuario($usuarioId, $_SESSION['tipo'] ?? '') : [];

// Gerar iniciais
$nome = $_SESSION['nome'] ?? '';
$iniciais = '';
if (strlen($nome) >= 2) {
    $iniciais = strtoupper(substr($nome, 0, 2));
} elseif (strlen($nome) == 1) {
    $iniciais = strtoupper($nome);
} else {
    $iniciais = 'US';
}

// Mapear tipos de usuário para nomes amigáveis
$tiposUsuario = [
    'ADM' => 'Administrador Geral',
    'GESTAO' => 'Gestor Escolar',
    'PROFESSOR' => 'Professor',
    'ALUNO' => 'Aluno',
    'NUTRICIONISTA' => 'Nutricionista',
    'ADM_MERENDA' => 'Administrador de Merenda'
];
$tipoUsuarioNome = $tiposUsuario[$_SESSION['tipo'] ?? ''] ?? $_SESSION['tipo'] ?? 'Usuário';

// Calcular percentuais
$totalEscolas = $stats->getTotalEscolas();
$totalUsuarios = $stats->getTotalUsuarios();
$percentualEscolas = $totalEscolas > 0 ? min(100, ($totalEscolas / 20) * 100) : 0;
$percentualUsuarios = $totalUsuarios > 0 ? min(100, ($totalUsuarios / 300) * 100) : 0;
$escolasEsteMes = $stats->getEscolasEsteMes();
$usuariosEstaSemana = $stats->getUsuariosEstaSemana();
?>
<div id="userProfileModal" class="fixed inset-0 bg-white z-50 hidden flex flex-col backdrop-blur-sm">
    <div class="bg-white w-full h-full overflow-hidden transform transition-all duration-300 ease-out scale-95 opacity-0" id="modalContent">
        <!-- Header - Responsivo -->
        <div class="bg-gradient-to-br from-slate-800 via-slate-700 to-slate-800 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-primary-green/20 to-blue-600/20"></div>
            <div class="relative z-10 flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                <div class="flex items-center space-x-2 sm:space-x-4 flex-1 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-white/10 backdrop-blur-sm rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 object-contain">
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold truncate">Perfil do Usuário</h1>
                        <p class="text-slate-300 text-xs sm:text-sm hidden sm:block">Gerencie suas informações e configurações</p>
                    </div>
                </div>
                <button onclick="closeUserProfile()" class="p-2 sm:p-3 hover:bg-white/10 rounded-lg sm:rounded-xl transition-all duration-200 group flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Navigation Tabs - Responsiva -->
        <div class="bg-white sticky top-0 z-20">
            <div class="px-2 sm:px-4 lg:px-8 py-2 sm:py-4">
                <nav class="flex items-center justify-center space-x-0.5 sm:space-x-1 bg-gray-100 rounded-xl sm:rounded-2xl p-1 sm:p-2 shadow-inner relative">
                    <button class="profile-tab active group flex-1 flex items-center justify-center px-2 sm:px-3 lg:px-6 py-2 sm:py-3 rounded-lg sm:rounded-xl transition-all duration-300" data-tab="overview" onclick="switchProfileTab('overview')">
                        <div class="flex items-center space-x-1 sm:space-x-2 lg:space-x-3 w-full">
                            <div class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 bg-white rounded-md sm:rounded-lg flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 lg:w-4 lg:h-4 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="text-left min-w-0 flex-1">
                                <span class="font-semibold text-xs sm:text-sm text-gray-900 truncate block">Visão Geral</span>
                                <p class="text-xs text-gray-500 hidden lg:block">Dashboard principal</p>
                            </div>
                        </div>
                    </button>
                    
                    <button class="profile-tab group flex-1 flex items-center justify-center px-2 sm:px-3 lg:px-6 py-2 sm:py-3 rounded-lg sm:rounded-xl transition-all duration-300 hover:bg-white/50" data-tab="personal" onclick="switchProfileTab('personal')">
                        <div class="flex items-center space-x-1 sm:space-x-2 lg:space-x-3 w-full">
                            <div class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 bg-gray-200 rounded-md sm:rounded-lg flex items-center justify-center group-hover:bg-blue-100 group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 lg:w-4 lg:h-4 text-gray-600 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div class="text-left min-w-0 flex-1">
                                <span class="font-semibold text-xs sm:text-sm text-gray-700 group-hover:text-gray-900 transition-colors truncate block">Perfil</span>
                                <p class="text-xs text-gray-500 hidden lg:block">Informações pessoais</p>
                            </div>
                        </div>
                    </button>
                    
                    <button class="profile-tab group flex-1 flex items-center justify-center px-2 sm:px-3 lg:px-6 py-2 sm:py-3 rounded-lg sm:rounded-xl transition-all duration-300 hover:bg-white/50" data-tab="system" onclick="switchProfileTab('system')">
                        <div class="flex items-center space-x-1 sm:space-x-2 lg:space-x-3 w-full">
                            <div class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 bg-gray-200 rounded-md sm:rounded-lg flex items-center justify-center group-hover:bg-purple-100 group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 lg:w-4 lg:h-4 text-gray-600 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                            </div>
                            <div class="text-left min-w-0 flex-1">
                                <span class="font-semibold text-xs sm:text-sm text-gray-700 group-hover:text-gray-900 transition-colors truncate block">Sistema</span>
                                <p class="text-xs text-gray-500 hidden lg:block">Status e métricas</p>
                            </div>
                        </div>
                    </button>
                    
                    <button class="profile-tab group flex-1 flex items-center justify-center px-2 sm:px-3 lg:px-6 py-2 sm:py-3 rounded-lg sm:rounded-xl transition-all duration-300 hover:bg-white/50" data-tab="settings" onclick="switchProfileTab('settings')">
                        <div class="flex items-center space-x-1 sm:space-x-2 lg:space-x-3 w-full">
                            <div class="w-6 h-6 sm:w-7 sm:h-7 lg:w-8 lg:h-8 bg-gray-200 rounded-md sm:rounded-lg flex items-center justify-center group-hover:bg-green-100 group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 lg:w-4 lg:h-4 text-gray-600 group-hover:text-green-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div class="text-left min-w-0 flex-1">
                                <span class="font-semibold text-xs sm:text-sm text-gray-700 group-hover:text-gray-900 transition-colors truncate block">Configurações</span>
                                <p class="text-xs text-gray-500 hidden lg:block">Preferências</p>
                            </div>
                        </div>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto bg-gray-50 relative" style="max-height: calc(100vh - 200px);">
            <!-- Scroll to Top Button -->
            <button id="scrollToTop" onclick="scrollToTop()" class="fixed bottom-4 right-4 sm:bottom-8 sm:right-8 w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-r from-primary-green to-green-600 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-300 opacity-0 pointer-events-none z-50 flex items-center justify-center">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
            </button>
            
            <div class="p-4 sm:p-6 lg:p-8 xl:p-12 min-h-full">
                <!-- Tab Content: Overview -->
                <div id="profile-overview" class="profile-tab-content">
                    <!-- User Profile Hero Card - Estilo Métricas -->
                    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-6 sm:mb-8 transform hover:scale-[1.01] sm:hover:scale-[1.02] transition-all duration-300">
                        <div class="bg-gradient-to-r from-slate-800 via-gray-800 to-slate-900 p-4 sm:p-6 lg:p-8 relative overflow-hidden">
                            <!-- Elementos decorativos - ocultos no mobile -->
                            <div class="absolute top-0 right-0 w-20 h-20 sm:w-32 sm:h-32 bg-blue-500/10 rounded-full -translate-y-10 sm:-translate-y-16 translate-x-10 sm:translate-x-16 hidden sm:block"></div>
                            <div class="absolute bottom-0 left-0 w-16 h-16 sm:w-24 sm:h-24 bg-green-500/5 rounded-full translate-y-8 sm:translate-y-12 -translate-x-8 sm:-translate-x-12 hidden sm:block"></div>
                            
                            <!-- Layout Simples e Organizado -->
                            <div class="relative z-10 flex items-center space-x-4 sm:space-x-6">
                                <!-- Avatar -->
                                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-600 rounded-xl flex items-center justify-center shadow-2xl flex-shrink-0">
                                    <span class="text-xl sm:text-2xl font-bold text-white"><?= $iniciais ?></span>
                                </div>
                                
                                <!-- Informações do Usuário -->
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-white text-lg sm:text-xl font-bold mb-1 truncate"><?= htmlspecialchars($nome) ?></h2>
                                    <p class="text-gray-300 text-sm mb-2">Administrador do Sistema</p>
                                    <p class="text-white text-sm font-medium"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></p>
                                </div>
                                
                                <!-- Status Online -->
                                <div class="flex-shrink-0">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                        <span class="text-green-100 text-sm font-medium">Online</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards - Limpas -->
                    <div class="mb-6 sm:mb-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-2 sm:gap-0">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Métricas do Sistema</h3>
                            <div class="flex items-center space-x-2 text-xs sm:text-sm text-gray-500">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                <span class="hidden sm:inline">Atualizado agora</span>
                                <span class="sm:hidden">Agora</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
                            <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between mb-2 sm:mb-3 lg:mb-4">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="text-right hidden sm:block">
                                        <?php if ($escolasEsteMes > 0): ?>
                                        <p class="text-xs text-green-600 font-medium">+<?= $escolasEsteMes ?> este mês</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors"><?= $totalEscolas ?></p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-600 mt-1">Escolas</p>
                                    <div class="mt-1 sm:mt-2 bg-gray-200 rounded-full h-1.5 sm:h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-1.5 sm:h-2 rounded-full" style="width: <?= $percentualEscolas ?>%"></div>
                                    </div>
                                    <?php if ($escolasEsteMes > 0): ?>
                                    <p class="text-xs text-green-600 font-medium mt-1 sm:hidden">+<?= $escolasEsteMes ?> este mês</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between mb-2 sm:mb-3 lg:mb-4">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-right hidden sm:block">
                                        <?php if ($usuariosEstaSemana > 0): ?>
                                        <p class="text-xs text-green-600 font-medium">+<?= $usuariosEstaSemana ?> esta semana</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 group-hover:text-green-600 transition-colors"><?= $totalUsuarios ?></p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-600 mt-1">Usuários</p>
                                    <div class="mt-1 sm:mt-2 bg-gray-200 rounded-full h-1.5 sm:h-2">
                                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-1.5 sm:h-2 rounded-full" style="width: <?= $percentualUsuarios ?>%"></div>
                                    </div>
                                    <?php if ($usuariosEstaSemana > 0): ?>
                                    <p class="text-xs text-green-600 font-medium mt-1 sm:hidden">+<?= $usuariosEstaSemana ?> esta semana</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between mb-2 sm:mb-3 lg:mb-4">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-right hidden sm:block">
                                        <p class="text-xs text-green-600 font-medium">Excelente</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 group-hover:text-purple-600 transition-colors">99.9%</p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-600 mt-1">Uptime</p>
                                    <div class="mt-1 sm:mt-2 bg-gray-200 rounded-full h-1.5 sm:h-2">
                                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-1.5 sm:h-2 rounded-full" style="width: 99.9%"></div>
                                    </div>
                                    <p class="text-xs text-green-600 font-medium mt-1 sm:hidden">Excelente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Personal Information -->
                <div id="profile-personal" class="profile-tab-content hidden">
                    <!-- Profile Header - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg mb-8">
                        <div class="p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                                <!-- Avatar -->
                                <div class="relative">
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                                        <span class="text-2xl font-medium text-gray-700"><?= $iniciais ?></span>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-white"></div>
                                </div>
                                
                                <!-- User Info -->
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-2xl font-semibold text-gray-900 mb-1"><?= htmlspecialchars($nome) ?></h2>
                                    <p class="text-gray-600 text-sm mb-3"><?= $tipoUsuarioNome ?></p>
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                        <?php if ($dadosUsuario && !empty($dadosUsuario['ultimo_login_formatado'])): ?>
                                        <span>Último acesso: <?= $dadosUsuario['ultimo_login_formatado'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($dadosUsuario && !empty($dadosUsuario['data_criacao_formatada'])): ?>
                                        <span>Membro desde <?= $dadosUsuario['data_criacao_formatada'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <button class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                                    Editar Perfil
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards - Minimalista -->
                    <?php if (!empty($estatisticasUsuario)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <?php foreach ($estatisticasUsuario as $key => $value): ?>
                        <div class="bg-white border border-gray-200 rounded-lg p-5">
                            <p class="text-2xl font-semibold text-gray-900 mb-1"><?= $value ?></p>
                            <p class="text-sm text-gray-600 capitalize"><?= str_replace('_', ' ', $key) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Information Cards - Minimalista -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Personal Info Card -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b border-gray-200">Informações Pessoais</h3>
                            
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nome Completo</label>
                                    <p class="text-base text-gray-900"><?= htmlspecialchars($nome) ?></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Email</label>
                                    <p class="text-base text-gray-900"><?= htmlspecialchars($_SESSION['email'] ?? 'Não informado') ?></p>
                                </div>
                                
                                <?php if ($dadosUsuario && !empty($dadosUsuario['cpf_formatado'])): ?>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">CPF</label>
                                    <p class="text-base text-gray-900"><?= $dadosUsuario['cpf_formatado'] ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($dadosUsuario && !empty($dadosUsuario['telefone'])): ?>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Telefone</label>
                                    <p class="text-base text-gray-900"><?= htmlspecialchars($dadosUsuario['telefone']) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($dadosUsuario && !empty($dadosUsuario['data_nascimento']) && $dadosUsuario['data_nascimento'] != '0000-00-00'): ?>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Data de Nascimento</label>
                                    <p class="text-base text-gray-900">
                                        <?php 
                                        $dataNasc = new DateTime($dadosUsuario['data_nascimento']);
                                        echo $dataNasc->format('d/m/Y');
                                        if (isset($dadosUsuario['idade'])) {
                                            echo ' (' . $dadosUsuario['idade'] . ' anos)';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Account & Status Card -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b border-gray-200">Conta & Status</h3>
                            
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-2">Tipo de Usuário</label>
                                    <span class="inline-block px-3 py-1 bg-gray-100 text-gray-900 rounded text-sm font-medium">
                                        <?= $tipoUsuarioNome ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <label class="block text-xs text-gray-500 mb-2">Status da Conta</label>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-sm text-gray-900">Ativo</span>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs text-gray-500 mb-2">Nível de Acesso</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700">Permissões</span>
                                            <span class="text-gray-900 font-medium">100%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-gray-900 h-2 rounded-full" style="width: 100%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($dadosUsuario && !empty($dadosUsuario['username'])): ?>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Username</label>
                                    <p class="text-base text-gray-900 font-mono">@<?= htmlspecialchars($dadosUsuario['username']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Timeline - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b border-gray-200">Atividade Recente</h3>
                        
                        <div class="space-y-4">
                            <?php
                            $atividadesRecentes = $stats->getAtividadesRecentes(5);
                            if (empty($atividadesRecentes)):
                            ?>
                            <div class="text-center py-8">
                                <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($atividadesRecentes as $atividade): 
                                    $dataAtividade = new DateTime($atividade['data']);
                                    $agora = new DateTime();
                                    $diff = $agora->diff($dataAtividade);
                                    
                                    $tempoRelativo = '';
                                    if ($diff->days > 0) {
                                        $tempoRelativo = $diff->days == 1 ? 'Ontem' : 'Há ' . $diff->days . ' dias';
                                    } elseif ($diff->h > 0) {
                                        $tempoRelativo = 'Há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                                    } elseif ($diff->i > 0) {
                                        $tempoRelativo = 'Há ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
                                    } else {
                                        $tempoRelativo = 'Agora mesmo';
                                    }
                                ?>
                                <div class="flex items-start gap-4 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full mt-2"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 mb-1"><?= htmlspecialchars($atividade['titulo']) ?></p>
                                        <p class="text-xs text-gray-600 mb-1"><?= htmlspecialchars($atividade['descricao']) ?></p>
                                        <p class="text-xs text-gray-500"><?= $tempoRelativo ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Sistema -->
                <div id="profile-system" class="profile-tab-content hidden">
                    <!-- System Overview Card -->
                    <div class="bg-gradient-to-br from-white to-gray-50 rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8">
                        <div class="bg-gradient-to-r from-slate-800 via-slate-700 to-slate-800 p-8 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
                            <div class="relative z-10 text-white text-center">
                                <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold mb-2">Status do Sistema</h2>
                                <p class="text-slate-300 mb-4">Todas as operações funcionando normalmente</p>
                                <div class="flex items-center justify-center space-x-2">
                                    <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                                    <span class="text-green-100 font-medium">Sistema Online</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Total</p>
                                    <?php if ($escolasEsteMes > 0): ?>
                                    <p class="text-sm font-medium text-green-600">+<?= $escolasEsteMes ?> este mês</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-1"><?= $totalEscolas ?></h3>
                            <p class="text-sm font-medium text-gray-600">Escolas Gerenciadas</p>
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: <?= $percentualEscolas ?>%"></div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Ativos</p>
                                    <?php if ($usuariosEstaSemana > 0): ?>
                                    <p class="text-sm font-medium text-green-600">+<?= $usuariosEstaSemana ?> esta semana</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-1"><?= $totalUsuarios ?></h3>
                            <p class="text-sm font-medium text-gray-600">Usuários Ativos</p>
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: <?= $percentualUsuarios ?>%"></div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Uptime</p>
                                    <p class="text-sm font-medium text-green-600">Excelente</p>
                                </div>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-1">99.9%</h3>
                            <p class="text-sm font-medium text-gray-600">Sistema Estável</p>
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full" style="width: 99.9%"></div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Última</p>
                                    <p class="text-sm font-medium text-green-600">Hoje</p>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= date('H:i') ?></h3>
                            <p class="text-sm font-medium text-gray-600">Última Atualização</p>
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- System Performance Chart -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Performance do Sistema</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="w-20 h-20 mx-auto mb-4 relative">
                                    <svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <path class="text-green-500" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="99.9, 100" stroke-dashoffset="0" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-lg font-bold text-green-600">99.9%</span>
                                    </div>
                                </div>
                                <h4 class="font-semibold text-gray-900">Uptime</h4>
                                <p class="text-sm text-gray-500">Disponibilidade</p>
                            </div>
                            
                            <div class="text-center">
                                <div class="w-20 h-20 mx-auto mb-4 relative">
                                    <svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <path class="text-blue-500" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="85, 100" stroke-dashoffset="0" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-lg font-bold text-blue-600">85%</span>
                                    </div>
                                </div>
                                <h4 class="font-semibold text-gray-900">Performance</h4>
                                <p class="text-sm text-gray-500">Velocidade</p>
                            </div>
                            
                            <div class="text-center">
                                <div class="w-20 h-20 mx-auto mb-4 relative">
                                    <svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <path class="text-purple-500" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="92, 100" stroke-dashoffset="0" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-lg font-bold text-purple-600">92%</span>
                                    </div>
                                </div>
                                <h4 class="font-semibold text-gray-900">Capacidade</h4>
                                <p class="text-sm text-gray-500">Recursos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Configurações -->
                <div id="profile-settings" class="profile-tab-content hidden">
                    <!-- Settings Header - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg mb-8">
                        <div class="p-6 sm:p-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-2">Configurações</h2>
                            <p class="text-gray-600 text-sm">Personalize sua experiência e preferências</p>
                        </div>
                    </div>

                    <!-- Theme Settings - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b border-gray-200">Tema Visual</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button id="theme-light" class="theme-option p-6 border-2 border-gray-200 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors text-left">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Tema Claro</h4>
                                        <p class="text-sm text-gray-600">Interface clara</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button id="theme-dark" class="theme-option p-6 border-2 border-gray-200 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors text-left">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-gray-800 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Tema Escuro</h4>
                                        <p class="text-sm text-gray-600">Interface escura</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Notification Settings - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b border-gray-200">Notificações</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">Notificações por Email</h4>
                                    <p class="text-sm text-gray-600">Receba atualizações importantes por email</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">Notificações do Sistema</h4>
                                    <p class="text-sm text-gray-600">Alertas sobre status do sistema</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">Notificações de Atividade</h4>
                                    <p class="text-sm text-gray-600">Alertas sobre atividades importantes</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Accessibility Settings - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b border-gray-200">Acessibilidade</h3>
                        
                        <div class="space-y-4">
                            <!-- VLibras Toggle -->
                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">VLibras (Libras)</h4>
                                    <p class="text-sm text-gray-600">Tradução automática para Libras</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="vlibras-toggle" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                </label>
                            </div>
                            
                            <!-- High Contrast Toggle -->
                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">Alto Contraste</h4>
                                    <p class="text-sm text-gray-600">Melhora a visibilidade dos elementos</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="contrast-toggle" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                </label>
                            </div>
                            
                            <!-- Font Size Controls -->
                            <div class="pt-3">
                                <h4 class="font-medium text-gray-900 mb-4">Tamanho da Fonte</h4>
                                <div class="flex items-center gap-3">
                                    <button id="font-decrease" class="w-10 h-10 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <span id="font-size-display" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium min-w-[60px] text-center">100%</span>
                                    <button id="font-increase" class="w-10 h-10 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                    <button id="font-reset" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                                        Padrão
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons - Minimalista -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex flex-col sm:flex-row gap-3 justify-end">
                            <button class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                                Cancelar
                            </button>
                            <button class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium">
                                Salvar Configurações
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

