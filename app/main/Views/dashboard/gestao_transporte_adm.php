<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM e ADM_TRANSPORTE podem acessar
$tipoUsuario = $_SESSION['tipo'] ?? '';
$tipoUsuarioUpper = strtoupper(trim($tipoUsuario));
if (!eAdm() && $tipoUsuarioUpper !== 'ADM_TRANSPORTE') {
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
            $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa'] ?? ''));
            $renavam = preg_replace('/[^0-9]/', '', $_POST['renavam'] ?? '');
            
            // Verificar se placa já existe
            $stmt = $conn->prepare("SELECT id FROM veiculo WHERE placa = :placa");
            $stmt->bindParam(':placa', $placa);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'Placa já cadastrada no sistema.'];
            } else {
                $stmt = $conn->prepare("INSERT INTO veiculo (placa, renavam, marca, modelo, ano, cor, capacidade_maxima, capacidade_minima, tipo, numero_frota, observacoes, criado_por) 
                                       VALUES (:placa, :renavam, :marca, :modelo, :ano, :cor, :capacidade_maxima, :capacidade_minima, :tipo, :numero_frota, :observacoes, :criado_por)");
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
                $stmt->bindParam(':criado_por', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                $resposta = ['status' => true, 'mensagem' => 'Veículo cadastrado com sucesso!'];
            }
        }
        
        // Listar Veículos
        elseif ($acao === 'listar_veiculos') {
            $busca = $_POST['busca'] ?? '';
            $sql = "SELECT v.*, u.username as criado_por_nome 
                    FROM veiculo v 
                    LEFT JOIN usuario u ON v.criado_por = u.id 
                    WHERE 1=1";
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
        
        // Cadastrar Motorista
        elseif ($acao === 'cadastrar_motorista') {
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            $cnh = preg_replace('/[^0-9A-Z]/', '', strtoupper($_POST['cnh'] ?? ''));
            
            // Verificar se CPF já existe
            $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf");
            $stmt->bindParam(':cpf', $cpf);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'CPF já cadastrado no sistema.'];
            } else {
                // Verificar se CNH já existe
                $stmt = $conn->prepare("SELECT id FROM motorista WHERE cnh = :cnh");
                $stmt->bindParam(':cnh', $cnh);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $resposta = ['status' => false, 'mensagem' => 'CNH já cadastrada no sistema.'];
                } else {
                    $conn->beginTransaction();
                    
                    // Criar pessoa
                    $stmt = $conn->prepare("INSERT INTO pessoa (nome, cpf, email, telefone, data_nascimento, tipo) 
                                           VALUES (:nome, :cpf, :email, :telefone, :data_nascimento, 'FUNCIONARIO')");
                    $stmt->bindParam(':nome', $_POST['nome']);
                    $stmt->bindParam(':cpf', $cpf);
                    $stmt->bindValue(':email', $_POST['email'] ?? null);
                    $stmt->bindValue(':telefone', $_POST['telefone'] ?? null);
                    $stmt->bindValue(':data_nascimento', !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null);
                    $stmt->execute();
                    $pessoaId = $conn->lastInsertId();
                    
                    // Criar motorista
                    $stmt = $conn->prepare("INSERT INTO motorista (pessoa_id, cnh, categoria_cnh, validade_cnh, data_admissao, observacoes, criado_por) 
                                           VALUES (:pessoa_id, :cnh, :categoria_cnh, :validade_cnh, :data_admissao, :observacoes, :criado_por)");
                    $stmt->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                    $stmt->bindParam(':cnh', $cnh);
                    $stmt->bindValue(':categoria_cnh', $_POST['categoria_cnh'] ?? null);
                    $stmt->bindValue(':validade_cnh', !empty($_POST['validade_cnh']) ? $_POST['validade_cnh'] : null);
                    $stmt->bindValue(':data_admissao', !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : null);
                    $stmt->bindValue(':observacoes', $_POST['observacoes'] ?? null);
                    $stmt->bindParam(':criado_por', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $conn->commit();
                    $resposta = ['status' => true, 'mensagem' => 'Motorista cadastrado com sucesso!'];
                }
            }
        }
        
        // Listar Motoristas
        elseif ($acao === 'listar_motoristas') {
            $busca = $_POST['busca'] ?? '';
            $sql = "SELECT m.*, p.nome, p.cpf, p.email, p.telefone, u.username as criado_por_nome 
                    FROM motorista m 
                    INNER JOIN pessoa p ON m.pessoa_id = p.id 
                    LEFT JOIN usuario u ON m.criado_por = u.id 
                    WHERE 1=1";
            if (!empty($busca)) {
                $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR m.cnh LIKE :busca)";
            }
            $sql .= " ORDER BY p.nome ASC";
            $stmt = $conn->prepare($sql);
            if (!empty($busca)) {
                $buscaParam = "%{$busca}%";
                $stmt->bindParam(':busca', $buscaParam);
            }
            $stmt->execute();
            $motoristas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $motoristas];
        }
        
        // Listar Rotas
        elseif ($acao === 'listar_rotas') {
            $busca = $_POST['busca'] ?? '';
            $sql = "SELECT r.*, e.nome as escola_nome, v.placa as veiculo_placa, 
                           CONCAT(p.nome, ' - ', m.cnh) as motorista_nome 
                    FROM rota r 
                    LEFT JOIN escola e ON r.escola_id = e.id 
                    LEFT JOIN veiculo v ON r.veiculo_id = v.id 
                    LEFT JOIN motorista m ON r.motorista_id = m.id 
                    LEFT JOIN pessoa p ON m.pessoa_id = p.id 
                    WHERE 1=1";
            if (!empty($busca)) {
                $sql .= " AND (r.nome LIKE :busca OR r.codigo LIKE :busca OR e.nome LIKE :busca)";
            }
            $sql .= " ORDER BY r.nome ASC";
            $stmt = $conn->prepare($sql);
            if (!empty($busca)) {
                $buscaParam = "%{$busca}%";
                $stmt->bindParam(':busca', $buscaParam);
            }
            $stmt->execute();
            $rotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $rotas];
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Buscar dados iniciais
$veiculos = [];
$motoristas = [];
$rotas = [];
$escolas = [];

try {
    $stmt = $conn->query("SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC");
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar escolas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Transporte Escolar - SIGAE</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-active {
            border-bottom: 2px solid #2D5A27;
            color: #2D5A27;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gestão de Transporte Escolar</h1>
                        <p class="text-sm text-gray-600 mt-1">Gerenciar veículos, motoristas, rotas e usuários do transporte</p>
                    </div>
                    <a href="dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-8 overflow-x-auto">
                    <a href="gestao_usuarios_transporte.php" class="py-4 px-2 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-users mr-2"></i>Usuários
                    </a>
                    <button onclick="showTab('veiculos', this)" class="tab-button tab-active py-4 px-2 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-bus mr-2"></i>Veículos
                    </button>
                    <button onclick="showTab('motoristas', this)" class="tab-button py-4 px-2 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-id-card mr-2"></i>Motoristas
                    </button>
                    <button onclick="showTab('rotas', this)" class="tab-button py-4 px-2 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-route mr-2"></i>Rotas
                    </button>
                    <button onclick="showTab('relatorios', this)" class="tab-button py-4 px-2 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-chart-bar mr-2"></i>Relatórios
                    </button>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Tab: Veículos -->
            <div id="tab-veiculos" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Veículos</h2>
                        <button onclick="abrirModalCriarVeiculo()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Cadastrar Veículo
                        </button>
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

            <!-- Tab: Motoristas -->
            <div id="tab-motoristas" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Motoristas</h2>
                        <button onclick="abrirModalCriarMotorista()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Cadastrar Motorista
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <input type="text" id="buscar-motorista" placeholder="Buscar por nome, CPF ou CNH..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CNH</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-motoristas" class="bg-white divide-y divide-gray-200">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Rotas -->
            <div id="tab-rotas" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Rotas</h2>
                        <a href="gestao_rotas_transporte.php" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-map-marked-alt mr-2"></i>Criar Rota no Mapa
                        </a>
                    </div>
                    
                    <div class="mb-4">
                        <input type="text" id="buscar-rota" placeholder="Buscar rotas..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veículo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motorista</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-rotas" class="bg-white divide-y divide-gray-200">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Relatórios -->
            <div id="tab-relatorios" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Relatórios de Transporte</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                            <h3 class="font-semibold text-gray-900 mb-2">Relatório de Viagens</h3>
                            <p class="text-sm text-gray-600 mb-4">Relatório de viagens realizadas</p>
                            <button class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Gerar Relatório
                            </button>
                        </div>
                        <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                            <h3 class="font-semibold text-gray-900 mb-2">Relatório de Rotas</h3>
                            <p class="text-sm text-gray-600 mb-4">Análise de rotas e pontos</p>
                            <button class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Gerar Relatório
                            </button>
                        </div>
                        <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                            <h3 class="font-semibold text-gray-900 mb-2">Relatório de Veículos</h3>
                            <p class="text-sm text-gray-600 mb-4">Status e utilização de veículos</p>
                            <button class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <select name="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
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

    <!-- Modal Criar Motorista -->
    <div id="modalCriarMotorista" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Cadastrar Motorista</h3>
                <button onclick="fecharModalCriarMotorista()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formCriarMotorista" onsubmit="criarMotorista(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                        <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                        <input type="text" name="cpf" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="000.000.000-00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="telefone" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="(85) 99999-9999">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CNH *</label>
                        <input type="text" name="cnh" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria CNH</label>
                        <select name="categoria_cnh" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Validade CNH</label>
                        <input type="date" name="validade_cnh" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão</label>
                        <input type="date" name="data_admissao" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="fecharModalCriarMotorista()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Cadastrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para alternar tabs
        function showTab(tabName, buttonElement = null) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remover classe active de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('tab-active');
            });
            
            // Mostrar tab selecionada
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            // Adicionar classe active ao botão (se fornecido ou encontrar pelo texto)
            if (buttonElement) {
                buttonElement.classList.add('tab-active');
            } else {
                // Encontrar o botão pela aba
                const buttons = document.querySelectorAll('.tab-button');
                buttons.forEach(btn => {
                    if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes("showTab('" + tabName + "')")) {
                        btn.classList.add('tab-active');
                    }
                });
            }
            
            // Carregar dados da tab
            if (tabName === 'veiculos') {
                carregarVeiculos();
            } else if (tabName === 'motoristas') {
                carregarMotoristas();
            } else if (tabName === 'rotas') {
                carregarRotas();
            }
        }

        // Modais
        function abrirModalCriarVeiculo() {
            document.getElementById('modalCriarVeiculo').classList.remove('hidden');
        }

        function fecharModalCriarVeiculo() {
            document.getElementById('modalCriarVeiculo').classList.add('hidden');
            document.getElementById('formCriarVeiculo').reset();
        }

        function abrirModalCriarMotorista() {
            document.getElementById('modalCriarMotorista').classList.remove('hidden');
        }

        function fecharModalCriarMotorista() {
            document.getElementById('modalCriarMotorista').classList.add('hidden');
            document.getElementById('formCriarMotorista').reset();
        }

        // Criar Veículo
        function criarVeiculo(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('acao', 'cadastrar_veiculo');
            
            fetch('gestao_transporte_adm.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalCriarVeiculo();
                    carregarVeiculos();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cadastrar veículo');
            });
        }

        // Criar Motorista
        function criarMotorista(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('acao', 'cadastrar_motorista');
            
            fetch('gestao_transporte_adm.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalCriarMotorista();
                    carregarMotoristas();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cadastrar motorista');
            });
        }

        function carregarVeiculos() {
            const busca = document.getElementById('buscar-veiculo')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'listar_veiculos');
            formData.append('busca', busca);
            
            fetch('gestao_transporte_adm.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const tbody = document.getElementById('lista-veiculos');
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
                                <button onclick="editarVeiculo(${v.id})" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirVeiculo(${v.id})" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        }

        function carregarMotoristas() {
            const busca = document.getElementById('buscar-motorista')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'listar_motoristas');
            formData.append('busca', busca);
            
            fetch('gestao_transporte_adm.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const tbody = document.getElementById('lista-motoristas');
                    tbody.innerHTML = data.dados.map(m => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${m.nome}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${m.cnh}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${m.categoria_cnh || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${m.telefone || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full ${m.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${m.ativo ? 'Ativo' : 'Inativo'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button onclick="editarMotorista(${m.id})" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirMotorista(${m.id})" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        }

        function carregarRotas() {
            const busca = document.getElementById('buscar-rota')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'listar_rotas');
            formData.append('busca', busca);
            
            fetch('gestao_transporte_adm.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const tbody = document.getElementById('lista-rotas');
                    tbody.innerHTML = data.dados.map(r => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${r.nome}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.escola_nome || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.veiculo_placa || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.motorista_nome || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.total_alunos || 0}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full ${r.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${r.ativo ? 'Ativa' : 'Inativa'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button onclick="visualizarRota(${r.id})" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editarRota(${r.id})" class="text-green-600 hover:text-green-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        }

        // Verificar hash na URL e abrir a aba correspondente
        function verificarHashEAbrirAba() {
            const hash = window.location.hash.replace('#', '');
            if (hash && ['veiculos', 'motoristas', 'rotas', 'relatorios'].includes(hash)) {
                showTab(hash);
            }
        }
        
        // Executar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            verificarHashEAbrirAba();
        });
        
        // Executar também quando o hash mudar (caso o usuário clique em um link com hash)
        window.addEventListener('hashchange', verificarHashEAbrirAba);
        
        // Event listeners
        document.getElementById('buscar-veiculo')?.addEventListener('input', debounce(carregarVeiculos, 500));
        document.getElementById('buscar-motorista')?.addEventListener('input', debounce(carregarMotoristas, 500));
        document.getElementById('buscar-rota')?.addEventListener('input', debounce(carregarRotas, 500));

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            carregarVeiculos();
        });
    </script>
</body>
</html>

