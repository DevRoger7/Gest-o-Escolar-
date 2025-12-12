<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Lotação de Professor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Diagnóstico - Lotação de Professor</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Teste de Requisições AJAX</h2>
            
            <div class="space-y-4">
                <div>
                    <button onclick="testarLotarProfessor()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                        Testar Lotar Professor (POST)
                    </button>
                </div>
                
                <div>
                    <button onclick="testarBuscarProfessor()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                        Testar Buscar Professor (GET)
                    </button>
                </div>
                
                <div>
                    <button onclick="testarBuscarLotacoes()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium">
                        Testar Buscar Lotações (GET)
                    </button>
                </div>
                
                <div>
                    <button onclick="limparResultados()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium">
                        Limpar Resultados
                    </button>
                </div>
            </div>
        </div>
        
        <div id="resultados" class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Resultados dos Testes</h2>
            <div id="conteudo-resultados" class="space-y-4">
                <p class="text-gray-500">Clique em um dos botões acima para iniciar os testes.</p>
            </div>
        </div>
    </div>
    
    <script>
        function adicionarResultado(titulo, dados, tipo = 'info') {
            const container = document.getElementById('conteudo-resultados');
            const div = document.createElement('div');
            div.className = `border rounded-lg p-4 ${tipo === 'erro' ? 'border-red-500 bg-red-50' : tipo === 'sucesso' ? 'border-green-500 bg-green-50' : 'border-gray-300 bg-gray-50'}`;
            
            div.innerHTML = `
                <h3 class="font-semibold text-gray-800 mb-2">${titulo}</h3>
                <pre class="text-xs overflow-auto max-h-96 bg-white p-3 rounded border">${JSON.stringify(dados, null, 2)}</pre>
            `;
            
            container.appendChild(div);
        }
        
        function limparResultados() {
            document.getElementById('conteudo-resultados').innerHTML = '<p class="text-gray-500">Resultados limpos.</p>';
        }
        
        async function testarLotarProfessor() {
            const formData = new FormData();
            formData.append('acao', 'lotar_professor');
            formData.append('professor_id', '1'); // ID de teste
            formData.append('escola_id', '1'); // ID de teste
            formData.append('data_inicio', new Date().toISOString().split('T')[0]);
            formData.append('carga_horaria', '20');
            formData.append('observacao', 'Teste de diagnóstico');
            
            try {
                adicionarResultado('Testando: Lotar Professor (POST)', {
                    url: 'gestao_professores_adm.php',
                    method: 'POST',
                    dados: Object.fromEntries(formData)
                }, 'info');
                
                const response = await fetch('gestao_professores_adm.php', {
                    method: 'POST',
                    body: formData
                });
                
                const contentType = response.headers.get('content-type');
                const status = response.status;
                const statusText = response.statusText;
                
                let resultado;
                const text = await response.text();
                
                // Tentar parsear como JSON
                try {
                    resultado = JSON.parse(text);
                } catch (e) {
                    // Se não for JSON, mostrar o texto
                    resultado = {
                        erro: 'Resposta não é JSON válido',
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        respostaTexto: text.substring(0, 1000) // Primeiros 1000 caracteres
                    };
                }
                
                if (contentType && contentType.includes('application/json')) {
                    adicionarResultado('✅ Resposta JSON Recebida', {
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        resposta: resultado
                    }, 'sucesso');
                } else {
                    adicionarResultado('❌ Resposta NÃO é JSON (HTML retornado)', {
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        respostaTexto: text.substring(0, 2000),
                        problema: 'O servidor está retornando HTML em vez de JSON. Isso geralmente acontece quando há um erro PHP ou o endpoint não existe.'
                    }, 'erro');
                }
            } catch (error) {
                adicionarResultado('❌ Erro na Requisição', {
                    erro: error.message,
                    stack: error.stack
                }, 'erro');
            }
        }
        
        async function testarBuscarProfessor() {
            try {
                adicionarResultado('Testando: Buscar Professor (GET)', {
                    url: 'gestao_professores_adm.php?acao=buscar_professor&id=1',
                    method: 'GET'
                }, 'info');
                
                const response = await fetch('gestao_professores_adm.php?acao=buscar_professor&id=1');
                
                const contentType = response.headers.get('content-type');
                const status = response.status;
                const statusText = response.statusText;
                
                let resultado;
                const text = await response.text();
                
                try {
                    resultado = JSON.parse(text);
                } catch (e) {
                    resultado = {
                        erro: 'Resposta não é JSON válido',
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        respostaTexto: text.substring(0, 1000)
                    };
                }
                
                if (contentType && contentType.includes('application/json')) {
                    adicionarResultado('✅ Resposta JSON Recebida', {
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        resposta: resultado
                    }, 'sucesso');
                } else {
                    adicionarResultado('❌ Resposta NÃO é JSON', {
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        respostaTexto: text.substring(0, 2000)
                    }, 'erro');
                }
            } catch (error) {
                adicionarResultado('❌ Erro na Requisição', {
                    erro: error.message,
                    stack: error.stack
                }, 'erro');
            }
        }
        
        async function testarBuscarLotacoes() {
            try {
                adicionarResultado('Testando: Buscar Lotações (GET)', {
                    url: 'gestao_professores_adm.php?acao=buscar_lotacoes&professor_id=1',
                    method: 'GET'
                }, 'info');
                
                const response = await fetch('gestao_professores_adm.php?acao=buscar_lotacoes&professor_id=1');
                
                const contentType = response.headers.get('content-type');
                const status = response.status;
                const statusText = response.statusText;
                
                let resultado;
                const text = await response.text();
                
                try {
                    resultado = JSON.parse(text);
                } catch (e) {
                    resultado = {
                        erro: 'Resposta não é JSON válido',
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        respostaTexto: text.substring(0, 1000)
                    };
                }
                
                if (contentType && contentType.includes('application/json')) {
                    adicionarResultado('✅ Resposta JSON Recebida', {
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        resposta: resultado
                    }, 'sucesso');
                } else {
                    adicionarResultado('❌ Resposta NÃO é JSON', {
                        contentType: contentType,
                        status: status,
                        statusText: statusText,
                        respostaTexto: text.substring(0, 2000)
                    }, 'erro');
                }
            } catch (error) {
                adicionarResultado('❌ Erro na Requisição', {
                    erro: error.message,
                    stack: error.stack
                }, 'erro');
            }
        }
    </script>
</body>
</html>

