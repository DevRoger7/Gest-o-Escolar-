<?php
require_once(__DIR__ . '/../models/sessions.php');
$session = new sessions();
$session->autenticar_session();
$session->tempo_session();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="content-language" content="pt-BR">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="theme-color" content="#005A24">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>Subsistemas - stgm </title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="Subsistemas do Sistema CREDE 1 - Coordenadoria Regional de Desenvolvimento da Educação">
    <meta name="author" content="CREDE 1">
    <meta name="keywords" content="subsistemas, CREDE 1, sistema, educação, controle de estoque">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://i.postimg.cc/Dy40VtFL/Design-sem-nome-13-removebg-preview.png">

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#007A33',
                        secondary: '#FFA500',
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --background-color: #f0f7ff;
            --text-color: #333333;
            --header-color: #007A33;
            --icon-bg: #ffffff;
            --icon-shadow: rgba(0, 0, 0, 0.1);
            --accent-color: #FFA500;
            --grid-color: #e0e0e0;
            --card-bg: rgba(255, 255, 255, 0.9);
            --header-bg: rgba(255, 255, 255, 0.95);
            --mobile-nav-bg: rgba(255, 255, 255, 0.95);
            --search-bar-bg: white;
            --card-border-hover: var(--header-color);
        }

        .dark {
            --background-color: #1a1a1a;
            --text-color: #ffffff;
            --header-color: #00b348;
            --icon-bg: #2d2d2d;
            --icon-shadow: rgba(0, 0, 0, 0.3);
            --accent-color: #ffb733;
            --grid-color: #333333;
            --card-bg: rgba(45, 45, 45, 0.9);
            --header-bg: rgba(28, 28, 28, 0.95);
            --mobile-nav-bg: rgba(28, 28, 28, 0.95);
            --search-bar-bg: #2d2d2d;
            --card-border-hover: var(--accent-color);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--background-color);
            min-height: 100vh;
            padding-top: 80px;
            background-image: radial-gradient(circle at 10% 20%, rgba(52, 152, 219, 0.05) 0%, rgba(52, 152, 219, 0) 20%), radial-gradient(circle at 90% 80%, rgba(46, 204, 113, 0.05) 0%, rgba(46, 204, 113, 0) 20%);
            color: var(--text-color);
            transition: background-color 0.3s ease;
        }

        @media (max-width: 768px) {
            body {
                padding-bottom: 100px;
            }
        }

        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulseSoft {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* Header melhorado */
        .header-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #F8FAF9 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 90, 36, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .header-gradient::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #005A24 0%, #FFA500 50%, #005A24 100%);
            box-shadow: 0 2px 8px rgba(0, 90, 36, 0.3);
        }

        .header-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(0, 90, 36, 0.02), transparent);
            pointer-events: none;
        }

        .logo-container {
            background: linear-gradient(135deg, #005A24 0%, #1A3C34 100%);
            box-shadow: 0 4px 12px rgba(0, 90, 36, 0.3);
        }

        /* Search bar aprimorada */
        .search-container {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .search-input {
            width: 100%;
            padding: 16px 24px 16px 56px;
            border: 2px solid rgba(0, 90, 36, 0.2);
            border-radius: 16px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            outline: none;
            border-color: #FFA500;
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.1), 0 8px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .search-icon {
            position: absolute;
            left: 44px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .search-input:focus+.search-icon {
            color: #FFA500;
        }

        .grid-container.transitioning {
            transition: all 0.3s ease-out;
        }

        .app-card-link {
            transition: all 0.3s ease-out;
            width: 100%;
        }

        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
                padding: 1rem;
            }

            .app-card {
                min-height: 150px;
                padding: 1rem;
            }
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            transition: all 0.3s ease-out;
        }

        .app-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            height: 100%;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .clear-search {

            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);


            background: none;
            border: none;
            border-radius: 50%;
            padding: 8px;
            margin: 0;

            color: #666;
            transition: all 0.2s ease;
            opacity: 0.7;


            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;

            min-width: 32px;
            min-height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .clear-search::before {
            content: "×";
            font-size: 20px;
            font-weight: 500;
            line-height: 1;
        }

        .clear-search:hover {
            color: #333;
            opacity: 1;

        }

        .clear-search:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
        }


        .search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        .app-card-link {
            transition: all 0.3s ease-out;
        }

        .transitioning {
            pointer-events: none;
        }

        .app-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }

        .app-card:hover::before {
            transform: translateX(100%);
        }

        .app-card:hover {
            transform: translateY(-5px) scale(1.02);
            border-color: var(--card-border-hover);
            box-shadow: 0 20px 30px rgba(0, 122, 51, 0.1);
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: var(--icon-bg);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 8px 16px var(--icon-shadow);
        }

        .app-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 24px var(--icon-shadow);
        }

        .app-icon {
            width: 50px;
            height: 50px;
            transition: transform 0.3s ease;
        }

        .app-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-top: 1rem;
            transition: color 0.3s ease;
        }

        .app-card:hover .app-name {
            color: var(--header-color);
        }

        .main-header {
            background: var(--header-bg);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 2px solid rgba(0, 122, 51, 0.1);
            transition: all 0.3s ease;
        }

        .main-header.scrolled {
            background: var(--header-bg);
            box-shadow: 0 4px 20px var(--icon-shadow);
        }

        .nav-link {
            position: relative;
            padding: 0.5rem 1rem;
            color: var(--text-color);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--header-color);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .category-tag {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            background: rgba(0, 122, 51, 0.1);
            color: var(--header-color);
            margin-top: 0.5rem;
            display: inline-block;
        }

        .search-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .search-bar {
            width: 100%;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            border: 2px solid rgba(0, 122, 51, 0.2);
            background: var(--search-bar-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--header-color);
            box-shadow: 0 4px 12px rgba(0, 122, 51, 0.1);
        }

        /* Gradientes para ícones */
        .icon-primary {
            background: linear-gradient(135deg, #005A24 0%, #1A3C34 100%);
        }

        .icon-purple {
            background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
        }

        .icon-orange {
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
        }

        .icon-green {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }

        .icon-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
        }

        /* Responsividade melhorada */
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 0 16px;
                max-width: 100%;
            }

            .search-container {
                padding: 0 16px;
                max-width: 100%;
            }

            .system-card {
                padding: 24px 20px;
            }

            .card-icon {
                width: 64px;
                height: 64px;
            }

            .card-icon img {
                width: 64px;
                height: 64px;
            }

            .search-input {
                padding: 14px 20px 14px 48px;
                font-size: 16px;
            }

            .search-icon {
                left: 20px;
                font-size: 16px;
            }
        }

        @media (min-width: 769px) {
            .mobile-footer {
                display: none !important;
            }

            .header-content {
                justify-content: space-between;
            }

            body {
                padding-bottom: 0;
            }

            /* Animação de entrada do header */
            .header-gradient {
                animation: slideDown 0.6s ease-out;
            }

            @keyframes slideDown {
                from {
                    transform: translateY(-100%);
                    opacity: 0;
                }

                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: row;
                gap: 16px;
                text-align: center;
                justify-content: center;
            }

            .mobile-footer {
                display: block !important;
            }

            body {
                padding-bottom: 100px;
            }
        }

        @media (max-width: 480px) {
            .header-content {
                gap: 12px;
            }

            .header-nav {
                gap: 8px;
            }

            .header-nav a {
                padding: 10px;
                font-size: 14px;
            }

            .header-nav button {
                width: 40px;
                height: 40px;
            }

            .search-input {
                padding: 12px 16px 12px 44px;
                font-size: 14px;
            }

            .search-icon {
                left: 16px;
                font-size: 14px;
            }
        }

        /* Estados de loading e feedback */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .card-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Footer Mobile */
        .mobile-footer {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 16px 0 24px 0;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 20px 20px 0 0;
        }

        .footer-buttons {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 12px;
            min-width: 60px;
        }

        .footer-btn i {
            font-size: 20px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }

        .footer-btn .footer-text {
            font-size: 11px;
            font-weight: 500;
            line-height: 1.2;
        }

        .footer-btn:hover {
            color: #005A24;
            background: rgba(0, 90, 36, 0.05);
        }

        .footer-btn.active {
            color: #005A24;
            background: rgba(0, 90, 36, 0.1);
        }

        .footer-btn.active i {
            transform: scale(1.1);
        }

        /* Painéis de Acessibilidade e Perfil */
        .accessibility-panel,
        .profile-panel {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            backdrop-filter: blur(5px);
        }

        .accessibility-content,
        .profile-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 20px 20px 0 0;
            padding: 24px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .accessibility-header,
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .accessibility-header h3,
        .profile-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .close-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f3f4f6;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .option-group {
            margin-bottom: 24px;
        }

        .option-group h4 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .option-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .option-btn {
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .option-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .option-btn.active {
            background: #005A24;
            color: #ffffff;
            border-color: #005A24;
        }

        /* Perfil */
        .profile-info {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 12px;
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #005A24;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 24px;
        }

        .profile-details h4 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .profile-details p {
            color: #6b7280;
            margin-bottom: 4px;
        }

        .profile-role {
            display: inline-block;
            padding: 4px 8px;
            background: #005A24;
            color: #ffffff;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .profile-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-action-btn:hover {
            background: #f9fafb;
            border-color: #005A24;
            color: #005A24;
        }

        .profile-action-btn i {
            width: 16px;
            color: #6b7280;
        }

        /* Botões do Header (Desktop) */
        .header-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(0, 90, 36, 0.1);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #005A24;
            font-size: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .header-btn:hover {
            background: rgba(0, 90, 36, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 90, 36, 0.2);
        }

        .header-btn:active {
            transform: translateY(0);
        }

        /* Botões do Header com Texto (Desktop) */
        .header-btn-with-text {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            padding: 12px 16px;
            border-radius: 16px;
            min-width: 70px;
            position: relative;
            overflow: hidden;
        }

        .header-btn-with-text::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 90, 36, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .header-btn-with-text:hover::before {
            left: 100%;
        }

        .header-btn-with-text i {
            font-size: 20px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .header-btn-with-text span {
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
            position: relative;
            z-index: 1;
            letter-spacing: 0.3px;
        }

        .header-btn-with-text:hover {
            color: #005A24;
            background: rgba(0, 90, 36, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 90, 36, 0.15);
        }

        .header-btn-with-text:hover i {
            transform: scale(1.15) translateY(-2px);
        }

        .header-btn-with-text:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 90, 36, 0.1);
        }

        /* Botão Sair especial */
        .header-btn-with-text.text-red-600 {
            color: #dc2626;
        }

        .header-btn-with-text.text-red-600:hover {
            color: #b91c1c;
            background: rgba(220, 38, 38, 0.08);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
        }

        .header-btn-with-text.text-red-600::before {
            background: linear-gradient(90deg, transparent, rgba(220, 38, 38, 0.1), transparent);
        }

        /* Classes de Acessibilidade */
        .contrast-high {
            filter: contrast(1.5);
        }

        .contrast-inverted {
            filter: invert(1) hue-rotate(180deg);
        }

        .font-large {
            font-size: 1.2em !important;
        }

        .font-large .card-title {
            font-size: 24px !important;
        }

        .font-large .footer-text {
            font-size: 13px !important;
        }

        .font-extra-large {
            font-size: 1.4em !important;
        }

        .font-extra-large .card-title {
            font-size: 28px !important;
        }

        .font-extra-large .footer-text {
            font-size: 15px !important;
        }



        /* Acessibilidade */
        .system-card:focus {
            outline: 3px solid #FFA500;
            outline-offset: 2px;
        }

        .search-input:focus {
            outline: none;
        }

        /* Scrollbar customizada */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #005A24 0%, #FFA500 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #1A3C34 0%, #E76F51 100%);
        }

        /* Animações de entrada */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Botão de Acessibilidade Desktop */
        .accessibility-fab {
            animation: float 3s ease-in-out infinite;
        }

        .accessibility-fab:hover {
            animation: none;
            transform: scale(1.1);
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(0, 90, 36, 0.7);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(0, 90, 36, 0);
            }
        }

        .accessibility-fab {
            animation: pulse-glow 2s infinite;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="https://i.postimg.cc/Dy40VtFL/Design-sem-nome-13-removebg-preview.png" alt="Logo"
                        class="h-12 w-auto object-contain">
                    <div>
                        <h1 class="md:text-xl lg:text-lg font-bold text-primary ">Subsistemas <span
                                class="text-secondary">STGM</span>
                        </h1>
                        <div class="h-0.5 bg-primary/20 rounded-full mt-1"></div>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-5">
                    <a href="../../" class="nav-link">Início</a>
                    <a href="../models/sessions.php?sair" class="nav-link">Sair</a>
                    <button id="darkModeToggle"
                        class="inline-flex items-center justify-center p-2 rounded-lg transition-colors" role="switch"
                        aria-label="Alternar modo escuro">
                        <svg class="w-5 h-5 sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-5 h-5 moon-icon hidden" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span class="sr-only">Alternar modo escuro</span>
                    </button>

                    <div class="relative">
                        <button id="accessibilityBtnDesktop"
                            class="flex items-center gap-2 p-2 rounded-lg transition-colors duration-300"
                            aria-expanded="false" aria-haspopup="true">
                            <i class="fa-solid fa-universal-access text-xl"></i>
                        </button>

                        <div id="accessibilityMenuDesktop"
                            class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg py-2 hidden z-50"
                            role="menu">
                            <div class="px-4 py-2 border-b" style="color: #1a1a1a;">
                                <span class="block text-sm font-semibold mb-2">Tamanho do Texto</span>
                                <div class="flex gap-2">
                                    <button class="p-2 hover:bg-gray-100 rounded"
                                        aria-label="Diminuir tamanho do texto"><i
                                            class="fa-solid fa-a"></i><b>-</b></button>
                                    <button class="p-2 hover:bg-gray-100 rounded"
                                        aria-label="Tamanho padrão do texto"><i class="fa-solid fa-a"></i></button>
                                    <button class="p-2 hover:bg-gray-100 rounded"
                                        aria-label="Aumentar tamanho do texto"><i
                                            class="fa-solid fa-a"></i><b>+</b></button>
                                </div>
                            </div>

                            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2"
                                style="color: #1a1a1a;">
                                <i class="fa-solid fa-ear-listen"></i>
                                <span>Leitor de Tela</span>
                            </button>

                            <button id="themeBtnDesktop"
                                class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center justify-between"
                                style="color: #1a1a1a;">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-half-stroke"></i>
                                    <span>Temas de Contraste</span>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <div id="themeMenuDesktop"
                            class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg py-2 hidden z-50">
                            <div class="flex items-center px-4 py-2 border-b">
                                <button id="backToMainMenuDesktop" class="mr-2" style="color: #1a1a1a;">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                                <span class="font-semibold">Temas de Contraste</span>
                            </div>

                            <div class="py-2" style="color: #1a1a1a;">
                                <button class="w-full px-4 py-2 text-left hover:bg-gray-100"
                                    data-theme="monochrome">Monocromático</button>
                                <button class="w-full px-4 py-2 text-left hover:bg-gray-100"
                                    data-theme="inverted-grayscale">Escala de cinza invertida</button>
                                <button class="w-full px-4 py-2 text-left hover:bg-gray-100"
                                    data-theme="inverted-color">Cor invertida</button>
                                <button class="w-full px-4 py-2 text-left hover:bg-gray-100" data-theme="original">Cores
                                    originais</button>
                            </div>
                        </div>
                    </div>
                    <!-- Profile dropdown placeholder -->
                </nav>

                <div class="md:hidden">
                    <!-- Mobile profile placeholder -->
                </div>
            </div>
        </div>
    </header>

    <div class="search-container">
        <input type="text" class="search-bar" placeholder="Buscar aplicativos..." id="search-input">
    </div>
    <main class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-8 md:gap-8 p-4 md:p-8 max-w-[1400px] mx-auto transition-all duration-300 grid-container">

                <!-- Card 1: Gerenciador de Usuários -->
                <?php if (isset($_SESSION['Gerenciador de Usuarios'])) { ?>
                    <a href="../../subsystems/gerenciador_usuario/index.php" class="app-card-link">
                        <div class="app-card w-{100px} h-full">
                            <div class="icon-wrapper">
                                <img src="https://i.postimg.cc/ZqY5Z9TG/useradm.png" alt="Ícone Gerenciador de Usuários" class="app-icon">
                            </div>
                            <h3 class="app-name">Gerenciador de Usuários</h3>
                            <span class="category-tag">Sistema</span>
                        </div>
                    </a>
                <?php } ?>

                <!-- Card 2: Controle de Estoque -->
                <?php if (isset($_SESSION['Estoque'])) { ?>
                    <a href="../../subsystems/controle_de_estoque/default.php" class="app-card-link">
                        <div class="app-card w-{100px} h-full">
                            <div class="icon-wrapper">
                                <img src="https://i.postimg.cc/wT8bLxNS/Design-sem-nome-7.png" alt="Ícone Controle de Estoque" class="app-icon">
                            </div>
                            <h3 class="app-name">Controle de Estoque</h3>
                            <span class="category-tag">Sistema</span>
                        </div>
                    </a>
                <?php } ?>
                <!-- Card 2: Controle de Estoque -->
                <?php if (isset($_SESSION['Biblioteca'])) { ?>
                    <a href="../../subsystems/biblioteca/default.php" class="app-card-link">
                        <div class="app-card w-{100px} h-full">
                            <div class="icon-wrapper">
                                <img src="https://i.postimg.cc/4dvShV26/icon-biblioteca.png" alt="Ícone Controle de Estoque" class="app-icon">
                            </div>
                            <h3 class="app-name">Biblioteca</h3>
                            <span class="category-tag">Sistema</span>
                        </div>
                    </a>
                <?php } ?>
                <!-- Card 2: Controle de Estoque -->
                <?php if (isset($_SESSION['Entrada/saída'])) { ?>
                    <a href="../../subsystems/entradasaida/app/main/views/inicio.php" class="app-card-link">
                        <div class="app-card w-{100px} h-full">
                            <div class="icon-wrapper">
                                <img src="https://i.postimg.cc/j2mdwpFn/icon-entrada-e-saida.png" alt="Ícone Controle de Estoque" class="app-icon">
                            </div>
                            <h3 class="app-name">Entrada/saída</h3>
                            <span class="category-tag">Sistema</span>
                        </div>
                    </a>
                <?php } ?>

            </div>
    </main>

    <!-- Botão de Acessibilidade (Desktop) -->
    <div class="hidden md:block fixed bottom-6 right-6 z-50">
        <button onclick="toggleAccessibility()"
            class="w-14 h-14 rounded-full bg-primary hover:bg-dark text-white shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group accessibility-fab"
            title="Acessibilidade">
            <i class="fas fa-universal-access text-xl group-hover:scale-110 transition-transform duration-300"></i>
        </button>
    </div>

    <!-- Painel de Acessibilidade -->
    <div id="accessibilityPanel" class="accessibility-panel">
        <div class="accessibility-content">
            <div class="accessibility-header">
                <h3>Acessibilidade</h3>
                <button onclick="toggleAccessibility()" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="accessibility-options">
                <div class="option-group">
                    <h4>Contraste</h4>
                    <div class="option-buttons">
                        <button onclick="setContrast('normal')" class="option-btn active">Normal</button>
                        <button onclick="setContrast('high')" class="option-btn">Alto</button>
                        <button onclick="setContrast('inverted')" class="option-btn">Invertido</button>
                    </div>
                </div>
                <div class="option-group">
                    <h4>Tamanho do Texto</h4>
                    <div class="option-buttons">
                        <button onclick="setFontSize('normal')" class="option-btn active">Normal</button>
                        <button onclick="setFontSize('large')" class="option-btn">Grande</button>
                        <button onclick="setFontSize('extra-large')" class="option-btn">Extra Grande</button>
                    </div>
                </div>

                <div class="option-group">
                    <h4>Navegação por Voz</h4>
                    <div class="option-buttons">
                        <button onclick="toggleVoiceNavigation()" class="option-btn">Ativar</button>
                        <button onclick="toggleTextToSpeech()" class="option-btn">Leitura</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel de Perfil -->
    <div id="profilePanel" class="profile-panel">
        <div class="profile-content">
            <div class="profile-header">
                <h3>Perfil do Usuário</h3>
                <button onclick="toggleProfile()" class="close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="profile-info">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-details">
                    <h4><?= $_SESSION['nome'] ?></h4>
                    <p><?= $_SESSION['email'] ?></p>
                    <span class="profile-role"><?= $_SESSION['setor'] ?></span>
                </div>
            </div>
            <div class="profile-actions">
                <button class="profile-action-btn">
                    <i class="fas fa-user-edit"></i>
                    Editar Perfil
                </button>
                <button class="profile-action-btn">
                    <i class="fas fa-cog"></i>
                    Configurações
                </button>
                <button class="profile-action-btn">
                    <i class="fas fa-question-circle"></i>
                    Ajuda
                </button>
            </div>
        </div>
    </div>

    <!-- Footer com Botões (Mobile) -->
    <footer class="mobile-footer">
        <div class="footer-buttons">
            <button class="footer-btn active" title="Início">
                <i class="fas fa-home"></i>
                <span class="footer-text">Início</span>
            </button>
            <button class="footer-btn" title="Sair" onclick="window.location.href='../models/sessions.php?sair'">
                <i class="fas fa-sign-out-alt"></i>
                <span class="footer-text">Sair</span>
            </button>
            <button class="footer-btn" title="Acessibilidade" onclick="toggleAccessibility()">
                <i class="fas fa-universal-access"></i>
                <span class="footer-text">Acessibilidade</span>
            </button>
            <button class="footer-btn" title="Perfil do usuário" onclick="window.location.href='perfil.php'">
                <i class="fas fa-user"></i>
                <span class="footer-text">Perfil</span>
            </button>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos
            const searchInput = document.getElementById('searchSubsystems');
            const cards = document.querySelectorAll('.system-card');
            const animatedElements = document.querySelectorAll('.animate-on-scroll');

            // Função de busca melhorada
            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                cards.forEach((card, index) => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('p').textContent.toLowerCase();
                    const isVisible = title.includes(searchTerm) || description.includes(searchTerm);

                    if (isVisible) {
                        card.style.display = 'block';
                        card.style.animationDelay = `${visibleCount * 0.1}s`;
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Feedback visual se nenhum resultado
                const noResults = document.getElementById('noResults');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResults) {
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.id = 'noResults';
                        noResultsDiv.className = 'text-center py-12 animate-fade-in';
                        noResultsDiv.innerHTML = `
                            <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum resultado encontrado</h3>
                            <p class="text-gray-500">Tente buscar por outros termos</p>
                        `;
                        document.querySelector('.card-grid').appendChild(noResultsDiv);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            }

            // Event listeners para busca
            searchInput.addEventListener('input', performSearch);
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });

            // Navegação por teclado nos cards
            cards.forEach(card => {
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');

                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });

            // Navegação por teclado no footer
            const footerButtons = document.querySelectorAll('.footer-btn');
            footerButtons.forEach(btn => {
                btn.setAttribute('tabindex', '0');
            });

            // Função de navegação melhorada
            window.navigateToSystem = function(url) {
                const card = event.currentTarget;
                card.classList.add('loading');

                // Feedback visual
                const icon = card.querySelector('.card-icon');
                icon.style.transform = 'scale(0.9)';

                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            };

            // Funções de Acessibilidade e Perfil
            window.toggleAccessibility = function() {
                const panel = document.getElementById('accessibilityPanel');
                const isVisible = panel.style.display === 'block';
                panel.style.display = isVisible ? 'none' : 'block';

                // Fechar painel de perfil se estiver aberto
                if (!isVisible) {
                    document.getElementById('profilePanel').style.display = 'none';
                }
            };

            window.toggleProfile = function() {
                const panel = document.getElementById('profilePanel');
                const isVisible = panel.style.display === 'block';
                panel.style.display = isVisible ? 'none' : 'block';

                // Fechar painel de acessibilidade se estiver aberto
                if (!isVisible) {
                    document.getElementById('accessibilityPanel').style.display = 'none';
                }
            };

            // Funções de Acessibilidade
            window.setContrast = function(contrast) {
                const body = document.body;
                const buttons = document.querySelectorAll('.option-group:nth-child(1) .option-btn');

                // Remove classes ativas do grupo de contraste
                buttons.forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');

                // Remove classes de contraste anteriores
                body.classList.remove('contrast-high', 'contrast-inverted');

                switch (contrast) {
                    case 'high':
                        body.classList.add('contrast-high');
                        break;
                    case 'inverted':
                        body.classList.add('contrast-inverted');
                        break;
                }
            };

            window.setFontSize = function(size) {
                const body = document.body;
                const buttons = document.querySelectorAll('.option-group:nth-child(2) .option-btn');

                // Remove classes ativas do grupo de tamanho
                buttons.forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');

                // Remove classes de tamanho anteriores
                body.classList.remove('font-large', 'font-extra-large');

                switch (size) {
                    case 'large':
                        body.classList.add('font-large');
                        break;
                    case 'extra-large':
                        body.classList.add('font-extra-large');
                        break;
                }
            };



            // Navegação por voz
            window.toggleVoiceNavigation = function() {
                const btn = event.target;
                if (btn.textContent === 'Ativar') {
                    btn.textContent = 'Desativar';
                    btn.style.background = '#dc2626';
                    btn.style.color = '#ffffff';

                    try {
                        // Verificar se o navegador suporta reconhecimento de voz
                        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

                        if (SpeechRecognition) {
                            window.voiceRecognition = new SpeechRecognition();
                            window.voiceRecognition.continuous = true;
                            window.voiceRecognition.interimResults = false;
                            window.voiceRecognition.lang = 'pt-BR';

                            window.voiceRecognition.onstart = function() {
                                console.log('Reconhecimento de voz iniciado');
                                const announcer = document.getElementById('screen-reader-announcer');
                                if (announcer) {
                                    announcer.textContent = 'Navegação por voz ativada. Diga "início", "sair", "estoque", "perfil" ou "acessibilidade".';
                                }
                            };

                            window.voiceRecognition.onresult = function(event) {
                                const command = event.results[event.results.length - 1][0].transcript.toLowerCase();
                                console.log('Comando reconhecido:', command);

                                // Comandos de voz
                                if (command.includes('início') || command.includes('home')) {
                                    document.querySelector('.footer-btn[title="Início"]').click();
                                } else if (command.includes('sair') || command.includes('logout')) {
                                    document.querySelector('.footer-btn[title="Sair"]').click();
                                } else if (command.includes('usuários') || command.includes('gerenciador')) {
                                    document.querySelector('[data-system="usuarios"]').click();
                                } else if (command.includes('estoque') || command.includes('controle')) {
                                    document.querySelector('[data-system="estoque"]').click();
                                } else if (command.includes('perfil')) {
                                    window.location.href = 'perfil.php';
                                } else if (command.includes('acessibilidade')) {
                                    document.querySelector('.footer-btn[title="Acessibilidade"]').click();
                                }

                                // Anunciar comando reconhecido
                                const announcer = document.getElementById('screen-reader-announcer');
                                if (announcer) {
                                    announcer.textContent = `Comando reconhecido: ${command}`;
                                }
                            };

                            window.voiceRecognition.onerror = function(event) {
                                console.error('Erro no reconhecimento de voz:', event.error);
                                alert(`Erro no reconhecimento de voz: ${event.error}. Verifique se o microfone está conectado e permitido.`);
                                btn.textContent = 'Ativar';
                                btn.style.background = '#005A24';
                                btn.style.color = '#ffffff';
                            };

                            window.voiceRecognition.onend = function() {
                                console.log('Reconhecimento de voz finalizado');
                            };

                            window.voiceRecognition.start();

                        } else {
                            throw new Error('API de reconhecimento de voz não suportada');
                        }
                    } catch (error) {
                        console.error('Erro ao inicializar reconhecimento de voz:', error);
                        alert('Seu navegador não suporta reconhecimento de voz ou o microfone não está disponível. Tente usar Chrome, Edge ou Safari.');
                        btn.textContent = 'Ativar';
                        btn.style.background = '#005A24';
                        btn.style.color = '#ffffff';
                    }
                } else {
                    btn.textContent = 'Ativar';
                    btn.style.background = '#005A24';
                    btn.style.color = '#ffffff';

                    // Parar reconhecimento de voz
                    if (window.voiceRecognition) {
                        try {
                            window.voiceRecognition.stop();
                        } catch (error) {
                            console.error('Erro ao parar reconhecimento de voz:', error);
                        }
                    }

                    // Anunciar desativação
                    const announcer = document.getElementById('screen-reader-announcer');
                    if (announcer) {
                        announcer.textContent = 'Navegação por voz desativada.';
                    }
                }
            };

            // Text-to-Speech (Leitura de texto)
            window.toggleTextToSpeech = function() {
                const btn = event.target;
                if (btn.textContent === 'Leitura') {
                    btn.textContent = 'Parar';
                    btn.style.background = '#dc2626';
                    btn.style.color = '#ffffff';

                    // Verificar se o navegador suporta síntese de voz
                    if ('speechSynthesis' in window) {
                        // Ler informações da página
                        const title = document.querySelector('h1').textContent;
                        const cards = document.querySelectorAll('.system-card');
                        let textToRead = `Página de subsistemas CREDE. ${title}. `;

                        cards.forEach((card, index) => {
                            const cardTitle = card.querySelector('.card-title').textContent;
                            const cardDesc = card.querySelector('p') ? card.querySelector('p').textContent : '';
                            textToRead += `Card ${index + 1}: ${cardTitle}. ${cardDesc}. `;
                        });

                        textToRead += 'Use Tab para navegar pelos elementos. Pressione Enter para ativar. Comandos de voz disponíveis: "usuários", "estoque", "perfil", "acessibilidade".';

                        // Criar utterance
                        const utterance = new SpeechSynthesisUtterance(textToRead);
                        utterance.lang = 'pt-BR';
                        utterance.rate = 0.9;
                        utterance.pitch = 1;

                        utterance.onend = function() {
                            btn.textContent = 'Leitura';
                            btn.style.background = '#005A24';
                            btn.style.color = '#ffffff';
                        };

                        utterance.onerror = function(event) {
                            console.error('Erro na síntese de voz:', event.error);
                            btn.textContent = 'Leitura';
                            btn.style.background = '#005A24';
                            btn.style.color = '#ffffff';
                        };

                        // Parar qualquer síntese anterior
                        window.speechSynthesis.cancel();

                        // Iniciar nova síntese
                        window.speechSynthesis.speak(utterance);

                    } else {
                        alert('Seu navegador não suporta síntese de voz.');
                        btn.textContent = 'Leitura';
                        btn.style.background = '#005A24';
                        btn.style.color = '#ffffff';
                    }
                } else {
                    btn.textContent = 'Leitura';
                    btn.style.background = '#005A24';
                    btn.style.color = '#ffffff';

                    // Parar síntese de voz
                    if ('speechSynthesis' in window) {
                        window.speechSynthesis.cancel();
                    }
                }
            };

            // Animação de entrada dos elementos
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                    }
                });
            }, observerOptions);

            animatedElements.forEach(element => {
                observer.observe(element);
            });

            // Efeitos de hover melhorados
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Feedback de clique
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    // Ripple effect
                    const ripple = document.createElement('div');
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(0, 90, 36, 0.3);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;

                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = (event.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (event.clientY - rect.top - size / 2) + 'px';

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Adicionar animação de ripple ao CSS
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            console.log('Sistema CREDE carregado com sucesso!');
        });
    </script>
</body>

</html>