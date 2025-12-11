<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once('../../config/system_helper.php'); ?>
    <title>Acesso Negado - <?= getNomeSistemaCurto() ?></title>
    <link rel="icon" href="../dashboard/assets/img/logoblock.png" type="image/png">
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
            <span class="text-xs text-gray-300 uppercase tracking-widest">Acesso Negado</span>
        </header>

        <!-- Conteúdo central -->
        <main class="flex-1 flex items-center justify-center px-8">
            <div class="max-w-7xl w-full">
                
                <!-- Grid de layout -->
                <div class="grid grid-cols-1 md:grid-cols-[1fr,1px,1fr] gap-8 md:gap-8 items-center">
                    
                    <!-- Lado esquerdo: Boneco -->
                    <div class="text-center md:text-right flex justify-center md:justify-end">
                        <div>
                            <img src="../dashboard/assets/img/mpe.png" 
                                 alt="Boneco Maranguape" 
                                 class="w-full max-w-[500px] md:max-w-[600px] lg:max-w-[700px] h-auto mx-auto md:mx-0"
                                 style="min-width: 400px;">
                        </div>
                    </div>

                    <!-- Divisor vertical -->
                    <div class="hidden md:block h-32 bg-gray-200"></div>

                    <!-- Lado direito: Informações -->
                    <div class="space-y-6">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.2em] text-gray-400 mb-2 font-medium">Acesso ao sistema</p>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                Negado
                            </h2>
                        </div>
                        
                        <div class="space-y-3 text-gray-600">
                            <p class="leading-relaxed">
                                A escola associada à sua conta foi removida do sistema.
                            </p>
                            <p class="leading-relaxed text-sm">
                                Entre em contato com a administração para mais informações.
                            </p>
                        </div>

                        <!-- Botão de ação -->
                        <div class="pt-4">
                            <a href="login.php" 
                               class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Voltar para o Login
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </main>

    </div>

</body>
</html>
