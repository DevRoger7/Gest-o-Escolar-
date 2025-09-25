<?php
// Configurar headers para AJAX
header('Content-Type: text/html; charset=utf-8');

// Configurar parâmetros de cookie para melhor compatibilidade com AJAX
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Iniciar sessão
session_start();

// Simular login de administrador para teste
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Simular dados de um usuário administrador logado
    $_SESSION['logado'] = true;
    $_SESSION['usuario_id'] = 1;
    $_SESSION['pessoa_id'] = 1;
    $_SESSION['nome'] = 'Administrador Teste';
    $_SESSION['email'] = 'admin@teste.com';
    $_SESSION['cpf'] = '12345678901';
    $_SESSION['telefone'] = '(85) 99999-9999';
    $_SESSION['tipo'] = 'ADM';
    $_SESSION['escola_atual'] = 'Escola Municipal';
    
    // Definir permissões de administrador
    $_SESSION['cadastrar_pessoas'] = true;
    $_SESSION['gerenciar_escolas'] = true;
    $_SESSION['gerenciar_professores'] = true;
    $_SESSION['relatorio_geral'] = true;
    $_SESSION['gerenciar_estoque_produtos'] = true;
    $_SESSION['pedidos_nutricionista'] = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Login e Edição de Escola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .console {
            background-color: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 20px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .btn {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-center">Teste de Login e Edição de Escola</h1>
        
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h2 class="text-xl font-semibold mb-4">Status da Sessão</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Session ID:</strong> <?= session_id() ?></div>
                <div><strong>Logado:</strong> <?= $_SESSION['logado'] ? 'Sim' : 'Não' ?></div>
                <div><strong>Nome:</strong> <?= $_SESSION['nome'] ?? 'Não definido' ?></div>
                <div><strong>Tipo:</strong> <?= $_SESSION['tipo'] ?? 'Não definido' ?></div>
                <div><strong>Email:</strong> <?= $_SESSION['email'] ?? 'Não definido' ?></div>
                <div><strong>Permissões:</strong> <?= isset($_SESSION['gerenciar_escolas']) ? 'Gerenciar Escolas' : 'Sem permissões' ?></div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h2 class="text-xl font-semibold mb-4">Testes de Funcionalidade</h2>
            <div class="space-x-2 mb-4">
                <button class="btn" onclick="testarSessao()">1. Verificar Sessão</button>
                <button class="btn" onclick="testarObterEscola()">2. Obter Escola ID 4</button>
                <button class="btn" onclick="testarBuscarGestores()">3. Buscar Gestores</button>
                <button class="btn" onclick="limparConsole()">Limpar Console</button>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold mb-4">Console de Debug</h2>
            <div id="console" class="console">
Aguardando testes...\n
            </div>
        </div>
    </div>

    <script>
        function log(message) {
            const console = document.getElementById('console');
            const timestamp = new Date().toLocaleTimeString();
            console.textContent += `[${timestamp}] ${message}\n`;
            console.scrollTop = console.scrollHeight;
        }

        function limparConsole() {
            document.getElementById('console').textContent = 'Console limpo.\n';
        }

        async function testarSessao() {
            log('Testando verificação de sessão...');
            try {
                const response = await fetch('verificar_sessao.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                const data = await response.json();
                log('Resposta da verificação de sessão:');
                log(JSON.stringify(data, null, 2));
            } catch (error) {
                log('ERRO na verificação de sessão: ' + error.message);
            }
        }

        async function testarObterEscola() {
            log('Testando obtenção de dados da escola ID 4...');
            try {
                const response = await fetch('obter_escola.php?id=4', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                const data = await response.json();
                log('Resposta da obtenção de escola:');
                log(JSON.stringify(data, null, 2));
                
                if (data.status) {
                    log('✅ SUCESSO: Dados da escola obtidos com sucesso!');
                } else {
                    log('❌ ERRO: ' + data.mensagem);
                }
            } catch (error) {
                log('❌ ERRO na obtenção de escola: ' + error.message);
            }
        }

        async function testarBuscarGestores() {
            log('Testando busca de gestores...');
            try {
                const response = await fetch('buscar_gestores.php?busca=admin', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                const data = await response.json();
                log('Resposta da busca de gestores:');
                log(JSON.stringify(data, null, 2));
            } catch (error) {
                log('❌ ERRO na busca de gestores: ' + error.message);
            }
        }

        // Executar teste inicial automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            log('Página carregada. Sessão simulada criada.');
            log('Clique nos botões acima para testar as funcionalidades.');
        });
    </script>
</body>
</html>