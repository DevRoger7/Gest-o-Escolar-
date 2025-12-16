<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM_TRANSPORTE, TRANSPORTE_ALUNO e ADM podem acessar
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
        // Listar alunos com geolocalização
        if ($acao === 'listar_alunos_geolocalizacao') {
            $busca = $_POST['busca'] ?? '';
            $escolaId = $_POST['escola_id'] ?? null;
            
            // Verificar se as colunas precisa_transporte e distrito_transporte existem
            $stmtCheck = $conn->query("SELECT COUNT(*) as col_exists 
                                      FROM INFORMATION_SCHEMA.COLUMNS 
                                      WHERE TABLE_SCHEMA = DATABASE() 
                                      AND TABLE_NAME = 'aluno' 
                                      AND COLUMN_NAME = 'precisa_transporte'");
            $colPrecisaTransporte = $stmtCheck->fetch(PDO::FETCH_ASSOC)['col_exists'] > 0;
            
            $stmtCheck = $conn->query("SELECT COUNT(*) as col_exists 
                                      FROM INFORMATION_SCHEMA.COLUMNS 
                                      WHERE TABLE_SCHEMA = DATABASE() 
                                      AND TABLE_NAME = 'aluno' 
                                      AND COLUMN_NAME = 'distrito_transporte'");
            $colDistritoTransporte = $stmtCheck->fetch(PDO::FETCH_ASSOC)['col_exists'] > 0;
            
            // Construir SELECT com colunas condicionais
            $selectPrecisaTransporte = $colPrecisaTransporte ? 'a.precisa_transporte' : 'NULL as precisa_transporte';
            $selectDistritoTransporte = $colDistritoTransporte ? 'a.distrito_transporte' : 'NULL as distrito_transporte';
            
            $sql = "SELECT a.id, a.matricula, p.nome, p.cpf, 
                           {$selectPrecisaTransporte}, {$selectDistritoTransporte},
                           ga.id as geoloc_id, ga.latitude, ga.longitude, ga.localidade,
                           ga.endereco, ga.bairro, ga.cidade, ga.principal,
                           e.nome as escola_nome
                    FROM aluno a
                    INNER JOIN pessoa p ON a.pessoa_id = p.id
                    LEFT JOIN geolocalizacao_aluno ga ON a.id = ga.aluno_id AND ga.principal = 1
                    LEFT JOIN aluno_turma at ON a.id = at.aluno_id AND (at.fim IS NULL OR at.fim = '' OR at.fim = '0000-00-00')
                    LEFT JOIN turma t ON at.turma_id = t.id AND t.ativo = 1
                    LEFT JOIN escola e ON t.escola_id = e.id AND e.ativo = 1
                    WHERE a.ativo = 1";
            
            $params = [];
            if (!empty($busca)) {
                $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR a.matricula LIKE :busca)";
                $params[':busca'] = "%{$busca}%";
            }
            if ($escolaId) {
                $sql .= " AND e.id = :escola_id";
                $params[':escola_id'] = $escolaId;
            }
            
            $sql .= " ORDER BY p.nome ASC";
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $alunos];
        }
        
        // Cadastrar/Atualizar geolocalização
        elseif ($acao === 'salvar_geolocalizacao') {
            $alunoId = $_POST['aluno_id'] ?? null;
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            $localidade = $_POST['localidade'] ?? null;
            $endereco = $_POST['endereco'] ?? null;
            $bairro = $_POST['bairro'] ?? null;
            $cidade = $_POST['cidade'] ?? null;
            $estado = $_POST['estado'] ?? 'CE';
            $cep = $_POST['cep'] ?? null;
            $tipo = $_POST['tipo'] ?? 'RESIDENCIA';
            $principal = isset($_POST['principal']) ? 1 : 0;
            
            if (!$alunoId || !$latitude || !$longitude) {
                throw new Exception('Dados obrigatórios não fornecidos');
            }
            
            // Verificar se já existe geolocalização principal
            $stmt = $conn->prepare("SELECT id FROM geolocalizacao_aluno WHERE aluno_id = :aluno_id AND principal = 1");
            $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            $geolocExistente = $stmt->fetch();
            
            if ($principal && $geolocExistente) {
                // Remover principal de outras geolocalizações
                $stmt = $conn->prepare("UPDATE geolocalizacao_aluno SET principal = 0 WHERE aluno_id = :aluno_id");
                $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
                $stmt->execute();
                
                // Atualizar existente
                $stmt = $conn->prepare("UPDATE geolocalizacao_aluno 
                                       SET latitude = :latitude, longitude = :longitude, 
                                           localidade = :localidade, endereco = :endereco,
                                           bairro = :bairro, cidade = :cidade, estado = :estado,
                                           cep = :cep, tipo = :tipo, principal = 1
                                       WHERE id = :id");
                $stmt->bindParam(':id', $geolocExistente['id'], PDO::PARAM_INT);
                $stmt->bindParam(':latitude', $latitude);
                $stmt->bindParam(':longitude', $longitude);
                $stmt->bindValue(':localidade', $localidade);
                $stmt->bindValue(':endereco', $endereco);
                $stmt->bindValue(':bairro', $bairro);
                $stmt->bindValue(':cidade', $cidade);
                $stmt->bindValue(':estado', $estado);
                $stmt->bindValue(':cep', $cep);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->execute();
                $resposta = ['status' => true, 'mensagem' => 'Geolocalização atualizada com sucesso!'];
            } else {
                // Criar nova
                if ($principal) {
                    // Remover principal de outras
                    $stmt = $conn->prepare("UPDATE geolocalizacao_aluno SET principal = 0 WHERE aluno_id = :aluno_id");
                    $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
                    $stmt->execute();
                }
                
                // Validar usuarioId antes de inserir
                $validUsuarioId = null;
                if ($usuarioId !== null) {
                    $stmtCheckUser = $conn->prepare("SELECT id FROM usuario WHERE id = :id");
                    $stmtCheckUser->bindParam(':id', $usuarioId, PDO::PARAM_INT);
                    $stmtCheckUser->execute();
                    if ($stmtCheckUser->fetch()) {
                        $validUsuarioId = $usuarioId;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO geolocalizacao_aluno 
                                       (aluno_id, tipo, nome, localidade, latitude, longitude, 
                                        endereco, bairro, cidade, estado, cep, principal, criado_por) 
                                       VALUES 
                                       (:aluno_id, :tipo, :nome, :localidade, :latitude, :longitude,
                                        :endereco, :bairro, :cidade, :estado, :cep, :principal, :criado_por)");
                $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindValue(':nome', 'Residência');
                $stmt->bindValue(':localidade', $localidade);
                $stmt->bindParam(':latitude', $latitude);
                $stmt->bindParam(':longitude', $longitude);
                $stmt->bindValue(':endereco', $endereco);
                $stmt->bindValue(':bairro', $bairro);
                $stmt->bindValue(':cidade', $cidade);
                $stmt->bindValue(':estado', $estado);
                $stmt->bindValue(':cep', $cep);
                $stmt->bindParam(':principal', $principal, PDO::PARAM_INT);
                $stmt->bindValue(':criado_por', $validUsuarioId, PDO::PARAM_INT);
                $stmt->execute();
                $resposta = ['status' => true, 'mensagem' => 'Geolocalização cadastrada com sucesso!'];
            }
        }
        
        // Buscar geolocalização de um aluno
        elseif ($acao === 'buscar_geolocalizacao_aluno') {
            $alunoId = $_POST['aluno_id'] ?? null;
            
            if (!$alunoId) {
                throw new Exception('ID do aluno não fornecido');
            }
            
            $stmt = $conn->prepare("SELECT * FROM geolocalizacao_aluno WHERE aluno_id = :aluno_id ORDER BY principal DESC, criado_em DESC");
            $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            $geolocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $geolocs];
        }
        
        // Excluir geolocalização
        elseif ($acao === 'excluir_geolocalizacao') {
            $geolocId = $_POST['geoloc_id'] ?? null;
            
            if (!$geolocId) {
                throw new Exception('ID da geolocalização não fornecido');
            }
            
            $stmt = $conn->prepare("DELETE FROM geolocalizacao_aluno WHERE id = :id");
            $stmt->bindParam(':id', $geolocId, PDO::PARAM_INT);
            $stmt->execute();
            
            $resposta = ['status' => true, 'mensagem' => 'Geolocalização excluída com sucesso!'];
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

// Buscar escolas
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
    <title>Gestão de Geolocalização de Alunos</title>
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
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestão de Geolocalização de Alunos</h1>
                <p class="text-gray-600">Gerencie as localizações dos alunos para planejamento de rotas</p>
            </div>
            
            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Aluno</label>
                        <input type="text" id="filtro-busca" placeholder="Nome, CPF ou Matrícula" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                        <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todas as escolas</option>
                            <?php foreach ($escolas as $escola): ?>
                                <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="filtrarAlunos()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filtrar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Alunos -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Alunos</h2>
                </div>
                <div id="lista-alunos" class="p-4 max-h-[600px] overflow-y-auto">
                    <p class="text-gray-500 text-center py-8">Use os filtros para buscar alunos</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de Geolocalização -->
    <div id="modal-geolocalizacao" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Cadastrar Geolocalização</h3>
                <button onclick="fecharModalGeolocalizacao()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-geolocalizacao" onsubmit="salvarGeolocalizacao(event)">
                <input type="hidden" id="geoloc-aluno-id">
                <input type="hidden" id="geoloc-latitude" value="0">
                <input type="hidden" id="geoloc-longitude" value="0">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Localidade</label>
                    <input type="text" id="geoloc-localidade" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Sede, Lagoa, Itapebussu">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                    <input type="text" id="geoloc-endereco" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Rua, Avenida, etc.">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                        <input type="text" id="geoloc-bairro" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                        <input type="text" id="geoloc-cidade" value="Maranguape" class="w-full px-4 py-2 border border-gray-300 rounded-lg" readonly>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="geoloc-principal" checked class="mr-2">
                        <span class="text-sm text-gray-700">Localização principal</span>
                    </label>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalGeolocalizacao()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let alunoSelecionado = null;
        
        function filtrarAlunos() {
            const busca = document.getElementById('filtro-busca').value;
            const escolaId = document.getElementById('filtro-escola').value;
            
            const formData = new FormData();
            formData.append('acao', 'listar_alunos_geolocalizacao');
            if (busca) formData.append('busca', busca);
            if (escolaId) formData.append('escola_id', escolaId);
            
            fetch('gestao_geolocalizacao_alunos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderizarAlunos(data.dados);
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao buscar alunos');
            });
        }
        
        function renderizarAlunos(alunos) {
            const container = document.getElementById('lista-alunos');
            
            if (alunos.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum aluno encontrado</p>';
                return;
            }
            
            container.innerHTML = alunos.map(aluno => `
                <div class="border border-gray-200 rounded-lg p-4 mb-3 hover:bg-gray-50 cursor-pointer" onclick="selecionarAluno(${aluno.id}, ${aluno.geoloc_id || 'null'})">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-900">${aluno.nome}</h3>
                            <p class="text-sm text-gray-600">Matrícula: ${aluno.matricula || 'N/A'}</p>
                            <p class="text-sm text-gray-600">${aluno.escola_nome || 'Sem escola'}</p>
                            ${aluno.geoloc_id ? 
                                '<span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Com localização</span>' :
                                '<span class="inline-block mt-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Sem localização</span>'
                            }
                        </div>
                        <button onclick="event.stopPropagation(); abrirModalGeolocalizacao(${aluno.id})" 
                                class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        function selecionarAluno(alunoId, geolocId) {
            alunoSelecionado = alunoId;
            // Destacar aluno na lista
        }
        
        function abrirModalGeolocalizacao(alunoId) {
            alunoSelecionado = alunoId;
            document.getElementById('geoloc-aluno-id').value = alunoId;
            document.getElementById('modal-geolocalizacao').classList.remove('hidden');
        }
        
        function fecharModalGeolocalizacao() {
            document.getElementById('modal-geolocalizacao').classList.add('hidden');
            alunoSelecionado = null;
        }
        
        function salvarGeolocalizacao(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('acao', 'salvar_geolocalizacao');
            formData.append('aluno_id', document.getElementById('geoloc-aluno-id').value);
            formData.append('latitude', document.getElementById('geoloc-latitude').value);
            formData.append('longitude', document.getElementById('geoloc-longitude').value);
            formData.append('localidade', document.getElementById('geoloc-localidade').value);
            formData.append('endereco', document.getElementById('geoloc-endereco').value);
            formData.append('bairro', document.getElementById('geoloc-bairro').value);
            formData.append('cidade', document.getElementById('geoloc-cidade').value);
            formData.append('estado', 'CE');
            formData.append('principal', document.getElementById('geoloc-principal').checked ? 1 : 0);
            
            fetch('gestao_geolocalizacao_alunos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalGeolocalizacao();
                    filtrarAlunos();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar geolocalização');
            });
        }
        
        // Inicializar ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
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

