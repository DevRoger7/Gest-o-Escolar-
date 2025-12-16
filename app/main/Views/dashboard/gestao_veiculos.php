<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// ADM_TRANSPORTE pode cadastrar/editar, TRANSPORTE_ALUNO pode visualizar
$tipoUsuario = $_SESSION['tipo'] ?? '';
$tipoUsuarioUpper = strtoupper(trim($tipoUsuario));
$podeEditar = eAdm() || $tipoUsuarioUpper === 'ADM_TRANSPORTE';
$podeVisualizar = $podeEditar || $tipoUsuarioUpper === 'TRANSPORTE_ALUNO';

if (!$podeVisualizar) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$usuarioId = $_SESSION['usuario_id'] ?? null;

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    $acao = $_POST['acao'];
    $resposta = ['status' => false, 'mensagem' => 'Ação não reconhecida'];
    
    try {
        // Cadastrar Veículo
        if ($acao === 'cadastrar_veiculo') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para cadastrar veículos');
            }
            
            $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa'] ?? ''));
            $renavam = preg_replace('/[^0-9]/', '', $_POST['renavam'] ?? '');
            
            if (empty($placa)) {
                throw new Exception('Placa é obrigatória');
            }
            
            if (empty($_POST['capacidade_maxima'])) {
                throw new Exception('Capacidade máxima é obrigatória');
            }
            
            if (empty($_POST['tipo'])) {
                throw new Exception('Tipo é obrigatório');
            }
            
            // Verificar se placa já existe
            $stmt = $conn->prepare("SELECT id FROM veiculo WHERE placa = :placa AND ativo = 1");
            $stmt->bindParam(':placa', $placa);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'Placa já cadastrada no sistema.'];
            } else {
                // Verificar se o usuário existe na tabela usuario
                $criadoPor = null;
                if (!empty($usuarioId)) {
                    $stmt = $conn->prepare("SELECT id FROM usuario WHERE id = :id");
                    $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                    if (!$stmt->fetch()) {
                        // Usuário não existe, usar NULL
                        $criadoPor = null;
                    } else {
                        $criadoPor = $usuarioId;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO veiculo (placa, renavam, marca, modelo, ano, cor, capacidade_maxima, capacidade_minima, tipo, numero_frota, observacoes, criado_por, ativo) 
                                       VALUES (:placa, :renavam, :marca, :modelo, :ano, :cor, :capacidade_maxima, :capacidade_minima, :tipo, :numero_frota, :observacoes, :criado_por, 1)");
                $stmt->bindParam(':placa', $placa);
                $stmt->bindValue(':renavam', !empty($renavam) ? $renavam : null);
                $stmt->bindValue(':marca', !empty($_POST['marca']) ? trim($_POST['marca']) : null);
                $stmt->bindValue(':modelo', !empty($_POST['modelo']) ? trim($_POST['modelo']) : null);
                $stmt->bindValue(':ano', !empty($_POST['ano']) ? (int)$_POST['ano'] : null, PDO::PARAM_INT);
                $stmt->bindValue(':cor', !empty($_POST['cor']) ? trim($_POST['cor']) : null);
                $stmt->bindValue(':capacidade_maxima', (int)$_POST['capacidade_maxima'], PDO::PARAM_INT);
                $stmt->bindValue(':capacidade_minima', !empty($_POST['capacidade_minima']) ? (int)$_POST['capacidade_minima'] : null, PDO::PARAM_INT);
                $stmt->bindParam(':tipo', $_POST['tipo']);
                $stmt->bindValue(':numero_frota', !empty($_POST['numero_frota']) ? trim($_POST['numero_frota']) : null);
                $stmt->bindValue(':observacoes', !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null);
                $stmt->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $resposta = ['status' => true, 'mensagem' => 'Veículo cadastrado com sucesso!'];
                } else {
                    throw new Exception('Erro ao executar inserção no banco de dados');
                }
            }
        }
        
        // Listar Veículos
        elseif ($acao === 'listar_veiculos') {
            $busca = $_POST['busca'] ?? '';
            $sql = "SELECT v.*, u.username as criado_por_nome 
                    FROM veiculo v 
                    LEFT JOIN usuario u ON v.criado_por = u.id 
                    WHERE v.ativo = 1";
            if (!empty($busca)) {
                $sql .= " AND (v.placa LIKE :busca OR v.marca LIKE :busca OR v.modelo LIKE :busca)";
            }
            $sql .= " ORDER BY v.placa ASC";
            $stmt = $conn->prepare($sql);
            if (!empty($busca)) {
                $buscaParam = "%{$busca}%";
                $stmt->bindParam(':busca', $buscaParam);
            }
            $stmt->execute();
            $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $veiculos];
        }
        
        // Editar Veículo
        elseif ($acao === 'editar_veiculo') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para editar veículos');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do veículo não informado');
            }
            
            $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa'] ?? ''));
            $renavam = preg_replace('/[^0-9]/', '', $_POST['renavam'] ?? '');
            
            // Verificar se placa já existe em outro veículo
            $stmt = $conn->prepare("SELECT id FROM veiculo WHERE placa = :placa AND id != :id");
            $stmt->bindParam(':placa', $placa);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'Placa já cadastrada em outro veículo.'];
            } else {
                $stmt = $conn->prepare("UPDATE veiculo SET 
                                       placa = :placa, renavam = :renavam, marca = :marca, modelo = :modelo, 
                                       ano = :ano, cor = :cor, capacidade_maxima = :capacidade_maxima, 
                                       capacidade_minima = :capacidade_minima, tipo = :tipo, 
                                       numero_frota = :numero_frota, observacoes = :observacoes
                                       WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':placa', $placa);
                $stmt->bindValue(':renavam', !empty($renavam) ? $renavam : null);
                $stmt->bindValue(':marca', $_POST['marca'] ?? null);
                $stmt->bindValue(':modelo', $_POST['modelo'] ?? null);
                $stmt->bindValue(':ano', !empty($_POST['ano']) ? $_POST['ano'] : null, PDO::PARAM_INT);
                $stmt->bindValue(':cor', $_POST['cor'] ?? null);
                $stmt->bindParam(':capacidade_maxima', $_POST['capacidade_maxima'], PDO::PARAM_INT);
                $stmt->bindValue(':capacidade_minima', !empty($_POST['capacidade_minima']) ? $_POST['capacidade_minima'] : null, PDO::PARAM_INT);
                $stmt->bindParam(':tipo', $_POST['tipo']);
                $stmt->bindValue(':numero_frota', $_POST['numero_frota'] ?? null);
                $stmt->bindValue(':observacoes', $_POST['observacoes'] ?? null);
                $stmt->execute();
                $resposta = ['status' => true, 'mensagem' => 'Veículo atualizado com sucesso!'];
            }
        }
        
        // Excluir Veículo
        elseif ($acao === 'excluir_veiculo') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para excluir veículos');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do veículo não informado');
            }
            
            $stmt = $conn->prepare("UPDATE veiculo SET ativo = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resposta = ['status' => true, 'mensagem' => 'Veículo excluído com sucesso!'];
        }
        
        // Buscar Veículo
        elseif ($acao === 'buscar_veiculo') {
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do veículo não informado');
            }
            
            $stmt = $conn->prepare("SELECT * FROM veiculo WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $veiculo ?: null];
        }
        
    } catch (PDOException $e) {
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => $e->getMessage()];
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Veículos - SIGAE</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .menu-item {
            transition: all 0.2s ease;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item.active svg {
            color: #2D5A27;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
    // Incluir sidebar correta baseada no tipo de usuário
    $tipoUsuario = $_SESSION['tipo'] ?? '';
    $tipoUsuarioUpper = strtoupper(trim($tipoUsuario));
    
    if ($tipoUsuarioUpper === 'ADM_TRANSPORTE') {
        include 'components/sidebar_transporte.php';
    } elseif ($tipoUsuarioUpper === 'TRANSPORTE_ALUNO') {
        include 'components/sidebar_transporte_aluno.php';
    } elseif (eAdm()) {
        include 'components/sidebar_adm.php';
    } else {
        include 'components/sidebar_adm.php'; // Fallback
    }
    ?>
    
    <!-- Header Mobile -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-sm z-40 p-4 flex items-center justify-between">
        <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h1 class="text-lg font-bold text-gray-800">Veículos</h1>
        <div class="w-10"></div>
    </header>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen pt-16 lg:pt-0">
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestão de Veículos</h1>
                <p class="text-gray-600">Cadastre e gerencie os veículos do transporte escolar</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Veículos Cadastrados</h2>
                    <?php if ($podeEditar): ?>
                    <button onclick="abrirModalCriarVeiculo()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Cadastrar Veículo
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <input type="text" id="buscar-veiculo" placeholder="Buscar por placa, marca ou modelo..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marca/Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacidade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-veiculos" class="bg-white divide-y divide-gray-200">
                            <!-- Será preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Criar Veículo -->
    <div id="modalCriarVeiculo" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Cadastrar Veículo</h3>
                <button onclick="fecharModalCriarVeiculo()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formCriarVeiculo" onsubmit="criarVeiculo(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Placa *</label>
                        <input type="text" name="placa" required maxlength="7" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="ABC1234">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RENAVAM</label>
                        <input type="text" name="renavam" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" name="marca" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                        <input type="text" name="modelo" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                        <input type="number" name="ano" min="1900" max="<?= date('Y') + 1 ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                        <input type="text" name="cor" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select name="tipo" id="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione um tipo</option>
                            <option value="ONIBUS">Ônibus</option>
                            <option value="VAN">Van</option>
                            <option value="MICROONIBUS">Microônibus</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número da Frota</label>
                        <input type="text" name="numero_frota" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidade Máxima *</label>
                        <input type="number" name="capacidade_maxima" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: 50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidade Mínima</label>
                        <input type="number" name="capacidade_minima" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: 30">
                        <p class="text-xs text-gray-500 mt-1">Lotação mínima recomendada para viabilidade</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="fecharModalCriarVeiculo()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Cadastrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Veículo -->
    <div id="modalEditarVeiculo" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Editar Veículo</h3>
                <button onclick="fecharModalEditarVeiculo()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formEditarVeiculo" onsubmit="editarVeiculo(event)">
                <input type="hidden" id="editar-veiculo-id" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Placa *</label>
                        <input type="text" id="editar-placa" name="placa" required maxlength="7" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RENAVAM</label>
                        <input type="text" id="editar-renavam" name="renavam" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" id="editar-marca" name="marca" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                        <input type="text" id="editar-modelo" name="modelo" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                        <input type="number" id="editar-ano" name="ano" min="1900" max="<?= date('Y') + 1 ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                        <input type="text" id="editar-cor" name="cor" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select id="editar-tipo" name="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione um tipo</option>
                            <option value="ONIBUS">Ônibus</option>
                            <option value="VAN">Van</option>
                            <option value="MICROONIBUS">Microônibus</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número da Frota</label>
                        <input type="text" id="editar-numero-frota" name="numero_frota" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidade Máxima *</label>
                        <input type="number" id="editar-capacidade-maxima" name="capacidade_maxima" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidade Mínima</label>
                        <input type="number" id="editar-capacidade-minima" name="capacidade_minima" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea id="editar-observacoes" name="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="fecharModalEditarVeiculo()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const podeEditar = <?= $podeEditar ? 'true' : 'false' ?>;
        
        // Modais
        function abrirModalCriarVeiculo() {
            document.getElementById('modalCriarVeiculo').classList.remove('hidden');
        }

        function fecharModalCriarVeiculo() {
            document.getElementById('modalCriarVeiculo').classList.add('hidden');
            document.getElementById('formCriarVeiculo').reset();
        }

        function abrirModalEditarVeiculo(id) {
            // Buscar dados do veículo
            const formData = new FormData();
            formData.append('acao', 'buscar_veiculo');
            formData.append('id', id);
            
            fetch('gestao_veiculos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status && data.dados) {
                    const v = data.dados;
                    document.getElementById('editar-veiculo-id').value = v.id;
                    document.getElementById('editar-placa').value = v.placa || '';
                    document.getElementById('editar-renavam').value = v.renavam || '';
                    document.getElementById('editar-marca').value = v.marca || '';
                    document.getElementById('editar-modelo').value = v.modelo || '';
                    document.getElementById('editar-ano').value = v.ano || '';
                    document.getElementById('editar-cor').value = v.cor || '';
                    document.getElementById('editar-tipo').value = v.tipo || 'ONIBUS';
                    document.getElementById('editar-numero-frota').value = v.numero_frota || '';
                    document.getElementById('editar-capacidade-maxima').value = v.capacidade_maxima || '';
                    document.getElementById('editar-capacidade-minima').value = v.capacidade_minima || '';
                    document.getElementById('editar-observacoes').value = v.observacoes || '';
                    document.getElementById('modalEditarVeiculo').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar dados do veículo');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados do veículo');
            });
        }

        function fecharModalEditarVeiculo() {
            document.getElementById('modalEditarVeiculo').classList.add('hidden');
            document.getElementById('formEditarVeiculo').reset();
        }

        // Criar Veículo
        function criarVeiculo(e) {
            e.preventDefault();
            
            // Validar campo tipo
            const tipoSelect = document.getElementById('tipo');
            if (!tipoSelect || !tipoSelect.value) {
                alert('Por favor, selecione o tipo do veículo');
                tipoSelect?.focus();
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('acao', 'cadastrar_veiculo');
            
            // Desabilitar botão durante o envio
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Criando...';
            }
            
            fetch('gestao_veiculos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Resposta do servidor:', JSON.stringify(data, null, 2));
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalCriarVeiculo();
                    setTimeout(() => {
                        carregarVeiculos();
                    }, 500);
                } else {
                    alert('Erro: ' + (data.mensagem || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cadastrar veículo: ' + error.message);
            })
            .finally(() => {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Criar';
                }
            });
        }

        // Editar Veículo
        function editarVeiculo(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('acao', 'editar_veiculo');
            
            fetch('gestao_veiculos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalEditarVeiculo();
                    carregarVeiculos();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao editar veículo');
            });
        }

        // Excluir Veículo
        function excluirVeiculo(id) {
            if (!confirm('Tem certeza que deseja excluir este veículo?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'excluir_veiculo');
            formData.append('id', id);
            
            fetch('gestao_veiculos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    carregarVeiculos();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir veículo');
            });
        }

        // Carregar Veículos
        function carregarVeiculos() {
            const busca = document.getElementById('buscar-veiculo')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'listar_veiculos');
            formData.append('busca', busca);
            
            fetch('gestao_veiculos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const tbody = document.getElementById('lista-veiculos');
                    if (data.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum veículo cadastrado</td></tr>';
                    } else {
                        tbody.innerHTML = data.dados.map(v => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${v.placa}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${v.tipo}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${v.marca || ''} ${v.modelo || ''}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${v.capacidade_minima || '-'} - ${v.capacidade_maxima}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full ${v.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${v.ativo ? 'Ativo' : 'Inativo'}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${podeEditar ? `
                                    <button onclick="abrirModalEditarVeiculo(${v.id})" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="excluirVeiculo(${v.id})" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    ` : '<span class="text-gray-400">Somente visualização</span>'}
                                </td>
                            </tr>
                        `).join('');
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        }

        // Event listeners
        document.getElementById('buscar-veiculo')?.addEventListener('input', function() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(carregarVeiculos, 500);
        });

        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            carregarVeiculos();
        });

        // Função de toggle sidebar (mobile)
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };

        // Fechar sidebar ao clicar no overlay
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('mobileOverlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    window.toggleSidebar();
                });
            }
        });

        // Função de logout
        window.confirmLogout = function() {
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = '../auth/logout.php';
            }
        };
    </script>
</body>
</html>

