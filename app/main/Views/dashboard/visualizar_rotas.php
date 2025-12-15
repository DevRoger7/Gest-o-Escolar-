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
if (!eAdm() && strtoupper($tipoUsuario) !== 'ADM_TRANSPORTE' && strtoupper($tipoUsuario) !== 'TRANSPORTE_ALUNO') {
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
        // Listar rotas
        if ($acao === 'listar_rotas') {
            $sql = "SELECT r.*, e.nome as escola_nome, v.placa as veiculo_placa, 
                           m.pessoa_id, p.nome as motorista_nome,
                           COUNT(DISTINCT pr.id) as total_pontos,
                           COUNT(DISTINCT ar.aluno_id) as total_alunos
                    FROM rota r
                    LEFT JOIN escola e ON r.escola_id = e.id
                    LEFT JOIN veiculo v ON r.veiculo_id = v.id
                    LEFT JOIN motorista m ON r.motorista_id = m.id
                    LEFT JOIN pessoa p ON m.pessoa_id = p.id
                    LEFT JOIN ponto_rota pr ON r.id = pr.rota_id AND pr.ativo = 1
                    LEFT JOIN aluno_rota ar ON r.id = ar.rota_id AND ar.status = 'ATIVO'
                    WHERE r.ativo = 1
                    GROUP BY r.id
                    ORDER BY r.nome ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $rotas];
        }
        
        // Buscar pontos de uma rota
        elseif ($acao === 'buscar_pontos_rota') {
            $rotaId = $_POST['rota_id'] ?? null;
            
            if (!$rotaId) {
                throw new Exception('ID da rota não fornecido');
            }
            
            $sql = "SELECT pr.*, 
                           COUNT(DISTINCT ar.aluno_id) as alunos_embarque
                    FROM ponto_rota pr
                    LEFT JOIN aluno_rota ar ON pr.id = ar.ponto_embarque_id AND ar.status = 'ATIVO'
                    WHERE pr.rota_id = :rota_id AND pr.ativo = 1
                    GROUP BY pr.id
                    ORDER BY pr.ordem ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':rota_id', $rotaId, PDO::PARAM_INT);
            $stmt->execute();
            $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $pontos];
        }
        
        // Buscar alunos de uma rota
        elseif ($acao === 'buscar_alunos_rota') {
            $rotaId = $_POST['rota_id'] ?? null;
            
            if (!$rotaId) {
                throw new Exception('ID da rota não fornecido');
            }
            
            $sql = "SELECT a.id, a.matricula, p.nome, p.cpf,
                           ar.ponto_embarque_id, pr.nome as ponto_nome,
                           ga.latitude, ga.longitude
                    FROM aluno_rota ar
                    INNER JOIN aluno a ON ar.aluno_id = a.id
                    INNER JOIN pessoa p ON a.pessoa_id = p.id
                    LEFT JOIN ponto_rota pr ON ar.ponto_embarque_id = pr.id
                    LEFT JOIN geolocalizacao_aluno ga ON a.id = ga.aluno_id AND ga.principal = 1
                    WHERE ar.rota_id = :rota_id AND ar.status = 'ATIVO'
                    ORDER BY p.nome ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':rota_id', $rotaId, PDO::PARAM_INT);
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $alunos];
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

// Buscar rotas para o select
$rotas = [];
try {
    $stmt = $conn->query("SELECT id, nome, codigo FROM rota WHERE ativo = 1 ORDER BY nome ASC");
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
    <title>Visualizar Rotas</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .leaflet-control-attribution {
            display: none !important;
        }
        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Visualizar Rotas</h1>
                <p class="text-gray-600">Visualize as rotas de transporte escolar no mapa</p>
            </div>
            
            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selecionar Rota</label>
                        <select id="filtro-rota" onchange="carregarRota()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Todas as rotas</option>
                            <?php foreach ($rotas as $rota): ?>
                                <option value="<?= $rota['id'] ?>"><?= htmlspecialchars($rota['nome']) ?> <?= $rota['codigo'] ? '(' . htmlspecialchars($rota['codigo']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="carregarTodasRotas()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-map mr-2"></i>Carregar Todas as Rotas
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lista de Rotas -->
                <div class="lg:col-span-1 bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Rotas</h2>
                    </div>
                    <div id="lista-rotas" class="p-4 max-h-[600px] overflow-y-auto">
                        <p class="text-gray-500 text-center py-8">Carregando rotas...</p>
                    </div>
                </div>
                
                <!-- Mapa -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Mapa</h2>
                    </div>
                    <div class="p-4">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        let map;
        let routeLayers = [];
        let markers = [];
        
        // Inicializar mapa
        function initMap() {
            map = L.map('map').setView([-3.890277, -38.625000], 12);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '',
                maxZoom: 19
            }).addTo(map);
        }
        
        function carregarTodasRotas() {
            const formData = new FormData();
            formData.append('acao', 'listar_rotas');
            
            fetch('visualizar_rotas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderizarRotas(data.dados);
                    atualizarMapaRotas(data.dados);
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar rotas');
            });
        }
        
        function carregarRota() {
            const rotaId = document.getElementById('filtro-rota').value;
            
            if (!rotaId) {
                carregarTodasRotas();
                return;
            }
            
            // Buscar pontos da rota
            const formData = new FormData();
            formData.append('acao', 'buscar_pontos_rota');
            formData.append('rota_id', rotaId);
            
            fetch('visualizar_rotas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    desenharRotaNoMapa(data.dados);
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar pontos da rota');
            });
        }
        
        function renderizarRotas(rotas) {
            const container = document.getElementById('lista-rotas');
            
            if (rotas.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhuma rota encontrada</p>';
                return;
            }
            
            container.innerHTML = rotas.map(rota => `
                <div class="border border-gray-200 rounded-lg p-4 mb-3 hover:bg-gray-50 cursor-pointer" onclick="selecionarRota(${rota.id})">
                    <h3 class="font-semibold text-gray-900">${rota.nome}</h3>
                    ${rota.codigo ? `<p class="text-sm text-gray-600">Código: ${rota.codigo}</p>` : ''}
                    <p class="text-sm text-gray-600">${rota.escola_nome || 'Sem escola'}</p>
                    <div class="mt-2 flex gap-2 text-xs">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">${rota.total_pontos || 0} pontos</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded">${rota.total_alunos || 0} alunos</span>
                    </div>
                </div>
            `).join('');
        }
        
        function atualizarMapaRotas(rotas) {
            // Limpar mapas anteriores
            routeLayers.forEach(layer => map.removeLayer(layer));
            routeLayers = [];
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            // Cores diferentes para cada rota
            const cores = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];
            
            rotas.forEach((rota, index) => {
                // Buscar pontos desta rota
                const formData = new FormData();
                formData.append('acao', 'buscar_pontos_rota');
                formData.append('rota_id', rota.id);
                
                fetch('visualizar_rotas.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status && data.dados.length > 0) {
                        desenharRotaNoMapa(data.dados, cores[index % cores.length], rota.nome);
                    }
                })
                .catch(error => console.error('Erro ao carregar pontos:', error));
            });
        }
        
        function desenharRotaNoMapa(pontos, cor = '#3B82F6', nomeRota = '') {
            if (pontos.length === 0) return;
            
            // Ordenar pontos por ordem
            pontos.sort((a, b) => (a.ordem || 0) - (b.ordem || 0));
            
            // Criar array de coordenadas
            const coordenadas = pontos.map(ponto => [parseFloat(ponto.latitude), parseFloat(ponto.longitude)]);
            
            // Desenhar linha da rota
            const polyline = L.polyline(coordenadas, {
                color: cor,
                weight: 4,
                opacity: 0.7
            }).addTo(map);
            
            routeLayers.push(polyline);
            
            // Adicionar marcadores
            pontos.forEach((ponto, index) => {
                const isInicio = index === 0;
                const isFim = index === pontos.length - 1;
                
                let iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png';
                if (isInicio) {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png';
                } else if (isFim) {
                    iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png';
                }
                
                const marker = L.marker([parseFloat(ponto.latitude), parseFloat(ponto.longitude)], {
                    icon: L.icon({
                        iconUrl: iconUrl,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41]
                    })
                }).addTo(map);
                
                marker.bindPopup(`
                    <strong>${ponto.nome || 'Ponto ' + (index + 1)}</strong><br>
                    ${ponto.localidade || ''}<br>
                    ${ponto.total_alunos_embarque || 0} alunos
                `);
                
                markers.push(marker);
            });
            
            // Ajustar zoom
            if (coordenadas.length > 0) {
                const bounds = L.latLngBounds(coordenadas);
                map.fitBounds(bounds.pad(0.1));
            }
        }
        
        function selecionarRota(rotaId) {
            document.getElementById('filtro-rota').value = rotaId;
            carregarRota();
        }
        
        // Carregar todas as rotas ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            carregarTodasRotas();
        });
    </script>
</body>
</html>

