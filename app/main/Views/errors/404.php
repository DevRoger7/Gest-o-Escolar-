<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Não Encontrada - SIGEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .bg-gradient-secondary {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .animate-bounce-slow {
            animation: bounce 3s infinite;
        }
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        .animate-fade-in-up-delay {
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        .animate-fade-in-up-delay-2 {
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gradient-secondary min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 animate-fade-in-up">
            <!-- Ícone 404 -->
            <div class="text-center mb-8">
                <div class="relative">
                    <!-- Número 404 -->
                    <div class="text-8xl sm:text-9xl font-bold text-gray-200 select-none">
                        404
                    </div>
                    <!-- Ícone flutuante -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 floating">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-search text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 animate-fade-in-up-delay">
                    Página Não Encontrada
                </h1>
                <p class="text-gray-600 text-sm sm:text-base leading-relaxed animate-fade-in-up-delay-2">
                    Ops! A página que você está procurando não existe ou foi movida. 
                    Verifique o endereço ou use os links abaixo para navegar.
                </p>
            </div>

            <!-- Informações Úteis -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-blue-600 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium mb-2">Possíveis causas:</p>
                            <ul class="space-y-1 text-xs">
                                <li>• URL digitada incorretamente</li>
                                <li>• Página foi movida ou removida</li>
                                <li>• Link quebrado ou desatualizado</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-tools text-green-600 mt-1 mr-3"></i>
                        <div class="text-sm text-green-800">
                            <p class="font-medium mb-2">O que fazer:</p>
                            <ul class="space-y-1 text-xs">
                                <li>• Verifique a URL</li>
                                <li>• Use o menu de navegação</li>
                                <li>• Volte à página anterior</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="space-y-3">
                <a href="../../auth/login.php" class="w-full bg-gradient-primary text-white py-3 px-6 rounded-lg font-semibold text-center block hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="fas fa-home mr-2"></i>
                    Ir para o Login
                </a>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button onclick="window.history.back()" class="bg-gray-100 text-gray-700 py-3 px-6 rounded-lg font-medium text-center hover:bg-gray-200 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Página Anterior
                    </button>
                    
                    <button onclick="window.location.reload()" class="bg-blue-100 text-blue-700 py-3 px-6 rounded-lg font-medium text-center hover:bg-blue-200 transition-colors duration-200">
                        <i class="fas fa-redo mr-2"></i>
                        Recarregar
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    Sistema Integrado de Gestão Acadêmica Escolar
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    © 2024 SIGEA - Todos os direitos reservados
                </p>
            </div>
        </div>

        <!-- Card de Navegação Rápida -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6 animate-fade-in-up-delay-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-compass text-primary-green mr-2"></i>
                Navegação Rápida
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="../../auth/login.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-sign-in-alt text-primary-green mr-3"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Login</p>
                        <p class="text-xs text-gray-500">Acessar o sistema</p>
                    </div>
                </a>
                
                <a href="../dashboard/dashboard.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-tachometer-alt text-blue-600 mr-3"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Dashboard</p>
                        <p class="text-xs text-gray-500">Painel principal</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Card de Ajuda -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6 animate-fade-in-up-delay-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-question-circle text-blue-600 mr-2"></i>
                Precisa de Ajuda?
            </h3>
            <div class="space-y-2 text-sm text-gray-600">
                <p>• Verifique se você está logado no sistema</p>
                <p>• Confirme se a URL está correta</p>
                <p>• Entre em contato com o suporte técnico</p>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="mailto:suporte@sigae.com" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                    <i class="fas fa-envelope mr-1"></i>
                    suporte@sigae.com
                </a>
            </div>
        </div>
    </div>

    <!-- Script para funcionalidades -->
    <script>
        // Adicionar efeito de hover nos botões
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('a, button');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Função para mostrar informações de debug (apenas em desenvolvimento)
        function showDebugInfo() {
            console.log('URL atual:', window.location.href);
            console.log('Referrer:', document.referrer);
            console.log('User Agent:', navigator.userAgent);
        }

        // Mostrar informações de debug no console
        showDebugInfo();
    </script>
</body>
</html>

