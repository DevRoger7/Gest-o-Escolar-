<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão Expirada - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .bg-gradient-secondary {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
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
    </style>
</head>
<body class="bg-gradient-secondary min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 animate-fade-in-up">
            <!-- Ícone de Aviso -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse-slow">
                    <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Sessão Expirada</h1>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Sua sessão expirou por motivos de segurança. Por favor, faça login novamente para continuar.
                </p>
            </div>

            <!-- Informações Adicionais -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-yellow-600 mt-0.5 mr-3"></i>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium mb-1">Por que isso aconteceu?</p>
                        <ul class="text-xs space-y-1">
                            <li>• Inatividade prolongada</li>
                            <li>• Segurança do sistema</li>
                            <li>• Fechamento do navegador</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botão de Ação -->
            <div class="space-y-3">
                <a href="login.php" class="w-full bg-gradient-primary text-white py-3 px-6 rounded-lg font-semibold text-center block hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Voltar ao Login
                </a>
                
                <button onclick="window.history.back()" class="w-full bg-gray-100 text-gray-700 py-3 px-6 rounded-lg font-medium text-center hover:bg-gray-200 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Página Anterior
                </button>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    Sistema Integrado de Gestão Acadêmica Escolar
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    © 2024 SIGAE - Todos os direitos reservados
                </p>
            </div>
        </div>

        <!-- Card de Ajuda (Opcional) -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6 animate-fade-in-up" style="animation-delay: 0.2s;">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-question-circle text-blue-600 mr-2"></i>
                Precisa de Ajuda?
            </h3>
            <div class="space-y-2 text-sm text-gray-600">
                <p>• Verifique sua conexão com a internet</p>
                <p>• Limpe o cache do navegador</p>
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

    <!-- Script para auto-redirect (opcional) -->
    <script>
        // Auto-redirect após 30 segundos (opcional)
        // setTimeout(() => {
        //     window.location.href = 'login.php';
        // }, 30000);

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
    </script>
</body>
</html>

