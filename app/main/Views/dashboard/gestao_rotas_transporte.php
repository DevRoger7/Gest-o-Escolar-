<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM_TRANSPORTE e TRANSPORTE_ALUNO podem acessar
$tipoUsuario = $_SESSION['tipo'] ?? '';
$tipoUsuarioUpper = strtoupper(trim($tipoUsuario));
if (!eAdm() && $tipoUsuarioUpper !== 'ADM_TRANSPORTE' && $tipoUsuarioUpper !== 'TRANSPORTE_ALUNO') {
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
        // Buscar alunos com geolocalização
        if ($acao === 'buscar_alunos_geolocalizacao') {
            $escolaId = $_POST['escola_id'] ?? null;
            $turno = $_POST['turno'] ?? null;
            
            // Buscar alunos agrupados por distrito_transporte
            $sql = "SELECT 
                           a.distrito_transporte as localidade,
                           COUNT(DISTINCT a.id) as total_alunos,
                           GROUP_CONCAT(DISTINCT a.id) as alunos_ids,
                           GROUP_CONCAT(DISTINCT p.nome SEPARATOR ', ') as alunos_nomes,
                           e.id as escola_id, 
                           e.nome as escola_nome,
                           t.turno,
                           COALESCE(AVG(ga.latitude), -3.890277) as latitude,
                           COALESCE(AVG(ga.longitude), -38.625000) as longitude
                    FROM aluno a
                    INNER JOIN pessoa p ON a.pessoa_id = p.id
                    LEFT JOIN aluno_turma at ON a.id = at.aluno_id AND (at.fim IS NULL OR at.fim = '' OR at.fim = '0000-00-00')
                    LEFT JOIN turma t ON at.turma_id = t.id AND t.ativo = 1
                    LEFT JOIN escola e ON t.escola_id = e.id AND e.ativo = 1
                    LEFT JOIN geolocalizacao_aluno ga ON a.id = ga.aluno_id AND ga.principal = 1
                    WHERE a.ativo = 1 
                      AND a.precisa_transporte = 1 
                      AND a.distrito_transporte IS NOT NULL 
                      AND a.distrito_transporte != ''";
            
            $params = [];
            if ($escolaId) {
                $sql .= " AND e.id = :escola_id";
                $params[':escola_id'] = $escolaId;
            }
            if ($turno) {
                $sql .= " AND t.turno = :turno";
                $params[':turno'] = $turno;
            }
            
            $sql .= " GROUP BY a.distrito_transporte, e.id, e.nome, t.turno
                      HAVING latitude IS NOT NULL AND longitude IS NOT NULL";
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $alunos];
        }
        
        // Buscar pontos de rota
        elseif ($acao === 'buscar_pontos_rota') {
            $rotaId = $_POST['rota_id'] ?? null;
            
            if ($rotaId) {
                $sql = "SELECT pr.*, r.nome as rota_nome 
                        FROM ponto_rota pr
                        INNER JOIN rota r ON pr.rota_id = r.id
                        WHERE pr.rota_id = :rota_id AND pr.ativo = 1
                        ORDER BY pr.ordem ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':rota_id', $rotaId, PDO::PARAM_INT);
                $stmt->execute();
                $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Buscar todos os pontos de todas as rotas ativas
                $sql = "SELECT pr.*, r.nome as rota_nome, r.codigo as rota_codigo,
                               e.nome as escola_nome
                        FROM ponto_rota pr
                        INNER JOIN rota r ON pr.rota_id = r.id
                        LEFT JOIN escola e ON r.escola_id = e.id
                        WHERE pr.ativo = 1 AND r.ativo = 1
                        ORDER BY pr.ordem ASC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $resposta = ['status' => true, 'dados' => $pontos];
        }
        
        // Criar rota
        elseif ($acao === 'criar_rota') {
            $conn->beginTransaction();
            
            // Verificar se o usuário existe na tabela usuario
            $criadoPor = null;
            if (!empty($usuarioId)) {
                $stmtCheck = $conn->prepare("SELECT id FROM usuario WHERE id = :id");
                $stmtCheck->bindParam(':id', $usuarioId, PDO::PARAM_INT);
                $stmtCheck->execute();
                if ($stmtCheck->fetch()) {
                    $criadoPor = $usuarioId;
                }
            }
            
            // Criar rota
            // Nota: Mantemos 'localidades' para compatibilidade, mas agora usamos 'distrito' como campo principal
            $stmt = $conn->prepare("INSERT INTO rota (nome, codigo, escola_id, turno, distrito, localidades, total_alunos, criado_por) 
                                   VALUES (:nome, :codigo, :escola_id, :turno, :distrito, :localidades, :total_alunos, :criado_por)");
            $stmt->bindParam(':nome', $_POST['nome']);
            $stmt->bindValue(':codigo', $_POST['codigo'] ?? null);
            $stmt->bindValue(':escola_id', !empty($_POST['escola_id']) ? $_POST['escola_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':turno', $_POST['turno'] ?? null);
            $stmt->bindValue(':distrito', $_POST['distrito'] ?? null); // Campo principal da nova lógica
            $stmt->bindValue(':localidades', json_encode($_POST['localidades'] ?? [])); // Mantido para compatibilidade
            $stmt->bindValue(':total_alunos', $_POST['total_alunos'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
            $stmt->execute();
            $rotaId = $conn->lastInsertId();
            
            // Criar pontos de rota
            if (!empty($_POST['pontos']) && is_array($_POST['pontos'])) {
                $stmtPonto = $conn->prepare("INSERT INTO ponto_rota (rota_id, nome, localidade, latitude, longitude, endereco, bairro, cidade, ordem, tipo, total_alunos_embarque) 
                                             VALUES (:rota_id, :nome, :localidade, :latitude, :longitude, :endereco, :bairro, :cidade, :ordem, :tipo, :total_alunos_embarque)");
                
                foreach ($_POST['pontos'] as $index => $ponto) {
                    $stmtPonto->bindParam(':rota_id', $rotaId, PDO::PARAM_INT);
                    $stmtPonto->bindValue(':nome', $ponto['nome'] ?? null);
                    $stmtPonto->bindValue(':localidade', $ponto['localidade'] ?? null);
                    $stmtPonto->bindParam(':latitude', $ponto['latitude']);
                    $stmtPonto->bindParam(':longitude', $ponto['longitude']);
                    $stmtPonto->bindValue(':endereco', $ponto['endereco'] ?? null);
                    $stmtPonto->bindValue(':bairro', $ponto['bairro'] ?? null);
                    $stmtPonto->bindValue(':cidade', $ponto['cidade'] ?? null);
                    $stmtPonto->bindValue(':ordem', $index + 1, PDO::PARAM_INT);
                    $stmtPonto->bindValue(':tipo', $ponto['tipo'] ?? 'PARADA');
                    $stmtPonto->bindValue(':total_alunos_embarque', $ponto['total_alunos'] ?? 0, PDO::PARAM_INT);
                    $stmtPonto->execute();
                }
            }
            
            // Vincular alunos à rota
            if (!empty($_POST['alunos']) && is_array($_POST['alunos'])) {
                $stmtAluno = $conn->prepare("INSERT INTO aluno_rota (aluno_id, rota_id, ponto_embarque_id, geolocalizacao_id, status, criado_por) 
                                            VALUES (:aluno_id, :rota_id, :ponto_embarque_id, :geolocalizacao_id, 'ATIVO', :criado_por)");
                
                foreach ($_POST['alunos'] as $aluno) {
                    $stmtAluno->bindParam(':aluno_id', $aluno['aluno_id'], PDO::PARAM_INT);
                    $stmtAluno->bindParam(':rota_id', $rotaId, PDO::PARAM_INT);
                    $stmtAluno->bindValue(':ponto_embarque_id', !empty($aluno['ponto_embarque_id']) ? $aluno['ponto_embarque_id'] : null, PDO::PARAM_INT);
                    $stmtAluno->bindValue(':geolocalizacao_id', !empty($aluno['geoloc_id']) ? $aluno['geoloc_id'] : null, PDO::PARAM_INT);
                    $stmtAluno->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
                    $stmtAluno->execute();
                }
            }
            
            $conn->commit();
            $resposta = ['status' => true, 'mensagem' => 'Rota criada com sucesso!', 'rota_id' => $rotaId];
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

// Buscar escolas
$escolas = [];
try {
    $stmt = $conn->query("SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC");
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar escolas: " . $e->getMessage());
}

// Buscar rotas existentes
$rotas = [];
try {
    $stmt = $conn->query("SELECT id, nome, codigo, escola_id FROM rota WHERE ativo = 1 ORDER BY nome ASC");
    $rotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar rotas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Rotas - Transporte Escolar - SIGAE</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
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
        /* Autocomplete customizado */
        .autocomplete-container {
            position: relative;
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 4px;
            display: none;
        }
        .autocomplete-dropdown.show {
            display: block;
        }
        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.15s;
            border-bottom: 1px solid #f3f4f6;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background-color: #f3f4f6;
        }
        .autocomplete-item .distrito-nome {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        .autocomplete-item:hover .distrito-nome,
        .autocomplete-item.selected .distrito-nome {
            color: #1f2937;
        }
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 999 !important;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                width: 16rem !important;
            }
            .sidebar-mobile.open {
                transform: translateX(0) !important;
                z-index: 999 !important;
            }
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
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Criar Rotas no Mapa</h1>
                <p class="text-gray-600">Maranguape - Ceará | Visualize e crie rotas baseadas na geolocalização dos alunos</p>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Escola</label>
                        <select id="filtro-escola" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Todas as escolas</option>
                            <?php foreach ($escolas as $escola): ?>
                                <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                        <select id="filtro-turno" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Todos os turnos</option>
                            <option value="MANHA">Manhã</option>
                            <option value="TARDE">Tarde</option>
                            <option value="NOITE">Noite</option>
                            <option value="INTEGRAL">Integral</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Visualizar Rota</label>
                        <select id="filtro-rota" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Todas as rotas</option>
                            <?php foreach ($rotas as $rota): ?>
                                <option value="<?= $rota['id'] ?>"><?= htmlspecialchars($rota['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="aplicarFiltros()" class="w-full px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Aplicar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="grid grid-cols-1 gap-6">
                <!-- Painel Lateral -->
                <div class="space-y-6">
                    <!-- Criar Nova Rota -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Criar Nova Rota</h3>
                        <form id="formCriarRota" onsubmit="criarRota(event)" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Rota *</label>
                                <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Rota Interior - Manhã">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                                <input type="text" name="codigo" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: ROTA-001">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Escola</label>
                                <select name="escola_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Selecione</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                                <select name="turno" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Selecione</option>
                                    <option value="MANHA">Manhã</option>
                                    <option value="TARDE">Tarde</option>
                                    <option value="NOITE">Noite</option>
                                    <option value="INTEGRAL">Integral</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Distrito Principal *</label>
                                <div class="autocomplete-container mb-3">
                                    <input type="text" id="input-distrito-principal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green" placeholder="Selecione o distrito principal..." autocomplete="off" required>
                                    <div id="autocomplete-dropdown-distrito" class="autocomplete-dropdown"></div>
                                </div>
                                <input type="hidden" id="distrito-principal" name="distrito">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Localidades</label>
                                <div id="localidades-selecionadas" class="space-y-2 mb-2"></div>
                                <div class="autocomplete-container">
                                    <input type="text" id="input-localidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green" placeholder="Digite o nome da localidade..." autocomplete="off">
                                    <div id="autocomplete-dropdown" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600 mb-2">Pontos selecionados: <span id="total-pontos">0</span></p>
                                <p class="text-sm text-gray-600 mb-2">Alunos selecionados: <span id="total-alunos">0</span></p>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Salvar Rota
                            </button>
                        </form>
                    </div>

                    <!-- Lista de Alunos Selecionados -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Alunos na Rota</h3>
                        <div id="lista-alunos-rota" class="space-y-2 max-h-64 overflow-y-auto">
                            <p class="text-sm text-gray-500 text-center py-4">Nenhum aluno selecionado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let alunosSelecionados = [];
        let pontosSelecionados = [];
        let localidadesSelecionadas = new Set();
        
        // Atualizar lista de alunos (agora distritos)
        function atualizarListaAlunos() {
            const container = document.getElementById('lista-alunos-rota');
            if (!container) return;
            
            if (alunosSelecionados.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Nenhum distrito selecionado</p>';
                return;
            }
            
            container.innerHTML = alunosSelecionados.map(distrito => `
                <div class="flex items-center justify-between bg-gray-50 p-2 rounded mb-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900">${distrito.localidade || 'Distrito não informado'}</p>
                        <p class="text-xs text-gray-500">${distrito.total_alunos} aluno(s) precisam de transporte</p>
                    </div>
                    <button onclick="removerDistritoSelecionado('${distrito.localidade}')" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }
        
        // Remover distrito selecionado
        window.removerDistritoSelecionado = function(localidade) {
            alunosSelecionados = alunosSelecionados.filter(d => d.localidade !== localidade);
            atualizarListaAlunos();
            atualizarContadores();
        };
        
        // Atualizar contadores
        function atualizarContadores() {
            const totalPontos = document.getElementById('total-pontos');
            const totalAlunos = document.getElementById('total-alunos');
            
            if (totalPontos) totalPontos.textContent = pontosSelecionados.length;
            if (totalAlunos) totalAlunos.textContent = alunosSelecionados.length;
        }
        
        // Carregar dados
        function carregarDados() {
            // Função mantida para compatibilidade, mas sem mapa
            atualizarContadores();
            carregarPontosRota();
        }
        
        // Carregar pontos de rota
        function carregarPontosRota() {
        }
        
        // Aplicar filtros
        function aplicarFiltros() {
            carregarDados();
        }
        
        // Criar rota
        function criarRota(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('acao', 'criar_rota');
            const distritoPrincipal = document.getElementById('distrito-principal').value;
            if (!distritoPrincipal) {
                alert('Selecione o distrito principal da rota');
                return;
            }
            formData.append('distrito', distritoPrincipal);
            formData.append('localidades', JSON.stringify(Array.from(localidadesSelecionadas)));
            formData.append('pontos', JSON.stringify(pontosSelecionados));
            formData.append('alunos', JSON.stringify(alunosSelecionados));
            formData.append('total_alunos', alunosSelecionados.length);
            
            fetch('gestao_rotas_transporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    location.reload();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao criar rota');
            });
        }
        
        // Lista de distritos de Maranguape para autocomplete
        const distritosMaranguape = [
            'Amanari',
            'Antônio Marques',
            'Cachoeira',
            'Itapebussu',
            'Jubaia',
            'Ladeira Grande',
            'Lages',
            'Lagoa do Juvenal',
            'Manoel Guedes',
            'Sede',
            'Papara',
            'Penedo',
            'Sapupara',
            'São João do Amanari',
            'Tanques',
            'Umarizeiras',
            'Vertentes do Lagedo'
        ];
        
        // Autocomplete para distrito principal
        const inputDistritoPrincipal = document.getElementById('input-distrito-principal');
        const dropdownDistrito = document.getElementById('autocomplete-dropdown-distrito');
        let filteredDistritosPrincipal = [];
        let selectedIndexPrincipal = -1;
        
        if (inputDistritoPrincipal && dropdownDistrito) {
            inputDistritoPrincipal.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                selectedIndexPrincipal = -1;
                
                if (query.length === 0) {
                    dropdownDistrito.classList.remove('show');
                    return;
                }
                
                filteredDistritosPrincipal = distritosMaranguape.filter(distrito => 
                    distrito.toLowerCase().includes(query)
                );
                
                if (filteredDistritosPrincipal.length === 0) {
                    dropdownDistrito.classList.remove('show');
                    return;
                }
                
                renderDropdownPrincipal();
                dropdownDistrito.classList.add('show');
            });
            
            inputDistritoPrincipal.addEventListener('keydown', function(e) {
                if (!dropdownDistrito.classList.contains('show')) return;
                
                const items = dropdownDistrito.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndexPrincipal = Math.min(selectedIndexPrincipal + 1, items.length - 1);
                    updateSelectionPrincipal(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndexPrincipal = Math.max(selectedIndexPrincipal - 1, -1);
                    updateSelectionPrincipal(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndexPrincipal >= 0 && filteredDistritosPrincipal[selectedIndexPrincipal]) {
                        selecionarDistritoPrincipal(filteredDistritosPrincipal[selectedIndexPrincipal]);
                    }
                } else if (e.key === 'Escape') {
                    dropdownDistrito.classList.remove('show');
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!inputDistritoPrincipal.contains(e.target) && !dropdownDistrito.contains(e.target)) {
                    dropdownDistrito.classList.remove('show');
                }
            });
            
            function renderDropdownPrincipal() {
                dropdownDistrito.innerHTML = filteredDistritosPrincipal.map((distrito, index) => `
                    <div class="autocomplete-item ${index === selectedIndexPrincipal ? 'selected' : ''}" 
                         data-index="${index}" 
                         onclick="selecionarDistritoPrincipal('${distrito}')">
                        <div class="distrito-nome">${distrito}</div>
                    </div>
                `).join('');
            }
            
            function updateSelectionPrincipal(items) {
                items.forEach((item, index) => {
                    if (index === selectedIndexPrincipal) {
                        item.classList.add('selected');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
            
            window.selecionarDistritoPrincipal = function(distrito) {
                document.getElementById('distrito-principal').value = distrito;
                inputDistritoPrincipal.value = distrito;
                dropdownDistrito.classList.remove('show');
            };
        }
        
        // Autocomplete customizado para localidades
        const inputLocalidade = document.getElementById('input-localidade');
        const dropdown = document.getElementById('autocomplete-dropdown');
        let selectedIndex = -1;
        let filteredDistritos = [];
        
        if (inputLocalidade && dropdown) {
            // Filtrar distritos conforme digitação
            inputLocalidade.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                selectedIndex = -1;
                
                if (query.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }
                
                // Filtrar distritos que contêm o texto digitado
                filteredDistritos = distritosMaranguape.filter(distrito => 
                    distrito.toLowerCase().includes(query)
                );
                
                if (filteredDistritos.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }
                
                // Renderizar dropdown
                renderDropdown();
                dropdown.classList.add('show');
            });
            
            // Navegação com teclado
            inputLocalidade.addEventListener('keydown', function(e) {
                if (!dropdown.classList.contains('show')) return;
                
                const items = dropdown.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && filteredDistritos[selectedIndex]) {
                        selecionarDistrito(filteredDistritos[selectedIndex]);
                    } else if (this.value.trim()) {
                        // Se não há seleção, adiciona o que foi digitado
                        adicionarLocalidade(this.value.trim());
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });
            
            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!inputLocalidade.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            
            function renderDropdown() {
                dropdown.innerHTML = filteredDistritos.map((distrito, index) => `
                    <div class="autocomplete-item ${index === selectedIndex ? 'selected' : ''}" 
                         data-index="${index}" 
                         onclick="selecionarDistrito('${distrito}')">
                        <div class="distrito-nome">${distrito}</div>
                    </div>
                `).join('');
            }
            
            function updateSelection(items) {
                items.forEach((item, index) => {
                    if (index === selectedIndex) {
                        item.classList.add('selected');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
            
            window.selecionarDistrito = function(distrito) {
                adicionarLocalidade(distrito);
                inputLocalidade.value = '';
                dropdown.classList.remove('show');
            };
            
            function adicionarLocalidade(localidade) {
                // Verificar se a localidade existe na lista (case-insensitive)
                const localidadeEncontrada = distritosMaranguape.find(d => 
                    d.toLowerCase() === localidade.toLowerCase()
                );
                
                if (localidadeEncontrada) {
                    if (!localidadesSelecionadas.has(localidadeEncontrada)) {
                        localidadesSelecionadas.add(localidadeEncontrada);
                        atualizarLocalidades();
                    }
                } else {
                    // Permite adicionar localidades customizadas também
                    if (!localidadesSelecionadas.has(localidade)) {
                        localidadesSelecionadas.add(localidade);
                        atualizarLocalidades();
                    }
                }
            }
        }
        
        function atualizarLocalidades() {
            const container = document.getElementById('localidades-selecionadas');
            container.innerHTML = Array.from(localidadesSelecionadas).map(loc => `
                <div class="flex items-center justify-between bg-blue-50 px-3 py-1 rounded">
                    <span class="text-sm text-gray-700">${loc}</span>
                    <button type="button" onclick="removerLocalidade('${loc}')" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `).join('');
        }
        
        function removerLocalidade(localidade) {
            localidadesSelecionadas.delete(localidade);
            atualizarLocalidades();
        }
        
        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            carregarDados();
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

