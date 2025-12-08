<?php

$mensagem = '';
$tipoMensagem = '';
$credenciaisValidadas = false;
$usuarioId = null;

// Processar verificação de credenciais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'verificar') {
    require_once('../../Controllers/autenticacao/RecuperarSenhaController.php');
    $controller = new RecuperarSenhaController();
    $resultado = $controller->verificarCredenciais($_POST['cpf'], $_POST['email'] ?? '');
    
    if ($resultado['status']) {
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = 'success';
        $credenciaisValidadas = true;
        $usuarioId = $resultado['usuario_id'];
    } else {
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = 'error';
    }
}

// Processar mudança de senha direta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'redefinir') {
    require_once('../../Controllers/autenticacao/RecuperarSenhaController.php');
    $controller = new RecuperarSenhaController();
    $resultado = $controller->redefinirSenhaDireta(
        $_POST['usuario_id'] ?? null,
        $_POST['nova_senha'] ?? '',
        $_POST['confirmar_senha'] ?? ''
    );
    
    if ($resultado['status']) {
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = 'success';
        $credenciaisValidadas = false; // Resetar para mostrar formulário de login
    } else {
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = 'error';
        // Manter credenciais validadas para tentar novamente
        $credenciaisValidadas = true;
        $usuarioId = $_POST['usuario_id'] ?? null;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - SIGEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="config.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    
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
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body class="min-h-screen bg-white md:gradient-mesh">
    
    <!-- Mobile Layout -->
    <div class="md:hidden w-full max-w-md mx-auto px-4 py-8">
        <!-- Logo e Branding -->
        <div class="text-center mb-8">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-16 h-16 mx-auto mb-4 object-contain">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">SIGEA</h1>
            <p class="text-gray-600 text-sm font-medium mb-1">Sistema Integrado de Gestão Escolar e Alimentação Escolar</p>
            <p class="text-gray-500 text-xs">Prefeitura Municipal de Maranguape</p>
        </div>

        <!-- Mensagens -->
        <?php if ($mensagem): ?>
            <div class="mb-4 <?= $tipoMensagem === 'success' ? 'success-message' : 'error-message' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Verificação ou Redefinição -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <?php if (!$credenciaisValidadas): ?>
                <!-- Formulário de Verificação -->
                <h2 class="text-xl font-bold text-gray-800 mb-2">Recuperar Senha</h2>
                <p class="text-gray-600 text-sm mb-6">Informe seu CPF e e-mail para verificar sua identidade</p>
                
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="acao" value="verificar">
                
                <!-- Campo CPF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                    <input 
                        type="text" 
                        name="cpf" 
                        id="cpfMobile"
                        placeholder="000.000.000-00" 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green transition-all duration-200 bg-white"
                        maxlength="14"
                        oninput="formatCPF(this)"
                        required
                    >
                </div>

                <!-- Campo Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">E-mail *</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="emailMobile"
                        placeholder="seu.email@exemplo.com" 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green transition-all duration-200 bg-white"
                        required
                    >
                </div>

                    <!-- Botão -->
                    <button 
                        type="submit" 
                        class="w-full bg-primary-green text-white font-semibold py-3 px-6 rounded-lg hover:bg-secondary-green transition-all duration-200"
                    >
                        Verificar Credenciais
                    </button>
                </form>
            <?php else: ?>
                <!-- Formulário de Redefinição de Senha -->
                <h2 class="text-xl font-bold text-gray-800 mb-2">Definir Nova Senha</h2>
                <p class="text-gray-600 text-sm mb-6">Digite sua nova senha abaixo</p>
                
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="acao" value="redefinir">
                    <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuarioId) ?>">
                    
                    <!-- Nova Senha -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha *</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="nova_senha" 
                                id="novaSenhaMobile"
                                placeholder="Digite sua nova senha" 
                                class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-lg focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green transition-all duration-200 bg-white"
                                required
                                minlength="6"
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword('novaSenhaMobile', 'eyeNovaMobile')"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-green transition-colors duration-200"
                            >
                                <svg id="eyeNovaMobile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirmar Senha -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Senha *</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="confirmar_senha" 
                                id="confirmarSenhaMobile"
                                placeholder="Confirme sua nova senha" 
                                class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-lg focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green transition-all duration-200 bg-white"
                                required
                                minlength="6"
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword('confirmarSenhaMobile', 'eyeConfirmarMobile')"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-green transition-colors duration-200"
                            >
                                <svg id="eyeConfirmarMobile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Botão -->
                    <button 
                        type="submit" 
                        class="w-full bg-primary-green text-white font-semibold py-3 px-6 rounded-lg hover:bg-secondary-green transition-all duration-200"
                    >
                        Redefinir Senha
                    </button>
                </form>
            <?php endif; ?>

            <!-- Link para voltar -->
            <div class="mt-6 text-center">
                <a href="login.php" class="text-sm text-primary-green hover:text-secondary-green transition-colors duration-200">
                    ← Voltar para o login
                </a>
            </div>
        </div>

        <!-- Desenvolvido por -->
        <div class="mt-8 text-center">
            <p class="text-gray-400 text-xs">Desenvolvido por Kron</p>
        </div>
    </div>

    <!-- Desktop Layout -->
    <div class="hidden md:flex min-h-screen">
        <div class="flex-1 relative bg-cover bg-center" style="background-image: url('https://i.postimg.cc/dtn35crz/maranguape-bg.jpg');">
            <div class="absolute inset-0 bg-black bg-opacity-40"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white p-8">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-2">
                        <img src="https://i.postimg.cc/j5RDSqbd/brasao2-marangupe.png" alt="Logo" class="w-30 h-30 object-contain" />
                    </div>
                    <h1 class="text-4xl font-bold mb-4">SIGEA</h1>
                    <p class="text-xl text-white/90">Sistema Integrado de Gestão Escolar e Alimentação Escolar</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="text-center mb-8">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Logo" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Recuperar Senha</h2>
                    <p class="text-slate-600">Informe seus dados para recuperar sua senha</p>
                </div>

                <!-- Mensagens -->
                <?php if ($mensagem): ?>
                    <div class="mb-4 <?= $tipoMensagem === 'success' ? 'success-message' : 'error-message' ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$credenciaisValidadas): ?>
                    <!-- Formulário de Verificação -->
                    <form method="POST" action="" class="space-y-6">
                        <input type="hidden" name="acao" value="verificar">
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">CPF *</label>
                            <input 
                                type="text" 
                                required 
                                name="cpf" 
                                id="cpfDesktop" 
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" 
                                placeholder="000.000.000-00" 
                                maxlength="14" 
                                oninput="formatCPF(this)"
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">E-mail *</label>
                            <input 
                                type="email" 
                                required 
                                name="email" 
                                id="emailDesktop" 
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" 
                                placeholder="seu.email@exemplo.com"
                            >
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-forest-600 text-white py-3 px-4 rounded-xl font-semibold hover:bg-forest-700 hover:shadow-lg transition-all"
                        >
                            Verificar Credenciais
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Formulário de Redefinição de Senha -->
                    <form method="POST" action="" class="space-y-6">
                        <input type="hidden" name="acao" value="redefinir">
                        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuarioId) ?>">
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nova Senha *</label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    required 
                                    name="nova_senha" 
                                    id="novaSenhaDesktop" 
                                    class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" 
                                    placeholder="Digite sua nova senha"
                                    minlength="6"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('novaSenhaDesktop', 'eyeNovaDesktop')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                >
                                    <svg id="eyeNovaDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Senha *</label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    required 
                                    name="confirmar_senha" 
                                    id="confirmarSenhaDesktop" 
                                    class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" 
                                    placeholder="Confirme sua nova senha"
                                    minlength="6"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('confirmarSenhaDesktop', 'eyeConfirmarDesktop')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                >
                                    <svg id="eyeConfirmarDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-forest-600 text-white py-3 px-4 rounded-xl font-semibold hover:bg-forest-700 hover:shadow-lg transition-all"
                        >
                            Redefinir Senha
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Link para voltar -->
                <div class="mt-8 text-center">
                    <a href="login.php" class="text-sm text-forest-600 hover:text-forest-700 font-medium">
                        ← Voltar para o login
                    </a>
                </div>

                <!-- Desenvolvido por -->
                <div class="mt-8 text-center">
                    <p class="text-slate-400 text-xs">Desenvolvido por Kron</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para formatar CPF
        function formatCPF(input) {
            let value = input.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            
            input.value = value;
        }
        
        // Função para toggle password
        function togglePassword(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(eyeId);
            
            if (input.type === 'password') {
                input.type = 'text';
                eye.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                input.type = 'password';
                eye.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        // Validação de senhas iguais
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const novaSenha = form.querySelector('input[name="nova_senha"]')?.value;
                const confirmarSenha = form.querySelector('input[name="confirmar_senha"]')?.value;
                
                if (novaSenha && confirmarSenha && novaSenha !== confirmarSenha) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                    return false;
                }
                
                if (novaSenha && novaSenha.length < 6) {
                    e.preventDefault();
                    alert('A senha deve ter no mínimo 6 caracteres!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>

