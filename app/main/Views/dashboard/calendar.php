<?php
require_once('../../Models/sessao/sessions.php');
$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}

// Verificar se o usuário tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Dashboard ADM - SIGEA</title>
    
    <!-- Favicon -->
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    
    <!-- Tailwind CSS -->
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
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    
    <!-- Sistema de Tema -->
    <script src="theme-manager.js"></script>
    <script src="js/modal-alerts.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fafafa;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Layout com sidebar */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--bg-primary);
            border-right: 1px solid #e5e7eb;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 16px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: #f8fafc;
            color: #1a1a1a;
        }

        .nav-item.active {
            background: #f0f9ff;
            color: #3b82f6;
            border-left-color: #3b82f6;
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            background: #fafafa;
        }

        /* NO GLOSSY SHINE EFFECT - Elements are normal */

        /* ===== SISTEMA DE TEMAS ===== */
        /* Variáveis CSS para tema claro */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --bg-quaternary: #e2e8f0;
            --text-primary: #1a1a1a;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --text-accent: #2d3748;
            --border-color: #e2e8f0;
            --border-light: #cbd5e0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --primary-green: #2D5A27;
            --primary-green-hover: #1a3d1a;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-orange: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
        }

        /* Variáveis CSS para tema escuro */
        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2a2a2a;
            --bg-tertiary: #3a3a3a;
            --bg-quaternary: #4a4a4a;
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

        /* Aplicar tema escuro */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Calendário no tema escuro */
        [data-theme="dark"] .calendar-container {
            background: var(--bg-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .calendar-header {
            background: var(--bg-primary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .calendar-title {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .calendar-subtitle {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .nav-btn,
        [data-theme="dark"] .today-btn,
        [data-theme="dark"] .view-dropdown,
        [data-theme="dark"] .add-event-btn {
            background: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .nav-btn:hover,
        [data-theme="dark"] .today-btn:hover,
        [data-theme="dark"] .view-dropdown:hover,
        [data-theme="dark"] .add-event-btn:hover {
            background: var(--bg-quaternary) !important;
        }

        /* FullCalendar no tema escuro */
        [data-theme="dark"] .fc {
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .fc-theme-standard .fc-scrollgrid {
            border: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .fc-theme-standard td,
        [data-theme="dark"] .fc-theme-standard th {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .fc-theme-standard .fc-scrollgrid-sync-table {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .fc-col-header-cell {
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .fc-daygrid-day {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .fc-daygrid-day-number {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .fc-daygrid-day:hover {
            background: var(--bg-tertiary) !important;
        }

        [data-theme="dark"] .fc-day-today {
            background: var(--bg-tertiary) !important;
        }

        [data-theme="dark"] .fc-day-today .fc-daygrid-day-number {
            color: var(--text-primary) !important;
        }

        /* Dias de outros meses no tema escuro */
        [data-theme="dark"] .fc-day-other .fc-daygrid-day-number {
            color: var(--text-muted) !important;
            opacity: 0.5 !important;
        }

        [data-theme="dark"] .fc-day-other {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .fc-day-other:hover {
            background: var(--bg-tertiary) !important;
        }

        /* Dias do mês atual no tema escuro */
        [data-theme="dark"] .fc-daygrid-day:not(.fc-day-other) .fc-daygrid-day-number {
            color: var(--text-primary) !important;
        }

        /* Hover nos dias do mês atual */
        [data-theme="dark"] .fc-daygrid-day:not(.fc-day-other):hover {
            background: var(--bg-tertiary) !important;
        }

        [data-theme="dark"] .bg-white {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        /* Header no tema escuro */
        [data-theme="dark"] .header {
            background: var(--bg-primary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .header-title {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .search-input {
            background: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .search-input::placeholder {
            color: var(--text-muted) !important;
        }

        [data-theme="dark"] .profile-btn {
            background: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        /* Sidebar no tema escuro */
        [data-theme="dark"] .sidebar {
            background: var(--bg-primary) !important;
            border-right: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .menu-item {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .menu-item:hover,
        [data-theme="dark"] .menu-item.active {
            background: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .user-info {
            border-bottom: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .user-name {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .user-role {
            color: var(--text-secondary) !important;
        }

        /* Aplicar tema escuro suave aos elementos */
        [data-theme="dark"] .bg-white {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .sidebar {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .header {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .calendar-container {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .calendar-header {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .nav-tabs {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc-theme-standard {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc-scrollgrid {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc-scrollgrid-sync-table {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc-col-header {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc-daygrid {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc-daygrid-day {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .modal {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .modal-content {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .modal-header {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .modal-body {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .modal-footer {
            background: var(--bg-secondary) !important;
        }

        /* Corrigir bordas no tema escuro */
        [data-theme="dark"] .border-gray-200,
        [data-theme="dark"] .border-gray-300,
        [data-theme="dark"] .border-gray-400 {
            border-color: var(--border-color) !important;
        }

        /* Corrigir texto no tema escuro */
        [data-theme="dark"] .text-gray-800,
        [data-theme="dark"] .text-gray-700,
        [data-theme="dark"] .text-gray-600,
        [data-theme="dark"] .text-gray-500 {
            color: var(--text-primary) !important;
        }

        /* Corrigir tabs de navegação */
        [data-theme="dark"] .nav-tab {
            color: var(--text-primary) !important;
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .nav-tab.active {
            color: var(--text-primary) !important;
            background: var(--bg-primary) !important;
            border-bottom: 2px solid var(--primary-green) !important;
        }

        [data-theme="dark"] .nav-tab:hover {
            color: var(--text-primary) !important;
            background: var(--bg-tertiary) !important;
        }

        /* Corrigir scrollbar */
        [data-theme="dark"] ::-webkit-scrollbar {
            width: 8px;
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] ::-webkit-scrollbar-track {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: var(--bg-tertiary) !important;
            border-radius: 4px;
        }

        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: var(--bg-quaternary) !important;
        }

        /* Corrigir seleção de texto */
        [data-theme="dark"] ::selection {
            background: var(--primary-green) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] ::-moz-selection {
            background: var(--primary-green) !important;
            color: var(--text-primary) !important;
        }

        /* Corrigir sombras no tema escuro */
        [data-theme="dark"] .shadow-lg,
        [data-theme="dark"] .shadow {
            box-shadow: var(--shadow-lg) !important;
        }

        /* Aplicar tema escuro suave */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }

        /* Exceções para elementos específicos */
        [data-theme="dark"] .fc-day-today {
            background: var(--bg-tertiary) !important;
        }

        [data-theme="dark"] .fc-day-other {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .fc-daygrid-day:hover {
            background: var(--bg-tertiary) !important;
        }

        [data-theme="dark"] .nav-btn:hover,
        [data-theme="dark"] .today-btn:hover,
        [data-theme="dark"] .view-dropdown:hover,
        [data-theme="dark"] .add-event-btn:hover {
            background: var(--bg-quaternary) !important;
        }

        [data-theme="dark"] .menu-item:hover,
        [data-theme="dark"] .menu-item.active {
            background: var(--bg-tertiary) !important;
        }

        /* Corrigir elementos com fundo branco */
        [data-theme="dark"] html,
        [data-theme="dark"] body,
        [data-theme="dark"] div,
        [data-theme="dark"] section,
        [data-theme="dark"] article,
        [data-theme="dark"] main,
        [data-theme="dark"] aside,
        [data-theme="dark"] header,
        [data-theme="dark"] footer,
        [data-theme="dark"] nav {
            background: var(--bg-primary) !important;
        }

        /* Forçar fundo escuro em todos os elementos */
        [data-theme="dark"] * {
            background-color: inherit !important;
        }

        /* Exceções para elementos específicos */
        [data-theme="dark"] .sidebar {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .header {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .calendar-container {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .nav-tabs {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .fc {
            background: var(--bg-secondary) !important;
        }

        [data-theme="dark"] .modal {
            background: var(--bg-primary) !important;
        }

        [data-theme="dark"] .modal-content {
            background: var(--bg-secondary) !important;
        }

        /* Dias de outros meses no tema claro */
        .fc-day-other .fc-daygrid-day-number {
            color: #9ca3af !important;
            opacity: 0.6 !important;
        }

        /* Estilos para feriados - sem fundo, apenas texto */
        .holiday-national,
        .holiday-state,
        .holiday-municipal {
            color: #991b1b !important;
            font-weight: bold !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-size: 0.8em !important;
            opacity: 1 !important;
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
            min-height: 24px !important;
            line-height: 1.2 !important;
        }

        /* Feriados nacionais - sem fundo */
        .holiday-national {
            color: #991b1b !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
        }

        /* Feriados estaduais - sem fundo */
        .holiday-state {
            color: #991b1b !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
        }

        /* Feriados municipais - sem fundo */
        .holiday-municipal {
            color: #991b1b !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
        }

        /* Dias com feriados - fundo vermelho mais forte */
        .fc-daygrid-day.fc-day-has-holiday {
            background-color: #fca5a5 !important;
            color: #991b1b !important;
        }

        /* Números dos dias com feriados */
        .fc-daygrid-day.fc-day-has-holiday .fc-daygrid-day-number {
            color: #991b1b !important;
            font-weight: bold !important;
        }

        /* Células dos dias com feriados */
        .fc-daygrid-day.fc-day-has-holiday .fc-daygrid-day-frame {
            background-color: #fca5a5 !important;
        }

        /* Hover - remover fundo da fonte */
        .fc-daygrid-day.fc-day-has-holiday:hover .holiday-national,
        .fc-daygrid-day.fc-day-has-holiday:hover .holiday-state,
        .fc-daygrid-day.fc-day-has-holiday:hover .holiday-municipal {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
        }

        /* Tema escuro - feriados sem fundo */
        [data-theme="dark"] .holiday-national,
        [data-theme="dark"] .holiday-state,
        [data-theme="dark"] .holiday-municipal {
            color: #fecaca !important;
            font-weight: bold !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-size: 0.8em !important;
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
            min-height: 24px !important;
            line-height: 1.2 !important;
        }

        [data-theme="dark"] .holiday-national {
            color: #fecaca !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
        }

        [data-theme="dark"] .holiday-state {
            color: #fecaca !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
        }

        [data-theme="dark"] .holiday-municipal {
            color: #fecaca !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
        }

        [data-theme="dark"] .fc-daygrid-day.fc-day-has-holiday {
            background-color: #991b1b !important;
            color: #fecaca !important;
        }

        [data-theme="dark"] .fc-daygrid-day.fc-day-has-holiday .fc-daygrid-day-number {
            color: #fecaca !important;
            font-weight: bold !important;
        }

        [data-theme="dark"] .fc-daygrid-day.fc-day-has-holiday .fc-daygrid-day-frame {
            background-color: #991b1b !important;
        }

        /* Tema escuro - hover para remover fundo da fonte */
        [data-theme="dark"] .fc-daygrid-day.fc-day-has-holiday:hover .holiday-national,
        [data-theme="dark"] .fc-daygrid-day.fc-day-has-holiday:hover .holiday-state,
        [data-theme="dark"] .fc-daygrid-day.fc-day-has-holiday:hover .holiday-municipal {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
        }

        /* Estilos para informações do feriado */
        .holiday-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            display: none;
        }

        .holiday-info-content h4 {
            color: #0c4a6e;
            margin: 0 0 12px 0;
            font-size: 1.1em;
            font-weight: 600;
        }

        .holiday-info-content p {
            margin: 8px 0;
            color: #0c4a6e;
            font-size: 0.95em;
        }

        .holiday-link {
            display: inline-block;
            background: #0ea5e9;
            color: white !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 8px;
            transition: all 0.3s ease;
        }

        .holiday-link:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        /* Tema escuro - informações do feriado */
        [data-theme="dark"] .holiday-info {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-color: #0ea5e9;
        }

        [data-theme="dark"] .holiday-info-content h4,
        [data-theme="dark"] .holiday-info-content p {
            color: #f1f5f9;
        }

        [data-theme="dark"] .holiday-link {
            background: #0ea5e9;
            color: white !important;
        }

        [data-theme="dark"] .holiday-link:hover {
            background: #0284c7;
        }

        /* Forçar transparência nos feriados */
        .fc-event.holiday-national,
        .fc-event.holiday-state,
        .fc-event.holiday-municipal {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            color: #991b1b !important;
            opacity: 1 !important;
            background-image: none !important;
            box-shadow: none !important;
            padding: 8px 16px !important;
            font-size: 0.8em !important;
            min-height: 32px !important;
            line-height: 1.3 !important;
            border-radius: 8px !important;
        }

        .fc-event.holiday-state {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            color: #991b1b !important;
            padding: 8px 16px !important;
            font-size: 0.8em !important;
            min-height: 32px !important;
            line-height: 1.3 !important;
            border-radius: 8px !important;
        }

        .fc-event.holiday-municipal {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            color: #991b1b !important;
            padding: 8px 16px !important;
            font-size: 0.8em !important;
            min-height: 32px !important;
            line-height: 1.3 !important;
            border-radius: 8px !important;
        }

        /* Tema escuro - transparência nos feriados */
        [data-theme="dark"] .fc-event.holiday-national,
        [data-theme="dark"] .fc-event.holiday-state,
        [data-theme="dark"] .fc-event.holiday-municipal {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            color: #fecaca !important;
            opacity: 1 !important;
            background-image: none !important;
            box-shadow: none !important;
            padding: 8px 16px !important;
            font-size: 0.8em !important;
            min-height: 32px !important;
            line-height: 1.3 !important;
            border-radius: 8px !important;
        }

        [data-theme="dark"] .fc-event.holiday-state {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            color: #fecaca !important;
            padding: 8px 16px !important;
            font-size: 0.8em !important;
            min-height: 32px !important;
            line-height: 1.3 !important;
            border-radius: 8px !important;
        }

        [data-theme="dark"] .fc-event.holiday-municipal {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            color: #fecaca !important;
            padding: 8px 16px !important;
            font-size: 0.8em !important;
            min-height: 32px !important;
            line-height: 1.3 !important;
            border-radius: 8px !important;
        }

        .fc-day-other {
            background: #f9fafb !important;
        }

        /* Bordas arredondadas para os cards do calendário */
        .fc-daygrid-day {
            border-radius: 8px !important;
        }

        .fc-daygrid-day-frame {
            border-radius: 8px !important;
        }

        .fc-daygrid-day-top {
            border-radius: 8px 8px 0 0 !important;
        }

        .fc-daygrid-day-events {
            border-radius: 0 0 8px 8px !important;
        }

        /* Tema escuro - bordas arredondadas */
        [data-theme="dark"] .fc-daygrid-day {
            border-radius: 8px !important;
        }

        [data-theme="dark"] .fc-daygrid-day-frame {
            border-radius: 8px !important;
        }

        [data-theme="dark"] .fc-daygrid-day-top {
            border-radius: 8px 8px 0 0 !important;
        }

        [data-theme="dark"] .fc-daygrid-day-events {
            border-radius: 0 0 8px 8px !important;
        }

        .fc-day-other:hover {
            background: #f3f4f6 !important;
        }

        /* Dias do mês atual no tema claro */
        .fc-daygrid-day:not(.fc-day-other) .fc-daygrid-day-number {
            color: #1f2937 !important;
        }

        /* Hover nos dias do mês atual no tema claro */
        .fc-daygrid-day:not(.fc-day-other):hover {
            background: #f3f4f6 !important;
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

        /* Calendário tema escuro */
        [data-theme="dark"] .calendar-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .calendar-header {
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] .calendar-title {
            color: var(--text-primary);
        }

        [data-theme="dark"] .calendar-subtitle {
            color: var(--text-secondary);
        }

        [data-theme="dark"] .nav-btn,
        [data-theme="dark"] .today-btn,
        [data-theme="dark"] .view-dropdown,
        [data-theme="dark"] .add-event-btn {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .nav-btn:hover,
        [data-theme="dark"] .today-btn:hover,
        [data-theme="dark"] .view-dropdown:hover,
        [data-theme="dark"] .add-event-btn:hover {
            background: var(--bg-quaternary);
            border-color: var(--border-light);
        }

        /* FullCalendar tema escuro */
        [data-theme="dark"] .fc {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        [data-theme="dark"] .fc-toolbar {
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] .fc-button {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .fc-button:hover {
            background: var(--bg-quaternary);
            border-color: var(--border-light);
        }

        [data-theme="dark"] .fc-daygrid-day {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .fc-daygrid-day:hover {
            background: var(--bg-tertiary);
        }

        [data-theme="dark"] .fc-daygrid-day-number {
            color: var(--text-primary);
        }

        [data-theme="dark"] .fc-daygrid-day-header {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] .fc-event {
            /* background: var(--primary-green); */
            /* color: white; */
            /* border: 1px solid var(--primary-green-hover); */
        }

        [data-theme="dark"] .fc-event:hover {
            /* background: var(--primary-green-hover); */
        }

        /* Modal tema escuro */
        [data-theme="dark"] .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .modal-header {
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] .modal-title {
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-group label {
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-control {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        }

        [data-theme="dark"] .btn-primary {
            background: var(--primary-green);
            border-color: var(--primary-green);
        }

        [data-theme="dark"] .btn-primary:hover {
            background: var(--primary-green-hover);
            border-color: var(--primary-green-hover);
        }

        [data-theme="dark"] .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .btn-secondary:hover {
            background: var(--bg-quaternary);
            border-color: var(--border-light);
        }

        /* ===== SIDEBAR STYLES ===== */
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }

        .sidebar-mobile {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            z-index: 999 !important;
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            height: 100vh !important;
            width: 16rem !important;
        }

        .sidebar-mobile.open {
            transform: translateX(0) !important;
            z-index: 999 !important;
        }

        .content-dimmed {
            opacity: 0.5 !important;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }

        /* Menu item styles */
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

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
        }

        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
            }
        }

        /* Header Styles */
        .header {
            position: sticky;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid #e5e5e5;
            padding: 20px 40px;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            width: 280px;
            padding: 10px 16px 10px 42px;
            border: 1.5px solid #e5e5e5;
            border-radius: 16px;
            font-size: 14px;
            background: #fafafa;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: var(--bg-primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Navigation Tabs */
        .nav-tabs {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 40px 0;
            display: flex;
            gap: 8px;
            border-bottom: 1px solid #e5e5e5;
        }

        .nav-tab {
            padding: 10px 20px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 12px 12px 0 0;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-tab:hover {
            color: #1a1a1a;
            background: #f5f5f5;
        }

        .nav-tab.active {
            color: #1a1a1a;
            background: var(--bg-primary);
        }

        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3b82f6;
        }

        /* Calendar Container */
        .calendar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 40px;
        }

        /* Calendar Header */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 0 8px;
        }

        .calendar-title-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .calendar-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }

        .calendar-subtitle {
            font-size: 14px;
            color: #6b7280;
            font-weight: 400;
        }

        .calendar-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-btn {
            width: 40px;
            height: 40px;
            border: 1.5px solid #e5e5e5;
            background: var(--bg-primary);
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #6b7280;
        }

        .nav-btn:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
            color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .today-btn {
            padding: 10px 20px;
            border: 1.5px solid #e5e5e5;
            background: var(--bg-primary);
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .today-btn:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
            color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .view-dropdown {
            padding: 10px 16px;
            border: 1.5px solid #e5e5e5;
            background: var(--bg-primary);
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .view-dropdown:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
            color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        /* Estilos para o dropdown de visualização */
        .view-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .view-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            margin-top: 4px;
        }

        .view-option {
            width: 100%;
            background: transparent;
            color: var(--text-primary);
            border: none;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            border-radius: 0;
        }

        .view-option:hover {
            background: var(--bg-secondary);
        }

        .view-option:first-child {
            border-radius: 8px 8px 0 0;
        }

        .view-option:last-child {
            border-radius: 0 0 8px 8px;
        }

        /* Tema escuro - dropdown de visualização */
        [data-theme="dark"] .view-dropdown-menu {
            background: var(--bg-primary);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .view-option {
            color: var(--text-primary);
        }

        [data-theme="dark"] .view-option:hover {
            background: var(--bg-secondary);
        }

        .add-event-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .add-event-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        /* FullCalendar Custom Styles */
        #calendar {
            background: var(--bg-primary);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 10px 40px rgba(0, 0, 0, 0.03);
        }

        .fc {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #f0f0f0;
        }

        .fc-col-header-cell {
            padding: 16px 8px;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #fafafa;
            border: none !important;
        }

        .fc-daygrid-day {
            background: var(--bg-primary);
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 120px;
        }

        .fc-daygrid-day:hover {
            background: #f8fafc;
        }

        .fc-daygrid-day-number {
            padding: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
        }

        .fc-day-today {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
        }

        .fc-day-today .fc-daygrid-day-number {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .fc-event {
            border: none !important;
            border-radius: 10px !important;
            padding: 6px 10px !important;
            margin: 2px 4px 4px 4px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }

        .fc-event:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .fc-event-time {
            font-weight: 600 !important;
            margin-right: 4px !important;
        }

        .fc-event-title {
            font-weight: 500 !important;
        }

        /* Event Colors with Gradients - REMOVIDO PARA PERMITIR CORES DINÂMICAS */
        /* .fc-event.event-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            color: white !important;
        }

        .fc-event.event-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            color: white !important;
        }

        .fc-event.event-pink {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%) !important;
            color: white !important;
        }

        .fc-event.event-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
        }

        .fc-event.event-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .fc-event.event-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .fc-event.event-cyan {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
            color: white !important;
        }

        .fc-event.event-amber {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
            color: white !important;
        } */

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            animation: fadeIn 0.2s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: 24px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 28px 32px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -0.3px;
        }

        .close-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: #f5f5f5;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: #6b7280;
        }

        .close-btn:hover {
            background: #fee2e2;
            color: #ef4444;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 32px;
            overflow-y: auto;
            flex: 1;
        }

        /* Custom Scrollbar */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f5f5f5;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #1a1a1a;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e5e5;
            border-radius: 14px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fafafa;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            background: var(--bg-primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .color-picker {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 12px;
        }

        .color-option {
            width: 100%;
            height: 56px;
            border: 3px solid transparent;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .color-option:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .color-option.selected {
            border-color: #1a1a1a;
            transform: scale(1.05);
        }

        .color-option.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .color-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .color-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .color-pink { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
        .color-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .color-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .color-red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .color-cyan { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .color-amber { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }

        .modal-footer {
            padding: 24px 32px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-shrink: 0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #6b7280;
        }

        .btn-secondary:hover {
            background: #e5e5e5;
            color: #1a1a1a;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        /* SVG Icons */
        .icon {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 1.5;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .icon-sm {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>
    <!-- Dashboard Layout -->
    <div class="dashboard-layout">
        <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile" style="background: var(--bg-primary);">
            <!-- Logo e Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">SIGEA</h1>
                        <p class="text-xs text-gray-500">Maranguape</p>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center flex-shrink-0" style="aspect-ratio: 1; min-width: 2.5rem; min-height: 2.5rem; overflow: hidden;">
                        <span class="text-sm font-bold text-white" id="profileInitials"><?php
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
                        <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
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
                        <a href="gestao_estoque_central.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>Estoque Central</span>
                        </a>
                    </li>
                    <li id="calendar-menu">
                        <a href="calendar.php" class="menu-item active flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Calendário</span>
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </nav>

        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Mobile Overlay -->
            <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleSidebar()" class="mobile-menu-btn p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200 lg:hidden" aria-label="Abrir menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <h1 class="header-title">Calendário</h1>
                    <div class="header-actions">
                        <div class="search-container">
                            <svg class="icon search-icon" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" class="search-input" placeholder="Buscar">
                        </div>
                        <button class="profile-btn">U</button>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="nav-tabs">
                <button class="nav-tab active">Todos os eventos</button>
                <button class="nav-tab">Compartilhados</button>
                <button class="nav-tab">Públicos</button>
                <button class="nav-tab">Arquivados</button>
            </div>

            <!-- Calendar Container -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="calendar-title-section">
                        <h2 class="calendar-title" id="currentMonth">Janeiro 2025</h2>
                        <span class="calendar-subtitle" id="dateRange"></span>
                    </div>
                    <div class="calendar-controls">
                        <button class="nav-btn" id="prevBtn">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <button class="today-btn" id="todayBtn">Hoje</button>
                        <button class="nav-btn" id="nextBtn">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <div class="view-dropdown-container">
                            <button class="view-dropdown" id="viewDropdown">
                                Visualização mensal
                                <svg class="icon icon-sm" viewBox="0 0 24 24">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="view-dropdown-menu" id="viewDropdownMenu" style="display: none;">
                                <button class="view-option" data-view="dayGridMonth">Mensal</button>
                                <button class="view-option" data-view="dayGridWeek">Semanal</button>
                                <button class="view-option" data-view="timeGridDay">Diário</button>
                                <button class="view-option" data-view="listWeek">Lista</button>
                            </div>
                        </div>
                        <button class="add-event-btn" id="addEventBtn">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Adicionar evento
                        </button>
                    </div>
                </div>

                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Adicionar Evento</h3>
                <button class="close-btn" id="closeModal">
                    <svg class="icon icon-sm" viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <div class="form-group">
                        <label class="form-label">Título do Evento</label>
                        <input type="text" class="form-input" id="eventTitle" placeholder="Ex: Reunião de equipe" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Data de Início</label>
                            <input type="date" class="form-input" id="eventStartDate" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Hora de Início</label>
                            <input type="time" class="form-input" id="eventStartTime" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Data de Término</label>
                            <input type="date" class="form-input" id="eventEndDate" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Hora de Término</label>
                            <input type="time" class="form-input" id="eventEndTime" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-textarea" id="eventDescription" placeholder="Adicione detalhes sobre o evento..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo de Evento</label>
                        <select class="form-select" id="eventType">
                            <option value="meeting">Reunião</option>
                            <option value="task">Tarefa</option>
                            <option value="reminder">Lembrete</option>
                            <option value="event">Evento</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cor do Evento</label>
                        <div class="color-picker">
                            <div class="color-option color-blue selected" data-color="blue"></div>
                            <div class="color-option color-purple" data-color="purple"></div>
                            <div class="color-option color-pink" data-color="pink"></div>
                            <div class="color-option color-orange" data-color="orange"></div>
                            <div class="color-option color-green" data-color="green"></div>
                            <div class="color-option color-red" data-color="red"></div>
                            <div class="color-option color-cyan" data-color="cyan"></div>
                            <div class="color-option color-amber" data-color="amber"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Cancelar</button>
                <button type="button" class="btn btn-danger" id="deleteBtn" style="display: none;">Excluir</button>
                <button type="submit" class="btn btn-primary" id="saveBtn">Salvar Evento</button>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/pt-br.global.min.js'></script>
    
    <script>
        let calendar;
        let currentEvent = null;
        let selectedColor = 'blue';

        // Sample events
        const sampleEvents = [
            { title: 'Reunião standup', start: '2025-01-06T09:00:00', end: '2025-01-06T09:30:00', className: 'event-blue' },
            { title: 'Reunião de equipe', start: '2025-01-02T10:00:00', end: '2025-01-02T11:00:00', className: 'event-purple' },
            { title: 'Almoço com cliente', start: '2025-01-09T12:00:00', end: '2025-01-09T13:30:00', className: 'event-pink' },
            { title: 'Planejamento SEO', start: '2025-01-08T13:30:00', end: '2025-01-08T15:00:00', className: 'event-blue' },
            { title: 'Trabalho profundo', start: '2025-01-08T09:00:00', end: '2025-01-08T12:00:00', className: 'event-cyan' },
            { title: 'Revisão trimestral', start: '2025-01-22T11:30:00', end: '2025-01-22T13:00:00', className: 'event-orange' },
            { title: 'Planejamento de produto', start: '2025-01-15T08:30:00', end: '2025-01-15T10:00:00', className: 'event-blue' },
            { title: 'Almoço de equipe', start: '2025-01-13T12:15:00', end: '2025-01-13T13:30:00', className: 'event-pink' },
            { title: 'Trabalho profundo', start: '2025-01-20T09:15:00', end: '2025-01-20T11:00:00', className: 'event-cyan' },
            { title: 'Café com Amelie', start: '2025-01-23T10:00:00', end: '2025-01-23T11:00:00', className: 'event-pink' },
            { title: 'Demonstração de produto', start: '2025-01-10T13:30:00', end: '2025-01-10T15:00:00', className: 'event-blue' },
            { title: 'Inspeção residencial', start: '2025-01-06T10:30:00', end: '2025-01-06T12:00:00', className: 'event-red' },
            { title: 'Reunião standup', start: '2025-01-10T09:00:00', end: '2025-01-10T09:30:00', className: 'event-blue' },
            { title: 'Reunião standup', start: '2025-01-17T09:00:00', end: '2025-01-17T09:30:00', className: 'event-blue' },
            { title: 'Reunião standup', start: '2025-01-24T09:00:00', end: '2025-01-24T09:30:00', className: 'event-blue' },
            { title: 'Reunião standup', start: '2025-01-31T09:00:00', end: '2025-01-31T09:30:00', className: 'event-blue' },
            { title: 'Maratona de meio período', start: '2025-01-18T07:00:00', end: '2025-01-18T11:00:00', className: 'event-green' },
            { title: 'Café com Amelie', start: '2025-01-17T09:30:00', end: '2025-01-17T10:30:00', className: 'event-pink' },
            { title: 'Feedback de design', start: '2025-01-17T17:30:00', end: '2025-01-17T18:30:00', className: 'event-blue' },
            { title: 'Contador', start: '2025-01-24T13:45:00', end: '2025-01-24T15:00:00', className: 'event-orange' },
            { title: 'Jantar de equipe', start: '2025-01-30T17:30:00', end: '2025-01-30T20:00:00', className: 'event-pink' },
            { title: 'Planejamento de conteúdo', start: '2025-01-28T11:00:00', end: '2025-01-28T12:30:00', className: 'event-purple' },
            { title: 'Almoço com Alina', start: '2025-01-28T12:45:00', end: '2025-01-28T14:00:00', className: 'event-pink' },
            { title: 'Planejamento de produto', start: '2025-01-29T08:30:00', end: '2025-01-29T10:00:00', className: 'event-blue' },
            { title: 'Reunião standup', start: '2025-01-13T09:00:00', end: '2025-01-13T09:30:00', className: 'event-blue' },
            { title: 'Reunião standup', start: '2025-01-20T09:00:00', end: '2025-01-20T09:30:00', className: 'event-blue' },
            { title: 'Reunião standup', start: '2025-01-27T09:00:00', end: '2025-01-27T09:30:00', className: 'event-blue' },
            { title: 'Engajamento de Ava', start: '2025-01-12T13:00:00', end: '2025-01-12T14:30:00', className: 'event-purple' },
            { title: 'Sincronização de design', start: '2025-01-08T10:30:00', end: '2025-01-08T11:30:00', className: 'event-cyan' },
            { title: 'Sincronização de design', start: '2025-01-22T14:30:00', end: '2025-01-22T15:30:00', className: 'event-cyan' },
            { title: 'Almoço com Zahir', start: '2025-01-21T13:00:00', end: '2025-01-21T14:00:00', className: 'event-pink' },
            { title: 'Jantar com C...', start: '2025-01-21T19:00:00', end: '2025-01-21T21:00:00', className: 'event-pink' },
            { title: 'Trabalho profundo', start: '2025-01-22T09:00:00', end: '2025-01-22T11:00:00', className: 'event-cyan' },
            { title: 'Eficiência de marketing', start: '2025-01-24T14:30:00', end: '2025-01-24T16:00:00', className: 'event-orange' },
            { title: 'Reunião de todos', start: '2025-01-02T16:00:00', end: '2025-01-02T17:00:00', className: 'event-purple' },
            { title: 'Reunião de todos', start: '2025-01-09T16:00:00', end: '2025-01-09T17:00:00', className: 'event-purple' },
            { title: 'Reunião de todos', start: '2025-01-23T16:00:00', end: '2025-01-23T17:00:00', className: 'event-purple' },
            { title: 'Reunião de todos', start: '2025-01-30T16:00:00', end: '2025-01-30T17:00:00', className: 'event-purple' },
            { title: 'Jantar com C...', start: '2025-01-15T19:00:00', end: '2025-01-15T21:00:00', className: 'event-pink' },
            { title: 'Primeiro encontro de Amelie', start: '2025-01-16T10:00:00', end: '2025-01-16T11:00:00', className: 'event-pink' },
            { title: 'Reunião de marketing', start: '2025-01-06T14:30:00', end: '2025-01-06T16:00:00', className: 'event-orange' },
            { title: 'Planejamento de conteúdo', start: '2025-01-07T11:00:00', end: '2025-01-07T12:00:00', className: 'event-purple' },
            { title: 'Reunião de um a um', start: '2025-01-07T10:00:00', end: '2025-01-07T11:00:00', className: 'event-pink' },
            { title: 'Almoço com Zahir', start: '2025-01-27T12:45:00', end: '2025-01-27T14:00:00', className: 'event-pink' },
            { title: 'Reunião standup', start: '2025-01-03T09:00:00', end: '2025-01-03T09:30:00', className: 'event-blue' },
            { title: 'Planejamento de marketing', start: '2025-01-06T14:30:00', end: '2025-01-06T16:00:00', className: 'event-orange' },
            { title: 'Reunião de vendas', start: '2025-01-10T10:00:00', end: '2025-01-10T11:00:00', className: 'event-green' },
            { title: 'Inspeção residencial', start: '2025-01-11T11:00:00', end: '2025-01-11T12:30:00', className: 'event-red' },
        ];

        // Sistema de Tema
        function initTheme() {
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            const theme = settings.theme || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        }

        // Inicializar tema ao carregar
        initTheme();

        // Toggle Sidebar Mobile
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const main = document.querySelector('main');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                
                if (main) {
                    main.classList.toggle('content-dimmed');
                }
            }
        };

        // Função para mostrar informações do feriado
        function showHolidayInfo(holidayType, title, infoUrl) {
            const modal = document.getElementById('eventModal');
            let holidayInfoDiv = document.getElementById('holidayInfo');
            
            if (!holidayInfoDiv) {
                holidayInfoDiv = document.createElement('div');
                holidayInfoDiv.id = 'holidayInfo';
                holidayInfoDiv.className = 'holiday-info';
                holidayInfoDiv.innerHTML = `
                    <div class="holiday-info-content">
                        <h4>📅 Informações do Feriado</h4>
                        <p><strong>Tipo:</strong> <span id="holidayType"></span></p>
                        <p><strong>Nome:</strong> <span id="holidayName"></span></p>
                        <a href="#" id="holidayLink" target="_blank" class="holiday-link">
                            🔗 Saiba mais sobre este feriado
                        </a>
                    </div>
                `;
                
                // Inserir antes do botão de salvar
                const saveBtn = document.getElementById('saveBtn');
                if (saveBtn) {
                    saveBtn.parentNode.insertBefore(holidayInfoDiv, saveBtn);
                }
            }
            
            // Preencher informações
            document.getElementById('holidayType').textContent = getHolidayTypeName(holidayType);
            document.getElementById('holidayName').textContent = title;
            document.getElementById('holidayLink').href = infoUrl;
            
            holidayInfoDiv.style.display = 'block';
        }
        
        // Função para esconder informações do feriado
        function hideHolidayInfo() {
            const holidayInfoDiv = document.getElementById('holidayInfo');
            if (holidayInfoDiv) {
                holidayInfoDiv.style.display = 'none';
            }
        }
        
        // Função para obter nome do tipo de feriado
        function getHolidayTypeName(holidayType) {
            const types = {
                'national': 'Feriado Nacional 🇧🇷',
                'state': 'Feriado Estadual 🏛️',
                'municipal': 'Feriado Municipal 🏘️'
            };
            return types[holidayType] || 'Feriado';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            // Initialize FullCalendar
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: false,
                height: 'auto',
                events: sampleEvents,
                dateClick: function(info) {
                    console.log('[v0] Date clicked:', info.dateStr);
                    openModal(info.dateStr);
                },
                eventClick: function(info) {
                    console.log('[v0] Event clicked:', info.event);
                    editEvent(info.event);
                },
                datesSet: function(info) {
                    updateCalendarHeader(info);
                }
            });

            calendar.render();
            // updateCalendarHeader will be called by datesSet callback
            
            // Carregar eventos do servidor
            loadEventsFromServer();
            
            // Carregar feriados do Brasil
            loadHolidaysFromAPI();

            // Event Listeners with error handling
            console.log('[v0] Adding event listeners...');
            
            // Navigation buttons
            const prevBtn = document.getElementById('prevBtn');
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    console.log('[v0] Previous button clicked');
                    calendar.prev();
                });
                console.log('[v0] Previous button listener added');
            } else {
                console.error('[v0] Previous button not found');
            }

            const nextBtn = document.getElementById('nextBtn');
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    console.log('[v0] Next button clicked');
                    calendar.next();
                });
                console.log('[v0] Next button listener added');
            } else {
                console.error('[v0] Next button not found');
            }

            const todayBtn = document.getElementById('todayBtn');
            if (todayBtn) {
                todayBtn.addEventListener('click', () => {
                    console.log('[v0] Today button clicked');
                    calendar.today();
                });
                console.log('[v0] Today button listener added');
            } else {
                console.error('[v0] Today button not found');
            }

            const addEventBtn = document.getElementById('addEventBtn');
            if (addEventBtn) {
                addEventBtn.addEventListener('click', () => {
                    console.log('[v0] Add event button clicked');
                    openModal();
                });
                console.log('[v0] Add event button listener added');
            } else {
                console.error('[v0] Add event button not found');
            }

            // Modal buttons
            const closeModalBtn = document.getElementById('closeModal');
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', closeModal);
                console.log('[v0] Close modal button listener added');
            } else {
                console.error('[v0] Close modal button not found');
            }

            const cancelBtn = document.getElementById('cancelBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
                console.log('[v0] Cancel button listener added');
            } else {
                console.error('[v0] Cancel button not found');
            }
            
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', saveEvent);
                console.log('[v0] Save button listener added');
            } else {
                console.error('[v0] Save button not found');
            }

            const deleteBtn = document.getElementById('deleteBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', deleteEvent);
                console.log('[v0] Delete button listener added');
            } else {
                console.error('[v0] Delete button not found');
            }

            // Color picker
            const colorOptions = document.querySelectorAll('.color-option');
            console.log('[v0] Found color options:', colorOptions.length);
            colorOptions.forEach((option, index) => {
                option.addEventListener('click', function() {
                    console.log('[v0] Color selected:', this.dataset.color);
                    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedColor = this.dataset.color;
                });
                console.log(`[v0] Color option ${index} listener added`);
            });

            // Close modal when clicking outside
            const eventModal = document.getElementById('eventModal');
            if (eventModal) {
                eventModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        console.log('[v0] Modal clicked outside, closing');
                        closeModal();
                    }
                });
                console.log('[v0] Modal outside click listener added');
            } else {
                console.error('[v0] Event modal not found');
            }

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    console.log('[v0] Search input:', e.target.value);
                    // Implement search functionality here
                });
                console.log('[v0] Search input listener added');
            } else {
                console.error('[v0] Search input not found');
            }

            // View dropdown
            const viewDropdown = document.getElementById('viewDropdown');
            const viewDropdownMenu = document.getElementById('viewDropdownMenu');
            
            if (viewDropdown && viewDropdownMenu) {
                // Toggle dropdown menu
                viewDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    console.log('[v0] View dropdown clicked');
                    const isVisible = viewDropdownMenu.style.display !== 'none';
                    viewDropdownMenu.style.display = isVisible ? 'none' : 'block';
                });
                
                // Handle view option clicks
                const viewOptions = document.querySelectorAll('.view-option');
                viewOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const view = this.getAttribute('data-view');
                        const viewName = this.textContent;
                        
                        console.log('[v0] Changing view to:', view);
                        
                        // Change calendar view
                        calendar.changeView(view);
                        
                        // Update dropdown button text
                        viewDropdown.innerHTML = `
                            ${viewName}
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        `;
                        
                        // Hide dropdown menu
                        viewDropdownMenu.style.display = 'none';
                    });
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!viewDropdown.contains(e.target) && !viewDropdownMenu.contains(e.target)) {
                        viewDropdownMenu.style.display = 'none';
                    }
                });
                
                console.log('[v0] View dropdown listener added');
            } else {
                console.error('[v0] View dropdown not found');
            }

            console.log('[v0] All event listeners setup complete');
        });

        function updateCalendarHeader(info) {
            console.log('[v0] Updating calendar header with info:', info);
            
            // Verificar se info e info.view existem
            if (!info || !info.view || !info.view.currentStart) {
                console.error('[v0] Invalid info object for updateCalendarHeader:', info);
                return;
            }
            
            const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            
            const currentDate = info.view.currentStart;
            const month = monthNames[currentDate.getMonth()];
            const year = currentDate.getFullYear();
            
            const currentMonthElement = document.getElementById('currentMonth');
            if (currentMonthElement) {
                currentMonthElement.textContent = `${month} ${year}`;
            }
            
            const startDate = new Date(info.view.currentStart);
            const endDate = new Date(info.view.currentEnd);
            endDate.setDate(endDate.getDate() - 1);
            
            const formatDate = (date) => {
                return `${date.getDate()} de ${monthNames[date.getMonth()].toLowerCase()}, ${date.getFullYear()}`;
            };
            
            const dateRangeElement = document.getElementById('dateRange');
            if (dateRangeElement) {
                dateRangeElement.textContent = `${formatDate(startDate)} — ${formatDate(endDate)}`;
            }
            
            console.log('[v0] Calendar header updated successfully');
        }

        function openModal(dateStr = null) {
            console.log('[v0] Opening modal with date:', dateStr);
            
            const modal = document.getElementById('eventModal');
            if (!modal) {
                console.error('[v0] Modal not found');
                return;
            }
            
            const modalTitle = document.getElementById('modalTitle');
            const deleteBtn = document.getElementById('deleteBtn');
            
            currentEvent = null;
            if (modalTitle) modalTitle.textContent = 'Adicionar Evento';
            if (deleteBtn) deleteBtn.style.display = 'none';
            
            // Reset form
            const eventForm = document.getElementById('eventForm');
            if (eventForm) {
                eventForm.reset();
            }
            
            // Reset color picker
            document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
            const blueOption = document.querySelector('.color-option[data-color="blue"]');
            if (blueOption) {
                blueOption.classList.add('selected');
                selectedColor = 'blue';
            }
            
            // Set date if provided
            if (dateStr) {
                const date = dateStr.split('T')[0];
                const startDateInput = document.getElementById('eventStartDate');
                const endDateInput = document.getElementById('eventEndDate');
                const startTimeInput = document.getElementById('eventStartTime');
                const endTimeInput = document.getElementById('eventEndTime');
                
                if (startDateInput) startDateInput.value = date;
                if (endDateInput) endDateInput.value = date;
                if (startTimeInput) startTimeInput.value = '09:00';
                if (endTimeInput) endTimeInput.value = '10:00';
            }
            
            modal.classList.add('active');
            console.log('[v0] Modal opened successfully');
        }

        function closeModal() {
            console.log('[v0] Closing modal');
            const modal = document.getElementById('eventModal');
            if (modal) {
                modal.classList.remove('active');
                currentEvent = null;
                console.log('[v0] Modal closed successfully');
            } else {
                console.error('[v0] Modal not found when trying to close');
            }
        }

        function editEvent(event) {
            console.log('[v0] editEvent called with event:', event);
            console.log('[v0] Event ID:', event.id);
            console.log('[v0] Event properties:', {
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                classNames: event.classNames
            });
            
            // Verificar se é um evento de feriado (não editável)
            if (event.id && (event.id.startsWith('holiday-national-') || 
                            event.id.startsWith('holiday-state-') || 
                            event.id.startsWith('holiday-municipal-'))) {
                console.log('[v0] Holiday event clicked - showing info only');
                showHolidayInfo(event.extendedProps?.holidayType, event.title, event.extendedProps?.infoUrl);
                openModal();
                return;
            }
            
            // Verificar se o evento tem ID válido para edição
            if (!event.id) {
                console.error('[v0] Event ID not found for editing:', event.id);
                showWarningAlert('Este evento não pode ser editado (sem ID).', 'Atenção');
                return;
            }
            
            // Se for um evento de feriado, mostrar apenas informações
            if (event.id.startsWith('holiday-')) {
                console.log('[v0] Holiday event - showing info only');
                showWarningAlert('Este é um feriado e não pode ser editado.', 'Atenção');
                return;
            }
            
            currentEvent = event;
            
            const modal = document.getElementById('eventModal');
            const modalTitle = document.getElementById('modalTitle');
            const deleteBtn = document.getElementById('deleteBtn');
            
            modalTitle.textContent = 'Editar Evento';
            deleteBtn.style.display = 'block';
            
            // Fill form with event data
            document.getElementById('eventTitle').value = event.title;
            
            const startDate = new Date(event.start);
            const endDate = event.end ? new Date(event.end) : startDate;
            
            document.getElementById('eventStartDate').value = startDate.toISOString().split('T')[0];
            document.getElementById('eventStartTime').value = startDate.toTimeString().slice(0, 5);
            document.getElementById('eventEndDate').value = endDate.toISOString().split('T')[0];
            document.getElementById('eventEndTime').value = endDate.toTimeString().slice(0, 5);
            
            // Set color
            console.log('[v0] Event color properties:', {
                backgroundColor: event.backgroundColor,
                borderColor: event.borderColor,
                color: event.color,
                classNames: event.classNames
            });
            
            // Tentar obter a cor do backgroundColor primeiro
            let eventColor = event.backgroundColor || event.borderColor || event.color;
            
            if (eventColor) {
                // Converter cor hex para nome da cor
                const colorName = getColorName(eventColor);
                console.log('[v0] Color name from hex:', colorName);
                
                if (colorName) {
                    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
                    const colorOption = document.querySelector(`.color-option[data-color="${colorName}"]`);
                    if (colorOption) {
                        colorOption.classList.add('selected');
                        selectedColor = colorName;
                        console.log('[v0] Color selected:', colorName);
                    } else {
                        console.warn('[v0] Color option not found for:', colorName);
                    }
                }
            } else {
                console.warn('[v0] No color found for event');
            }
            
            modal.classList.add('active');
        }

        async function saveEvent() {
            console.log('[v0] Save event function called');
            
            // Validação básica
            const title = document.getElementById('eventTitle').value;
            const startDate = document.getElementById('eventStartDate').value;
            const startTime = document.getElementById('eventStartTime').value;
            const endDate = document.getElementById('eventEndDate').value;
            const endTime = document.getElementById('eventEndTime').value;
            
            if (!title.trim()) {
                showWarningAlert('Por favor, insira um título para o evento.', 'Validação');
                return;
            }
            
            if (!startDate) {
                showWarningAlert('Por favor, selecione uma data de início.', 'Validação');
                return;
            }
            
            // Construir datas
            const start = `${startDate}T${startTime}:00`;
            const end = `${endDate}T${endTime}:00`;
            
            const eventData = {
                title: title,
                start_date: start,
                end_date: end,
                all_day: false,
                color: getColorHex(selectedColor),
                event_type: document.getElementById('eventType').value,
                description: document.getElementById('eventDescription').value || ''
            };
            
            console.log('[v0] Event data:', eventData);
            
            try {
                if (currentEvent) {
                    // Update existing event
                    console.log('[v0] Updating existing event:', currentEvent.id);
                    console.log('[v0] Event data to update:', eventData);
                    
                    // Verificar se o ID do evento existe e é válido
                    if (!currentEvent.id || currentEvent.id.startsWith('holiday-')) {
                        console.error('[v0] Event ID not found or invalid for editing:', currentEvent.id);
                        showInfoAlert('Este evento não pode ser editado. Criando um novo evento...', 'Informação');
                        
                        // Criar novo evento em vez de editar
                        const success = await saveEventToServer(eventData);
                        if (success) {
                            closeModal();
                            await loadEventsFromServer();
                        }
                        return;
                    }
                    
                    const success = await updateEventOnServer(currentEvent.id, eventData);
                    if (success) {
                        currentEvent.setProp('title', title);
                        currentEvent.setStart(start);
                        currentEvent.setEnd(end);
                        currentEvent.setProp('classNames', [`event-${selectedColor}`]);
                        closeModal();
                        // Recarregar eventos do servidor
                        await loadEventsFromServer();
                    }
                } else {
                    // Add new event
                    console.log('[v0] Adding new event');
                    const success = await saveEventToServer(eventData);
                    if (success) {
                        closeModal();
                        // Recarregar eventos do servidor
                        await loadEventsFromServer();
                    }
                }
            } catch (error) {
                console.error('[v0] Error saving event:', error);
                showErrorAlert('Erro ao salvar evento. Tente novamente.', 'Erro');
            }
        }

        // Função para obter cor em hex
        function getColorHex(color) {
            const colors = {
                blue: '#3B82F6',
                green: '#10B981',
                red: '#EF4444',
                orange: '#F59E0B',
                purple: '#8B5CF6',
                pink: '#EC4899',
                indigo: '#6366F1',
                teal: '#14B8A6'
            };
            return colors[color] || '#3B82F6';
        }

        async function deleteEvent() {
            if (currentEvent && confirm('Tem certeza que deseja excluir este evento?')) {
                console.log('[v0] Deleting event:', currentEvent.id);
                try {
                    const success = await deleteEventFromServer(currentEvent.id);
                    if (success) {
                        closeModal();
                        // Recarregar eventos do servidor
                        await loadEventsFromServer();
                    }
                } catch (error) {
                    console.error('[v0] Error deleting event:', error);
                    showErrorAlert('Erro ao excluir evento. Tente novamente.', 'Erro');
                }
            }
        }

        // Função para selecionar cor
        function selectColor(color) {
            document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelector(`.color-option[data-color="${color}"]`).classList.add('selected');
            selectedColor = color;
        }

        // Função para converter cor hex para nome
        function getColorName(hexColor) {
            const colorMap = {
                '#3B82F6': 'blue',
                '#EF4444': 'red', 
                '#10B981': 'green',
                '#F59E0B': 'yellow',
                '#8B5CF6': 'purple',
                '#EC4899': 'pink',
                '#14B8A6': 'teal',
                '#F97316': 'orange',
                '#84CC16': 'lime',
                '#6366F1': 'indigo',
                '#F43F5E': 'rose',
                '#06B6D4': 'cyan'
            };
            
            return colorMap[hexColor] || null;
        }

        // Função para alternar dia todo
        function toggleAllDay() {
            const allDayCheckbox = document.getElementById('allDay');
            const timeInputs = document.querySelectorAll('#eventStartTime, #eventEndTime');
            
            timeInputs.forEach(input => {
                input.disabled = allDayCheckbox.checked;
                if (allDayCheckbox.checked) {
                    input.value = '';
                } else {
                    input.value = input.id === 'eventStartTime' ? '09:00' : '10:00';
                }
            });
        }

        // Função para formatar data
        function formatDate(date) {
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            return date.toLocaleDateString('pt-BR', options);
        }

        // Função para validar formulário
        function validateForm() {
            const title = document.getElementById('eventTitle').value.trim();
            const startDate = document.getElementById('eventStartDate').value;
            const endDate = document.getElementById('eventEndDate').value;
            
            if (!title) {
                showWarningAlert('Por favor, insira um título para o evento.', 'Validação');
                return false;
            }
            
            if (!startDate) {
                showWarningAlert('Por favor, selecione uma data de início.', 'Validação');
                return false;
            }
            
            if (!endDate) {
                showWarningAlert('Por favor, selecione uma data de fim.', 'Validação');
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                showWarningAlert('A data de início não pode ser posterior à data de fim.', 'Validação');
                return false;
            }
            
            return true;
        }

        // Função para salvar evento no servidor
        async function saveEventToServer(eventData) {
            try {
                console.log('[v0] saveEventToServer called with:', eventData);
                
                const response = await fetch('api/events_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(eventData)
                });
                
                console.log('[v0] Response status:', response.status);
                console.log('[v0] Response headers:', response.headers);
                
                // Verificar se a resposta tem conteúdo
                const responseText = await response.text();
                console.log('[v0] Raw response:', responseText);
                
                if (!responseText.trim()) {
                    throw new Error('Resposta vazia do servidor');
                }
                
                const result = JSON.parse(responseText);
                console.log('[v0] Response data:', result);
                
                if (result.success) {
                    console.log('Evento salvo com sucesso:', result);
                    return true;
                } else {
                    console.error('Erro ao salvar evento:', result.message);
                    showErrorAlert('Erro ao salvar evento: ' + result.message, 'Erro');
                    return false;
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                console.error('Erro details:', {
                    message: error.message,
                    stack: error.stack,
                    name: error.name
                });
                showErrorAlert('Erro de conexão ao salvar evento: ' + error.message, 'Erro');
                return false;
            }
        }

        // Função para carregar eventos do servidor
        async function loadEventsFromServer() {
            try {
                const response = await fetch('api/events_simple.php');
                const events = await response.json();
                
                // Limpar eventos existentes
                calendar.removeAllEvents();
                
                // Adicionar eventos do servidor
                events.forEach(event => {
                    console.log('[v0] Adding event to calendar:', event);
                    console.log('[v0] Event color from server:', event.color);
                    
                    const calendarEvent = {
                        id: event.id,
                        title: event.title,
                        start: event.start,
                        end: event.end,
                        allDay: event.all_day,
                        backgroundColor: event.color,
                        borderColor: event.color,
                        textColor: '#ffffff',
                        color: event.color, // Adicionar propriedade color também
                        className: `event-${event.event_type || 'blue'}`
                    };
                    console.log('[v0] Calendar event object:', calendarEvent);
                    calendar.addEvent(calendarEvent);
                });
                
                console.log('Eventos carregados do servidor:', events);
            } catch (error) {
                console.error('Erro ao carregar eventos:', error);
            }
        }

        // Função para carregar feriados do Brasil
        async function loadHolidaysFromAPI() {
            try {
                const currentDate = new Date();
                const year = currentDate.getFullYear();
                
                // API do Brasil API para feriados nacionais
                const nationalResponse = await fetch(`https://brasilapi.com.br/api/feriados/v1/${year}`);
                const nationalHolidays = await nationalResponse.json();
                
                // Adicionar feriados nacionais
                nationalHolidays.forEach(holiday => {
                    calendar.addEvent({
                        id: `holiday-national-${holiday.date}`,
                        title: `${holiday.name}`,
                        start: holiday.date,
                        allDay: true,
                        color: '#dc2626', // Vermelho forte
                        className: 'holiday-national',
                        display: 'block',
                        backgroundColor: '#dc2626',
                        borderColor: '#b91c1c',
                        extendedProps: {
                            holidayType: 'national',
                            infoUrl: `https://pt.wikipedia.org/wiki/${encodeURIComponent(holiday.name)}`
                        }
                    });
                    
                    // Marcar o dia como tendo feriado
                    const holidayDate = new Date(holiday.date);
                    const dayElement = document.querySelector(`[data-date="${holiday.date}"]`);
                    if (dayElement) {
                        dayElement.classList.add('fc-day-has-holiday');
                    }
                });
                
                // Feriados estaduais do Ceará
                const cearaHolidays = [
                    { date: `${year}-03-25`, name: 'Dia do Ceará' },
                    { date: `${year}-08-15`, name: 'Nossa Senhora da Assunção' },
                    { date: `${year}-11-15`, name: 'Proclamação da República' }
                ];
                
                cearaHolidays.forEach(holiday => {
                    calendar.addEvent({
                        id: `holiday-state-${holiday.date}`,
                        title: `${holiday.name}`,
                        start: holiday.date,
                        allDay: true,
                        color: '#ef4444', // Vermelho médio
                        className: 'holiday-state',
                        display: 'block',
                        backgroundColor: '#ef4444',
                        borderColor: '#dc2626',
                        extendedProps: {
                            holidayType: 'state',
                            infoUrl: `https://pt.wikipedia.org/wiki/${encodeURIComponent(holiday.name)}`
                        }
                    });
                    
                    // Marcar o dia como tendo feriado
                    const dayElement = document.querySelector(`[data-date="${holiday.date}"]`);
                    if (dayElement) {
                        dayElement.classList.add('fc-day-has-holiday');
                    }
                });
                
                // Feriados municipais de Maranguape
                const maranguapeHolidays = [
                    { date: `${year}-01-20`, name: 'São Sebastião - Padroeiro de Maranguape' },
                    { date: `${year}-03-25`, name: 'Dia do Município de Maranguape' },
                    { date: `${year}-06-13`, name: 'Santo Antônio' },
                    { date: `${year}-12-08`, name: 'Nossa Senhora da Conceição' }
                ];
                
                maranguapeHolidays.forEach(holiday => {
                    calendar.addEvent({
                        id: `holiday-municipal-${holiday.date}`,
                        title: `${holiday.name}`,
                        start: holiday.date,
                        allDay: true,
                        color: '#f87171', // Vermelho claro
                        className: 'holiday-municipal',
                        display: 'block',
                        backgroundColor: '#f87171',
                        borderColor: '#ef4444',
                        extendedProps: {
                            holidayType: 'municipal',
                            infoUrl: `https://pt.wikipedia.org/wiki/${encodeURIComponent(holiday.name)}`
                        }
                    });
                    
                    // Marcar o dia como tendo feriado
                    const dayElement = document.querySelector(`[data-date="${holiday.date}"]`);
                    if (dayElement) {
                        dayElement.classList.add('fc-day-has-holiday');
                    }
                });
                
                console.log('Feriados carregados:', {
                    nacional: nationalHolidays.length,
                    estadual: cearaHolidays.length,
                    municipal: maranguapeHolidays.length
                });
                
                // Aplicar classe aos dias com feriados após um pequeno delay
                setTimeout(() => {
                    applyHolidayClassToDays();
                }, 500);
                
            } catch (error) {
                console.error('Erro ao carregar feriados:', error);
            }
        }
        
        // Função para aplicar classe aos dias com feriados
        function applyHolidayClassToDays() {
            // Aplicar para feriados nacionais (buscar elementos com classe holiday-national)
            const nationalElements = document.querySelectorAll('.holiday-national');
            nationalElements.forEach(element => {
                const dayElement = element.closest('.fc-daygrid-day');
                if (dayElement) {
                    dayElement.classList.add('fc-day-has-holiday');
                }
            });
            
            // Aplicar para feriados estaduais (buscar elementos com classe holiday-state)
            const stateElements = document.querySelectorAll('.holiday-state');
            stateElements.forEach(element => {
                const dayElement = element.closest('.fc-daygrid-day');
                if (dayElement) {
                    dayElement.classList.add('fc-day-has-holiday');
                }
            });
            
            // Aplicar para feriados municipais (buscar elementos com classe holiday-municipal)
            const municipalElements = document.querySelectorAll('.holiday-municipal');
            municipalElements.forEach(element => {
                const dayElement = element.closest('.fc-daygrid-day');
                if (dayElement) {
                    dayElement.classList.add('fc-day-has-holiday');
                }
            });
        }

        // Função para atualizar evento no servidor
        async function updateEventOnServer(eventId, eventData) {
            try {
                console.log('[v0] updateEventOnServer called with:', { eventId, eventData });
                
                const url = `api/events_simple.php?id=${eventId}`;
                console.log('[v0] Request URL:', url);
                
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(eventData)
                });
                
                console.log('[v0] Response status:', response.status);
                console.log('[v0] Response headers:', response.headers);
                
                // Verificar se a resposta tem conteúdo
                const responseText = await response.text();
                console.log('[v0] Raw response:', responseText);
                
                if (!responseText.trim()) {
                    throw new Error('Resposta vazia do servidor');
                }
                
                const result = JSON.parse(responseText);
                console.log('[v0] Response data:', result);
                
                if (result.success) {
                    console.log('Evento atualizado com sucesso:', result);
                    return true;
                } else {
                    console.error('Erro ao atualizar evento:', result.message);
                    showErrorAlert('Erro ao atualizar evento: ' + result.message, 'Erro');
                    return false;
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                console.error('Erro details:', {
                    message: error.message,
                    stack: error.stack,
                    name: error.name
                });
                showErrorAlert('Erro de conexão ao atualizar evento: ' + error.message, 'Erro');
                return false;
            }
        }

        // Função para deletar evento do servidor
        async function deleteEventFromServer(eventId) {
            try {
                const response = await fetch(`api/events_simple.php?id=${eventId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Evento deletado com sucesso:', result);
                    return true;
                } else {
                    console.error('Erro ao deletar evento:', result.message);
                    alert('Erro ao deletar evento: ' + result.message);
                    return false;
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Erro de conexão ao deletar evento.');
                return false;
            }
        }

        // Função para exportar calendário
        function exportCalendar() {
            const events = calendar.getEvents();
            const icalData = generateICal(events);
            
            const blob = new Blob([icalData], { type: 'text/calendar' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = 'calendario.ics';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Função para gerar iCal
        function generateICal(events) {
            let ical = 'BEGIN:VCALENDAR\n';
            ical += 'VERSION:2.0\n';
            ical += 'PRODID:-//SIGEA//Calendário//PT\n';
            ical += 'CALSCALE:GREGORIAN\n';
            
            events.forEach(event => {
                ical += 'BEGIN:VEVENT\n';
                ical += `UID:${event.id || Date.now()}@sigea.com\n`;
                ical += `DTSTART:${formatDateForICal(event.start)}\n`;
                if (event.end) {
                    ical += `DTEND:${formatDateForICal(event.end)}\n`;
                }
                ical += `SUMMARY:${event.title}\n`;
                ical += 'END:VEVENT\n';
            });
            
            ical += 'END:VCALENDAR\n';
            return ical;
        }

        // Função para formatar data para iCal
        function formatDateForICal(date) {
            const d = new Date(date);
            return d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        }

        // Função para importar calendário
        function importCalendar() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.ics,.csv';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (file.name.endsWith('.ics')) {
                            parseICal(e.target.result);
                        } else if (file.name.endsWith('.csv')) {
                            parseCSV(e.target.result);
                        }
                    };
                    reader.readAsText(file);
                }
            };
            input.click();
        }

    </script>
</body>
</html>