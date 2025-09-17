<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão Escolar Maranguape</title>
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
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(45, 90, 39, 0.2);
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(45, 90, 39, 0.4);
        }
        .logo-container {
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }
        .logo-container:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <!-- Elementos decorativos de fundo -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-white bg-opacity-10 rounded-full floating-animation"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-accent-orange bg-opacity-20 rounded-full floating-animation" style="animation-delay: -3s;"></div>
        <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-light-green bg-opacity-15 rounded-full floating-animation" style="animation-delay: -1.5s;"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl mx-auto">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden min-h-[580px] flex">
            <!-- Painel Esquerdo - Informações -->
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-green to-secondary-green p-10 flex-col justify-between relative overflow-hidden">
                <!-- Elementos decorativos -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white bg-opacity-10 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-accent-orange bg-opacity-20 rounded-full translate-y-12 -translate-x-12"></div>
                
                <div class="relative z-10">
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-white mb-3">
                            Bem-Vindo ao
                        </h1>
                        <h2 class="text-3xl font-extrabold text-white mb-4">
                            Sistema de Gestão Escolar
                        </h2>
                        <p class="text-lg text-white text-opacity-90 leading-relaxed">
                            Centralize e automatize todos os processos acadêmicos e de merenda em um único ambiente moderno e eficiente.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center text-white text-opacity-90 text-sm">
                            <svg class="w-4 h-4 mr-3 text-accent-orange" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Gestão completa de turmas e alunos
                        </div>
                        <div class="flex items-center text-white text-opacity-90 text-sm">
                            <svg class="w-4 h-4 mr-3 text-accent-orange" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Controle de frequência e notas
                        </div>
                        <div class="flex items-center text-white text-opacity-90 text-sm">
                            <svg class="w-4 h-4 mr-3 text-accent-orange" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Gestão de merenda escolar
                        </div>
                        <div class="flex items-center text-white text-opacity-90 text-sm">
                            <svg class="w-4 h-4 mr-3 text-accent-orange" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Relatórios e transparência
                        </div>
                    </div>
                </div>
                
                <div class="relative z-10">
                    <button class="w-full bg-transparent border-2 border-white text-white font-semibold py-3 px-6 rounded-xl hover:bg-white hover:text-primary-green transition-all duration-300 btn-hover text-sm">
                        CADASTRAR
                    </button>
                </div>
            </div>

            <div class="w-full lg:w-1/2 p-8 lg:p-10 flex flex-col justify-center slide-in">
                <div class="max-w-md mx-auto w-full">
                    <div class="text-center mb-6">
                        <img src="<?php echo BASE_URL; ?>/assets/img/brasao_maranguape.png" alt="Brasão de Maranguape" class="w-20 h-20 mx-auto mb-4 object-contain logo-container">
                    </div>
                
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-primary-green mb-2">LOGIN</h1>
                        <p class="text-gray-600 text-sm">Acesse sua conta para continuar</p>
                        
                        <?php if (isset($_SESSION['login_error'])): ?>
                            <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                                <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                            </div>
                            <?php unset($_SESSION['login_error']); ?>
                        <?php endif; ?>
                    </div>

                    <form class="space-y-5" method="POST" action="/login">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                name="cpf" 
                                placeholder="CPF ou Usuário" 
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-primary-green focus:outline-none transition-all duration-300 input-focus bg-gray-50 focus:bg-white text-sm"
                                required
                            >
                        </div>

                        <!-- Campo Senha -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                name="senha" 
                                id="senha"
                                placeholder="Senha" 
                                class="w-full pl-12 pr-14 py-3 border-2 border-gray-200 rounded-xl focus:border-primary-green focus:outline-none transition-all duration-300 input-focus bg-gray-50 focus:bg-white text-sm"
                                required
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-green transition-colors duration-200"
                            >
                                <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Opções adicionais -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input type="checkbox" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                <span class="ml-2 text-xs text-gray-600">Lembrar-me</span>
                            </label>
                            <a href="#" class="text-xs text-primary-green hover:text-secondary-green transition-colors duration-200">
                                Esqueceu a senha?
                            </a>
                        </div>

                        <!-- Botão de Login -->
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-primary-green to-secondary-green text-white font-semibold py-3 px-6 rounded-xl hover:from-secondary-green hover:to-primary-green transition-all duration-300 btn-hover shadow-lg text-sm"
                        >
                            ENTRAR
                        </button>
                    </form>

                    <!-- Links adicionais -->
                    <div class="mt-8 text-center">
                        <p class="text-gray-600 mb-3 text-sm">Não tem uma conta?</p>
                        <a href="#" class="text-primary-green hover:text-secondary-green font-semibold transition-colors duration-200 text-sm">
                            Solicitar acesso
                        </a>
                    </div>


                    <!-- Versão mobile - Botão cadastrar -->
                    <div class="lg:hidden mt-4">
                        <button class="w-full bg-transparent border-2 border-primary-green text-primary-green font-semibold py-3 px-6 rounded-xl hover:bg-primary-green hover:text-white transition-all duration-300 text-sm">
                            CADASTRAR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                senhaInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

        // Adicionar efeitos de foco nos inputs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-primary-green', 'ring-opacity-20');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-primary-green', 'ring-opacity-20');
            });
        });

        // Animação de entrada suave
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease-in-out';
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>
