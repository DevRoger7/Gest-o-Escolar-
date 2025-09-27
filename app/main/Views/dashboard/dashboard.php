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
    <title><?php 
        $userType = $_SESSION['tipo'] ?? 'Usuário';
        $userTypeFormatted = ucfirst(strtolower($userType));
        echo "Dashboard $userTypeFormatted - SIGAE";
    ?></title>

    <!-- Favicon -->
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <link rel="shortcut icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <link rel="apple-touch-icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png">

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

    <!-- Theme Manager -->
    <script src="theme-manager.js"></script>

    <!-- VLibras -->
    <div id="vlibras-widget" vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        // Inicializar VLibras apenas se estiver habilitado
        function initializeVLibras() {
            if (localStorage.getItem('vlibras-enabled') !== 'false') {
                if (window.VLibras) {
                    new window.VLibras.Widget('https://vlibras.gov.br/app');
                }
            }
        }
        
        // Aguardar o carregamento do script
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeVLibras);
        } else {
            initializeVLibras();
        }
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
        input,
        select,
        textarea {
            transition: all 0.2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
        }

        .mobile-menu-overlay {
            backdrop-filter: blur(4px);
        }



        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 999 !important;
            }

            .sidebar-mobile.open {
                transform: translateX(0);
                z-index: 999 !important;
            }
            
        /* Classe para reduzir opacidade do header quando menu está aberto */
        .header-dimmed {
            opacity: 0.3 !important;
            transition: opacity 0.3s ease-in-out;
        }

        /* Profile Modal Tabs */
        .profile-tab {
            padding: 12px 20px;
            font-weight: 600;
            color: #9ca3af;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            border: none;
            cursor: pointer;
            position: relative;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
            font-size: 0.9rem;
        }

        .profile-tab:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .profile-tab.active {
            color: #ffffff;
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
        }

        .profile-tab.active::before {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #22c55e, #16a34a);
            border-radius: 2px 2px 0 0;
        }

        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: -9px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
        }

        /* Navigation bar styling */
        .navigation-bar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
        }

        .profile-tab-content {
            display: block;
            animation: fadeInUp 0.4s ease-out;
        }

        .profile-tab-content.hidden {
            display: none;
        }

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

        /* Modal Animation */
        #userProfileModal.show #modalContent {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        #userProfileModal #modalContent {
            transform: scale(0.95) translateY(20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Fullscreen Modal Styles */
        #userProfileModal {
            background: white;
        }
        
        #userProfileModal #modalContent {
            border-radius: 0;
            box-shadow: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            #userProfileModal {
                padding: 0;
            }
            
            #userProfileModal #modalContent {
                border-radius: 0;
                margin: 0;
            }
            
            .profile-tab {
                padding: 10px 16px;
                min-width: 100px;
                font-size: 0.8rem;
            }
            
            .profile-tab svg {
                width: 4rem;
                height: 4rem;
                margin-right: 0.75rem;
            }
            
            /* Mobile navigation layout */
            .flex.items-center.justify-start {
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0.75rem;
            }
            
            /* Mobile hero card adjustments */
            .bg-gradient-to-r.from-primary-green {
                padding: 1.5rem !important;
            }
            
            /* Mobile content padding */
            .p-8 {
                padding: 1rem;
            }
            
            .bg-gradient-to-r.from-primary-green .flex.items-center {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .bg-gradient-to-r.from-primary-green .w-24.h-24 {
                width: 4rem;
                height: 4rem;
            }
            
            .bg-gradient-to-r.from-primary-green .text-3xl {
                font-size: 1.5rem;
            }
            
            /* Mobile stats grid */
            .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .bg-white.rounded-2xl.p-6 {
                padding: 1rem;
            }
            
            .text-4xl.font-bold {
                font-size: 2rem;
            }
            
            /* Mobile profile cards */
            .grid.grid-cols-1.lg\\:grid-cols-2 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Mobile system performance */
            .grid.grid-cols-1.md\\:grid-cols-3 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Mobile settings */
            .grid.grid-cols-1.md\\:grid-cols-2 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .flex.flex-col.sm\\:flex-row {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            #userProfileModal {
                padding: 0;
            }
            
            #userProfileModal #modalContent {
                border-radius: 0;
            }
            
            .profile-tab {
                padding: 8px 12px;
                min-width: 80px;
                font-size: 0.75rem;
            }
            
            .profile-tab svg {
                width: 3.5rem;
                height: 3.5rem;
                margin-right: 0.5rem;
            }
            
            /* Very small screens navigation */
            .flex.items-center.justify-start {
                padding: 0.5rem;
                gap: 0.25rem;
            }
            
            .bg-gradient-to-r.from-primary-green {
                padding: 1rem !important;
            }
            
            /* Very small screens content padding */
            .p-8 {
                padding: 0.75rem;
            }
            
            .bg-gradient-to-r.from-primary-green .text-3xl {
                font-size: 1.25rem;
            }
            
            .bg-gradient-to-r.from-primary-green .text-2xl {
                font-size: 1rem;
            }
            
            /* Single column stats on very small screens */
            .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 {
                grid-template-columns: 1fr;
            }
            
            .text-4xl.font-bold {
                font-size: 1.75rem;
            }
            
            .w-14.h-14 {
                width: 2.5rem;
                height: 2.5rem;
            }
            
            .w-12.h-12 {
                width: 2rem;
                height: 2rem;
            }
        }
        }

        /* Acessibilidade - Tema Escuro */
        [data-theme="dark"] {
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2a2a2a;
            --bg-quaternary: #3a3a3a;
            --text-primary: #ffffff;
            --text-secondary: #e0e0e0;
            --text-muted: #b0b0b0;
            --text-accent: #d0d0d0;
            --border-color: #404040;
            --border-light: #505050;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.6);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.7);
            --primary-green: #4ade80;
            --primary-green-hover: #22c55e;
            --accent-blue: #60a5fa;
            --accent-purple: #a78bfa;
            --accent-orange: #fbbf24;
            --success: #34d399;
            --warning: #fbbf24;
            --error: #f87171;
            --info: #60a5fa;
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
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #c0c0c0 !important;
        }

        [data-theme="dark"] .text-gray-400 {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] .text-gray-300 {
            color: #d0d0d0 !important;
        }

        [data-theme="dark"] .text-gray-200 {
            color: #e8e8e8 !important;
        }

        [data-theme="dark"] .text-gray-100 {
            color: #f0f0f0 !important;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .border-gray-300 {
            border-color: var(--border-light) !important;
        }

        [data-theme="dark"] .border-gray-400 {
            border-color: var(--border-light) !important;
        }

        [data-theme="dark"] .bg-gray-50 {
            background: #2a2a2a !important;
            border: 1px solid #555555 !important;
        }

        [data-theme="dark"] .bg-gray-100 {
            background-color: #333333 !important;
        }

        [data-theme="dark"] .bg-gray-200 {
            background-color: #3a3a3a !important;
        }

        [data-theme="dark"] .bg-gray-300 {
            background-color: #404040 !important;
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
            background-color: #2d2d2d !important;
            border-color: #555555 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] input:focus,
        [data-theme="dark"] select:focus,
        [data-theme="dark"] textarea:focus {
            border-color: var(--primary-green) !important;
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3) !important;
            background-color: #333333 !important;
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

        /* VLibras - Estilos para controle */
        #vlibras-widget.disabled {
            display: none !important;
        }
        
        #vlibras-widget.enabled {
            display: block !important;
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

        /* Estilos específicos para o modal de professores no tema escuro */
        [data-theme="dark"] #addTeachersModal .bg-white {
            background-color: var(--bg-secondary) !important;
        }

        [data-theme="dark"] #addTeachersModal .text-gray-900 {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] #addTeachersModal .text-gray-600 {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] #addTeachersModal .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] #addTeachersModal .hover\:bg-gray-50:hover {
            background-color: var(--bg-tertiary) !important;
        }

        [data-theme="dark"] #addTeachersModal input,
        [data-theme="dark"] #addTeachersModal select {
            background-color: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] #addTeachersModal input::placeholder {
            color: var(--text-muted) !important;
        }

        /* Estilos específicos para o modal de perfil no tema escuro */
        [data-theme="dark"] #userProfileModal .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-800 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-700 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-600 {
            color: #c0c0c0 !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-500 {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] #userProfileModal .bg-white {
            background-color: var(--bg-secondary) !important;
        }

        [data-theme="dark"] #userProfileModal .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] #userProfileModal .bg-gray-50 {
            background-color: var(--bg-tertiary) !important;
        }

        /* Estilos específicos para o modal de logout no tema escuro */
        [data-theme="dark"] #logoutModal .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #logoutModal .text-gray-600 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] #logoutModal .bg-white {
            background-color: var(--bg-secondary) !important;
        }

        /* Hover effects melhorados para tema escuro */
        [data-theme="dark"] .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== MELHORIAS DE RESPONSIVIDADE ===== */
        
        /* Mobile First - Breakpoints */
        @media (max-width: 640px) {
            
            /* Header mobile - FORÇA VISIBILIDADE */
            header {
                padding: 0.5rem 1rem !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 50 !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                background: white !important;
                border-bottom: 1px solid #e5e7eb !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            }
            
            .header-content {
                display: flex !important;
                visibility: visible !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: 3rem !important;
            }
            
            /* Botão menu MOBILE - SEM FUNDO */
            .mobile-menu-btn {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
                z-index: 999 !important;
                background: transparent !important;
                border: none !important;
                width: 32px !important;
                height: 32px !important;
                color: #4b5563 !important;
                padding: 0.25rem !important;
            }
            
            .mobile-menu-btn svg {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }
            
            /* Logo no mobile */
            header img {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
            
            /* Cards responsivos */
            .card-hover {
                margin-bottom: 1rem;
            }
            
            /* Tabelas responsivas */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table-responsive table {
                min-width: 600px;
            }
            
            /* Modais mobile */
            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
            }
            
            /* Formulários mobile */
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Botões mobile */
            .btn-mobile {
                width: 100%;
                padding: 0.75rem;
                font-size: 1rem;
            }
        }
        
        @media (min-width: 641px) and (max-width: 1024px) {
            /* Tablet */
            #sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1025px) {
            /* Desktop */
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* ===== COMPONENTES RESPONSIVOS ===== */
        
        /* Grid responsivo para cards */
        .card-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 640px) {
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            /* Desktop - esconder botão menu mobile */
            .mobile-menu-btn {
                display: none !important;
            }
        }
        
        /* Tabelas responsivas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .table-responsive table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-responsive th,
        .table-responsive td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-responsive th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        /* Formulários responsivos */
        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 640px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Botões responsivos */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .btn-group {
                flex-direction: row;
            }
        }
        
        /* ===== MELHORIAS DE UX ===== */
        
        /* Loading states */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Feedback visual */
        .success-feedback {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .error-feedback {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        /* Estados de foco melhorados */
        .focus-visible {
            outline: 2px solid #2D5A27;
            outline-offset: 2px;
        }
        
        /* Microinterações */
        .micro-interaction {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .micro-interaction:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .micro-interaction:active {
            transform: translateY(0);
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

        /* Scrollbar hide para navegação de meses */
        .scrollbar-hide {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;  /* Chrome, Safari and Opera */
        }
        
        /* Scroll horizontal para tabs */
        .tabs-container {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 transparent;
        }
        
        .tabs-container::-webkit-scrollbar {
            height: 4px;
        }
        
        .tabs-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .tabs-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 2px;
        }
        
        .tabs-container::-webkit-scrollbar-thumb:hover {
            background-color: #a0aec0;
        }
        
        /* Responsividade para mobile */
        @media (max-width: 640px) {
            .tabs-container {
                padding-bottom: 8px;
            }
            
            .school-tab {
                min-width: 80px;
                flex-shrink: 0;
            }
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
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">SIGAE</h1>
                    <p class="text-xs text-gray-500">Maranguape</p>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center">
                    <span class="text-2 font-bold text-white" id="profileInitials"><?php
                                                                                        // Pega as 2 primeiras letras do nome da sessão
                                                                                        $nome = $_SESSION['nome'] ?? '';
                                                                                        $iniciais = '';
                                                                                        if (strlen($nome) >= 2) {
                                                                                            $iniciais = strtoupper(substr($nome, 0, 2));
                                                                                        } elseif (strlen($nome) == 1) {
                                                                                            $iniciais = strtoupper($nome);
                                                                                        } else {
                                                                                            $iniciais = 'US'; // Fallback para "User"
                                                                                        }
                                                                                        echo $iniciais;
                                                                                        ?></span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800" id="userName"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                    <p class="text-xs text-gray-500"><?= $_SESSION['tipo'] ?? 'Funcionário' ?></p>
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
                <?php if (isset($_SESSION['cadastrar_pessoas']) || isset($_SESSION['matricular_alunos']) || isset($_SESSION['acessar_registros']) || $_SESSION['tipo'] === 'ADM') { ?>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'GESTAO') { ?>
                <li id="gestao-menu">
                    <a href="#" onclick="showSection('gestao')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Gestão Escolar</span>
                    </a>
                </li>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'ADM_MERENDA') { ?>
                <li id="merenda-menu">
                    <a href="#" onclick="showSection('merenda')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Merenda</span>
                    </a>
                </li>
                <?php } ?>
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
                <?php if (isset($_SESSION['relatorio_geral']) || isset($_SESSION['gerar_relatorios_pedagogicos']) || $_SESSION['tipo'] === 'ADM') { ?>
                <li id="relatorios-menu">
                    <a href="#" onclick="showSection('relatorios')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Relatórios</span>
                    </a>
                </li>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                <li id="escolas-menu">
                    <a href="gestao_escolas.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>Escolas</span>
                    </a>
                </li>
                <li id="usuarios-menu">
                    <a href="gestao_usuarios.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Usuários</span>
                    </a>
                </li>
                <li id="estoque-central-menu">
                    <a href="#" onclick="showSection('estoque-central')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Estoque Central</span>
                    </a>
                </li>
                <?php } ?>
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
                <div class="flex justify-between items-center h-16 header-content">
                    <!-- Mobile Menu Button - SEMPRE VISÍVEL NO MOBILE -->
                    <button onclick="toggleSidebar()" class="mobile-menu-btn p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green" aria-label="Abrir menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Page Title - Centered on mobile -->
                    <div class="flex-1 text-center lg:text-left lg:flex-none">
                        <div class="flex items-center justify-center lg:justify-start">
                            <!-- Logo apenas no mobile -->
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-8 h-8 object-contain lg:hidden">
                            <!-- Título apenas no desktop -->
                            <h1 class="hidden lg:block text-xl font-semibold text-gray-800 ml-2" id="pageTitle"><?php 
                                $userType = $_SESSION['tipo'] ?? 'Usuário';
                                if ($userType === 'ADM') {
                                    echo "Dashboard ADM";
                                } else {
                                    $userTypeFormatted = ucfirst(strtolower($userType));
                                    echo "Dashboard $userTypeFormatted";
                                }
                            ?></h1>
                        </div>
                    </div>

                    <!-- User Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                            <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="text-sm font-semibold">
                                            <?php echo $_SESSION['escola_atual'] ?? 'Escola Municipal'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php } ?>
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
                        <h2 class="text-2xl font-bold mb-2">Bem-vindo(a), <?= $_SESSION['nome'] ?? 'Usuário' ?>!</h2>
                        <p class="text-green-100">Aqui está um resumo das suas atividades escolares</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'GESTAO') { ?>
                <div class="card-grid mb-8">
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
                <?php } ?>

                <!-- Recent Activities -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Atividades Recentes</h3>
                        <button class="text-primary-green hover:text-secondary-green text-sm font-medium">Ver todas</button>
                    </div>
                        <div class="space-y-4">
                        <?php 
                        $userType = $_SESSION['tipo'] ?? '';
                        if (strtolower($userType) === 'aluno') { 
                        ?>
                            <!-- Atividades específicas para alunos -->
                            <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl">
                                <div class="p-2 bg-blue-500 rounded-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">Nova nota lançada</p>
                                    <p class="text-xs text-gray-600">Matemática - Nota: 8.5</p>
                                    <p class="text-xs text-gray-500 mt-1">Há 2 horas</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl">
                                <div class="p-2 bg-green-500 rounded-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">Presença registrada</p>
                                    <p class="text-xs text-gray-600">Aula de Português - Presente</p>
                                    <p class="text-xs text-gray-500 mt-1">Há 4 horas</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4 p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl">
                                <div class="p-2 bg-orange-500 rounded-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">Nova atividade disponível</p>
                                    <p class="text-xs text-gray-600">História - Trabalho sobre Independência</p>
                                    <p class="text-xs text-gray-500 mt-1">Ontem</p>
                                </div>
                            </div>
                        <?php } else { ?>
                            <!-- Atividades para outros tipos de usuário -->
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
                        <?php } ?>
                        </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mb-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Acesso Rápido</h3>
                        <p class="text-sm text-gray-600 mt-1">Acesse rapidamente as principais funcionalidades</p>
                    </div>
                    
                    <div class="space-y-3">
                        <?php 
                        $userType = $_SESSION['tipo'] ?? '';
                        if (strtolower($userType) === 'aluno') { 
                        ?>
                            <!-- Botões específicos para alunos -->
                            <button onclick="showSection('notas')" class="group w-full flex items-center p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-md hover:shadow-lg hover:scale-[1.02]">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <h4 class="font-semibold text-sm">Minhas Notas</h4>
                                        <p class="text-xs opacity-90">Visualize suas notas e conceitos</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80">Média</div>
                                        <div class="text-sm font-bold">8.2</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-2 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            
                            <button onclick="showSection('frequencia')" class="group w-full flex items-center p-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-md hover:shadow-lg hover:scale-[1.02]">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <h4 class="font-semibold text-sm">Minha Frequência</h4>
                                        <p class="text-xs opacity-90">Acompanhe sua presença nas aulas</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80">Frequência</div>
                                        <div class="text-sm font-bold">95%</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-2 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        <?php } else { ?>
                            <!-- Botões para outros tipos de usuário -->
                            <?php if (isset($_SESSION['cadastrar_pessoas']) || isset($_SESSION['matricular_alunos']) || isset($_SESSION['acessar_registros'])) { ?>
                            <button class="group w-full flex items-center p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-md hover:shadow-lg hover:scale-[1.02]">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <h4 class="font-semibold text-sm">Novo Aluno</h4>
                                        <p class="text-xs opacity-90">Cadastre um novo estudante</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80">Total</div>
                                        <div class="text-sm font-bold">245</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-2 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <?php } ?>

                            <?php if (isset($_SESSION['lancar_frequencia']) || isset($_SESSION['acessar_registros'])) { ?>
                            <button class="group w-full flex items-center p-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-md hover:shadow-lg hover:scale-[1.02]">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <h4 class="font-semibold text-sm">Registrar Frequência</h4>
                                        <p class="text-xs opacity-90">Registre a presença dos alunos</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80">Hoje</div>
                                        <div class="text-sm font-bold">12</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-2 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <?php } ?>

                            <?php if (isset($_SESSION['lancar_nota']) || isset($_SESSION['acessar_registros'])) { ?>
                            <button class="group w-full flex items-center p-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all duration-300 shadow-md hover:shadow-lg hover:scale-[1.02]">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <h4 class="font-semibold text-sm">Lançar Notas</h4>
                                        <p class="text-xs opacity-90">Registre as notas dos alunos</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80">Pendentes</div>
                                        <div class="text-sm font-bold">8</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-2 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <?php } ?>

                            <?php if (isset($_SESSION['relatorio_geral']) || isset($_SESSION['gerar_relatorios_pedagogicos'])) { ?>
                            <button class="group w-full flex items-center p-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-md hover:shadow-lg hover:scale-[1.02]">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="p-2 bg-white/20 rounded-lg group-hover:bg-white/30 transition-all duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <h4 class="font-semibold text-sm">Relatórios</h4>
                                        <p class="text-xs opacity-90">Gere relatórios e análises</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80">Disponíveis</div>
                                        <div class="text-sm font-bold">15</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-2 opacity-70 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <?php } ?>
                            <?php } ?>
                        </div>
                </div>
            </section>

            <!-- === INTERFACES DINÂMICAS BASEADAS NO TIPO DE USUÁRIO === -->
            <?php
            // Função para renderizar interfaces baseadas nas permissões do usuário
            function renderUserInterface() {
                $userType = $_SESSION['tipo'] ?? '';
                
                switch(strtolower($userType)) {
                    case 'aluno':
                        renderAlunoInterface();
                        break;
                    case 'professor':
                        renderProfessorInterface();
                        break;
                    case 'nutricionista':
                        renderNutricionistaInterface();
                        break;
                    case 'adm_merenda':
                        renderAdmMerendaInterface();
                        break;
                    case 'gestao':
                        renderGestaoInterface();
                        break;
                    case 'adm':
                        // ADM não renderiza interface específica - usa apenas o dashboard principal
                        return;
                        break;
                    default:
                        renderDefaultInterface();
                        break;
                }
            }

            // === INTERFACE DO ALUNO ===
            function renderAlunoInterface() {
                // Interface do aluno removida - apenas o dashboard principal será exibido
                return;
            }

            // === INTERFACE DO PROFESSOR ===
            function renderProfessorInterface() {
                if (isset($_SESSION['resgistrar_plano_aula']) || isset($_SESSION['cadastrar_avaliacao']) || isset($_SESSION['lancar_frequencia']) || isset($_SESSION['lancar_nota'])) {
                    echo '<section id="user-interface" class="content-section mt-8">';
                    echo '<div class="card-grid">';
                    
                    // Card de Planos de Aula
                    if (isset($_SESSION['resgistrar_plano_aula'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-100 rounded-xl">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">5 esta semana</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Planos de Aula</h3>
                            <p class="text-gray-600 text-sm mb-4">Criar e gerenciar planos de aula</p>
                            <button class="w-full bg-gradient-to-r from-purple-500 to-purple-600 text-white py-2 px-4 rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all duration-200">
                                Gerenciar Planos
                            </button>
                        </div>';
                    }
                    
                    // Card de Avaliações
                    if (isset($_SESSION['cadastrar_avaliacao'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-blue-100 rounded-xl">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">3 pendentes</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Avaliações</h3>
                            <p class="text-gray-600 text-sm mb-4">Criar provas e atividades</p>
                            <button class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2 px-4 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200">
                                Criar Avaliação
                            </button>
                        </div>';
                    }
                    
                    // Card de Frequência
                    if (isset($_SESSION['lancar_frequencia'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-green-100 rounded-xl">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Hoje</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Frequência</h3>
                            <p class="text-gray-600 text-sm mb-4">Registrar presença dos alunos</p>
                            <button class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-2 px-4 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200">
                                Lançar Frequência
                            </button>
                        </div>';
                    }
                    
                    // Card de Notas
                    if (isset($_SESSION['lancar_nota'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-orange-100 rounded-xl">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">12 pendentes</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Notas</h3>
                            <p class="text-gray-600 text-sm mb-4">Lançar notas e conceitos</p>
                            <button class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-2 px-4 rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all duration-200">
                                Lançar Notas
                            </button>
                        </div>';
                    }
                    
                    echo '</div>';
                    echo '</section>';
                }
            }

            // === INTERFACE DO NUTRICIONISTA ===
            function renderNutricionistaInterface() {
                if (isset($_SESSION['adc_cardapio']) || isset($_SESSION['lista_insulmos']) || isset($_SESSION['env_pedidos'])) {
                    echo '<section id="user-interface" class="content-section mt-8">';
                    echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
                    
                    // Card de Cardápios
                    if (isset($_SESSION['adc_cardapio'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-green-100 rounded-xl">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Semanal</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Cardápios</h3>
                            <p class="text-gray-600 text-sm mb-4">Criar e gerenciar cardápios escolares</p>
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Esta semana</span>
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Aprovado</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Próxima semana</span>
                                    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Pendente</span>
                                </div>
                            </div>
                            <button class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-2 px-4 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200">
                                Gerenciar Cardápios
                            </button>
                        </div>';
                    }
                    
                    // Card de Insumos
                    if (isset($_SESSION['lista_insulmos'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-blue-100 rounded-xl">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">156 itens</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Insumos</h3>
                            <p class="text-gray-600 text-sm mb-4">Consultar insumos disponíveis</p>
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Arroz</span>
                                    <span class="text-sm font-semibold text-green-600">50kg</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Feijão</span>
                                    <span class="text-sm font-semibold text-orange-600">15kg</span>
                                </div>
                            </div>
                            <button class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2 px-4 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200">
                                Ver Estoque
                            </button>
                        </div>';
                    }
                    
                    // Card de Pedidos
                    if (isset($_SESSION['env_pedidos'])) {
                        echo '
                        <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-orange-100 rounded-xl">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">3 pendentes</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Pedidos</h3>
                            <p class="text-gray-600 text-sm mb-4">Solicitar produtos e ingredientes</p>
                            <div class="space-y-2 mb-4">
                                <div class="p-2 bg-gray-50 rounded-lg">
                                    <p class="text-xs text-gray-600">Pedido #001</p>
                                    <p class="text-sm font-medium">Verduras e legumes</p>
                                </div>
                                <div class="p-2 bg-gray-50 rounded-lg">
                                    <p class="text-xs text-gray-600">Pedido #002</p>
                                    <p class="text-sm font-medium">Proteínas</p>
                                </div>
                            </div>
                            <button class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-2 px-4 rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all duration-200">
                                Fazer Pedido
                            </button>
                        </div>';
                    }
                    
                    echo '</div>';
                    echo '</section>';
                }
            }

            // === INTERFACE DO ADMINISTRADOR DE MERENDA ===
            function renderAdmMerendaInterface() {
                echo '<section id="merenda" class="content-section mt-8 hidden">';
                echo '<div class="mb-6">';
                echo '<h2 class="text-2xl font-bold text-gray-900 mb-2">Administração da Merenda</h2>';
                echo '<p class="text-gray-600">Gestão completa da alimentação escolar: cardápios, estoque, consumo, fornecedores e relatórios</p>';
                echo '</div>';
                echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">';
                
                // Card de Cardápios
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Cardápios</h3>
                                <p class="text-sm text-gray-600">Cadastrar e editar</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            15 ativos
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Esta semana</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">5 cardápios</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Pendentes</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">2 revisões</span>
                        </div>
                    </div>
                    
                    <button onclick="openCardapios()" class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                        Gerenciar Cardápios
                    </button>
                </div>';

                // Card de Estoque
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Estoque</h3>
                                <p class="text-sm text-gray-600">Entradas e saídas</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            245 itens
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Entradas hoje</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">12 produtos</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Baixo estoque</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">8 itens</span>
                        </div>
                    </div>
                    
                    <button onclick="openEstoque()" class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                        Gerenciar Estoque
                    </button>
                </div>';

                // Card de Consumo Diário
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Consumo</h3>
                                <p class="text-sm text-gray-600">Por turma e turno</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            485 alunos
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Hoje</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">485 refeições</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Média diária</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">472 refeições</span>
                        </div>
                    </div>
                    
                    <button onclick="openConsumo()" class="w-full bg-green-600 text-white py-2.5 px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                        Registrar Consumo
                    </button>
                </div>';

                // Card de Desperdício
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Desperdício</h3>
                                <p class="text-sm text-gray-600">Relatórios de uso</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            3.2%
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Hoje</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">15.5kg</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Esta semana</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">89.2kg</span>
                        </div>
                    </div>
                    
                    <button onclick="openDesperdicio()" class="w-full bg-red-600 text-white py-2.5 px-4 rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium">
                        Ver Relatórios
                    </button>
                </div>';

                // Card de Fornecedores
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Fornecedores</h3>
                                <p class="text-sm text-gray-600">Pedidos e entregas</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            12 ativos
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Pedidos pendentes</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">5 pedidos</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Entregas hoje</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">2 entregas</span>
                        </div>
                    </div>
                    
                    <button onclick="openFornecedores()" class="w-full bg-purple-600 text-white py-2.5 px-4 rounded-lg hover:bg-purple-700 transition-colors duration-200 font-medium">
                        Gerenciar Fornecedores
                    </button>
                </div>';

                // Card de Distribuição
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Distribuição</h3>
                                <p class="text-sm text-gray-600">Por turma e turno</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                            Aprovada
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Matutino</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">245 alunos</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Vespertino</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">240 alunos</span>
                        </div>
                    </div>
                    
                    <button onclick="openDistribuicao()" class="w-full bg-teal-600 text-white py-2.5 px-4 rounded-lg hover:bg-teal-700 transition-colors duration-200 font-medium">
                        Ajustar Distribuição
                    </button>
                </div>';

                // Card de Custos
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Custos</h3>
                                <p class="text-sm text-gray-600">Monitoramento</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            R$ 2.450
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Este mês</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">R$ 2.450</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Por aluno</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">R$ 5,05</span>
                        </div>
                    </div>
                    
                    <button onclick="openCustos()" class="w-full bg-yellow-600 text-white py-2.5 px-4 rounded-lg hover:bg-yellow-700 transition-colors duration-200 font-medium">
                        Ver Custos
                    </button>
                </div>';

                // Card de Relatórios
                echo '
                <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Relatórios</h3>
                                <p class="text-sm text-gray-600">Gerais para gestão</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Ativo
                        </span>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Mensais</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">12 relatórios</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">Trimestrais</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">4 relatórios</span>
                        </div>
                    </div>
                    
                    <button onclick="openRelatorios()" class="w-full bg-gray-600 text-white py-2.5 px-4 rounded-lg hover:bg-gray-700 transition-colors duration-200 font-medium">
                        Gerar Relatórios
                    </button>
                </div>';
                
                echo '</div>';
                echo '</section>';
            }

            // === INTERFACE DE GESTÃO ===
            function renderGestaoInterface() {
                echo '<section id="gestao" class="content-section mt-8 hidden">';
                echo '<div class="mb-6">';
                echo '<h2 class="text-2xl font-bold text-gray-900 mb-2">Gestão Escolar</h2>';
                echo '<p class="text-gray-600">Rotina diária, acompanhamento de turmas, horários, presença, notas e comunicação</p>';
                echo '</div>';
                echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">';
                    
                    // Card de Turmas
                    if (isset($_SESSION['criar_turma'])) {
                        echo '
                        <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Turmas</h3>
                                        <p class="text-sm text-gray-600">Gerenciar turmas escolares</p>
                            </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    12 ativas
                                </span>
                                </div>
                            
                            <div class="space-y-3 mb-4">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-sm text-gray-700">1º Ano A</span>
                            </div>
                                    <span class="text-sm font-semibold text-gray-900">25 alunos</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span class="text-sm text-gray-700">2º Ano B</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">28 alunos</span>
                                </div>
                            </div>
                            
                            <button class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                                Gerenciar Turmas
                            </button>
                        </div>';
                    }
                    
                    // Card de Matrículas e Cadastros
                    if (isset($_SESSION['matricular_alunos'])) {
                        echo '
                        <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Matrículas</h3>
                                        <p class="text-sm text-gray-600">Cadastros e autorizações</p>
                            </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    15 pendentes
                                </span>
                                </div>
                            
                            <div class="space-y-3 mb-4">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span class="text-sm text-gray-700">Novos cadastros</span>
                            </div>
                                    <span class="text-sm font-semibold text-gray-900">5 hoje</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                        <span class="text-sm text-gray-700">Aguardando</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">15 aprovações</span>
                                </div>
                            </div>
                            
                            <button class="w-full bg-green-600 text-white py-2.5 px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                                Gerenciar Cadastros
                            </button>
                        </div>';
                    }
                    
                    
                    // Card de Horários e Calendário
                        echo '
                    <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Horários</h3>
                                    <p class="text-sm text-gray-600">Calendário e horários</p>
                            </div>
                                </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                Ativo
                            </span>
                                </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Aulas hoje</span>
                            </div>
                                <span class="text-sm font-semibold text-gray-900">24 aulas</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Próximos eventos</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">3 eventos</span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                            Gerenciar Horários
                            </button>
                        </div>';
                    
                    // Card de Presença e Frequência
                    echo '
                    <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Presença</h3>
                                    <p class="text-sm text-gray-600">Controle de frequência</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                94.2%
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Presentes hoje</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">485 alunos</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Faltosos</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">29 alunos</span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-orange-600 text-white py-2.5 px-4 rounded-lg hover:bg-orange-700 transition-colors duration-200 font-medium">
                            Registrar Presença
                        </button>
                    </div>';
                    
                    // Card de Notas e Boletins
                    echo '
                    <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Notas</h3>
                                    <p class="text-sm text-gray-600">Registros e consultas</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Ativo
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Registros hoje</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">47</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Boletins</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">485</span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-yellow-600 text-white py-2.5 px-4 rounded-lg hover:bg-yellow-700 transition-colors duration-200 font-medium">
                            Gerenciar Notas
                        </button>
                    </div>';
                    
                    // Card de Professores e Funcionários
                    echo '
                    <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Equipe</h3>
                                    <p class="text-sm text-gray-600">Professores e funcionários</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                28 ativos
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Professores</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">18</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Funcionários</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">10</span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-teal-600 text-white py-2.5 px-4 rounded-lg hover:bg-teal-700 transition-colors duration-200 font-medium">
                            Gerenciar Equipe
                        </button>
                    </div>';
                    
                    // Card de Comunicação
                    echo '
                    <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-pink-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Comunicação</h3>
                                    <p class="text-sm text-gray-600">Pais e responsáveis</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                5 pendentes
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Mensagens hoje</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">12</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Enviadas</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">8</span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-pink-600 text-white py-2.5 px-4 rounded-lg hover:bg-pink-700 transition-colors duration-200 font-medium">
                            Nova Mensagem
                        </button>
                    </div>';
                    
                    // Card de Validação de Informações
                    echo '
                    <div class="group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Validação</h3>
                                    <p class="text-sm text-gray-600">Aprovar informações</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                3 pendentes
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Aguardando</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">3</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-gray-700">Aprovadas hoje</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">7</span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-red-600 text-white py-2.5 px-4 rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium">
                            Validar Informações
                        </button>
                    </div>';
                    
                    echo '</div>';
                    echo '</section>';
            }

            // === INTERFACE DO ADMINISTRADOR ===
            function renderAdministradorInterface() {
                echo '<section id="user-interface" class="content-section mt-8">';
                echo '<div class="mb-6">';
                echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">Painel Administrativo</h2>';
                echo '<p class="text-gray-600">Acesso completo a todas as funcionalidades do sistema</p>';
                echo '</div>';
                    echo '<div class="card-grid">';
                
                // Cards principais do administrador
                $adminCards = [
                    ['title' => 'Usuários', 'desc' => 'Gerenciar usuários do sistema', 'icon' => 'users', 'color' => 'blue', 'count' => '156'],
                    ['title' => 'Escolas', 'desc' => 'Administrar dados das escolas', 'icon' => 'building', 'color' => 'green', 'count' => '12'],
                    ['title' => 'Relatórios', 'desc' => 'Relatórios completos do sistema', 'icon' => 'chart', 'color' => 'purple', 'count' => '45'],
                    ['title' => 'Estoque', 'desc' => 'Controle total do estoque', 'icon' => 'box', 'color' => 'orange', 'count' => '2.5k']
                ];
                
                $icons = [
                    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"></path>',
                    'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>',
                    'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>',
                    'box' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>'
                ];
                
                foreach ($adminCards as $card) {
                    echo '
                    <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover-lift">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-'.$card['color'].'-100 rounded-xl">
                                <svg class="w-6 h-6 text-'.$card['color'].'-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    '.$icons[$card['icon']].'
                                </svg>
                            </div>
                            <span class="text-xs bg-'.$card['color'].'-100 text-'.$card['color'].'-800 px-2 py-1 rounded-full">'.$card['count'].'</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">'.$card['title'].'</h3>
                        <p class="text-gray-600 text-sm mb-4">'.$card['desc'].'</p>
                        <button class="w-full bg-gradient-to-r from-'.$card['color'].'-500 to-'.$card['color'].'-600 text-white py-2 px-4 rounded-lg hover:from-'.$card['color'].'-600 hover:to-'.$card['color'].'-700 transition-all duration-200">
                            Gerenciar
                        </button>
                    </div>';
                }
                
                echo '</div>';
                echo '</section>';
            }

            // === INTERFACE PADRÃO ===
            function renderDefaultInterface() {
                echo '<section id="user-interface" class="content-section mt-8">';
                echo '<div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 text-center">';
                echo '<div class="p-4 bg-gray-100 rounded-xl inline-block mb-4">';
                echo '<svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                echo '</svg>';
                echo '</div>';
                echo '<h3 class="text-xl font-bold text-gray-800 mb-2">Interface não configurada</h3>';
                echo '<p class="text-gray-600">Nenhuma interface específica foi configurada para este tipo de usuário.</p>';
                echo '</div>';
                echo '</section>';
            }

            // Renderizar a interface do usuário atual
            renderUserInterface();
            ?>



            <!-- Frequência Section - REMOVIDA -->
            <section id="frequencia" class="content-section hidden" style="display: none;">
                <div class="mx-4 sm:mx-6 lg:mx-8">
                    <!-- Header Moderno -->
                    <div class="flex items-center justify-between mb-6 sm:mb-8">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Frequência</h2>
                                <p class="text-sm text-gray-600">Acompanhe sua presença nas aulas</p>
                            </div>
                        </div>
                    <div class="flex items-center space-x-4">
                        <!-- Seletor de Ano -->
                        <div class="relative">
                            <select id="anoSeletor" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm">
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025" selected>2025</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                    </div>
                                    </div>
                    <button onclick="showSection('dashboard')" class="p-2 hover:bg-gray-100 rounded-xl transition-all duration-200">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                    </button>
                            </div>
                        </div>
                        
                <!-- Navegação por Meses com Scroll -->
                <div class="mb-6 sm:mb-8">
                    <div class="overflow-x-auto scrollbar-hide" style="scrollbar-width: none; -ms-overflow-style: none;">
                        <div class="flex items-center gap-3 px-4" style="width: max-content;">
                            <button onclick="selecionarMes('jan')" id="btn-jan" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-105 border-b-2 border-green-500 flex-shrink-0">
                                JAN
                                </button>
                            <button onclick="selecionarMes('fev')" id="btn-fev" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                FEV
                            </button>
                            <button onclick="selecionarMes('mar')" id="btn-mar" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                MAR
                            </button>
                            <button onclick="selecionarMes('abr')" id="btn-abr" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                ABR
                            </button>
                            <button onclick="selecionarMes('mai')" id="btn-mai" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                MAI
                            </button>
                            <button onclick="selecionarMes('jun')" id="btn-jun" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                JUN
                            </button>
                            <button onclick="selecionarMes('jul')" id="btn-jul" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                JUL
                            </button>
                            <button onclick="selecionarMes('ago')" id="btn-ago" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                AGO
                            </button>
                            <button onclick="selecionarMes('set')" id="btn-set" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                SET
                            </button>
                            <button onclick="selecionarMes('out')" id="btn-out" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                OUT
                            </button>
                            <button onclick="selecionarMes('nov')" id="btn-nov" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                NOV
                            </button>
                            <button onclick="selecionarMes('dez')" id="btn-dez" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                DEZ
                                </button>
                            </div>
                        </div>
                        </div>
                        
                <!-- Tabela de Disciplinas -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                        <div class="table-responsive">
                            <table class="w-full">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200">
                                            Faltas Mensais
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">
                                            Disciplinas
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">APROFUNDAMENTO EM MATEMATICA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">BIOLOGIA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">EDUCAÇÃO FÍSICA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">ESTAGIO CURRICULAR</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">FILOSOFIA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">FÍSICA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">FORMAÇÃO PARA CIDADANIA E DESENV. DE COMP. SOCIOEMOCIONAIS</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">GEOGRAFIA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">HISTÓRIA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">HORARIO DE ESTUDO I</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">HORARIO DE ESTUDO II</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">LINGUA ESTRANGEIRA - ESPANHOL</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">LINGUA ESTRANGEIRA - INGLES</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">LÍNGUA PORTUGUESA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">MATEMÁTICA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">PREPARAÇÃO E AVALIAÇÃO DA PRÁTICA DE ESTÁGIO</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">PROJETOS INTERDISCIPLINARESI</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150 bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">QUÍMICA</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">0</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">SOCIOLOGIA</td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                            </div>
                    </div>
                </div>
            </section>

            <!-- Notas Section -->
            <section id="notas" class="content-section hidden">
                <!-- Container Principal com Margens -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 lg:p-8 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden relative mx-2 sm:mx-4 lg:mx-6">
                    <!-- Header Moderno -->
                    <div class="flex items-center justify-between mb-6 sm:mb-8">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">Minhas Notas</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Acompanhe seu desempenho acadêmico</p>
                            </div>
                        </div>
                        <button onclick="showSection('dashboard')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all duration-200">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Notas por Disciplina - Interface de Páginas -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Notas por Disciplina</h3>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400" id="anoLetivoAtual">Ano Letivo 2025</span>
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            </div>
                        </div>
                        
                        <!-- Navegação entre Bimestres com Scroll -->
                        <div class="mb-6 sm:mb-8">
                            <div class="overflow-x-auto scrollbar-hide" style="scrollbar-width: none; -ms-overflow-style: none;">
                                <div class="flex items-center gap-3 px-2" style="width: max-content;">
                                    <button onclick="showBimestre(1)" id="btn-bim-1" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-105 border-b-2 border-blue-500 flex-shrink-0">
                                        1º Bimestre
                                    </button>
                                    <button onclick="showBimestre(2)" id="btn-bim-2" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                        2º Bimestre
                                    </button>
                                    <button onclick="showBimestre(3)" id="btn-bim-3" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                        3º Bimestre
                                    </button>
                                    <button onclick="showBimestre(4)" id="btn-bim-4" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                        4º Bimestre
                                    </button>
                                    <button onclick="showBimestre(5)" id="btn-bim-5" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                        Recuperação
                                    </button>
                                    <button onclick="showBimestre(6)" id="btn-bim-6" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-600 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0">
                                        Final
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Páginas dos Bimestres -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <!-- 1º Bimestre -->
                            <div id="bimestre-1" class="bimestre-page">
                                <div class="bg-blue-50 dark:bg-gray-700 border-b border-blue-200 dark:border-gray-600 p-3 sm:p-4">
                                    <h4 class="text-base sm:text-lg font-semibold text-blue-800 dark:text-white">1º Bimestre</h4>
                                    <p class="text-xs sm:text-sm text-blue-600 dark:text-gray-300">Notas do primeiro bimestre</p>
                                </div>
                                <div class="p-3 sm:p-6">
                                    <div class="space-y-3">
                                        <!-- Matemática -->
                                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('mat-1')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 dark:text-white text-base truncate">Matemática</h5>
                                                </div>
                                                <div class="flex items-center space-x-2 flex-shrink-0">
                                                    <div class="w-12 h-12 bg-blue-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-blue-800 dark:text-white">8.0</span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" id="arrow-mat-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-mat-1" class="hidden mt-4 pt-4 border-t border-gray-100">
                                                <div class="space-y-4">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                                <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                                <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Português -->
                                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('port-1')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 dark:text-white text-base truncate">Língua Portuguesa</h5>
                                                </div>
                                                <div class="flex items-center space-x-2 flex-shrink-0">
                                                    <div class="w-12 h-12 bg-blue-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-blue-800 dark:text-white">8.5</span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" id="arrow-port-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-port-1" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- História -->
                                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('hist-1')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 dark:text-white text-base truncate">História</h5>
                                                </div>
                                                <div class="flex items-center space-x-2 flex-shrink-0">
                                                    <div class="w-12 h-12 bg-blue-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-blue-800 dark:text-white">7.5</span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" id="arrow-hist-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-hist-1" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ciências -->
                                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('cien-1')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 dark:text-white text-base truncate">Ciências</h5>
                                                </div>
                                                <div class="flex items-center space-x-2 flex-shrink-0">
                                                    <div class="w-12 h-12 bg-blue-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-blue-800 dark:text-white">8.0</span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" id="arrow-cien-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-cien-1" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Geografia -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('geo-1')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-base truncate">Geografia</h5>
                                                </div>
                                                <div class="flex items-center space-x-2 flex-shrink-0">
                                                    <div class="w-12 h-12 bg-blue-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-blue-800 dark:text-white">7.0</span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" id="arrow-geo-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-geo-1" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">6.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Educação Física -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('edf-1')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-base truncate">Educação Física</h5>
                                                </div>
                                                <div class="flex items-center space-x-2 flex-shrink-0">
                                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-lg font-bold text-blue-800">9.0</span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" id="arrow-edf-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-edf-1" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 2º Bimestre -->
                            <div id="bimestre-2" class="bimestre-page hidden">
                                <div class="bg-green-50 border-b border-green-200 p-3 sm:p-4">
                                    <h4 class="text-base sm:text-lg font-semibold text-green-800">2º Bimestre</h4>
                                    <p class="text-xs sm:text-sm text-green-600">Notas do segundo bimestre</p>
                                </div>
                                <div class="p-3 sm:p-6">
                                    <div class="space-y-2 sm:space-y-3">
                                        <!-- Matemática -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('mat-2')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Matemática</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-mat-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-mat-2" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Português -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('port-2')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Língua Portuguesa</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-port-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-port-2" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">10.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- História -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('hist-2')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">História</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-hist-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-hist-2" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ciências -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('cien-2')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Ciências</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-cien-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-cien-2" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Geografia -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('geo-2')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Geografia</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-geo-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-geo-2" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Educação Física -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('edf-2')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Educação Física</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-edf-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-edf-2" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">10.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 3º Bimestre -->
                            <div id="bimestre-3" class="bimestre-page hidden">
                                <div class="bg-orange-50 border-b border-orange-200 p-3 sm:p-4">
                                    <h4 class="text-base sm:text-lg font-semibold text-orange-800">3º Bimestre</h4>
                                    <p class="text-xs sm:text-sm text-orange-600">Notas do terceiro bimestre</p>
                                </div>
                                <div class="p-3 sm:p-6">
                                    <div class="space-y-2 sm:space-y-3">
                                        <!-- Matemática -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('mat-3')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Matemática</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-mat-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-mat-3" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Português -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('port-3')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Língua Portuguesa</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-port-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-port-3" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- História -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('hist-3')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">História</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-hist-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-hist-3" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ciências -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('cien-3')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Ciências</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-cien-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-cien-3" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Geografia -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('geo-3')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Geografia</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">7.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-geo-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-geo-3" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Educação Física -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('edf-3')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Educação Física</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-edf-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-edf-3" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 4º Bimestre -->
                            <div id="bimestre-4" class="bimestre-page hidden">
                                <div class="bg-purple-50 border-b border-purple-200 p-3 sm:p-4">
                                    <h4 class="text-base sm:text-lg font-semibold text-purple-800">4º Bimestre</h4>
                                    <p class="text-xs sm:text-sm text-purple-600">Notas do quarto bimestre</p>
                                </div>
                                <div class="p-3 sm:p-6">
                                    <div class="space-y-2 sm:space-y-3">
                                        <!-- Matemática -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('mat-4')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Matemática</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-mat-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-mat-4" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Português -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('port-4')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Língua Portuguesa</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-port-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-port-4" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- História -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('hist-4')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">História</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">7.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-hist-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-hist-4" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ciências -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('cien-4')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Ciências</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-cien-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-cien-4" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Geografia -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('geo-4')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Geografia</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.0</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-geo-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-geo-4" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">7.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Educação Física -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('edf-4')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Educação Física</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-edf-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-edf-4" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota Parcial -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Parcial</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">9.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Nota Bimestral -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Bimestral</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">10.0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recuperação -->
                            <div id="bimestre-5" class="bimestre-page hidden">
                                <div class="bg-yellow-50 border-b border-yellow-200 p-3 sm:p-4">
                                    <h4 class="text-base sm:text-lg font-semibold text-yellow-800">Recuperação</h4>
                                    <p class="text-xs sm:text-sm text-yellow-600">Notas de recuperação</p>
                                </div>
                                <div class="p-3 sm:p-6">
                                    <div class="space-y-2 sm:space-y-3">
                                        <!-- Matemática -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Matemática</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-gray-400">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Português -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Língua Portuguesa</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-gray-400">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- História -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">História</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-gray-400">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ciências -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Ciências</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-gray-400">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Geografia -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="toggleNotas('geo-5')">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Geografia</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.5</span>
                                                    </div>
                                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform" id="arrow-geo-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Notas Detalhadas (Ocultas por padrão) -->
                                            <div id="notas-geo-5" class="hidden mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100">
                                                <div class="space-y-2 sm:space-y-3">
                                                    <!-- Nota de Recuperação -->
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 sm:p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                                <span class="text-xs sm:text-sm font-medium text-blue-800">Recuperação</span>
                                                            </div>
                                                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <span class="text-xs sm:text-sm font-bold text-blue-700">8.5</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Educação Física -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Educação Física</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-gray-400">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Final -->
                            <div id="bimestre-6" class="bimestre-page hidden">
                                <div class="bg-green-50 border-b border-green-200 p-3 sm:p-4">
                                    <h4 class="text-base sm:text-lg font-semibold text-green-800">Notas Finais</h4>
                                    <p class="text-xs sm:text-sm text-green-600">Médias finais das disciplinas</p>
                                </div>
                                <div class="p-3 sm:p-6">
                                    <div class="space-y-2 sm:space-y-3">
                                        <!-- Matemática -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Matemática</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.5</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Português -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Língua Portuguesa</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- História -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">História</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">7.8</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ciências -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Ciências</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">8.2</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Geografia -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Geografia</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">7.6</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Educação Física -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-gray-400 rounded-full flex-shrink-0"></div>
                                                    <h5 class="font-semibold text-gray-800 text-sm sm:text-base truncate">Educação Física</h5>
                                                </div>
                                                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                        <span class="text-sm sm:text-lg font-bold text-blue-800">9.2</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- JavaScript para Navegação -->
                        <script>
                        function showBimestre(bimestre) {
                            // Esconder todas as páginas
                            const pages = document.querySelectorAll('.bimestre-page');
                            pages.forEach(page => page.classList.add('hidden'));
                            
                            // Mostrar a página selecionada
                            const selectedPage = document.getElementById(`bimestre-${bimestre}`);
                            if (selectedPage) {
                                selectedPage.classList.remove('hidden');
                            }
                            
                            // Atualizar botões - remover classes ativas
                            const buttons = document.querySelectorAll('[id^="btn-bim-"]');
                            buttons.forEach(btn => {
                                // Remover classes ativas
                                btn.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-indigo-600', 'text-white', 'shadow-lg', 'border-b-2', 'border-blue-500');
                                // Adicionar classes inativas
                                btn.classList.add('bg-white', 'text-gray-600', 'hover:bg-gray-50', 'shadow-sm', 'hover:shadow-md');
                            });
                            
                            // Destacar botão ativo
                            const activeBtn = document.getElementById(`btn-bim-${bimestre}`);
                            if (activeBtn) {
                                // Remover classes inativas
                                activeBtn.classList.remove('bg-white', 'text-gray-600', 'hover:bg-gray-50', 'shadow-sm', 'hover:shadow-md');
                                // Adicionar classes ativas
                                activeBtn.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-indigo-600', 'text-white', 'shadow-lg', 'border-b-2', 'border-blue-500');
                            }
                        }
                        
                        // Inicializar com o primeiro bimestre
                        document.addEventListener('DOMContentLoaded', function() {
                            showBimestre(1);
                        });
                        
                        // Função para alternar exibição das notas detalhadas
                        function toggleNotas(disciplinaId) {
                            const notasDiv = document.getElementById(`notas-${disciplinaId}`);
                            const arrowIcon = document.getElementById(`arrow-${disciplinaId}`);
                            
                            if (notasDiv.classList.contains('hidden')) {
                                // Mostrar notas detalhadas
                                notasDiv.classList.remove('hidden');
                                arrowIcon.style.transform = 'rotate(180deg)';
                            } else {
                                // Esconder notas detalhadas
                                notasDiv.classList.add('hidden');
                                arrowIcon.style.transform = 'rotate(0deg)';
                            }
                        }
                        </script>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Relatórios Section -->
            <section id="relatorios" class="content-section hidden">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mx-4 sm:mx-6 lg:mx-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Relatórios</h2>
                    <p class="text-gray-600">Aqui você pode gerar e visualizar relatórios diversos.</p>
                </div>
            </section>

            <!-- Merenda Section -->
            <section id="merenda" class="content-section hidden">
                <div class="space-y-6 mx-4 sm:mx-6 lg:mx-8">
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

                        <div class="table-responsive">
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

            <!-- Escolas Section (ADM Only) -->
            <section id="escolas" class="content-section hidden">
                <div class="space-y-4 sm:space-y-6 mx-2 sm:mx-4 lg:mx-6 xl:mx-8">
                    <!-- Header -->
                    <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Gestão de Escolas</h2>
                                <p class="text-sm sm:text-base text-gray-600 mt-1">Cadastro e administração de escolas municipais</p>
                            </div>
                            <button onclick="openAddSchoolModal()" class="bg-primary-green text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center space-x-2 text-sm sm:text-base w-full sm:w-auto">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Nova Escola</span>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6">
                        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Total de Escolas</p>
                                    <p class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900" id="totalSchools">12</p>
                        </div>
                                <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Escolas Ativas</p>
                                    <p class="text-lg sm:text-xl lg:text-2xl font-bold text-green-600" id="activeSchools">11</p>
                                </div>
                                <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Gestores</p>
                                    <p class="text-lg sm:text-xl lg:text-2xl font-bold text-purple-600" id="totalManagers">12</p>
                                </div>
                                <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Total Alunos</p>
                                    <p class="text-lg sm:text-xl lg:text-2xl font-bold text-orange-600" id="totalStudents">2,847</p>
                                </div>
                                <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schools Grid -->
                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-4 sm:p-6 border-b border-gray-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Escolas Cadastradas</h3>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    <div class="relative">
                                        <input type="text" id="searchSchools" placeholder="Buscar escola..." class="w-full sm:w-auto pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent text-sm">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <select id="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent text-sm">
                                        <option value="">Todas</option>
                                        <option value="active">Ativas</option>
                                        <option value="inactive">Inativas</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6">
                            <div id="schoolsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">
                                <!-- Schools will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Usuários Section (ADM SME Only) -->
            <!-- A seção de usuários foi movida para gestao_usuarios.php -->

            <!-- Estoque Central Section (ADM SME e ADM Merenda) -->
            <section id="estoque-central" class="content-section hidden">
                <div class="space-y-6 mx-4 sm:mx-6 lg:mx-8">
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

            <!-- School Configuration Section -->
            <section id="school-config" class="content-section hidden">
                <div class="space-y-4 sm:space-y-6 mx-2 sm:mx-4 lg:mx-6 xl:mx-8">
                    <!-- Header -->
                    <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 sm:space-x-4">
                                <button onclick="showSection('escolas')" class="p-1 sm:p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <div>
                                    <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800" id="schoolConfigTitle">Configuração da Escola</h2>
                                    <p class="text-sm sm:text-base text-gray-600 mt-1 hidden sm:block" id="schoolConfigSubtitle">Gerencie todas as configurações da escola</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="border-b border-gray-200">
                            <nav class="tabs-container flex space-x-2 sm:space-x-4 lg:space-x-6 px-2 sm:px-4 lg:px-6 overflow-x-auto" aria-label="Tabs">
                                <button onclick="showSchoolTab('basic')" id="tab-basic" class="school-tab active py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-primary-green font-medium text-xs sm:text-sm text-primary-green flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Dados Básicos</span>
                                    <span class="sm:hidden">Dados</span>
                                </button>
                                <button onclick="showSchoolTab('manager')" id="tab-manager" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>Gestor</span>
                                </button>
                                <button onclick="showSchoolTab('students')" id="tab-students" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <span>Alunos</span>
                                </button>
                                <button onclick="showSchoolTab('classes')" id="tab-classes" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span>Turmas</span>
                                </button>
                                <button onclick="showSchoolTab('attendance')" id="tab-attendance" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Frequência</span>
                                    <span class="sm:hidden">Freq.</span>
                                </button>
                                <button onclick="showSchoolTab('grades')" id="tab-grades" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>Notas</span>
                                </button>
                                <button onclick="showSchoolTab('subjects')" id="tab-subjects" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Disciplinas</span>
                                    <span class="sm:hidden">Disc.</span>
                                </button>
                                <button onclick="showSchoolTab('teachers')" id="tab-teachers" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Professores</span>
                                    <span class="sm:hidden">Prof.</span>
                                </button>
                                <button onclick="showSchoolTab('settings')" id="tab-settings" class="school-tab py-2 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex-shrink-0 flex items-center space-x-1 sm:space-x-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Configurações</span>
                                    <span class="sm:hidden">Config.</span>
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        <div class="p-3 sm:p-4 lg:p-6">
                            <!-- Basic Info Tab -->
                            <div id="content-basic" class="school-tab-content">
                                <div class="max-w-2xl">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas da Escola</h3>
                                    <form class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Escola</label>
                                                <input type="text" id="schoolName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: EMEB José da Silva">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Código INEP</label>
                                                <input type="text" id="schoolCode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: 12345678">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                                            <input type="text" id="schoolAddress" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: Rua das Flores, 123">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                                <input type="text" id="schoolCEP" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: 61900-000">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                                <input type="text" id="schoolPhone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: (85) 99999-9999">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                                <input type="email" id="schoolEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: escola@maranguape.ce.gov.br">
                                            </div>
                                        </div>
                                            <div class="flex items-center space-x-4">
                                                <button type="button" onclick="saveSchoolData()" class="bg-primary-green text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                                    Salvar Alterações
                                                </button>
                                                <button type="button" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                                    Cancelar
                                                </button>
                                            </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Manager Tab -->
                            <div id="content-manager" class="school-tab-content hidden">
                                <div class="max-w-2xl">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Gestor da Escola</h3>
                                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-primary-green rounded-full flex items-center justify-center">
                                                    <span class="text-white font-bold text-lg" id="managerInitials">JS</span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900" id="managerName">João Silva</p>
                                                    <p class="text-sm text-gray-600" id="managerEmail">joao.silva@maranguape.ce.gov.br</p>
                                                </div>
                                            </div>
                                            <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                                Editar Gestor
                                            </button>
                                        </div>
                                    </div>
                                    <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        Cadastrar Novo Gestor
                                    </button>
                                </div>
                            </div>

                            <!-- Students Tab -->
                            <div id="content-students" class="school-tab-content hidden">
                                <div class="max-w-6xl">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900">Alunos da Escola</h3>
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                            <div class="relative">
                                                <input type="text" id="searchStudents" placeholder="Buscar aluno..." class="w-full sm:w-auto pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent text-sm">
                                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <select id="filterClass" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent text-sm">
                                                <option value="">Todas as turmas</option>
                                                <option value="1A">1º Ano A</option>
                                                <option value="1B">1º Ano B</option>
                                                <option value="2A">2º Ano A</option>
                                                <option value="2B">2º Ano B</option>
                                                <option value="3A">3º Ano A</option>
                                                <option value="3B">3º Ano B</option>
                                                <option value="4A">4º Ano A</option>
                                                <option value="4B">4º Ano B</option>
                                                <option value="5A">5º Ano A</option>
                                                <option value="5B">5º Ano B</option>
                                            </select>
                                            <button onclick="addNewStudent()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center space-x-2 text-sm">
                                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                <span class="hidden sm:inline">Novo Aluno</span>
                                                <span class="sm:hidden">Novo</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tabs para Alunos por Turma e Geral -->
                                    <div class="mb-6">
                                        <div class="border-b border-gray-200">
                                            <nav class="tabs-container flex space-x-4 sm:space-x-8 overflow-x-auto" aria-label="Tabs">
                                                <button onclick="showStudentsTab('all')" id="students-tab-all" class="students-tab flex-shrink-0 py-2 px-1 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center space-x-1 sm:space-x-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                    </svg>
                                                    <span class="hidden sm:inline">Todos os Alunos</span>
                                                    <span class="sm:hidden">Todos</span>
                                                </button>
                                                <button onclick="showStudentsTab('by-class')" id="students-tab-class" class="students-tab active flex-shrink-0 py-2 px-1 border-b-2 border-primary-green font-medium text-xs sm:text-sm text-primary-green flex items-center space-x-1 sm:space-x-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                    <span class="hidden sm:inline">Por Turma</span>
                                                    <span class="sm:hidden">Turmas</span>
                                                </button>
                                            </nav>
                                        </div>
                                    </div>

                                    <!-- Students Stats -->
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                                        <div class="bg-blue-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-blue-600">Total de Alunos</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-blue-800" id="totalStudentsSchool">245</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-green-600">Alunos Ativos</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-green-800" id="activeStudentsSchool">238</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-orange-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-orange-600">Novos Este Mês</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-orange-800" id="newStudentsSchool">12</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-purple-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-purple-600">Média de Idade</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-purple-800" id="avgAgeSchool">11.2</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Conteúdo das Abas de Alunos -->
                                    <!-- Todos os Alunos -->
                                    <div id="students-content-all" class="students-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <h4 class="text-lg font-semibold text-gray-900">Lista Geral de Alunos</h4>
                                                <p class="text-sm text-gray-600">Todos os alunos matriculados na escola</p>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Turma</th>
                                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Idade</th>
                                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Responsável</th>
                                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200" id="allStudentsTableBody">
                                                        <tr>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                                        <span class="text-blue-600 font-bold text-xs sm:text-sm">MS</span>
                                                                    </div>
                                                                    <div class="ml-2 sm:ml-4">
                                                                        <div class="text-xs sm:text-sm font-medium text-gray-900">Maria Silva</div>
                                                                        <div class="text-xs text-gray-500">#2024001</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden sm:table-cell">5º Ano A</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden md:table-cell">11 anos</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden lg:table-cell">João Silva</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    Ativo
                                                                </span>
                                                            </td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium">
                                                                <div class="flex flex-col sm:flex-row gap-1 sm:gap-0">
                                                                    <button class="text-primary-green hover:text-green-700 sm:mr-3">Editar</button>
                                                                    <button class="text-blue-600 hover:text-blue-700 sm:mr-3">Histórico</button>
                                                                    <button class="text-red-600 hover:text-red-700">Transferir</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                                        <span class="text-green-600 font-bold">JS</span>
                                                                    </div>
                                                                    <div class="ml-4">
                                                                        <div class="text-sm font-medium text-gray-900">João Santos</div>
                                                                        <div class="text-sm text-gray-500">#2024002</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">5º Ano A</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">10 anos</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ana Santos</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    Ativo
                                                                </span>
                                                            </td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium">
                                                                <div class="flex flex-col sm:flex-row gap-1 sm:gap-0">
                                                                    <button class="text-primary-green hover:text-green-700 sm:mr-3">Editar</button>
                                                                    <button class="text-blue-600 hover:text-blue-700 sm:mr-3">Histórico</button>
                                                                    <button class="text-red-600 hover:text-red-700">Transferir</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                                                        <span class="text-purple-600 font-bold">AS</span>
                                                                    </div>
                                                                    <div class="ml-4">
                                                                        <div class="text-sm font-medium text-gray-900">Ana Costa</div>
                                                                        <div class="text-sm text-gray-500">#2024003</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">4º Ano B</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">9 anos</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Carlos Costa</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    Ativo
                                                                </span>
                                                            </td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium">
                                                                <div class="flex flex-col sm:flex-row gap-1 sm:gap-0">
                                                                    <button class="text-primary-green hover:text-green-700 sm:mr-3">Editar</button>
                                                                    <button class="text-blue-600 hover:text-blue-700 sm:mr-3">Histórico</button>
                                                                    <button class="text-red-600 hover:text-red-700">Transferir</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Por Turma -->
                                    <div id="students-content-by-class" class="students-content">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                            <!-- Turma 1º Ano A -->
                                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-lg font-semibold text-gray-900">1º Ano A</h4>
                                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">25 alunos</span>
                                                </div>
                                                <div class="space-y-4">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                            <span class="text-blue-600 font-bold text-sm">PS</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Pedro Silva</p>
                                                            <p class="text-xs text-gray-500">6 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                            <span class="text-green-600 font-bold text-sm">LS</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Lucas Santos</p>
                                                            <p class="text-xs text-gray-500">6 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                            <span class="text-purple-600 font-bold text-sm">JF</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Julia Ferreira</p>
                                                            <p class="text-xs text-gray-500">6 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                </div>
                                                <div class="mt-4 pt-3 border-t border-gray-200">
                                                    <button onclick="showClassStudents('1A')" class="w-full text-primary-green hover:text-green-700 text-sm font-medium">
                                                        Ver todos os alunos desta turma
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Turma 2º Ano A -->
                                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-lg font-semibold text-gray-900">2º Ano A</h4>
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">28 alunos</span>
                                                </div>
                                                <div class="space-y-4">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                                            <span class="text-orange-600 font-bold text-sm">RO</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Rafael Oliveira</p>
                                                            <p class="text-xs text-gray-500">7 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center">
                                                            <span class="text-pink-600 font-bold text-sm">SM</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Sofia Mendes</p>
                                                            <p class="text-xs text-gray-500">7 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                            <span class="text-indigo-600 font-bold text-sm">GC</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Gabriel Costa</p>
                                                            <p class="text-xs text-gray-500">7 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                </div>
                                                <div class="mt-4 pt-3 border-t border-gray-200">
                                                    <button onclick="showClassStudents('2A')" class="w-full text-primary-green hover:text-green-700 text-sm font-medium">
                                                        Ver todos os alunos desta turma
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Turma 3º Ano A -->
                                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-lg font-semibold text-gray-900">3º Ano A</h4>
                                                    <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full">26 alunos</span>
                                                </div>
                                                <div class="space-y-4">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                                            <span class="text-teal-600 font-bold text-sm">AL</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Alice Lima</p>
                                                            <p class="text-xs text-gray-500">8 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                            <span class="text-yellow-600 font-bold text-sm">BR</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Bruno Rodrigues</p>
                                                            <p class="text-xs text-gray-500">8 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                            <span class="text-red-600 font-bold text-sm">CL</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-900">Camila Lima</p>
                                                            <p class="text-xs text-gray-500">8 anos</p>
                                                        </div>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativo</span>
                                                    </div>
                                                </div>
                                                <div class="mt-4 pt-3 border-t border-gray-200">
                                                    <button onclick="showClassStudents('3A')" class="w-full text-primary-green hover:text-green-700 text-sm font-medium">
                                                        Ver todos os alunos desta turma
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Detalhes da Turma Selecionada -->
                                    <div id="students-content-class-detail" class="students-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900" id="classDetailTitle">Alunos da Turma</h4>
                                                        <p class="text-sm text-gray-600" id="classDetailSubtitle">Lista completa de alunos da turma selecionada</p>
                                                    </div>
                                                    <button onclick="showStudentsTab('by-class')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200" id="classStudentsTableBody">
                                                        <!-- Alunos da turma serão carregados aqui -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Notes Tab - Design Completamente Novo -->
                            <div id="content-notes" class="school-tab-content hidden">
                                <div class="space-y-4 sm:space-y-6">
                                    <!-- Header Mobile-First -->
                                    <div class="bg-white rounded-lg p-3 sm:p-4 shadow-sm border border-gray-200">
                                        <!-- Título Mobile -->
                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-8 h-8 bg-primary-green rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-bold text-gray-900">Notas da Escola</h3>
                                                <p class="text-xs text-gray-500">Controle de desempenho</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Filtros Mobile - Empilhados -->
                                        <div class="space-y-3">
                                            <div class="flex gap-2">
                                                <select class="flex-1 text-xs border border-gray-300 rounded-md px-2 py-2 focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <option>Todas as turmas</option>
                                                    <option>1º Ano A</option>
                                                    <option>1º Ano B</option>
                                                </select>
                                                <select class="flex-1 text-xs border border-gray-300 rounded-md px-2 py-2 focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <option>1º Bimestre</option>
                                                    <option>2º Bimestre</option>
                                                    <option>3º Bimestre</option>
                                                </select>
                                            </div>
                                            
                                            <!-- Botão Mobile - Largura Total -->
                                            <button class="w-full bg-primary-green text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center space-x-2 text-sm font-medium">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                <span>Lançar Notas</span>
                                            </button>
                                        </div>
                                    </div>


                                    <!-- Botões de Ação -->
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <button onclick="showNotesView('by-class')" class="flex-1 bg-primary-green text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center space-x-2 text-sm font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            <span>Ver por Turma</span>
                                        </button>
                                        <button onclick="showNotesView('by-student')" class="flex-1 bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center space-x-2 text-sm font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>Ver por Aluno</span>
                                        </button>
                                    </div>

                                    <!-- Resumo de Notas -->
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        <!-- Card Aprovados -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Aprovados</p>
                                                    <p class="text-2xl font-bold text-green-600" id="totalApproved">198</p>
                                                </div>
                                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>80.8%</span>
                                                    <span>do total</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Recuperação -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Recuperação</p>
                                                    <p class="text-2xl font-bold text-orange-600" id="totalRecovery">32</p>
                                                </div>
                                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>13.1%</span>
                                                    <span>do total</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Reprovados -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Reprovados</p>
                                                    <p class="text-2xl font-bold text-red-600" id="totalFailed">15</p>
                                                </div>
                                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>6.1%</span>
                                                    <span>do total</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Média Geral -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Média Geral</p>
                                                    <p class="text-2xl font-bold text-blue-600" id="schoolAverage">7.8</p>
                                                </div>
                                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>Bom</span>
                                                    <span>desempenho</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes Content -->
                                    <div id="notes-content-by-class" class="notes-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900">Selecione uma Turma</h4>
                                                        <p class="text-sm text-gray-600">Clique em uma turma para ver as notas dos alunos</p>
                                                    </div>
                                                    <button onclick="showNotesView('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-6">
                                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4" id="notesClassGrid">
                                                    <!-- Turmas serão carregadas aqui -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="notes-content-by-student" class="notes-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900">Selecione um Aluno</h4>
                                                        <p class="text-sm text-gray-600">Clique em um aluno para ver suas notas</p>
                                                    </div>
                                                    <button onclick="showNotesView('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-6">
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="notesStudentGrid">
                                                    <!-- Alunos serão carregados aqui -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Class Details -->
                                    <div id="notes-content-class-detail" class="notes-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900" id="notesClassDetailTitle">Notas da Turma</h4>
                                                        <p class="text-sm text-gray-600" id="notesClassDetailSubtitle">Lista de notas dos alunos da turma selecionada</p>
                                                    </div>
                                                    <button onclick="showNotesView('by-class')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                        <tr>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                    </svg>
                                                                    <span>Aluno</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                                    </svg>
                                                                    <span>Matemática</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                                    </svg>
                                                                    <span>Português</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                                                    </svg>
                                                                    <span>Ciências</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    <span>História</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                                    </svg>
                                                                    <span>Média</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    <span>Status</span>
                                                                </div>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200" id="notesClassDetailTableBody">
                                                        <!-- Notas da turma serão carregadas aqui -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Student Details -->
                                    <div id="notes-content-student-detail" class="notes-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900" id="notesStudentDetailTitle">Notas do Aluno</h4>
                                                        <p class="text-sm text-gray-600" id="notesStudentDetailSubtitle">Histórico de notas do aluno selecionado</p>
                                                    </div>
                                                    <button onclick="showNotesView('by-student')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-6" id="notesStudentDetailContent">
                                                <!-- Detalhes do aluno serão carregados aqui -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Tab -->
                            <div id="content-attendance" class="school-tab-content hidden">
                                <div class="max-w-6xl">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900">Frequência da Escola</h3>
                                        <div class="flex items-center space-x-3">
                                            <select id="attendanceMonthFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                <option value="jan">Janeiro</option>
                                                <option value="fev">Fevereiro</option>
                                                <option value="mar">Março</option>
                                                <option value="abr">Abril</option>
                                                <option value="mai">Maio</option>
                                                <option value="jun">Junho</option>
                                                <option value="jul">Julho</option>
                                                <option value="ago">Agosto</option>
                                                <option value="set">Setembro</option>
                                                <option value="out">Outubro</option>
                                                <option value="nov">Novembro</option>
                                                <option value="dez">Dezembro</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Navigation Buttons -->
                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 mb-6 sm:mb-8">
                                        <button onclick="showAttendanceView('by-class')" class="w-full sm:w-auto group bg-gradient-to-r from-primary-green to-green-600 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-300 text-base sm:text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                            <div class="flex items-center justify-center space-x-2 sm:space-x-3">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                <span>Por Turma</span>
                                            </div>
                                        </button>
                                        <button onclick="showAttendanceView('by-student')" class="w-full sm:w-auto group bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 text-base sm:text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                            <div class="flex items-center justify-center space-x-2 sm:space-x-3">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span>Por Aluno</span>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Attendance Stats -->
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                                        <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-green-600">Presenças</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-green-800" id="totalPresent">1,245</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-red-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-red-600">Faltas</p>
                                                    <p class="text-2xl font-bold text-red-800" id="totalAbsent">45</p>
                                                </div>
                                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-blue-600">Justificadas</p>
                                                    <p class="text-2xl font-bold text-blue-800" id="totalJustified">12</p>
                                                </div>
                                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-purple-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-purple-600">Frequência Geral</p>
                                                    <p class="text-2xl font-bold text-purple-800" id="attendanceRate">96.5%</p>
                                                </div>
                                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Attendance Content -->
                                    <div id="attendance-content-by-class" class="attendance-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900">Selecione uma Turma</h4>
                                                        <p class="text-sm text-gray-600">Clique em uma turma para ver a frequência dos alunos</p>
                                                    </div>
                                                    <button onclick="showAttendanceView('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-6">
                                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4" id="attendanceClassGrid">
                                                    <!-- Turmas serão carregadas aqui -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="attendance-content-by-student" class="attendance-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900">Selecione um Aluno</h4>
                                                        <p class="text-sm text-gray-600">Clique em um aluno para ver sua frequência</p>
                                                    </div>
                                                    <button onclick="showAttendanceView('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-6">
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="attendanceStudentGrid">
                                                    <!-- Alunos serão carregados aqui -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Class Details -->
                                    <div id="attendance-content-class-detail" class="attendance-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900" id="attendanceClassDetailTitle">Frequência da Turma</h4>
                                                        <p class="text-sm text-gray-600" id="attendanceClassDetailSubtitle">Lista de frequência dos alunos da turma selecionada</p>
                                                    </div>
                                                    <button onclick="showAttendanceView('by-class')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                        <tr>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                    </svg>
                                                                    <span>Aluno</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    <span>Presenças</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                    </svg>
                                                                    <span>Faltas</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                                    <span>Justificadas</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                                    </svg>
                                                                    <span>Frequência</span>
                                                                </div>
                                                            </th>
                                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                    </svg>
                                                                    <span>Ações</span>
                                                                </div>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200" id="attendanceClassDetailTableBody">
                                                        <!-- Frequência da turma será carregada aqui -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Student Details -->
                                    <div id="attendance-content-student-detail" class="attendance-content hidden">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="p-4 border-b border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900" id="attendanceStudentDetailTitle">Frequência do Aluno</h4>
                                                        <p class="text-sm text-gray-600" id="attendanceStudentDetailSubtitle">Histórico de frequência do aluno selecionado</p>
                                                    </div>
                                                    <button onclick="showAttendanceView('by-student')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-6" id="attendanceStudentDetailContent">
                                                <!-- Detalhes do aluno serão carregados aqui -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subjects Tab -->
                            <div id="content-subjects" class="school-tab-content hidden">
                                <div class="max-w-4xl">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900">Disciplinas da Escola</h3>
                                        <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                            Adicionar Disciplina
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">Matemática</h4>
                                                    <p class="text-sm text-gray-600">5 professores</p>
                                                </div>
                                                <button class="text-red-600 hover:text-red-700">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">Português</h4>
                                                    <p class="text-sm text-gray-600">4 professores</p>
                                                </div>
                                                <button class="text-red-600 hover:text-red-700">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">Ciências</h4>
                                                    <p class="text-sm text-gray-600">3 professores</p>
                                                </div>
                                                <button class="text-red-600 hover:text-red-700">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Teachers Tab -->
                            <div id="content-teachers" class="school-tab-content hidden">
                                <div class="max-w-7xl">
                                    <!-- Header com filtros melhorados -->
                                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-4 sm:p-6 mb-6 border border-purple-100">
                                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                            <div>
                                                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">👨‍🏫 Professores da Escola</h3>
                                                <p class="text-sm sm:text-base text-gray-600">Gerencie o corpo docente e suas disciplinas</p>
                                            </div>
                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                                <div class="relative">
                                                    <input type="text" id="searchTeachers" placeholder="Buscar professor..." class="w-full sm:w-auto pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent text-sm">
                                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="relative">
                                                    <select id="teacherSubjectFilter" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:ring-2 focus:ring-primary-green focus:border-transparent text-sm font-medium">
                                                        <option value="">Todas as disciplinas</option>
                                                        <option value="matematica">Matemática</option>
                                                        <option value="portugues">Português</option>
                                                        <option value="ciencias">Ciências</option>
                                                        <option value="historia">História</option>
                                                        <option value="geografia">Geografia</option>
                                                        <option value="artes">Artes</option>
                                                        <option value="educacao-fisica">Educação Física</option>
                                                    </select>
                                                    <svg class="w-4 h-4 text-gray-400 absolute right-2 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                                <button onclick="openAddTeachersModal()" class="bg-gradient-to-r from-primary-green to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 flex items-center space-x-2 text-sm font-semibold shadow-md hover:shadow-lg">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                    <span>Adicionar Professor</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Teachers Stats -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                                        <!-- Card Total de Professores -->
                                        <div class="group bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-4 sm:p-6 border border-blue-100 hover:border-blue-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Total</p>
                                                    <p class="text-2xl sm:text-3xl font-bold text-blue-800" id="totalTeachers">24</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-blue-600 font-medium">Professores</span>
                                                <div class="w-16 h-2 bg-blue-200 rounded-full overflow-hidden">
                                                    <div class="w-full h-full bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Professores Ativos -->
                                        <div class="group bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-4 sm:p-6 border border-green-100 hover:border-green-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Ativos</p>
                                                    <p class="text-2xl sm:text-3xl font-bold text-green-800" id="activeTeachers">22</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-green-600 font-medium">91.7%</span>
                                                <div class="w-16 h-2 bg-green-200 rounded-full overflow-hidden">
                                                    <div class="w-11/12 h-full bg-gradient-to-r from-green-500 to-emerald-600 rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Disciplinas -->
                                        <div class="group bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl p-4 sm:p-6 border border-purple-100 hover:border-purple-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs font-medium text-purple-600 uppercase tracking-wide">Disciplinas</p>
                                                    <p class="text-2xl sm:text-3xl font-bold text-purple-800" id="totalSubjects">8</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-purple-600 font-medium">Cobertura</span>
                                                <div class="w-16 h-2 bg-purple-200 rounded-full overflow-hidden">
                                                    <div class="w-4/5 h-full bg-gradient-to-r from-purple-500 to-violet-600 rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Carga Horária -->
                                        <div class="group bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-4 sm:p-6 border border-orange-100 hover:border-orange-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs font-medium text-orange-600 uppercase tracking-wide">Carga H.</p>
                                                    <p class="text-2xl sm:text-3xl font-bold text-orange-800" id="totalWorkload">40h</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-orange-600 font-medium">Semanal</span>
                                                <div class="w-16 h-2 bg-orange-200 rounded-full overflow-hidden">
                                                    <div class="w-3/4 h-full bg-gradient-to-r from-orange-500 to-amber-600 rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Lista de Professores com Cards -->
                                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
                                        <!-- Card Professor 1 -->
                                        <div class="group bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 hover:border-purple-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">MS</span>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900 text-sm sm:text-base">Maria Santos</h4>
                                                        <p class="text-xs text-gray-500">maria.santos@maranguape.ce.gov.br</p>
                                                    </div>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Ativo
                                                </span>
                                            </div>
                                            
                                            <div class="space-y-3 mb-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disciplina</span>
                                                    <span class="text-sm font-semibold text-gray-900">Matemática</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Turmas</span>
                                                    <span class="text-sm font-semibold text-gray-900">3 turmas</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Carga H.</span>
                                                    <span class="text-sm font-semibold text-gray-900">40h/semana</span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                                <div class="flex space-x-2">
                                                    <button class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-2 text-primary-green hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <button class="text-xs font-medium text-purple-600 hover:text-purple-700 px-3 py-1 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                                                    Ver Detalhes
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Card Professor 2 -->
                                        <div class="group bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 hover:border-purple-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">JS</span>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900 text-sm sm:text-base">João Silva</h4>
                                                        <p class="text-xs text-gray-500">joao.silva@maranguape.ce.gov.br</p>
                                                    </div>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Ativo
                                                </span>
                                            </div>
                                            
                                            <div class="space-y-3 mb-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disciplina</span>
                                                    <span class="text-sm font-semibold text-gray-900">Português</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Turmas</span>
                                                    <span class="text-sm font-semibold text-gray-900">4 turmas</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Carga H.</span>
                                                    <span class="text-sm font-semibold text-gray-900">32h/semana</span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                                <div class="flex space-x-2">
                                                    <button class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-2 text-primary-green hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <button class="text-xs font-medium text-purple-600 hover:text-purple-700 px-3 py-1 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                                                    Ver Detalhes
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Card Professor 3 -->
                                        <div class="group bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 hover:border-purple-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">AC</span>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900 text-sm sm:text-base">Ana Costa</h4>
                                                        <p class="text-xs text-gray-500">ana.costa@maranguape.ce.gov.br</p>
                                                    </div>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    Licença
                                                </span>
                                            </div>
                                            
                                            <div class="space-y-3 mb-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disciplina</span>
                                                    <span class="text-sm font-semibold text-gray-900">Ciências</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Turmas</span>
                                                    <span class="text-sm font-semibold text-gray-900">2 turmas</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Carga H.</span>
                                                    <span class="text-sm font-semibold text-gray-900">20h/semana</span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                                <div class="flex space-x-2">
                                                    <button class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-2 text-primary-green hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <button class="text-xs font-medium text-purple-600 hover:text-purple-700 px-3 py-1 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                                                    Ver Detalhes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Classes Tab -->
                            <div id="content-classes" class="school-tab-content hidden">
                                <div class="max-w-4xl">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900">Turmas da Escola</h3>
                                        <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                            Criar Turma
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="font-semibold text-gray-900">6º Ano A</h4>
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Ativa</span>
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-sm text-gray-600">32 alunos</p>
                                                <p class="text-sm text-gray-600">Professores: 8</p>
                                                <p class="text-sm text-gray-600">Período: Matutino</p>
                                            </div>
                                            <div class="mt-3 flex space-x-2">
                                                <button class="text-primary-green hover:text-green-700 text-sm">Editar</button>
                                                <button class="text-red-600 hover:text-red-700 text-sm">Excluir</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Tab -->
                            <div id="content-attendance" class="school-tab-content hidden">
                                <div class="max-w-6xl">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900">Frequência da Escola</h3>
                                        <div class="flex items-center space-x-3">
                                            <select id="attendanceClass" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                <option value="">Todas as turmas</option>
                                                <option value="1A">1º Ano A</option>
                                                <option value="1B">1º Ano B</option>
                                                <option value="2A">2º Ano A</option>
                                                <option value="2B">2º Ano B</option>
                                            </select>
                                            <select id="attendanceMonth" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                <option value="jan">Janeiro</option>
                                                <option value="fev">Fevereiro</option>
                                                <option value="mar">Março</option>
                                                <option value="abr">Abril</option>
                                                <option value="mai">Maio</option>
                                                <option value="jun">Junho</option>
                                            </select>
                                            <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                                Registrar Frequência
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Attendance Stats -->
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                                        <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-green-600">Presenças</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-green-800" id="totalPresent">1,245</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-red-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-red-600">Faltas</p>
                                                    <p class="text-2xl font-bold text-red-800" id="totalAbsent">45</p>
                                                </div>
                                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-blue-600">Justificadas</p>
                                                    <p class="text-2xl font-bold text-blue-800" id="totalJustified">12</p>
                                                </div>
                                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-purple-50 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-purple-600">Frequência Geral</p>
                                                    <p class="text-2xl font-bold text-purple-800" id="attendanceRate">96.5%</p>
                                                </div>
                                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Attendance Table -->
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                        <table class="w-full">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presenças</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faltas</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Justificadas</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200" id="attendanceTableBody">
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                                <span class="text-blue-600 font-bold">MS</span>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">Maria Silva</div>
                                                                <div class="text-sm text-gray-500">#2024001</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">5º Ano A</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">22</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">1</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">0</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">95.7%</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button class="text-primary-green hover:text-green-700 mr-3">Editar</button>
                                                        <button class="text-blue-600 hover:text-blue-700">Ver Histórico</button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                                <span class="text-green-600 font-bold">JS</span>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">João Santos</div>
                                                                <div class="text-sm text-gray-500">#2024002</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">5º Ano A</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">23</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">0</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">0</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">100%</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button class="text-primary-green hover:text-green-700 mr-3">Editar</button>
                                                        <button class="text-blue-600 hover:text-blue-700">Ver Histórico</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Grades Tab -->
                            <div id="content-grades" class="school-tab-content hidden">
                                <div class="max-w-6xl">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900">Notas da Escola</h3>
                                        <div class="flex items-center space-x-3">
                                            <select id="gradesClass" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                <option value="">Todas as turmas</option>
                                                <option value="1A">1º Ano A</option>
                                                <option value="1B">1º Ano B</option>
                                                <option value="2A">2º Ano A</option>
                                                <option value="2B">2º Ano B</option>
                                            </select>
                                            <select id="gradesBimestre" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                <option value="1">1º Bimestre</option>
                                                <option value="2">2º Bimestre</option>
                                                <option value="3">3º Bimestre</option>
                                                <option value="4">4º Bimestre</option>
                                                <option value="final">Final</option>
                                            </select>
                                            <button class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                                Lançar Notas
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Grades Stats -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                        <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs sm:text-sm font-medium text-green-600">Aprovados</p>
                                                    <p class="text-lg sm:text-2xl font-bold text-green-800" id="totalApproved">198</p>
                                                </div>
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Recuperação -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Recuperação</p>
                                                    <p class="text-2xl font-bold text-orange-600" id="totalRecovery">32</p>
                                                </div>
                                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>13.1%</span>
                                                    <span>do total</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Reprovados -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Reprovados</p>
                                                    <p class="text-2xl font-bold text-red-600" id="totalFailed">15</p>
                                                </div>
                                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>6.1%</span>
                                                    <span>do total</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Média Geral -->
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600">Média Geral</p>
                                                    <p class="text-2xl font-bold text-blue-600" id="schoolAverage">7.8</p>
                                                </div>
                                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>Bom</span>
                                                    <span>desempenho</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Grades Table -->
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                        <table class="w-full">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matemática</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Português</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciências</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">História</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200" id="gradesTableBody">
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                                <span class="text-blue-600 font-bold">MS</span>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">Maria Silva</div>
                                                                <div class="text-sm text-gray-500">#2024001</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">5º Ano A</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">8.5</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">9.0</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">8.0</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">8.8</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">8.6</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Aprovado
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                                <span class="text-green-600 font-bold">JS</span>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">João Santos</div>
                                                                <div class="text-sm text-gray-500">#2024002</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">5º Ano A</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">6.5</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">7.0</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">6.8</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">7.2</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">6.9</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                            Recuperação
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Settings Tab -->
                            <div id="content-settings" class="school-tab-content hidden">
                                <div class="max-w-2xl">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configurações da Escola</h3>
                                    <div class="space-y-6">
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h4 class="font-semibold text-gray-900 mb-3">Status da Escola</h4>
                                            <div class="flex items-center space-x-3">
                                                <label class="flex items-center">
                                                    <input type="radio" name="schoolStatus" value="active" class="text-primary-green focus:ring-primary-green" checked>
                                                    <span class="ml-2 text-sm text-gray-700">Ativa</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="radio" name="schoolStatus" value="inactive" class="text-primary-green focus:ring-primary-green">
                                                    <span class="ml-2 text-sm text-gray-700">Inativa</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h4 class="font-semibold text-gray-900 mb-3">Permissões</h4>
                                            <div class="space-y-2">
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="text-primary-green focus:ring-primary-green" checked>
                                                    <span class="ml-2 text-sm text-gray-700">Permitir cadastro de alunos</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="text-primary-green focus:ring-primary-green" checked>
                                                    <span class="ml-2 text-sm text-gray-700">Permitir lançamento de notas</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="text-primary-green focus:ring-primary-green" checked>
                                                    <span class="ml-2 text-sm text-gray-700">Permitir controle de frequência</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <button class="bg-primary-green text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                                Salvar Configurações
                                            </button>
                                            <button class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    <!-- Add School Modal -->
    <div id="addSchoolModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Cadastrar Nova Escola</h3>
                <button onclick="closeAddSchoolModal()" class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="addSchoolForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Escola *</label>
                        <input type="text" id="newSchoolName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: EMEB José da Silva" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código INEP</label>
                        <input type="text" id="newSchoolCode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: 12345678">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
                    <input type="text" id="newSchoolAddress" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: Rua das Flores, 123" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                        <input type="text" id="newSchoolCEP" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: 61900-000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                        <input type="text" id="newSchoolPhone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: (85) 99999-9999">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="newSchoolEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Ex: escola@maranguape.ce.gov.br">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Escola</label>
                        <select id="newSchoolType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <option value="EMEB">EMEB - Escola Municipal de Educação Básica</option>
                            <option value="EMEF">EMEF - Escola Municipal de Ensino Fundamental</option>
                            <option value="EMEI">EMEI - Escola Municipal de Educação Infantil</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="newSchoolStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <option value="active">Ativa</option>
                            <option value="inactive">Inativa</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center space-x-4 pt-4">
                    <button type="submit" class="flex-1 bg-primary-green text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                        Cadastrar Escola
                    </button>
                    <button type="button" onclick="closeAddSchoolModal()" class="flex-1 bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Teachers Modal -->
    <div id="addTeachersModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-4xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-primary-green bg-opacity-10 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Adicionar Professores</h3>
                        <p class="text-sm text-gray-600">Selecione os professores para adicionar à escola</p>
                    </div>
                </div>
                <button onclick="closeAddTeachersModal()" class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Search and Filter -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" id="teacherSearchInput" placeholder="Buscar professor por nome..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="sm:w-64">
                        <select id="teacherSubjectFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <option value="">Todas as disciplinas</option>
                            <option value="matematica">Matemática</option>
                            <option value="portugues">Português</option>
                            <option value="ciencias">Ciências</option>
                            <option value="historia">História</option>
                            <option value="geografia">Geografia</option>
                            <option value="artes">Artes</option>
                            <option value="educacao-fisica">Educação Física</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Teachers List -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Professores Disponíveis</h4>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="selectAllTeachers" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                        <label for="selectAllTeachers" class="text-sm text-gray-600">Selecionar todos</label>
                    </div>
                </div>
                
                <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg" id="teachersListContainer">
                    <!-- Lista de professores será carregada aqui -->
                </div>
            </div>

            <!-- Selected Teachers Summary -->
            <div id="selectedTeachersSummary" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg hidden">
                <div class="flex items-center space-x-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-800">Professores selecionados:</span>
                </div>
                <div id="selectedTeachersList" class="text-sm text-green-700">
                    <!-- Lista dos professores selecionados -->
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeAddTeachersModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="button" onclick="addSelectedTeachers()" class="flex-1 px-4 py-2 text-white bg-primary-green hover:bg-green-700 rounded-lg font-medium transition-colors duration-200">
                    Adicionar Professores
                </button>
            </div>
        </div>
    </div>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="fixed inset-0 bg-white z-50 hidden flex flex-col backdrop-blur-sm">
        <div class="bg-white w-full h-full overflow-hidden transform transition-all duration-300 ease-out scale-95 opacity-0" id="modalContent">
            <!-- Header -->
            <div class="bg-gradient-to-br from-slate-800 via-slate-700 to-slate-800 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-primary-green/20 to-blue-600/20"></div>
                <div class="relative z-10 flex items-center justify-between px-8 py-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-8 h-8 object-contain">
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Perfil do Usuário</h1>
                            <p class="text-slate-300 text-sm">Gerencie suas informações e configurações</p>
                        </div>
                    </div>
                    <button onclick="closeUserProfile()" class="p-3 hover:bg-white/10 rounded-xl transition-all duration-200 group">
                        <svg class="w-6 h-6 group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bg-gradient-to-r from-slate-800 via-slate-700 to-slate-800 border-b border-gray-200 sticky top-0 z-20">
                <nav class="flex items-center justify-start px-8 py-3 space-x-2">
                    <button class="profile-tab active group" data-tab="overview" onclick="switchProfileTab('overview')">
                        <svg class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="font-semibold">Visão Geral</span>
                    </button>
                    
                    <button class="profile-tab group" data-tab="personal" onclick="switchProfileTab('personal')">
                        <svg class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="font-semibold">Perfil</span>
                    </button>
                    
                    <button class="profile-tab group" data-tab="system" onclick="switchProfileTab('system')">
                        <svg class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                        <span class="font-semibold">Sistema</span>
                    </button>
                    
                    <button class="profile-tab group" data-tab="settings" onclick="switchProfileTab('settings')">
                        <svg class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="font-semibold">Configurações</span>
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100">
                <div class="p-8 lg:p-12 min-h-full">
                    <!-- Tab Content: Overview -->
                    <div id="profile-overview" class="profile-tab-content">
                        <!-- User Profile Hero Card -->
                        <div class="bg-gradient-to-br from-white to-gray-50 rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8 transform hover:scale-[1.02] transition-all duration-300">
                            <div class="bg-gradient-to-r from-primary-green via-green-500 to-emerald-600 p-8 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
                                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
                                <div class="relative z-10 flex items-center space-x-6">
                                    <div class="w-24 h-24 bg-white rounded-2xl flex items-center justify-center shadow-2xl transform hover:rotate-6 transition-transform duration-300">
                                        <span class="text-3xl font-bold text-primary-green"><?php
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
                                        ?></span>
                                    </div>
                                    <div class="flex-1 text-white">
                                        <h2 class="text-3xl font-bold mb-2"><?php echo $_SESSION['nome']; ?></h2>
                                        <div class="flex flex-wrap gap-3 mb-4">
                                            <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold border border-white/30">
                                                Administrador Geral
                                            </span>
                                            <span class="bg-blue-500/80 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold border border-blue-400/30">
                                                Acesso Total
                                            </span>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-green-100 text-sm font-medium">Email</p>
                                            <p class="text-white text-lg font-semibold"><?php echo $_SESSION['email']; ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right text-white">
                                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                                                <p class="text-green-100 text-sm font-medium">Online</p>
                                            </div>
                                            <p class="text-white font-bold text-lg">Agora</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-4xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">12</p>
                                        <p class="text-sm font-semibold text-gray-600 mt-1">Escolas</p>
                                        <p class="text-xs text-green-600 font-medium mt-1">+2 este mês</p>
                                    </div>
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-4xl font-bold text-gray-900 group-hover:text-green-600 transition-colors">248</p>
                                        <p class="text-sm font-semibold text-gray-600 mt-1">Usuários</p>
                                        <p class="text-xs text-green-600 font-medium mt-1">+15 esta semana</p>
                                    </div>
                                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-4xl font-bold text-gray-900 group-hover:text-purple-600 transition-colors">99.9%</p>
                                        <p class="text-sm font-semibold text-gray-600 mt-1">Sistema</p>
                                        <p class="text-xs text-green-600 font-medium mt-1">Excelente</p>
                                    </div>
                                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                                            <p class="text-2xl font-bold text-green-600">Online</p>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600 mt-1">Status</p>
                                        <p class="text-xs text-green-600 font-medium mt-1">Conectado</p>
                                    </div>
                                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: Personal Information -->
                    <div id="profile-personal" class="profile-tab-content hidden">
                        <!-- Profile Header Card -->
                        <div class="bg-gradient-to-br from-white to-gray-50 rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8">
                            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-8 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-20 translate-x-20"></div>
                                <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-16 -translate-x-16"></div>
                                <div class="relative z-10 flex items-center space-x-6">
                                    <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/30">
                                        <span class="text-2xl font-bold text-white"><?php
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
                                        ?></span>
                                    </div>
                                    <div class="flex-1 text-white">
                                        <h2 class="text-2xl font-bold mb-2"><?php echo $_SESSION['nome']; ?></h2>
                                        <p class="text-blue-100 text-sm mb-3">Administrador do Sistema</p>
                                        <div class="flex items-center space-x-4">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                                <span class="text-sm text-green-100">Online</span>
                                            </div>
                                            <div class="text-sm text-blue-100">
                                                Último acesso: Agora
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 border border-white/30">
                                            Editar Perfil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Information Cards -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Personal Info Card -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 hover:shadow-xl transition-all duration-300">
                                <div class="flex items-center space-x-3 mb-6">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900">Informações Pessoais</h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Nome Completo</label>
                                        <p class="text-lg font-semibold text-gray-900"><?php echo $_SESSION['nome']; ?></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                                        <p class="text-lg text-gray-900"><?php echo $_SESSION['email']; ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Role & Status Card -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 hover:shadow-xl transition-all duration-300">
                                <div class="flex items-center space-x-3 mb-6">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900">Função & Status</h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <label class="block text-sm font-medium text-gray-600 mb-2">Tipo de Usuário</label>
                                        <div class="flex flex-wrap gap-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gradient-to-r from-primary-green to-green-600 text-white">
                                                Administrador Geral
                                            </span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <label class="block text-sm font-medium text-gray-600 mb-2">Status da Conta</label>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                                Ativo
                                            </span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Nível de Acesso</label>
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full w-full"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-600">100%</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Acesso total ao sistema</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Timeline -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Atividade Recente</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center space-x-4 p-3 bg-green-50 rounded-xl border border-green-100">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Login realizado com sucesso</p>
                                        <p class="text-xs text-gray-500">Agora mesmo</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4 p-3 bg-blue-50 rounded-xl border border-blue-100">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Acesso ao dashboard principal</p>
                                        <p class="text-xs text-gray-500">Há 5 minutos</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4 p-3 bg-purple-50 rounded-xl border border-purple-100">
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Configurações do perfil visualizadas</p>
                                        <p class="text-xs text-gray-500">Há 10 minutos</p>
                                    </div>
                                </div>
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
                                        <p class="text-sm font-medium text-green-600">+2 este mês</p>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold text-gray-900 mb-1">12</h3>
                                <p class="text-sm font-medium text-gray-600">Escolas Gerenciadas</p>
                                <div class="mt-3 bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: 85%"></div>
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
                                        <p class="text-sm font-medium text-green-600">+15 esta semana</p>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold text-gray-900 mb-1">248</h3>
                                <p class="text-sm font-medium text-gray-600">Usuários Ativos</p>
                                <div class="mt-3 bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: 92%"></div>
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
                                <h3 class="text-2xl font-bold text-gray-900 mb-1">14:30</h3>
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
                        <!-- Settings Header -->
                        <div class="bg-gradient-to-br from-white to-gray-50 rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8">
                            <div class="bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-700 p-8 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12"></div>
                                <div class="relative z-10 text-white text-center">
                                    <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <h2 class="text-2xl font-bold mb-2">Configurações do Sistema</h2>
                                    <p class="text-purple-100 text-sm">Personalize sua experiência de uso</p>
                                </div>
                            </div>
                        </div>

                        <!-- Theme Settings -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Tema Visual</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button class="theme-option group p-4 border-2 border-gray-200 rounded-2xl hover:border-blue-500 hover:shadow-lg transition-all duration-300" data-theme="light">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <h4 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">Tema Claro</h4>
                                            <p class="text-sm text-gray-500">Interface clara e brilhante</p>
                                        </div>
                                    </div>
                                </button>
                                
                                <button class="theme-option group p-4 border-2 border-gray-200 rounded-2xl hover:border-purple-500 hover:shadow-lg transition-all duration-300" data-theme="dark">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-indigo-700 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <h4 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">Tema Escuro</h4>
                                            <p class="text-sm text-gray-500">Interface escura e elegante</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Notificações</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">Notificações por Email</h4>
                                            <p class="text-sm text-gray-500">Receba atualizações importantes por email</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">Notificações do Sistema</h4>
                                            <p class="text-sm text-gray-500">Alertas sobre status do sistema e manutenções</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">Notificações de Atividade</h4>
                                            <p class="text-sm text-gray-500">Alertas sobre atividades importantes</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                            <div class="flex flex-col sm:flex-row gap-4 justify-end">
                                <button class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 font-medium">
                                    Cancelar
                                </button>
                                <button class="px-6 py-3 bg-gradient-to-r from-primary-green to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    Salvar Configurações
                                </button>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <script>
        // Profile Modal Functions
        function openUserProfile() {
            const modal = document.getElementById('userProfileModal');
            const modalContent = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('show');
            
            // Trigger animation after a small delay
            setTimeout(() => {
                modalContent.style.transform = 'scale(1) translateY(0)';
                modalContent.style.opacity = '1';
            }, 10);
        }

        function closeUserProfile() {
            const modal = document.getElementById('userProfileModal');
            const modalContent = document.getElementById('modalContent');
            
            // Animate out
            modalContent.style.transform = 'scale(0.95) translateY(20px)';
            modalContent.style.opacity = '0';
            
            // Hide modal after animation
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('show');
            }, 300);
        }

        function switchProfileTab(tabName) {
            // Remove active class from all tabs
            document.querySelectorAll('.profile-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Hide all tab contents
            document.querySelectorAll('.profile-tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Add active class to clicked tab
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

            // Show corresponding content
            document.getElementById(`profile-${tabName}`).classList.remove('hidden');
        }

        // Keyboard shortcut for profile modal
        document.addEventListener('keydown', function(event) {
            if (event.altKey && event.key === 'a') {
                event.preventDefault();
                openUserProfile();
            }
            if (event.key === 'Escape') {
                closeUserProfile();
            }
        });
    </script>

</body>
</html>
