<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once('../../config/system_helper.php'); ?>
    <title>Requisição Inválida - <?= getNomeSistemaCurto() ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background: #fafafa; 
        }
        .error-code {
            font-feature-settings: 'tnum' on, 'lnum' on;
            letter-spacing: -0.05em;
        }
    </style>
</head>
<body class="h-screen overflow-hidden font-sans antialiased">
    
    <!-- Container principal com grid para posicionamento preciso -->
    <div class="h-full flex flex-col">
        
        <!-- Header minimalista -->
        <header class="flex items-center justify-between px-8 py-6">
            <div class="flex items-center gap-3">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-8 h-8 object-contain">
                <span class="text-sm font-medium text-gray-400">Maranguape · Educação</span>
            </div>
            <span class="text-xs text-gray-300 uppercase tracking-widest">Erro</span>
        </header>

        <!-- Conteúdo central -->
        <main class="flex-1 flex items-center justify-center px-8">
            <div class="max-w-2xl w-full">
                
                <!-- Grid de layout -->
                <div class="grid grid-cols-1 md:grid-cols-[1fr,1px,1fr] gap-8 md:gap-12 items-center">
                    
                    <!-- Lado esquerdo: Código do erro -->
                    <div class="text-center md:text-right">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-gray-400 mb-4 font-medium">Código do erro</p>
                        <h1 class="error-code font-display text-[140px] md:text-[180px] leading-[0.85] text-gray-900 font-bold">
                            400
                        </h1>
                    </div>

                    <!-- Divisor vertical -->
                    <div class="hidden md:block h-32 bg-gray-200"></div>

                    <!-- Lado direito: Informações -->
                    <div class="text-center md:text-left">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">
                            Requisição Inválida
                        </h2>
                        <p class="text-sm text-gray-500 leading-relaxed mb-8 max-w-xs">
                            Os dados enviados não puderam ser processados pelo servidor. Verifique as informações e tente novamente.
                        </p>
                        
                        <!-- Botões -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button 
                                onclick="window.history.back()" 
                                class="group inline-flex items-center justify-center gap-2 bg-gray-900 text-white text-sm py-3 px-6 rounded-lg font-medium hover:bg-gray-800 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Voltar
                            </button>
                            <a 
                                href="../../auth/login.php" 
                                class="inline-flex items-center justify-center text-sm py-3 px-6 rounded-lg font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                            >
                                Ir para Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="px-8 py-6">
            <div class="flex items-center justify-between text-xs text-gray-400">
                <span>Sistema Educacional</span>
                <span>Prefeitura Municipal de Maranguape</span>
            </div>
        </footer>

    </div>

</body>
</html>
