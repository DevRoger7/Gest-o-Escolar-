<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Não Encontrada - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/x-icon">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .bg-institutional {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .text-primary-green {
            color: #2D5A27;
        }
        
        .bg-primary-green {
            background-color: #2D5A27;
        }
        
        .border-primary-green {
            border-color: #2D5A27;
        }
        
        .hover\:bg-primary-green:hover {
            background-color: #1e3d1a;
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in-left {
            animation: slideInLeft 0.8s ease-out;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .slide-in-right {
            animation: slideInRight 0.8s ease-out 0.2s both;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .institutional-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #2D5A27, #4A7C59, #2D5A27);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .floating-animation {
            animation: floating 6s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .pulse-gentle {
            animation: pulseGentle 3s ease-in-out infinite;
        }
        
        @keyframes pulseGentle {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .modern-shadow {
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.1), 0 8px 16px -8px rgba(0, 0, 0, 0.1);
        }
        
        .hover-lift-modern {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.15), 0 16px 32px -8px rgba(0, 0, 0, 0.1);
        }
        
        .bg-pattern {
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(45, 90, 39, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(45, 90, 39, 0.05) 0%, transparent 50%);
        }
    </style>
</head>
<body class="min-h-screen bg-institutional bg-pattern">
    <!-- Header Institucional -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/40px-Bras%C3%A3o_de_Maranguape.png" 
                         alt="Brasão de Maranguape" class="w-10 h-10">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">SIGAE</h1>
                        <p class="text-xs text-gray-600">Sistema Integrado de Gestão Acadêmica Escolar</p>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    Prefeitura Municipal de Maranguape
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center fade-in">
            <!-- Hero Section -->
            <div class="relative mb-24">
                <!-- Elemento Flutuante -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-96 h-96 bg-gradient-to-br from-primary-green/10 to-green-600/5 rounded-full blur-3xl floating-animation"></div>
                </div>
                
                <!-- Conteúdo Principal -->
                <div class="relative z-10">
                    <!-- Ícone Moderno -->
                    <div class="w-48 h-48 mx-auto bg-gradient-to-br from-red-50 via-red-100 to-red-200 rounded-full flex items-center justify-center mb-12 modern-shadow pulse-gentle">
                        <div class="w-36 h-36 bg-gradient-to-br from-red-100 to-red-300 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-7xl"></i>
                        </div>
                    </div>

                    <!-- Número 404 Estilizado -->
                    <div class="mb-12">
                        <h1 class="text-[12rem] md:text-[16rem] font-black gradient-text mb-4 slide-in-left leading-none">
                            404
                        </h1>
                        <div class="w-32 h-1 bg-gradient-to-r from-primary-green to-green-600 mx-auto rounded-full"></div>
                    </div>

                    <!-- Título e Descrição -->
                    <div class="mb-16">
                        <h2 class="text-5xl md:text-6xl font-bold text-gray-800 mb-8 slide-in-right">
                            Página Não Encontrada
                        </h2>
                        <p class="text-2xl text-gray-600 max-w-4xl mx-auto leading-relaxed font-light">
                            A página que você está procurando não existe ou foi movida. 
                            Não se preocupe, vamos te ajudar a encontrar o que precisa.
                        </p>
                    </div>

                    <!-- Botão Principal Premium -->
                    <div class="mb-20">
                        <a href="app/main/Views/auth/login.php" 
                           class="group relative inline-flex items-center space-x-4 bg-primary-green text-white px-20 py-6 rounded-2xl font-bold text-2xl hover:bg-green-600 hover-lift-modern modern-shadow shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-home text-3xl text-white group-hover:rotate-12 transition-transform duration-300"></i>
                            <span class="text-white font-bold">Voltar ao Sistema</span>
                            <div class="absolute -right-2 -top-2 w-6 h-6 bg-yellow-400 rounded-full animate-ping"></div>
                        </a>
                    </div>
                </div>
            </div>


        </div>
    </main>

    <!-- Footer Institucional -->
    <footer class="relative bg-gray-900 text-white mt-20 overflow-hidden">
        <!-- Imagem de Fundo -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-30" 
             style="background-image: url('https://i.postimg.cc/XJQ48jCk/conhecampe.jpg'); background-attachment: fixed;">
        </div>
        
        <!-- Overlay para melhorar legibilidade -->
        <div class="absolute inset-0 bg-gray-900 bg-opacity-60"></div>
        
        <!-- Conteúdo do Footer -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Seção Principal -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <!-- Logo e Informações -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/40px-Bras%C3%A3o_de_Maranguape.png" 
                            alt="Brasão de Maranguape" class="w-10 h-10">
                        <div>
                            <h3 class="text-lg font-bold">PREFEITURA DE MARANGUAPE</h3>
                            <p class="text-sm text-gray-300">Sistema Integrado de Gestão Escolar e Alimentação Escolar</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        Sistema oficial da Secretaria Municipal de Educação de Maranguape, 
                        desenvolvido para gestão acadêmica das escolas municipais.
                    </p>
                </div>

                <!-- Contato -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-primary-green">Contato</h4>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-map-marker-alt text-primary-green mt-1"></i>
                            <div>
                                <p class="text-sm font-medium">Endereço</p>
                                <p class="text-sm text-gray-300">Rua Napoleão Lima, nº 253</p>
                                <p class="text-sm text-gray-300">Maranguape - CE</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-phone text-primary-green mt-1"></i>
                            <div>
                                <p class="text-sm font-medium">Telefone</p>
                                <p class="text-sm text-gray-300">(85) 3369.9152</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-envelope text-primary-green mt-1"></i>
                            <div>
                                <p class="text-sm font-medium">E-mail</p>
                                <p class="text-sm text-gray-300">gabinete@maranguape.ce.gov.br</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Links Úteis -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-primary-green">Links Úteis</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://maranguape.ce.gov.br/" class="text-sm text-gray-300 hover:text-primary-green transition-colors">
                            Site Oficial
                        </a>
                        <a href="https://maranguape.ce.gov.br/servicos-digitais/transparencia" class="text-sm text-gray-300 hover:text-primary-green transition-colors">
                            Transparência
                        </a>
                        <a href="https://maranguape.ce.gov.br/servicos-digitais/noticias" class="text-sm text-gray-300 hover:text-primary-green transition-colors">
                            Notícias
                        </a>
                        <a href="https://maranguape.ce.gov.br/servicos-digitais/telefones-uteis" class="text-sm text-gray-300 hover:text-primary-green transition-colors">
                            Telefones Úteis
                        </a>
                    </div>
                </div>
            </div>

            <!-- Linha Divisória -->
            <div class="border-t border-gray-700 pt-8">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="text-center md:text-left mb-4 md:mb-0">
                        <p class="text-sm text-gray-300">
                            © 2025 <span class="font-semibold">Prefeitura Municipal de Maranguape</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Todos os direitos reservados
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <a href="https://maranguape.ce.gov.br/" class="text-gray-400 hover:text-primary-green transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://maranguape.ce.gov.br/" class="text-gray-400 hover:text-primary-green transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://maranguape.ce.gov.br/" class="text-gray-400 hover:text-primary-green transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar efeito de hover nos cards
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Função para debug
        function showDebugInfo() {
            console.log('SIGAE 404 Page - Debug Info:');
            console.log('URL atual:', window.location.href);
            console.log('Referrer:', document.referrer);
            console.log('User Agent:', navigator.userAgent);
            console.log('Timestamp:', new Date().toISOString());
        }

        showDebugInfo();
    </script>
</body>
</html>
