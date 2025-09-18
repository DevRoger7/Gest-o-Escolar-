
<?php
require_once('../../Models/sessao/sessions.php');
$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIGEM</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/Brasão_de_Maranguape.png" type="image/png">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/img/Brasão_de_Maranguape.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/img/Brasão_de_Maranguape.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                        'accent-orange': '#FF6B35',
                        'accent-red': '#D62828',
                        'light-green': '#A8D5BA',
                        'warm-orange': '#FF8C42'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
    
    <style>
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
        
        /* Tema Claro Melhorado */
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .card-hover {
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
        }
        
        /* Sidebar tema claro */
        #sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
        }
        
        .menu-item {
            transition: all 0.2s ease;
        }
        
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        
        /* Header tema claro */
        header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Botões melhorados */
        button {
            transition: all 0.2s ease;
        }
        
        button:hover {
            transform: translateY(-1px);
        }
        
        /* Inputs melhorados */
        input, select, textarea {
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
        }
        
        .mobile-menu-overlay {
            backdrop-filter: blur(4px);
        }
        
        
        
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }
        
        /* Acessibilidade - Tema Escuro */
        [data-theme="dark"] {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #262626;
            --bg-quaternary: #333333;
            --text-primary: #ffffff;
            --text-secondary: #e5e5e5;
            --text-muted: #a3a3a3;
            --text-accent: #d4d4d4;
            --border-color: #404040;
            --border-light: #525252;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --primary-green: #22c55e;
            --primary-green-hover: #16a34a;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-orange: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        [data-theme="dark"] .bg-white {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }
        
        [data-theme="dark"] .text-gray-800 {
            color: var(--text-primary) !important;
        }
        
        [data-theme="dark"] .text-gray-600 {
            color: var(--text-secondary) !important;
        }
        
        [data-theme="dark"] .text-gray-500 {
            color: var(--text-muted) !important;
        }
        
        [data-theme="dark"] .text-gray-400 {
            color: var(--text-muted) !important;
        }
        
        [data-theme="dark"] .border-gray-200 {
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .bg-gray-50 {
            background: linear-gradient(145deg, var(--bg-tertiary) 0%, var(--bg-quaternary) 100%) !important;
            border: 1px solid var(--border-light) !important;
        }
        
        [data-theme="dark"] .bg-gray-100 {
            background-color: var(--bg-quaternary) !important;
        }
        
        [data-theme="dark"] .shadow-lg {
            box-shadow: var(--shadow-lg) !important;
        }
        
        [data-theme="dark"] .shadow-sm {
            box-shadow: var(--shadow) !important;
        }
        
        /* Cores específicas do tema escuro */
        [data-theme="dark"] .bg-primary-green {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-hover) 100%) !important;
            color: white !important;
        }
        
        [data-theme="dark"] .text-primary-green {
            color: var(--primary-green) !important;
        }
        
        [data-theme="dark"] .bg-blue-100 {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }
        
        [data-theme="dark"] .text-blue-600 {
            color: var(--accent-blue) !important;
        }
        
        [data-theme="dark"] .bg-green-100 {
            background-color: rgba(34, 197, 94, 0.2) !important;
        }
        
        [data-theme="dark"] .text-green-600 {
            color: var(--success) !important;
        }
        
        [data-theme="dark"] .bg-orange-100 {
            background-color: rgba(245, 158, 11, 0.2) !important;
        }
        
        [data-theme="dark"] .text-orange-600 {
            color: var(--accent-orange) !important;
        }
        
        [data-theme="dark"] .bg-purple-100 {
            background-color: rgba(139, 92, 246, 0.2) !important;
        }
        
        [data-theme="dark"] .text-purple-600 {
            color: var(--accent-purple) !important;
        }
        
        [data-theme="dark"] .bg-red-100 {
            background-color: rgba(239, 68, 68, 0.2) !important;
        }
        
        [data-theme="dark"] .text-red-600 {
            color: var(--error) !important;
        }
        
        /* Gradientes especiais para o tema escuro */
        [data-theme="dark"] .card-hover {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .card-hover:hover {
            background: linear-gradient(145deg, var(--bg-tertiary) 0%, var(--bg-quaternary) 100%);
            border-color: var(--border-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Sidebar tema escuro */
        [data-theme="dark"] #sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .menu-item {
            color: var(--text-secondary) !important;
        }
        
        [data-theme="dark"] .menu-item:hover {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            color: var(--text-primary) !important;
        }
        
        [data-theme="dark"] .menu-item.active {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border-right: 3px solid var(--primary-green);
            color: var(--text-primary) !important;
        }
        
        /* Header tema escuro */
        [data-theme="dark"] header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Botões tema escuro */
        [data-theme="dark"] button {
            transition: all 0.2s ease;
        }
        
        [data-theme="dark"] button:hover {
            transform: translateY(-1px);
        }
        
        /* Inputs tema escuro */
        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea {
            background-color: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        
        [data-theme="dark"] input:focus,
        [data-theme="dark"] select:focus,
        [data-theme="dark"] textarea:focus {
            border-color: var(--primary-green) !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1) !important;
        }
        
        /* Correção para cards de atividades no tema escuro */
        [data-theme="dark"] .bg-gradient-to-r {
            background: linear-gradient(145deg, var(--bg-tertiary) 0%, var(--bg-quaternary) 100%) !important;
            border: 1px solid var(--border-color) !important;
        }
        
        [data-theme="dark"] .from-blue-50,
        [data-theme="dark"] .to-blue-100 {
            background: linear-gradient(145deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%) !important;
        }
        
        [data-theme="dark"] .from-green-50,
        [data-theme="dark"] .to-green-100 {
            background: linear-gradient(145deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.2) 100%) !important;
        }
        
        [data-theme="dark"] .from-orange-50,
        [data-theme="dark"] .to-orange-100 {
            background: linear-gradient(145deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%) !important;
        }
        
        /* Texto dos cards de atividades no tema escuro */
        [data-theme="dark"] .text-gray-800 {
            color: var(--text-primary) !important;
        }
        
        [data-theme="dark"] .text-gray-600 {
            color: var(--text-secondary) !important;
        }
        
        [data-theme="dark"] .text-gray-500 {
            color: var(--text-muted) !important;
        }
        
        /* Ícones dos cards de atividades no tema escuro */
        [data-theme="dark"] .bg-blue-500 {
            background-color: var(--accent-blue) !important;
        }
        
        [data-theme="dark"] .bg-green-500 {
            background-color: var(--success) !important;
        }
        
        [data-theme="dark"] .bg-orange-500 {
            background-color: var(--accent-orange) !important;
        }
        
        [data-theme="dark"] .text-white {
            color: var(--text-primary) !important;
        }
        
        /* Correção para botões de ações rápidas no tema escuro */
        [data-theme="dark"] .from-blue-500,
        [data-theme="dark"] .to-blue-600 {
            background: linear-gradient(135deg, var(--accent-blue) 0%, #2563eb 100%) !important;
        }
        
        [data-theme="dark"] .from-green-500,
        [data-theme="dark"] .to-green-600 {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%) !important;
        }
        
        [data-theme="dark"] .from-orange-500,
        [data-theme="dark"] .to-orange-600 {
            background: linear-gradient(135deg, var(--accent-orange) 0%, #d97706 100%) !important;
        }
        
        [data-theme="dark"] .from-purple-500,
        [data-theme="dark"] .to-purple-600 {
            background: linear-gradient(135deg, var(--accent-purple) 0%, #7c3aed 100%) !important;
        }
        
        /* Correção para hover dos botões no tema escuro */
        [data-theme="dark"] .hover\:from-blue-600:hover,
        [data-theme="dark"] .hover\:to-blue-700:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        }
        
        [data-theme="dark"] .hover\:from-green-600:hover,
        [data-theme="dark"] .hover\:to-green-700:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
        }
        
        [data-theme="dark"] .hover\:from-orange-600:hover,
        [data-theme="dark"] .hover\:to-orange-700:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
        }
        
        [data-theme="dark"] .hover\:from-purple-600:hover,
        [data-theme="dark"] .hover\:to-purple-700:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        }
        
        /* Correção para o banner de boas-vindas no tema escuro */
        [data-theme="dark"] .text-green-100 {
            color: var(--text-secondary) !important;
        }
        
        /* Estilos para seção de acessibilidade no tema escuro */
        [data-theme="dark"] .bg-gradient-to-br.from-gray-50.to-gray-100 {
            background: linear-gradient(145deg, var(--bg-tertiary) 0%, var(--bg-quaternary) 100%) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .bg-white {
            background-color: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .bg-gradient-to-r.from-blue-50.to-indigo-50 {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .border-blue-200 {
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .bg-gray-100 {
            background-color: var(--bg-quaternary) !important;
        }
        
        [data-theme="dark"] .text-gray-600 {
            color: var(--text-muted) !important;
        }
        
        [data-theme="dark"] .text-gray-700 {
            color: var(--text-secondary) !important;
        }
        
        /* Toggle switches no tema escuro */
        [data-theme="dark"] .peer-checked\:bg-primary-green:checked {
            background-color: var(--primary-green) !important;
        }
        
        [data-theme="dark"] .bg-gray-200 {
            background-color: var(--bg-quaternary) !important;
        }
        
        /* Acessibilidade - Alto Contraste */
        [data-contrast="high"] {
            --contrast-bg: #000000;
            --contrast-text: #ffffff;
            --contrast-border: #ffffff;
            --contrast-accent: #ffff00;
        }
        
        [data-contrast="high"] body {
            background-color: var(--contrast-bg);
            color: var(--contrast-text);
        }
        
        [data-contrast="high"] .bg-white,
        [data-contrast="high"] .bg-gray-50 {
            background-color: var(--contrast-bg) !important;
            color: var(--contrast-text) !important;
            border: 2px solid var(--contrast-border) !important;
        }
        
        [data-contrast="high"] .text-gray-800,
        [data-contrast="high"] .text-gray-600,
        [data-contrast="high"] .text-gray-500 {
            color: var(--contrast-text) !important;
        }
        
        [data-contrast="high"] .border-gray-200 {
            border-color: var(--contrast-border) !important;
        }
        
        [data-contrast="high"] .bg-primary-green {
            background-color: var(--contrast-accent) !important;
            color: var(--contrast-bg) !important;
        }
        
        [data-contrast="high"] .text-primary-green {
            color: var(--contrast-accent) !important;
        }
        
        /* Acessibilidade - Foco Visível */
        .focus-visible:focus {
            outline: 3px solid #4A90E2 !important;
            outline-offset: 2px !important;
        }
        
        /* Acessibilidade - Redução de Movimento */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Acessibilidade - Tamanho de Fonte */
        [data-font-size="large"] {
            font-size: 1.125rem;
        }
        
        [data-font-size="larger"] {
            font-size: 1.25rem;
        }
        
        [data-font-size="largest"] {
            font-size: 1.5rem;
        }
        
        /* Scrollbar personalizada para tema escuro */
        [data-theme="dark"] ::-webkit-scrollbar {
            width: 8px;
        }
        
        [data-theme="dark"] ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        [data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: var(--bg-quaternary);
            border-radius: 4px;
        }
        
        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: var(--border-light);
        }
        
        /* Efeitos especiais para o tema escuro */
        [data-theme="dark"] .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Melhorias nos modais do tema escuro */
        [data-theme="dark"] .modal-content {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border: 1px solid var(--border-color);
        }
        
        /* Hover effects melhorados para tema escuro */
        [data-theme="dark"] .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        [data-theme="dark"] .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        
        /* Gradientes especiais para cards no tema escuro */
        [data-theme="dark"] .gradient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        [data-theme="dark"] .gradient-card-green {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        [data-theme="dark"] .gradient-card-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        [data-theme="dark"] .gradient-card-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        /* Scrollbar personalizada para tema claro */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Melhorias visuais extras para tema claro */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Animações suaves */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Hover effects melhorados */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        /* Gradientes especiais para cards */
        .gradient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-card-green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .gradient-card-orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .gradient-card-blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile">
        <!-- Logo e Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <img src="<?php echo BASE_URL; ?>/assets/img/Brasão_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">SIGEM</h1>
                    <p class="text-xs text-gray-500">Maranguape</p>
                </div>
            </div>
        </div>
        
        <!-- User Info -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold text-sm" id="userInitials">JS</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800" id="userName"><?= $_SESSION['usuario_nome'] ?? 'Usuário' ?></p>
                    <p class="text-xs text-gray-500"><?= $_SESSION['usuario_tipo'] ?? 'Funcionário' ?></p>
                </div>
            </div>
        </div>
        
        <nav class="p-4">
            <ul class="space-y-2">
                <li>
                    <a href="#" onclick="showSection('dashboard')" class="menu-item active flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li id="alunos-menu">
                    <a href="#" onclick="showSection('alunos')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                         </svg>
                        <span>Alunos</span>
                    </a>
                </li>
                <li id="turmas-menu">
                    <a href="#" onclick="showSection('turmas')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                         </svg>
                        <span>Turmas</span>
                    </a>
                </li>
                <li id="frequencia-menu">
                    <a href="#" onclick="showSection('frequencia')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                         </svg>
                        <span>Frequência</span>
                    </a>
                </li>
                <li id="notas-menu">
                    <a href="#" onclick="showSection('notas')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                         </svg>
                        <span>Notas</span>
                    </a>
                </li>
                <li id="merenda-menu">
                    <a href="#" onclick="showSection('merenda')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                         </svg>
                        <span>Merenda</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['Gerenciador de Usuarios'])) { ?>
                <li>
                    <a href="../../subsystems/gerenciador_usuario/index.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Gerenciador de Usuários</span>
                    </a>
                </li>
                <?php } ?>
                <?php if (isset($_SESSION['Estoque'])) { ?>
                <li>
                    <a href="../../subsystems/controle_de_estoque/default.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Controle de Estoque</span>
                    </a>
                </li>
                <?php } ?>
                <?php if (isset($_SESSION['Biblioteca'])) { ?>
                <li>
                    <a href="../../subsystems/biblioteca/default.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>Biblioteca</span>
                    </a>
                </li>
                <?php } ?>
                <?php if (isset($_SESSION['Entrada/saída'])) { ?>
                <li>
                    <a href="../../subsystems/entradasaida/app/main/views/inicio.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Entrada/Saída</span>
                    </a>
                </li>
                <?php } ?>
                <li>
                    <a href="#" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Relatórios</span>
                    </a>
                </li>
            </ul>
        </nav>

        
        <!-- Logout -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
            <button onclick="confirmLogout()" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Sair</span>
            </button>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green" aria-label="Abrir menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <!-- Page Title - Centered on mobile -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 lg:relative lg:left-auto lg:transform-none flex items-center">
                        <img src="<?php echo BASE_URL; ?>/assets/img/Brasão_de_Maranguape.png" alt="Brasão de Maranguape" class="w-8 h-8 object-contain lg:hidden">
                        <h1 class="hidden sm:block text-xl font-semibold text-gray-800" id="pageTitle">Dashboard</h1>
                    </div>
                    
                    <!-- User Actions -->
                    <div class="flex items-center space-x-4">
                        <div class="text-right hidden lg:block">
                            <p class="text-sm font-medium text-gray-800" id="currentSchool"><?= $_SESSION['escola_atual'] ?? 'Escola Municipal' ?></p>
                            <p class="text-xs text-gray-500">Escola Atual</p>
                        </div>
                        
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="text-sm font-semibold">Antonio Luiz Coelho</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Profile Button -->
                        <button onclick="openUserProfile()" class="p-2 text-gray-600 bg-gray-100 hover:text-gray-900 hover:bg-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-green transition-colors duration-200" aria-label="Abrir perfil do usuário e configurações de acessibilidade" title="Perfil e Acessibilidade (Alt+A)">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section">
                <!-- Welcome Banner -->
                <div class="relative rounded-2xl p-8 mb-8 text-white overflow-hidden" style="background: linear-gradient(135deg, rgba(45, 90, 39, 0.85) 0%, rgba(74, 124, 89, 0.75) 100%), url('https://www.opovo.com.br/_midias/jpg/2024/01/10/_pontos_turisticos_maranguape_anuario__3-24969493.jpg'); background-size: cover; background-position: center;">
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold mb-2">Bem-vindo de volta!</h2>
                        <p class="text-green-100">Aqui está um resumo das suas atividades escolares</p>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                    <div class="card-hover bg-white rounded-2xl p-4 md:p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-3 md:mb-4">
                                <div class="p-2 md:p-3 bg-blue-100 rounded-xl">
                                    <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">+12%</span>
                            </div>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-1">245</h3>
                            <p class="text-gray-600 text-xs md:text-sm">Total de Alunos</p>
                            <p class="text-xs text-gray-500 mt-1 hidden md:block">vs. mês anterior</p>
                        </div>
                    </div>
                    
                    <div class="card-hover bg-white rounded-2xl p-4 md:p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up" style="animation-delay: 0.1s">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-green-100 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 md:p-3 bg-green-100 rounded-xl">
                                    <svg class="w-5 h-5 md:w-6 md:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">+8%</span>
                            </div>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-1">12</h3>
                            <p class="text-gray-600 text-xs md:text-sm">Turmas Ativas</p>
                            <p class="text-xs text-gray-500 mt-1 hidden md:block">vs. mês anterior</p>
                        </div>
                    </div>
                    
                    <div class="card-hover bg-white rounded-2xl p-4 md:p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up" style="animation-delay: 0.2s">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-orange-100 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 md:p-3 bg-orange-100 rounded-xl">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">-3%</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-1">94.2%</h3>
                            <p class="text-gray-600 text-sm">Frequência Média</p>
                            <p class="text-xs text-gray-500 mt-1">vs. mês anterior</p>
                        </div>
                    </div>
                    
                    <div class="card-hover bg-white rounded-2xl p-4 md:p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up" style="animation-delay: 0.3s">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-purple-100 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-100 rounded-xl">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">+15%</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-1">7.8</h3>
                            <p class="text-gray-600 text-sm">Média Geral</p>
                            <p class="text-xs text-gray-500 mt-1">vs. mês anterior</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-800">Atividades Recentes</h3>
                            <button class="text-primary-green hover:text-secondary-green text-sm font-medium">Ver todas</button>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl">
                                <div class="p-2 bg-blue-500 rounded-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">Novo aluno matriculado</p>
                                    <p class="text-xs text-gray-600">Maria Silva - 5º Ano A</p>
                                    <p class="text-xs text-gray-500 mt-1">Há 2 horas</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl">
                                <div class="p-2 bg-green-500 rounded-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">Frequência registrada</p>
                                    <p class="text-xs text-gray-600">4º Ano B - 25 alunos presentes</p>
                                    <p class="text-xs text-gray-500 mt-1">Há 4 horas</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl">
                                <div class="p-2 bg-orange-500 rounded-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">Notas lançadas</p>
                                    <p class="text-xs text-gray-600">Matemática - 3º Ano A</p>
                                    <p class="text-xs text-gray-500 mt-1">Ontem</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Ações Rápidas</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <button class="p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <p class="text-sm font-medium">Novo Aluno</p>
                            </button>
                            
                            <button class="p-4 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                <p class="text-sm font-medium">Registrar Frequência</p>
                            </button>
                            
                            <button class="p-4 bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm font-medium">Lançar Notas</p>
                            </button>
                            
                            <button class="p-4 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <p class="text-sm font-medium">Relatórios</p>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Alunos Section -->
            <section id="alunos" class="content-section hidden">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Gestão de Alunos</h2>
                    <p class="text-gray-600">Aqui você pode gerenciar todos os alunos da escola.</p>
                </div>
            </section>
            
            <!-- Turmas Section -->
            <section id="turmas" class="content-section hidden">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Gestão de Turmas</h2>
                    <p class="text-gray-600">Aqui você pode gerenciar as turmas e suas configurações.</p>
                </div>
            </section>
            
            <!-- Frequência Section -->
            <section id="frequencia" class="content-section hidden">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Controle de Frequência</h2>
                    <p class="text-gray-600">Aqui você pode registrar e acompanhar a frequência dos alunos.</p>
                </div>
            </section>
            
            <!-- Notas Section -->
            <section id="notas" class="content-section hidden">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Gestão de Notas</h2>
                    <p class="text-gray-600">Aqui você pode lançar e gerenciar as notas dos alunos.</p>
                </div>
            </section>
            
            <!-- Relatórios Section -->
            <section id="relatorios" class="content-section hidden">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Relatórios</h2>
                    <p class="text-gray-600">Aqui você pode gerar e visualizar relatórios diversos.</p>
                </div>
            </section>
            
            <!-- Merenda Section -->
            <section id="merenda" class="content-section hidden">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Gestão de Merenda</h2>
                                <p class="text-gray-600 mt-1">Controle de estoque e cardápios escolares</p>
                            </div>
                            <button onclick="openAddProductModal()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Adicionar Produto</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total de Produtos</p>
                                    <p class="text-2xl font-bold text-gray-900" id="totalProducts">24</p>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Próximos do Vencimento</p>
                                    <p class="text-2xl font-bold text-orange-600" id="expiringProducts">3</p>
                                </div>
                                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Estoque Baixo</p>
                                    <p class="text-2xl font-bold text-red-600" id="lowStockProducts">5</p>
                                </div>
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Table -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Controle de Estoque</h3>
                            <p class="text-sm text-gray-600 mt-1">Gerencie produtos e suas datas de validade</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Validade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="productsTableBody">
                                    <!-- Products will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Escolas Section (ADM SME Only) -->
            <section id="escolas" class="content-section hidden">
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Gestão de Escolas</h2>
                                <p class="text-gray-600 mt-1">Cadastro e administração de escolas municipais</p>
                            </div>
                            <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Nova Escola</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Escolas Cadastradas</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600">Sistema de gestão de escolas em desenvolvimento...</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Usuários Section (ADM SME Only) -->
            <section id="usuarios" class="content-section hidden">
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Gestão de Usuários</h2>
                                <p class="text-gray-600 mt-1">Cadastro e administração de usuários do sistema</p>
                            </div>
                            <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Novo Usuário</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Usuários do Sistema</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600">Sistema de gestão de usuários em desenvolvimento...</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Estoque Central Section (ADM SME e ADM Merenda) -->
            <section id="estoque-central" class="content-section hidden">
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Estoque Central</h2>
                                <p class="text-gray-600 mt-1">Controle centralizado de estoque de alimentos</p>
                            </div>
                            <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Adicionar Produto</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Controle Central de Estoque</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600">Sistema de estoque central em desenvolvimento...</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
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
                <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Sim, Sair
                </button>
            </div>
        </div>
    </div>
    
    <!-- User Profile Modal -->
    <div id="userProfileModal" class="fixed inset-0 bg-white z-50 hidden">
        <div class="h-full w-full overflow-hidden">
            <div class="bg-white h-full w-full overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-primary-green text-white p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <span class="text-2xl font-bold text-white" id="profileInitials">JS</span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold" id="profileName">João Silva Santos</h2>
                                <p class="text-green-100" id="profileRole">Professor</p>
                            </div>
                        </div>
                        <button onclick="closeUserProfile()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto h-[calc(100vh-120px)]">
                    <!-- User Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Nome Completo</label>
                                <p class="text-gray-900 font-medium" id="profileFullName">João Silva Santos</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">CPF</label>
                                <p class="text-gray-900 font-medium" id="profileCPF">123.456.789-00</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Email</label>
                                <p class="text-gray-900 font-medium" id="profileEmail">joao.silva@escola.gov.br</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Telefone</label>
                                <p class="text-gray-900 font-medium" id="profilePhone">(85) 99999-9999</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- School Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4" id="schoolsTitle">Escola Atual</h3>
                        <div id="schoolsContainer">
                            <!-- Schools will be dynamically loaded here -->
                        </div>
                    </div>
                    
                    <!-- User Type Specific Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Gerais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Carga Horária Total</label>
                                <p class="text-gray-900 font-medium" id="profileWorkload">40h semanais</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Data de Admissão</label>
                                <p class="text-gray-900 font-medium" id="profileAdmission">15/03/2020</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" id="profileStatus">
                                    Ativo
                                </span>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Total de Escolas</label>
                                <p class="text-gray-900 font-medium" id="totalSchools">1 escola</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configurações de Acessibilidade -->
                    <div class="mb-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Configurações de Acessibilidade</h3>
                                <p class="text-sm text-gray-600">Personalize sua experiência para melhor usabilidade</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Tema -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-300">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Tema Visual</h4>
                                        <p class="text-xs text-gray-600">Escolha entre tema claro ou escuro</p>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="setTheme('light')" id="theme-light" class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200 group">
                                        <div class="flex items-center justify-center space-x-2">
                                            <svg class="w-4 h-4 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                            <span class="font-medium">Claro</span>
                                        </div>
                                    </button>
                                    <button onclick="setTheme('dark')" id="theme-dark" class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200 group">
                                        <div class="flex items-center justify-center space-x-2">
                                            <svg class="w-4 h-4 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                            </svg>
                                            <span class="font-medium">Escuro</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Contraste -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-300">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Contraste</h4>
                                        <p class="text-xs text-gray-600">Ajuste o contraste das cores</p>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="setContrast('normal')" id="contrast-normal" class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200">
                                        <span class="font-medium">Normal</span>
                                    </button>
                                    <button onclick="setContrast('high')" id="contrast-high" class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200">
                                        <span class="font-medium">Alto</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Tamanho da Fonte -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-300">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Tamanho da Fonte</h4>
                                        <p class="text-xs text-gray-600">Ajuste o tamanho do texto</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <button onclick="setFontSize('normal')" id="font-normal" class="px-3 py-2 border-2 border-gray-300 rounded-lg hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200">
                                        <span class="text-sm font-medium">A</span>
                                    </button>
                                    <button onclick="setFontSize('large')" id="font-large" class="px-3 py-2 border-2 border-gray-300 rounded-lg hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200">
                                        <span class="text-base font-medium">A</span>
                                    </button>
                                    <button onclick="setFontSize('larger')" id="font-larger" class="px-3 py-2 border-2 border-gray-300 rounded-lg hover:border-primary-green hover:bg-primary-green hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200">
                                        <span class="text-lg font-medium">A</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Configurações Avançadas -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-300">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Configurações Avançadas</h4>
                                        <p class="text-xs text-gray-600">Opções adicionais de acessibilidade</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <!-- Redução de Movimento -->
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Redução de Movimento</p>
                                                <p class="text-xs text-gray-600">Reduzir animações e transições</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="reduce-motion" onchange="setReduceMotion(this.checked)" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-green peer-focus:ring-opacity-20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-green"></div>
                                        </label>
                                    </div>
                                    
                                    <!-- Navegação por Teclado -->
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-6 h-6 bg-green-100 rounded-md flex items-center justify-center">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Navegação por Teclado</p>
                                                <p class="text-xs text-gray-600">Destacar elementos focáveis</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="keyboard-nav" onchange="setKeyboardNavigation(this.checked)" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-green peer-focus:ring-opacity-20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-green"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button class="flex-1 px-4 py-2 text-primary-green border border-primary-green hover:bg-primary-green hover:text-white rounded-lg font-medium transition-colors duration-200">
                            Editar Perfil
                        </button>
                        <button onclick="confirmLogout()" class="flex-1 px-4 py-2 text-red-600 border border-red-600 hover:bg-red-600 hover:text-white rounded-lg font-medium transition-colors duration-200">
                            Sair do Sistema
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Adicionar Produto</h3>
                <button onclick="closeAddProductModal()" class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="addProductForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Produto</label>
                    <input type="text" id="productName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: Arroz" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                    <input type="number" id="productQuantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: 50" min="1" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unidade</label>
                    <select id="productUnit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" required>
                        <option value="">Selecione a unidade</option>
                        <option value="kg">Quilograma (kg)</option>
                        <option value="g">Grama (g)</option>
                        <option value="l">Litro (l)</option>
                        <option value="ml">Mililitro (ml)</option>
                        <option value="un">Unidade</option>
                        <option value="cx">Caixa</option>
                        <option value="pct">Pacote</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Validade</label>
                    <input type="date" id="productExpiry" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                    <select id="productCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" required>
                        <option value="">Selecione a categoria</option>
                        <option value="cereais">Cereais</option>
                        <option value="legumes">Legumes</option>
                        <option value="frutas">Frutas</option>
                        <option value="proteinas">Proteínas</option>
                        <option value="laticinios">Laticínios</option>
                        <option value="temperos">Temperos</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeAddProductModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 text-white bg-primary-green hover:bg-green-700 rounded-lg font-medium transition-colors duration-200">
                        Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // User types and permissions
        const USER_TYPES = {
            ADM_SME: 'adm_sme',
            GESTOR: 'gestor',
            PROFESSOR: 'professor',
            NUTRICIONISTA: 'nutricionista',
            ADM_MERENDA: 'adm_merenda',
            ALUNO: 'aluno'
        };

        const PERMISSIONS = {
            [USER_TYPES.ADM_SME]: {
                dashboard: true,
                alunos: true,
                turmas: true,
                frequencia: true,
                notas: true,
                relatorios: true,
                merenda: true,
                escolas: true,
                usuarios: true,
                estoque_central: true
            },
            [USER_TYPES.GESTOR]: {
                dashboard: true,
                alunos: true,
                turmas: true,
                frequencia: true,
                notas: true,
                relatorios: true,
                merenda: false,
                escolas: false,
                usuarios: false,
                estoque_central: false
            },
            [USER_TYPES.PROFESSOR]: {
                dashboard: true,
                alunos: true,
                turmas: true,
                frequencia: true,
                notas: true,
                relatorios: false,
                merenda: false,
                escolas: false,
                usuarios: false,
                estoque_central: false
            },
            [USER_TYPES.NUTRICIONISTA]: {
                dashboard: true,
                alunos: false,
                turmas: false,
                frequencia: false,
                notas: false,
                relatorios: true,
                merenda: true,
                escolas: false,
                usuarios: false,
                estoque_central: false
            },
            [USER_TYPES.ADM_MERENDA]: {
                dashboard: true,
                alunos: false,
                turmas: false,
                frequencia: false,
                notas: false,
                relatorios: true,
                merenda: true,
                escolas: false,
                usuarios: false,
                estoque_central: true
            },
            [USER_TYPES.ALUNO]: {
                dashboard: true,
                alunos: false,
                turmas: false,
                frequencia: false,
                notas: false,
                relatorios: false,
                merenda: false,
                escolas: false,
                usuarios: false,
                estoque_central: false
            }
        };

        // Load user data and setup permissions
        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (user.nome) {
                document.getElementById('userName').textContent = user.nome;
                // Limit initials to first 2 names only
                const initials = user.nome.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
                document.getElementById('userInitials').textContent = initials;
                
                // Setup permissions based on user type
                setupUserPermissions(user.tipo || USER_TYPES.PROFESSOR);
                
                // Set dynamic page title based on user type
                setDynamicPageTitle(user.tipo || USER_TYPES.PROFESSOR);
            }
            
            // Load accessibility settings
            loadAccessibilitySettings();
        });
        
        function setDynamicPageTitle(userType) {
            const pageTitle = document.getElementById('pageTitle');
            const roleNames = {
                [USER_TYPES.ADM_SME]: 'Dashboard ADM SME',
                [USER_TYPES.GESTOR]: 'Dashboard Gestor',
                [USER_TYPES.PROFESSOR]: 'Dashboard Professor',
                [USER_TYPES.NUTRICIONISTA]: 'Dashboard Nutricionista',
                [USER_TYPES.ADM_MERENDA]: 'Dashboard ADM Merenda',
                [USER_TYPES.ALUNO]: 'Dashboard Aluno'
            };
            
            if (pageTitle) {
                pageTitle.textContent = roleNames[userType] || 'Dashboard Professor';
            }
        }

         function setupUserPermissions(userType) {
             const permissions = PERMISSIONS[userType] || PERMISSIONS[USER_TYPES.PROFESSOR];
             
             // Hide/show menu items based on permissions
             const menuItems = {
                 'alunos': document.getElementById('alunos-menu'),
                 'turmas': document.getElementById('turmas-menu'),
                 'frequencia': document.getElementById('frequencia-menu'),
                 'notas': document.getElementById('notas-menu'),
                 'relatorios': document.getElementById('relatorios-menu'),
                 'merenda': document.getElementById('merenda-menu'),
                 'escolas': document.getElementById('escolas-menu'),
                 'usuarios': document.getElementById('usuarios-menu'),
                 'estoque-central': document.getElementById('estoque-central-menu')
             };
        }
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('hidden');
        }
        
        // Close sidebar when clicking overlay
        document.getElementById('mobileOverlay').addEventListener('click', function() {
            toggleSidebar();
        });
        
        
        // Modal functions
        function confirmLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }
        
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }
        
        function openUserProfile() {
            // Load user data into profile modal
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (user.nome) {
                document.getElementById('profileName').textContent = user.nome;
                document.getElementById('profileFullName').textContent = user.nome;
                // Limit initials to first 2 names only
                const initials = user.nome.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
                document.getElementById('profileInitials').textContent = initials;
                
                // Update role in profile
                const roleNames = {
                    [USER_TYPES.ADM_SME]: 'ADM SME',
                    [USER_TYPES.GESTOR]: 'Gestor',
                    [USER_TYPES.PROFESSOR]: 'Professor',
                    [USER_TYPES.NUTRICIONISTA]: 'Nutricionista',
                    [USER_TYPES.ADM_MERENDA]: 'ADM Merenda',
                    [USER_TYPES.ALUNO]: 'Aluno'
                };
                
                const profileRole = document.getElementById('profileRole');
                if (profileRole) {
                    profileRole.textContent = roleNames[user.tipo] || 'Professor';
                }
                
                // Update schools section for multi-school users
                updateSchoolsSection(user);
            }
            document.getElementById('userProfileModal').classList.remove('hidden');
        }
        
        function updateSchoolsSection(user) {
            const schoolsContainer = document.getElementById('schoolsContainer');
            const schoolsTitle = document.getElementById('schoolsTitle');
            const totalSchoolsElement = document.getElementById('totalSchools');
            
            if (schoolsContainer && user.escolas && user.escolas.length > 0) {
                // Update title
                if (user.escolas.length === 1) {
                    schoolsTitle.textContent = 'Escola Atual';
                } else {
                    schoolsTitle.textContent = `Escolas (${user.escolas.length})`;
                }
                
                // Update total schools in general info
                if (totalSchoolsElement) {
                    if (user.escolas.length === 1) {
                        totalSchoolsElement.textContent = '1 escola';
                    } else {
                        totalSchoolsElement.textContent = `${user.escolas.length} escolas`;
                    }
                }
                
                // Clear container
                schoolsContainer.innerHTML = '';
                
                // Create school cards
                user.escolas.forEach((escola, index) => {
                    const schoolCard = document.createElement('div');
                    schoolCard.className = 'bg-primary-green bg-opacity-10 p-4 rounded-lg border border-primary-green border-opacity-20 mb-3';
                    
                    schoolCard.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-primary-green bg-opacity-20 rounded-full flex items-center justify-center">
                                    <span class="text-primary-green font-bold text-sm">${index + 1}</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">${escola.nome}</p>
                                <p class="text-sm text-gray-600">${escola.cargo || 'Professor'}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                    `;
                    
                    schoolsContainer.appendChild(schoolCard);
                });
            }
        }
        
        function closeUserProfile() {
            document.getElementById('userProfileModal').classList.add('hidden');
        }
        
        // Logout function
        function logout() {
            localStorage.removeItem('user');
            window.location.href = '../../Models/sessao/sessions.php?sair';
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                document.getElementById('mobileOverlay').classList.add('hidden');
                document.getElementById('sidebar').classList.remove('open');
            }
        });
        
        // Close modals when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });
        
        
        document.getElementById('addProductModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddProductModal();
            }
        });
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
                closeUserProfile();
                closeAddProductModal();
            }
        });
        
        // Inventory Management Functions
        let products = JSON.parse(localStorage.getItem('products') || '[]');
        
        // Sample data for demonstration
        if (products.length === 0) {
            products = [
                {
                    id: 1,
                    name: 'Arroz',
                    quantity: 50,
                    unit: 'kg',
                    expiryDate: '2024-03-15',
                    category: 'cereais',
                    status: 'ok'
                },
                {
                    id: 2,
                    name: 'Feijão',
                    quantity: 25,
                    unit: 'kg',
                    expiryDate: '2024-02-28',
                    category: 'legumes',
                    status: 'expiring'
                },
                {
                    id: 3,
                    name: 'Leite',
                    quantity: 5,
                    unit: 'l',
                    expiryDate: '2024-01-20',
                    category: 'laticinios',
                    status: 'low'
                },
                {
                    id: 4,
                    name: 'Macarrão',
                    quantity: 30,
                    unit: 'kg',
                    expiryDate: '2024-06-10',
                    category: 'cereais',
                    status: 'ok'
                }
            ];
            localStorage.setItem('products', JSON.stringify(products));
        }
        
        function loadProducts() {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';
            
            products.forEach(product => {
                const row = document.createElement('tr');
                const statusBadge = getStatusBadge(product.status);
                const daysUntilExpiry = getDaysUntilExpiry(product.expiryDate);
                
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-600">${product.name.charAt(0)}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${product.name}</div>
                                <div class="text-sm text-gray-500">${product.category}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${product.quantity} ${product.unit}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${formatDate(product.expiryDate)}</div>
                        <div class="text-xs text-gray-500">${daysUntilExpiry} dias</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${statusBadge}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="editProduct(${product.id})" class="text-primary-green hover:text-green-700 mr-3">Editar</button>
                        <button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-700">Excluir</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            updateStats();
        }
        
        function getStatusBadge(status) {
            switch(status) {
                case 'ok':
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>';
                case 'expiring':
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Vencendo</span>';
                case 'low':
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Estoque Baixo</span>';
                default:
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">-</span>';
            }
        }
        
        function getDaysUntilExpiry(expiryDate) {
            const today = new Date();
            const expiry = new Date(expiryDate);
            const diffTime = expiry - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }
        
        function updateStats() {
            const total = products.length;
            const expiring = products.filter(p => getDaysUntilExpiry(p.expiryDate) <= 30 && getDaysUntilExpiry(p.expiryDate) > 0).length;
            const low = products.filter(p => p.quantity <= 10).length;
            
            document.getElementById('totalProducts').textContent = total;
            document.getElementById('expiringProducts').textContent = expiring;
            document.getElementById('lowStockProducts').textContent = low;
        }
        
        function openAddProductModal() {
            document.getElementById('addProductModal').classList.remove('hidden');
        }
        
        function closeAddProductModal() {
            document.getElementById('addProductModal').classList.add('hidden');
            document.getElementById('addProductForm').reset();
        }
        
        function editProduct(id) {
            const product = products.find(p => p.id === id);
            if (product) {
                document.getElementById('productName').value = product.name;
                document.getElementById('productQuantity').value = product.quantity;
                document.getElementById('productUnit').value = product.unit;
                document.getElementById('productExpiry').value = product.expiryDate;
                document.getElementById('productCategory').value = product.category;
                openAddProductModal();
            }
        }
        
        function deleteProduct(id) {
            if (confirm('Tem certeza que deseja excluir este produto?')) {
                products = products.filter(p => p.id !== id);
                localStorage.setItem('products', JSON.stringify(products));
                loadProducts();
            }
        }
        
        // Handle add product form submission
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('productName').value;
            const quantity = parseInt(document.getElementById('productQuantity').value);
            const unit = document.getElementById('productUnit').value;
            const expiryDate = document.getElementById('productExpiry').value;
            const category = document.getElementById('productCategory').value;
            
            // Determine status based on quantity and expiry
            let status = 'ok';
            const daysUntilExpiry = getDaysUntilExpiry(expiryDate);
            
            if (quantity <= 10) {
                status = 'low';
            } else if (daysUntilExpiry <= 30 && daysUntilExpiry > 0) {
                status = 'expiring';
            }
            
            const newProduct = {
                id: Date.now(),
                name,
                quantity,
                unit,
                expiryDate,
                category,
                status
            };
            
            products.push(newProduct);
            localStorage.setItem('products', JSON.stringify(products));
            loadProducts();
            closeAddProductModal();
        });
        
        // Load products when merenda section is shown
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.remove('hidden');
            
            // Update active menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                item.querySelector('svg').classList.remove('text-primary-green');
                item.querySelector('svg').classList.add('text-gray-500');
            });
            
            // Set active state
            const activeButton = event.target.closest('.menu-item');
            activeButton.classList.add('active');
            activeButton.querySelector('svg').classList.remove('text-gray-500');
            activeButton.querySelector('svg').classList.add('text-primary-green');
            
            // Update page title
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            const userType = user.tipo || USER_TYPES.PROFESSOR;
            
            if (sectionId === 'dashboard') {
                // For dashboard, show dynamic title based on user type
                setDynamicPageTitle(userType);
            } else {
                // For other sections, show section name
                const titles = {
                    'alunos': 'Alunos',
                    'turmas': 'Turmas',
                    'frequencia': 'Frequência',
                    'notas': 'Notas',
                    'relatorios': 'Relatórios',
                    'merenda': 'Merenda',
                    'escolas': 'Escolas',
                    'usuarios': 'Usuários',
                    'estoque-central': 'Estoque Central'
                };
                document.getElementById('pageTitle').textContent = titles[sectionId] || 'Dashboard';
            }
            
            // Load products if merenda section is shown
            if (sectionId === 'merenda') {
                loadProducts();
            }
            
            // Close mobile sidebar
            if (window.innerWidth < 1024) {
                toggleSidebar();
            }
        }
        
        // Accessibility Functions
        function loadAccessibilitySettings() {
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            
            // Apply theme
            if (settings.theme) {
                setTheme(settings.theme);
            }
            
            // Apply contrast
            if (settings.contrast) {
                setContrast(settings.contrast);
            }
            
            // Apply font size
            if (settings.fontSize) {
                setFontSize(settings.fontSize);
            }
            
            // Apply reduce motion
            if (settings.reduceMotion) {
                document.getElementById('reduce-motion').checked = true;
                setReduceMotion(true);
            }
            
            // Apply keyboard navigation
            if (settings.keyboardNavigation) {
                document.getElementById('keyboard-nav').checked = true;
                setKeyboardNavigation(true);
            }
        }
        
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            
            // Update button states
            document.querySelectorAll('[id^="theme-"]').forEach(btn => {
                btn.classList.remove('bg-primary-green', 'text-white');
                btn.classList.add('border-gray-300');
            });
            
            const activeBtn = document.getElementById(`theme-${theme}`);
            if (activeBtn) {
                activeBtn.classList.add('bg-primary-green', 'text-white');
                activeBtn.classList.remove('border-gray-300');
            }
            
            // Save setting
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.theme = theme;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }
        
        function setContrast(contrast) {
            document.documentElement.setAttribute('data-contrast', contrast);
            
            // Update button states
            document.querySelectorAll('[id^="contrast-"]').forEach(btn => {
                btn.classList.remove('bg-primary-green', 'text-white');
                btn.classList.add('border-gray-300');
            });
            
            const activeBtn = document.getElementById(`contrast-${contrast}`);
            if (activeBtn) {
                activeBtn.classList.add('bg-primary-green', 'text-white');
                activeBtn.classList.remove('border-gray-300');
            }
            
            // Save setting
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.contrast = contrast;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }
        
        function setFontSize(size) {
            document.documentElement.setAttribute('data-font-size', size);
            
            // Update button states
            document.querySelectorAll('[id^="font-"]').forEach(btn => {
                btn.classList.remove('bg-primary-green', 'text-white');
                btn.classList.add('border-gray-300');
            });
            
            const activeBtn = document.getElementById(`font-${size}`);
            if (activeBtn) {
                activeBtn.classList.add('bg-primary-green', 'text-white');
                activeBtn.classList.remove('border-gray-300');
            }
            
            // Save setting
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.fontSize = size;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }
        
        function setReduceMotion(enabled) {
            if (enabled) {
                document.documentElement.setAttribute('data-reduce-motion', 'true');
            } else {
                document.documentElement.removeAttribute('data-reduce-motion');
            }
            
            // Save setting
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.reduceMotion = enabled;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }
        
        function setKeyboardNavigation(enabled) {
            if (enabled) {
                document.documentElement.setAttribute('data-keyboard-nav', 'true');
                // Add focus styles to all focusable elements
                document.querySelectorAll('button, a, input, select, textarea, [tabindex]').forEach(el => {
                    el.classList.add('focus-visible');
                });
            } else {
                document.documentElement.removeAttribute('data-keyboard-nav');
                // Remove focus styles
                document.querySelectorAll('.focus-visible').forEach(el => {
                    el.classList.remove('focus-visible');
                });
            }
            
            // Save setting
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.keyboardNavigation = enabled;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }
        
        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            // Alt + 1 to 9 for quick navigation
            if (e.altKey && e.key >= '1' && e.key <= '9') {
                e.preventDefault();
                const menuItems = document.querySelectorAll('.menu-item');
                const index = parseInt(e.key) - 1;
                if (menuItems[index]) {
                    menuItems[index].click();
                }
            }
            
            // Alt + A for accessibility settings
            if (e.altKey && e.key.toLowerCase() === 'a') {
                e.preventDefault();
                openUserProfile();
            }
            
            // Alt + T for theme toggle
            if (e.altKey && e.key.toLowerCase() === 't') {
                e.preventDefault();
                const currentTheme = document.documentElement.getAttribute('data-theme');
                setTheme(currentTheme === 'dark' ? 'light' : 'dark');
            }
        });
    </script>
</body>
</html>
