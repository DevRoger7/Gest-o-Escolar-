<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eNutricionista() && !eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar produtos para substituição
$sqlProdutos = "SELECT id, nome, unidade_medida, categoria FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Sugestões de Substituição') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/sidebar_nutricionista.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Sugestões de Substituição</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="hidden lg:block">
                            <div class="text-right px-4 py-2">
                                <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                <p class="text-xs text-gray-500">Órgão Central</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Substituições de Alimentos</h2>
                    <p class="text-gray-600 mt-1">Sugira substituições de alimentos considerando disponibilidade, sazonalidade e valor nutricional equivalente</p>
                </div>
                
                <!-- Formulário de Substituição -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Nova Sugestão de Substituição</h3>
                    <form id="formSubstituicao" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alimento Original</label>
                                <select id="alimento-original" name="alimento_original" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                    <option value="">Selecione um alimento</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?> (<?= htmlspecialchars($produto['unidade_medida']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alimento Substituto</label>
                                <select id="alimento-substituto" name="alimento_substituto" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                    <option value="">Selecione um alimento</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?> (<?= htmlspecialchars($produto['unidade_medida']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Substituição</label>
                            <select id="motivo" name="motivo" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                <option value="">Selecione um motivo</option>
                                <option value="SAZONALIDADE">Sazonalidade</option>
                                <option value="DISPONIBILIDADE">Disponibilidade</option>
                                <option value="NECESSIDADE_ESPECIAL">Necessidade Especial</option>
                                <option value="VALOR_NUTRICIONAL">Equivalência Nutricional</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Descreva a equivalência nutricional, proporção de substituição, etc."></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                                Salvar Sugestão
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Lista de Substituições -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Substituições Registradas</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-500 text-center">Funcionalidade em desenvolvimento. As substituições serão salvas e disponibilizadas para consulta.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        document.getElementById('formSubstituicao').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Funcionalidade de salvamento de substituições será implementada em breve.');
        });
    </script>
</body>
</html>

