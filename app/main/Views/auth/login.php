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
    <title>Login - SIGAE | Sistema Integrado de Gestão Escolar e Alimentação Escolar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="config.js"></script>
    <script src="assets/js/modal-system.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
    </style>
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
                        'warm-orange': '#FF8C42',
                        'forest': {
                            50: '#f0f9f0', 100: '#dcf2dc', 200: '#bce5bc', 300: '#8dd18d',
                            400: '#5bb85b', 500: '#369e36', 600: '#2a7f2a', 700: '#236523',
                            800: '#1f511f', 900: '#1a431a',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-white md:gradient-mesh">
    <!-- Mobile Layout -->
    <div class="md:hidden w-full max-w-md mx-auto px-4 py-8">
        <!-- Logo e Branding -->
        <div class="text-center mb-8">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-16 h-16 mx-auto mb-4 object-contain">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">SIGAE</h1>
            <p class="text-gray-600 text-sm font-medium mb-1">Sistema Integrado de Gestão Escolar e Alimentação Escolar</p>
            <p class="text-gray-500 text-xs">Prefeitura Municipal de Maranguape</p>
        </div>

        <!-- Mensagem de Boas-vindas -->
        <div class="text-center mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Bem-vindo de volta!</h2>
            <p class="text-gray-600 text-sm">Entre na sua conta do sistema</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if(isset($_GET['erro']) && $_GET['erro'] == 'sessao_expirada'): ?>
        <script>
            window.location.href = 'session-expired.php';
        </script>
        <?php endif; ?>
        
        <?php if(isset($_GET['erro']) && $_GET['erro'] == '1'): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
            <span>CPF ou senha incorretos. Tente novamente.</span>
        </div>
        <?php endif; ?>
        <div id="errorMessage" class="error-message">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span id="errorText">Erro no login</span>
            </div>
        </div>

        <div id="successMessage" class="success-message">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span id="successText">Login realizado com sucesso!</span>
            </div>
            </div>

        <!-- Formulário -->
        <form id="loginForm" action="../../Controllers/autenticacao/controllerLogin.php" method="post" class="space-y-5">
            <!-- Campo CPF -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                <input 
                    type="text" 
                    id="cpfMobile" 
                    name="cpf" 
                    placeholder="000.000.000-00" 
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
                    <input type="checkbox" name="remember" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                    <span class="ml-2 text-sm text-gray-600">Lembrar de mim</span>
                </label>
                <a href="#" onclick="showForgotPassword(); return false;" class="text-sm text-primary-green hover:text-secondary-green transition-colors duration-200">
                    Esqueceu a senha?
                </a>
            </div>

            <!-- Botão de Login -->
            <button 
                type="submit" 
                id="loginBtn" 
                class="w-full bg-primary-green text-white font-semibold py-3 px-6 rounded-lg hover:bg-secondary-green transition-all duration-200"
            >
                <span class="loading-spinner" id="loadingSpinner" style="display: none;"></span>
                <span id="loginText">Entrar</span>
            </button>
        </form>

        <!-- Links adicionais -->
        <div class="mt-6 text-center">
            <p class="text-gray-600 text-sm mb-2">Acesso restrito a funcionários cadastrados</p>
            <p class="text-gray-500 text-xs">Entre em contato com a administração para solicitar acesso</p>
        </div>
    </div>

    <div class="hidden md:flex min-h-screen">
        <div class="flex-1 relative bg-cover bg-center" style="background-image: url('https://i.postimg.cc/dtn35crz/maranguape-bg.jpg');">
            <div class="absolute inset-0 bg-black bg-opacity-40"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white p-8">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-2">
                        <img src="https://i.postimg.cc/j5RDSqbd/brasao2-marangupe.png" alt="Logo" class="w-30 h-30 object-contain" />
                    </div>
                    <h1 class="text-4xl font-bold mb-4">SIGAE</h1>
                    <p class="text-xl text-white/90"> Sistema Integrado de Gestão Escolar e Alimentação Escolar</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Logo acima do bem-vindo -->
                <div class="text-center mb-8">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Logo Fazenda" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Bem-vindo(a)!</h2>
                    <p class="text-slate-600">Acesse sua conta para continuar</p>
                </div>

                <!-- Error/Success Messages Desktop -->
                <?php if(isset($_GET['erro']) && $_GET['erro'] == 'sessao_expirada'): ?>
                <script>
                    window.location.href = 'session-expired.php';
                </script>
                <?php endif; ?>
                
                <?php if(isset($_GET['erro']) && $_GET['erro'] == '1'): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <span>CPF ou senha incorretos. Tente novamente.</span>
                </div>
                <?php endif; ?>
                <div id="errorMessageDesktop" class="error-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="errorTextDesktop">Erro no login</span>
                    </div>
                </div>

                <div id="successMessageDesktop" class="success-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="successTextDesktop">Login realizado com sucesso!</span>
                    </div>
                </div>

                <form id="loginFormDesktop" action="../../Controllers/autenticacao/controllerLogin.php" method="post" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">CPF</label>
                        <input type="text" required name="cpf" id="cpfDesktop" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="000.000.000-00" maxlength="14" oninput="formatCPF(this)">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Senha</label>
                        <div class="relative">
                            <input type="password" required name="senha" id="senhaDesktop" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Sua senha">
                            <button type="button" onclick="togglePasswordDesktop()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg id="eyeIconDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-forest-600 border-slate-300 rounded focus:ring-forest-500">
                            <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                        </label>
                        <a href="#" onclick="showForgotPassword(); return false;" class="text-sm text-forest-600 hover:text-forest-700 font-medium">Esqueceu a senha?</a>
                    </div>

                    <button type="submit" id="loginBtnDesktop" class="w-full bg-forest-600 text-white py-3 px-4 rounded-xl font-semibold hover:bg-forest-700 hover:shadow-lg transition-all">
                        <span class="loading-spinner" id="loadingSpinnerDesktop" style="display: none;"></span>
                        <span id="loginTextDesktop">Entrar</span>
                    </button>
                </form>

                <!-- <div class="mt-8 text-center">
                    <p class="text-slate-600 text-sm">
                        Não tem uma conta? 
                        <a class='text-forest-600 hover:text-forest-700 font-semibold' href='/primeiroacesso'>Criar conta</a>
                    </p>
                </div> -->
            </div>
        </div>
    </div>

    <script>

        // Global variables for authentication state
        let isAuthenticating = false;

        // Password toggle functions
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function togglePasswordDesktop() {
            const password = document.getElementById('senhaDesktop');
            const eyeIcon = document.getElementById('eyeIconDesktop');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function togglePasswordMobile() {
            const password = document.getElementById('senhaMobile');
            const eyeOpen = document.getElementById('eye-open-mobile');
            const eyeClosed = document.getElementById('eye-closed-mobile');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                password.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

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

        // Show/hide messages
        function showError(message, isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const errorText = document.getElementById(isDesktop ? 'errorTextDesktop' : 'errorText');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            errorText.textContent = message;
            errorDiv.style.display = 'block';
            successDiv.style.display = 'none';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function showSuccess(message, isDesktop = false) {
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            const successText = document.getElementById(isDesktop ? 'successTextDesktop' : 'successText');
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            
            successText.textContent = message;
            successDiv.style.display = 'block';
            errorDiv.style.display = 'none';
        }

        function hideMessages(isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
        }

        // Set loading state
        function setLoadingState(isLoading, isDesktop = false) {
            const loginBtn = document.getElementById(isDesktop ? 'loginBtnDesktop' : 'loginBtn');
            const loadingSpinner = document.getElementById(isDesktop ? 'loadingSpinnerDesktop' : 'loadingSpinner');
            const loginText = document.getElementById(isDesktop ? 'loginTextDesktop' : 'loginText');
            
            if (isLoading) {
                loginBtn.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                loginText.textContent = 'Entrando...';
                isAuthenticating = true;
            } else {
                loginBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                loginText.textContent = 'Entrar';
                isAuthenticating = false;
            }
        }


        // Handle form submission - validação básica
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
                    const cpf = document.getElementById('cpfMobile').value.trim();
                    const senha = document.getElementById('senhaMobile').value.trim();
                    
                    if (!cpf || !senha) {
                        e.preventDefault();
                showError('Por favor, preencha todos os campos.', false);
            }
        });

        document.getElementById('loginFormDesktop')?.addEventListener('submit', function(e) {
            const cpf = document.getElementById('cpfDesktop').value.trim();
            const senha = document.getElementById('senhaDesktop').value.trim();
            
            if (!cpf || !senha) {
                e.preventDefault();
                showError('Por favor, preencha todos os campos.', true);
            }
        });

        // Forgot password functionality - redirect to recovery request page
        function showForgotPassword() {
            // Get the email from the login form - tentar múltiplos seletores
            let emailInput = document.querySelector('input[type="email"]');
            
            // Se não encontrar, tentar por name
            if (!emailInput) {
                emailInput = document.querySelector('input[name="email"]');
            }
            
            // Se ainda não encontrar, tentar por placeholder
            if (!emailInput) {
                emailInput = document.querySelector('input[placeholder*="email"]');
            }
            
            const email = emailInput ? emailInput.value.trim() : '';
            
            // Redirect to the password recovery request page with email parameter
            if (email) {
                const url = `solicitar-alteracao-senha.html?email=${encodeURIComponent(email)}`;
                window.location.href = url;
            } else {
                window.location.href = 'solicitar-alteracao-senha.html';
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Página inicializada
        });
    </script>
</body>
</html>
