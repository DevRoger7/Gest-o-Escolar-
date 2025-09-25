<!DOCTYPE html>
<html>
<head>
    <title>Teste Edição Completa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Teste de Edição de Escola</h1>
        
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold mb-4">1. Verificar Sessão</h2>
            <button onclick="verificarSessao()" class="bg-blue-500 text-white px-4 py-2 rounded">Verificar Sessão</button>
            <div id="sessao-resultado" class="mt-2 p-2 bg-gray-50 rounded"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold mb-4">2. Fazer Login</h2>
            <button onclick="fazerLogin()" class="bg-green-500 text-white px-4 py-2 rounded">Fazer Login</button>
            <div id="login-resultado" class="mt-2 p-2 bg-gray-50 rounded"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold mb-4">3. Obter Dados da Escola</h2>
            <button onclick="obterEscola()" class="bg-purple-500 text-white px-4 py-2 rounded">Obter Escola ID 4</button>
            <div id="escola-resultado" class="mt-2 p-2 bg-gray-50 rounded"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">4. Console de Debug</h2>
            <div id="debug-console" class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto"></div>
        </div>
    </div>

    <script>
        function log(message) {
            const console = document.getElementById('debug-console');
            const timestamp = new Date().toLocaleTimeString();
            console.innerHTML += `[${timestamp}] ${message}\n`;
            console.scrollTop = console.scrollHeight;
        }

        function verificarSessao() {
            log('Verificando sessão...');
            fetch('verificar_sessao.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('sessao-resultado').innerHTML = data;
                log('Sessão verificada: ' + data.substring(0, 100) + '...');
            })
            .catch(error => {
                log('Erro ao verificar sessão: ' + error);
            });
        }

        function fazerLogin() {
            log('Fazendo login...');
            fetch('../../Controllers/autenticacao/controllerLogin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: 'email=admin@admin.com&senha=123456'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('login-resultado').innerHTML = data.substring(0, 200) + '...';
                log('Login realizado: ' + (data.includes('success') ? 'SUCESSO' : 'FALHA'));
            })
            .catch(error => {
                log('Erro no login: ' + error);
            });
        }

        function obterEscola() {
            log('Obtendo dados da escola...');
            fetch('obter_escola.php?id=4', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => {
                log('Status da resposta: ' + response.status);
                return response.text();
            })
            .then(data => {
                document.getElementById('escola-resultado').innerHTML = '<pre>' + data + '</pre>';
                log('Dados da escola obtidos: ' + data.substring(0, 100) + '...');
                
                // Tentar parsear como JSON
                try {
                    const json = JSON.parse(data);
                    log('JSON válido: ' + (json.status ? 'SUCESSO' : 'FALHA - ' + (json.message || 'Sem mensagem')));
                } catch(e) {
                    log('Resposta não é JSON válido');
                }
            })
            .catch(error => {
                log('Erro ao obter escola: ' + error);
            });
        }

        // Log inicial
        log('Página carregada. Pronto para testes.');
    </script>
</body>
</html>