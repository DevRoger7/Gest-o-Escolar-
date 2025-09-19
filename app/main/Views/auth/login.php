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
    <title>Login - SIGEM | Sistema Integrado de Gestão Educacional Municipal</title>
    
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
    <style>
        .professional-bg {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.75) 100%), 
                        url('https://i.postimg.cc/dtn35crz/maranguape-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
        }

        /* Mobile: fundo branco limpo */
        @media (max-width: 1023px) {
            .professional-bg {
                background: #ffffff;
                background-attachment: scroll;
            }
            
            .login-container {
                background: transparent;
                border: none;
                box-shadow: none;
                backdrop-filter: none;
                min-height: auto;
                flex-direction: column;
                z-index: 1;
            }
        }
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .input-focus:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.15);
        }
        .btn-hover:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 90, 39, 0.25);
        }
        .logo-container {
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .form-input {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(45, 90, 39, 0.2);
            transition: all 0.3s ease;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #2D5A27;
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
        }

        .login-button {
            background: linear-gradient(135deg, #2D5A27 0%, #4A7C59 100%);
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px -5px rgba(45, 90, 39, 0.3);
        }

        .login-button:hover {
            background: linear-gradient(135deg, #1e3f1a 0%, #3a6348 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px -5px rgba(45, 90, 39, 0.4);
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="min-h-screen professional-bg flex items-center justify-center p-4">
    
    <!-- Desktop Layout -->
    <div class="hidden lg:block w-full max-w-5xl mx-auto">
        <div class="login-container rounded-2xl overflow-hidden min-h-[500px] flex">
            <!-- Painel Esquerdo - Informações -->
            <div class="lg:w-1/2 bg-gradient-to-br from-primary-green via-secondary-green to-primary-green p-10 flex flex-col justify-between relative">
                
                <div class="relative z-10">
                    <div class="mb-8">
                        
                        <div class="mb-8">
                            <h1 class="text-3xl font-light text-white mb-2 tracking-wider opacity-90">
                                BEM-VINDO AO
                            </h1>
                            <h2 class="text-6xl font-black text-white mb-4 tracking-tight leading-none">
                                SIGEM
                            </h2>
                            <div class="w-16 h-1 bg-accent-orange rounded-full mb-6"></div>
                            <p class="text-white text-opacity-95 text-lg mb-6 font-semibold leading-relaxed">
                                Sistema Integrado de Gestão Educacional Municipal
                            </p>
                            <p class="text-white text-opacity-85 leading-relaxed font-light text-base">
                                Plataforma oficial para gestão completa da rede municipal de ensino, integrando processos pedagógicos, administrativos e de merenda escolar.
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-5 mb-10">
                        <div class="flex items-center text-white text-opacity-95 text-base font-medium group">
                            <div class="w-8 h-8 bg-accent-orange rounded-lg flex items-center justify-center mr-4 flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <span class="group-hover:text-accent-orange transition-colors duration-200">Gestão pedagógica e acadêmica</span>
                        </div>
                        <div class="flex items-center text-white text-opacity-95 text-base font-medium group">
                            <div class="w-8 h-8 bg-accent-orange rounded-lg flex items-center justify-center mr-4 flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <span class="group-hover:text-accent-orange transition-colors duration-200">Controle de frequência e avaliações</span>
                        </div>
                        <div class="flex items-center text-white text-opacity-95 text-base font-medium group">
                            <div class="w-8 h-8 bg-accent-orange rounded-lg flex items-center justify-center mr-4 flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                                </svg>
                            </div>
                            <span class="group-hover:text-accent-orange transition-colors duration-200">Gestão de merenda e recursos</span>
                        </div>
                        <div class="flex items-center text-white text-opacity-95 text-base font-medium group">
                            <div class="w-8 h-8 bg-accent-orange rounded-lg flex items-center justify-center mr-4 flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <span class="group-hover:text-accent-orange transition-colors duration-200">Relatórios e transparência pública</span>
                        </div>
                    </div>
                </div>
                
                <div class="relative z-10 space-y-4">
                    <div class="text-center">
                        <p class="text-white text-opacity-75 text-sm font-light leading-relaxed">
                            Para professores, gestores e funcionários da rede municipal
                        </p>
                        <div class="w-12 h-px bg-white bg-opacity-30 mx-auto mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Painel Direito - Formulário de Login -->
            <div class="w-full lg:w-1/2 p-6 lg:p-8 flex flex-col justify-center slide-in">
                <div class="max-w-md mx-auto w-full">
                    <!-- Logo -->
                    <div class="text-center mb-6">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-20 h-20 mx-auto mb-4 object-contain logo-container">
                    </div>
                    
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-primary-green mb-2">LOGIN</h1>
                        <p class="text-gray-600 text-sm">Acesse sua conta para continuar</p>
                        
                        <!-- Mensagem de erro -->
                        <?php if(isset($_GET['erro']) && $_GET['erro'] == '1'): ?>
                        <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                            <span>CPF ou senha incorretos. Tente novamente.</span>
                        </div>
                        <?php endif; ?>
                        <div id="errorMessage" class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm hidden">
                            <span id="errorText"></span>
                        </div>
                    </div>

                    <!-- Formulário -->
                    <form id="loginForm" action="../../Controllers/autenticacao/controllerLogin.php" method="post" class="space-y-5">
                        <!-- Campo CPF/Usuário -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="cpf" 
                                name="cpf" 
                                placeholder="CPF" 
                                class="w-full pl-12 pr-4 py-3 rounded-xl focus:outline-none form-input text-sm"
                                maxlength="14"
                                oninput="formatCPF(this)"
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
                                id="senha"
                                name="senha" 
                                placeholder="Senha" 
                                class="w-full pl-12 pr-14 py-3 rounded-xl focus:outline-none form-input text-sm"
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
                            class="w-full text-white font-semibold py-3 px-6 rounded-xl login-button text-sm"
                        >
                            ENTRAR
                        </button>
                    </form>

                    <!-- Links adicionais -->
                    <div class="mt-8 text-center">
                        <p class="text-gray-600 mb-3 text-sm">Acesso restrito a funcionários cadastrados</p>
                        <p class="text-gray-500 text-xs">Entre em contato com a administração para solicitar acesso</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Layout -->
    <div class="lg:hidden w-full max-w-md mx-auto px-4 py-8">
        <!-- Logo e Branding -->
        <div class="text-center mb-8">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-16 h-16 mx-auto mb-4 object-contain">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">SIGEM</h1>
            <p class="text-gray-600 text-sm font-medium mb-1">Sistema Integrado de Gestão Educacional Municipal</p>
            <p class="text-gray-500 text-xs">Prefeitura Municipal de Maranguape</p>
        </div>

        <!-- Mensagem de Boas-vindas -->
        <div class="text-center mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Bem-vindo de volta!</h2>
            <p class="text-gray-600 text-sm">Entre na sua conta do sistema</p>
        </div>

            <!-- Mensagem de erro -->
            <?php if(isset($_GET['erro']) && $_GET['erro'] == '1'): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                <span>CPF ou senha incorretos. Tente novamente.</span>
            </div>
            <?php endif; ?>
            <div id="errorMessageMobile" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm hidden">
                <span id="errorTextMobile"></span>
            </div>

        <!-- Formulário -->
        <form id="loginFormMobile" action="../../Controllers/autenticacao/controllerLogin.php" method="post" class="space-y-5">
            <!-- Campo CPF/Usuário -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                <input 
                    type="text" 
                    id="cpfMobile" 
                    name="cpf" 
                    placeholder="CPF" 
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green transition-all duration-200 bg-white"
                    maxlength="14"
                    oninput="formatCPF(this)"
                    required
                >
            </div>

            <!-- Campo Senha -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="senhaMobile"
                        name="senha" 
                        placeholder="Sua senha" 
                        class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-lg focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green transition-all duration-200 bg-white"
                        required
                    >
                    <button 
                        type="button" 
                        onclick="togglePasswordMobile()"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-green transition-colors duration-200"
                    >
                        <svg id="eye-open-mobile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg id="eye-closed-mobile" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Opções adicionais -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                    <span class="ml-2 text-sm text-gray-600">Lembrar de mim</span>
                </label>
                <a href="#" class="text-sm text-primary-green hover:text-secondary-green transition-colors duration-200">
                    Esqueceu a senha?
                </a>
            </div>

            <!-- Botão de Login -->
            <button 
                type="submit" 
                class="w-full bg-primary-green text-white font-semibold py-3 px-6 rounded-lg hover:bg-secondary-green transition-all duration-200"
            >
                Entrar
            </button>
        </form>

        <!-- Links adicionais -->
        <div class="mt-6 text-center">
            <p class="text-gray-600 text-sm mb-2">Acesso restrito a funcionários cadastrados</p>
            <p class="text-gray-500 text-xs">Entre em contato com a administração para solicitar acesso</p>
        </div>

    </div>

    <script>

        // Função para formatar CPF
        function formatCPF(input) {
            // Remove tudo que não é dígito
            let value = input.value.replace(/\D/g, '');
            
            // Aplica a máscara do CPF
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            
            input.value = value;
        }

        // Função para mostrar/ocultar senha (Desktop)
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

        // Função para mostrar/ocultar senha (Mobile)
        function togglePasswordMobile() {
            const senhaInput = document.getElementById('senhaMobile');
            const eyeOpen = document.getElementById('eye-open-mobile');
            const eyeClosed = document.getElementById('eye-closed-mobile');
            
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
            
            if (loginFormMobile) {
                loginFormMobile.addEventListener('submit', function(e) {
                    // Validação básica
                    const cpf = document.getElementById('cpfMobile').value.trim();
                    const senha = document.getElementById('senhaMobile').value.trim();
                    
                    if (!cpf || !senha) {
                        e.preventDefault();
                        showErrorMobile('Por favor, preencha todos os campos.');
                    }
                });
            }
        });
        
        // Função para mostrar erro no desktop
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            if (errorDiv && errorText) {
                errorText.textContent = message;
                errorDiv.classList.remove('hidden');
            }
        }
        
        // Função para mostrar erro no mobile
        function showErrorMobile(message) {
            const errorDiv = document.getElementById('errorMessageMobile');
            const errorText = document.getElementById('errorTextMobile');
            if (errorDiv && errorText) {
                errorText.textContent = message;
                errorDiv.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
