<?php
// Incluir configurações
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Hub de Escolas - Sistema de Gestão Escolar Maranguape'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon.png" type="image/png">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon.png">
    
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
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #2D5A27 0%, #4A7C59 50%, #FF6B35 100%);
        }
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .school-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(45, 90, 39, 0.3);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <!-- Elementos decorativos de fundo -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-white bg-opacity-10 rounded-full floating-animation"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-accent-orange bg-opacity-20 rounded-full floating-animation" style="animation-delay: -3s;"></div>
        <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-light-green bg-opacity-15 rounded-full floating-animation" style="animation-delay: -1.5s;"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 bg-white bg-opacity-10 backdrop-blur-md border-b border-white border-opacity-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="<?php echo BASE_URL; ?>/assets/icons/favicon.png" alt="Brasão" class="w-10 h-10 mr-3">
                    <h1 class="text-xl font-bold text-white">Sistema de Gestão Escolar</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-white text-sm">Olá, <?php echo htmlspecialchars($userName); ?></span>
                    <a href="/logout" class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition-all duration-200 text-sm">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="relative z-10 py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Título -->
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-white mb-4">
                    Selecione uma Escola
                </h2>
                <p class="text-xl text-white text-opacity-90">
                    Escolha a escola onde você deseja atuar hoje
                </p>
            </div>

            <!-- Grid de Escolas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
                <?php foreach ($schools as $school): ?>
                <div class="school-card bg-white rounded-2xl shadow-xl p-8 transition-all duration-300 cursor-pointer slide-in" 
                     onclick="selectSchool(<?php echo $school['id']; ?>)">
                    
                    <!-- Ícone da Escola -->
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-green to-secondary-green rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-green mb-2">
                            <?php echo htmlspecialchars($school['nome']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Código: <?php echo htmlspecialchars($school['codigo']); ?>
                        </p>
                    </div>

                    <!-- Informações da Escola -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-accent-orange mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="text-gray-600 text-sm">
                                <?php echo htmlspecialchars($school['endereco']); ?>
                            </p>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-accent-orange mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <p class="text-gray-600 text-sm">
                                <?php echo htmlspecialchars($school['telefone']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Botão de Acesso -->
                    <button class="w-full bg-gradient-to-r from-primary-green to-secondary-green text-white font-semibold py-3 px-6 rounded-xl hover:from-secondary-green hover:to-primary-green transition-all duration-300 shadow-lg">
                        Acessar Escola
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Informações Adicionais -->
            <div class="mt-12 text-center">
                <div class="bg-white bg-opacity-10 backdrop-blur-md rounded-2xl p-8 max-w-4xl mx-auto">
                    <h3 class="text-2xl font-bold text-white mb-4">
                        Professor Multi-Escolas
                    </h3>
                    <p class="text-white text-opacity-90 mb-6">
                        Você está lotado em <strong><?php echo count($schools); ?> escolas</strong> do município de Maranguape. 
                        Selecione a escola onde deseja atuar hoje para acessar suas turmas e funcionalidades.
                    </p>
                    <div class="flex justify-center space-x-8 text-white text-opacity-80">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-accent-orange"><?php echo count($schools); ?></div>
                            <div class="text-sm">Escolas</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-accent-orange">Multi</div>
                            <div class="text-sm">Disciplinas</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-accent-orange">24/7</div>
                            <div class="text-sm">Acesso</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Formulário Oculto para Seleção -->
    <form id="schoolForm" method="POST" action="/hub/select" style="display: none;">
        <input type="hidden" name="school_id" id="selectedSchoolId">
    </form>

    <script>
        function selectSchool(schoolId) {
            document.getElementById('selectedSchoolId').value = schoolId;
            document.getElementById('schoolForm').submit();
        }

        // Animação de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.school-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
