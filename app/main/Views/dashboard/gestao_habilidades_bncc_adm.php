<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Verificar estrutura da tabela
$sqlVerificarColunas = "SHOW COLUMNS FROM habilidades_bncc";
$stmtVerificar = $conn->prepare($sqlVerificarColunas);
$stmtVerificar->execute();
$colunas = $stmtVerificar->fetchAll(PDO::FETCH_COLUMN);

// Determinar nome da coluna de código
$colunaCodigo = 'codigo_bncc';
if (!in_array('codigo_bncc', $colunas) && in_array('codigo', $colunas)) {
    $colunaCodigo = 'codigo';
}

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'cadastrar_habilidade') {
        try {
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            $descricao = trim($_POST['descricao'] ?? '');
            
            if (empty($codigo)) {
                throw new Exception('Código da habilidade é obrigatório.');
            }
            
            if (empty($descricao)) {
                throw new Exception('Descrição da habilidade é obrigatória.');
            }
            
            // Verificar se código já existe
            $sqlVerificar = "SELECT id FROM habilidades_bncc WHERE {$colunaCodigo} = :codigo";
            $stmtVerificar = $conn->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':codigo', $codigo);
            $stmtVerificar->execute();
            
            if ($stmtVerificar->fetch()) {
                throw new Exception('Este código de habilidade já está cadastrado.');
            }
            
            // Preparar campos opcionais
            $nivelEnsino = !empty($_POST['nivel_ensino']) ? trim($_POST['nivel_ensino']) : null;
            $disciplinaId = !empty($_POST['disciplina_id']) ? (int)$_POST['disciplina_id'] : null;
            $serie = !empty($_POST['serie']) ? trim($_POST['serie']) : null;
            $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
            
            // Construir query baseada nas colunas disponíveis
            $campos = [$colunaCodigo, 'descricao'];
            $valores = [':codigo', ':descricao'];
            
            if (in_array('nivel_ensino', $colunas)) {
                $campos[] = 'nivel_ensino';
                $valores[] = ':nivel_ensino';
            }
            
            if (in_array('disciplina_id', $colunas)) {
                $campos[] = 'disciplina_id';
                $valores[] = ':disciplina_id';
            }
            
            if (in_array('serie', $colunas)) {
                $campos[] = 'serie';
                $valores[] = ':serie';
            }
            
            if (in_array('ativo', $colunas)) {
                $campos[] = 'ativo';
                $valores[] = ':ativo';
            }
            
            $sql = "INSERT INTO habilidades_bncc (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $valores) . ")";
            $stmt = $conn->prepare($sql);
            
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':descricao', $descricao);
            
            if (in_array('nivel_ensino', $colunas)) {
                $stmt->bindParam(':nivel_ensino', $nivelEnsino);
            }
            
            if (in_array('disciplina_id', $colunas)) {
                $stmt->bindParam(':disciplina_id', $disciplinaId);
            }
            
            if (in_array('serie', $colunas)) {
                $stmt->bindParam(':serie', $serie);
            }
            
            if (in_array('ativo', $colunas)) {
                $stmt->bindParam(':ativo', $ativo);
            }
            
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Habilidade BNCC cadastrada com sucesso!'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Buscar disciplinas para o select
$sqlDisciplinas = "SELECT id, nome FROM disciplina WHERE ativo = 1 ORDER BY nome ASC";
$stmtDisciplinas = $conn->prepare($sqlDisciplinas);
$stmtDisciplinas->execute();
$disciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);

// Buscar habilidades existentes
$sqlHabilidades = "SELECT id, {$colunaCodigo} as codigo, descricao";
if (in_array('nivel_ensino', $colunas)) {
    $sqlHabilidades .= ", nivel_ensino";
}
if (in_array('disciplina_id', $colunas)) {
    $sqlHabilidades .= ", disciplina_id";
}
if (in_array('serie', $colunas)) {
    $sqlHabilidades .= ", serie";
}
if (in_array('ativo', $colunas)) {
    $sqlHabilidades .= ", ativo";
}
$sqlHabilidades .= " FROM habilidades_bncc ORDER BY {$colunaCodigo} ASC LIMIT 100";
$stmtHabilidades = $conn->prepare($sqlHabilidades);
$stmtHabilidades->execute();
$habilidades = $stmtHabilidades->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Gestão de Habilidades BNCC') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.12) 0%, rgba(220, 38, 38, 0.06) 100%);
            border-right: 3px solid #dc2626;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.08) 0%, rgba(220, 38, 38, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Habilidades BNCC</h1>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Habilidades BNCC</h2>
                        <p class="text-gray-600 mt-1">Cadastre e gerencie habilidades da Base Nacional Comum Curricular</p>
                    </div>
                    <button onclick="abrirModalNovaHabilidade()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Habilidade</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Código</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Descrição</th>
                                    <?php if (in_array('nivel_ensino', $colunas)): ?>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nível de Ensino</th>
                                    <?php endif; ?>
                                    <?php if (in_array('serie', $colunas)): ?>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Série</th>
                                    <?php endif; ?>
                                    <?php if (in_array('ativo', $colunas)): ?>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($habilidades)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhuma habilidade cadastrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($habilidades as $hab): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4 font-mono text-sm"><?= htmlspecialchars($hab['codigo']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($hab['descricao']) ?></td>
                                            <?php if (in_array('nivel_ensino', $colunas)): ?>
                                            <td class="py-3 px-4 text-sm"><?= htmlspecialchars($hab['nivel_ensino'] ?? '-') ?></td>
                                            <?php endif; ?>
                                            <?php if (in_array('serie', $colunas)): ?>
                                            <td class="py-3 px-4 text-sm"><?= htmlspecialchars($hab['serie'] ?? '-') ?></td>
                                            <?php endif; ?>
                                            <?php if (in_array('ativo', $colunas)): ?>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 text-xs rounded-full <?= $hab['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                    <?= $hab['ativo'] ? 'Ativo' : 'Inativo' ?>
                                                </span>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de Cadastro -->
    <div id="modalNovaHabilidade" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center" style="display: none;">
        <div class="bg-white w-full h-full flex flex-col shadow-2xl max-w-4xl mx-auto my-8 rounded-lg">
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold text-gray-900">Cadastrar Nova Habilidade BNCC</h2>
                <button onclick="fecharModalNovaHabilidade()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                <form id="formNovaHabilidade" class="space-y-6">
                    <div id="alertaErro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"></div>
                    <div id="alertaSucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg"></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código BNCC *</label>
                            <input type="text" name="codigo" id="codigo" required 
                                   placeholder="Ex: EF01MA01, EF69LP01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   style="text-transform: uppercase;">
                            <p class="text-xs text-gray-500 mt-1">Formato: EF + Ano + Disciplina + Número (ex: EF01MA01)</p>
                        </div>
                        
                        <?php if (in_array('nivel_ensino', $colunas)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nível de Ensino</label>
                            <select name="nivel_ensino" id="nivel_ensino"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Selecione...</option>
                                <option value="ENSINO_FUNDAMENTAL_ANOS_INICIAIS">Ensino Fundamental - Anos Iniciais</option>
                                <option value="ENSINO_FUNDAMENTAL_ANOS_FINAIS">Ensino Fundamental - Anos Finais</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('disciplina_id', $colunas)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Disciplina</label>
                            <select name="disciplina_id" id="disciplina_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Selecione...</option>
                                <?php foreach ($disciplinas as $disc): ?>
                                    <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('serie', $colunas)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                            <input type="text" name="serie" id="serie" 
                                   placeholder="Ex: 1º, 2º, 6º ao 9º"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('ativo', $colunas)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="ativo" id="ativo"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descrição *</label>
                        <textarea name="descricao" id="descricao" required rows="6"
                                  placeholder="Descreva a habilidade BNCC..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button type="button" onclick="fecharModalNovaHabilidade()" 
                        class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit" form="formNovaHabilidade" id="btnSalvar"
                        class="px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Habilidade</span>
                    <svg id="spinnerSalvar" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function abrirModalNovaHabilidade() {
            const modal = document.getElementById('modalNovaHabilidade');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            document.getElementById('alertaErro').classList.add('hidden');
            document.getElementById('alertaSucesso').classList.add('hidden');
            document.getElementById('formNovaHabilidade').reset();
        }
        
        function fecharModalNovaHabilidade() {
            const modal = document.getElementById('modalNovaHabilidade');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }
        
        // Converter código para maiúsculas
        document.getElementById('codigo')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
        
        // Submissão do formulário
        document.getElementById('formNovaHabilidade').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSalvar = document.getElementById('btnSalvar');
            const spinner = document.getElementById('spinnerSalvar');
            const alertaErro = document.getElementById('alertaErro');
            const alertaSucesso = document.getElementById('alertaSucesso');
            
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            alertaSucesso.classList.add('hidden');
            
            const formData = new FormData(this);
            formData.append('acao', 'cadastrar_habilidade');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertaSucesso.textContent = data.message;
                    alertaSucesso.classList.remove('hidden');
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alertaErro.textContent = data.message || 'Erro ao cadastrar habilidade.';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = 'Erro ao processar requisição. Tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        });
        
        // Fechar modal ao clicar fora
        document.getElementById('modalNovaHabilidade')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalNovaHabilidade();
            }
        });
    </script>
</body>
</html>

