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
        // Listar localidades de um distrito
        if ($acao === 'listar_localidades') {
            $distrito = $_POST['distrito'] ?? '';
            
            if (empty($distrito)) {
                throw new Exception('Distrito não informado');
            }
            
            // Verificar se a coluna distrito_transporte existe
            $stmtCheck = $conn->query("SELECT COUNT(*) as col_exists 
                                      FROM INFORMATION_SCHEMA.COLUMNS 
                                      WHERE TABLE_SCHEMA = DATABASE() 
                                      AND TABLE_NAME = 'aluno' 
                                      AND COLUMN_NAME = 'distrito_transporte'");
            $colExists = $stmtCheck->fetch(PDO::FETCH_ASSOC)['col_exists'] > 0;
            
            if ($colExists) {
                $sql = "SELECT dl.*, 
                               (SELECT COUNT(*) FROM aluno a WHERE a.distrito_transporte = dl.distrito AND a.precisa_transporte = 1 AND a.ativo = 1) as total_alunos
                        FROM distrito_localidade dl
                        WHERE dl.distrito = :distrito AND dl.ativo = 1
                        ORDER BY dl.localidade ASC";
            } else {
                // Se a coluna não existe, retornar 0 para total_alunos
                $sql = "SELECT dl.*, 0 as total_alunos
                        FROM distrito_localidade dl
                        WHERE dl.distrito = :distrito AND dl.ativo = 1
                        ORDER BY dl.localidade ASC";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':distrito', $distrito);
            $stmt->execute();
            $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            $resposta = ['status' => true, 'dados' => $localidades];
        }
        
        // Buscar localidade por ID
        elseif ($acao === 'buscar_localidade') {
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID da localidade não informado');
            }
            
            $stmt = $conn->prepare("SELECT * FROM distrito_localidade WHERE id = :id AND ativo = 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $localidade = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$localidade) {
                throw new Exception('Localidade não encontrada');
            }
            
            $resposta = ['status' => true, 'dados' => $localidade];
        }
        
        // Cadastrar localidade
        elseif ($acao === 'cadastrar_localidade') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para cadastrar localidades');
            }
            
            $distrito = $_POST['distrito'] ?? '';
            $localidade = $_POST['localidade'] ?? '';
            
            if (empty($distrito) || empty($localidade)) {
                throw new Exception('Distrito e localidade são obrigatórios');
            }
            
            // Verificar se já existe
            $stmt = $conn->prepare("SELECT id FROM distrito_localidade WHERE distrito = :distrito AND localidade = :localidade AND ativo = 1");
            $stmt->bindParam(':distrito', $distrito);
            $stmt->bindParam(':localidade', $localidade);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                throw new Exception('Localidade já cadastrada neste distrito');
            }
            
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
            
            $stmt = $conn->prepare("INSERT INTO distrito_localidade 
                                   (distrito, localidade, latitude, longitude, endereco, bairro, cidade, estado, cep, descricao, distancia_centro_km, criado_por, ativo) 
                                   VALUES 
                                   (:distrito, :localidade, NULL, NULL, :endereco, :bairro, :cidade, :estado, :cep, :descricao, NULL, :criado_por, 1)");
            $stmt->bindParam(':distrito', $distrito);
            $stmt->bindParam(':localidade', $localidade);
            $stmt->bindValue(':endereco', $_POST['endereco'] ?? null);
            $stmt->bindValue(':bairro', $_POST['bairro'] ?? null);
            $stmt->bindValue(':cidade', $_POST['cidade'] ?? 'Maranguape');
            $stmt->bindValue(':estado', $_POST['estado'] ?? 'CE');
            $stmt->bindValue(':cep', $_POST['cep'] ?? null);
            $stmt->bindValue(':descricao', $_POST['descricao'] ?? null);
            $stmt->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
            $stmt->execute();
            
            $resposta = ['status' => true, 'mensagem' => 'Localidade cadastrada com sucesso!'];
        }
        
        // Editar localidade
        elseif ($acao === 'editar_localidade') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para editar localidades');
            }
            
            $id = $_POST['id'] ?? null;
            $localidade = $_POST['localidade'] ?? '';
            
            if (!$id) {
                throw new Exception('ID da localidade não informado');
            }
            
            $stmt = $conn->prepare("UPDATE distrito_localidade 
                                   SET localidade = :localidade,
                                       endereco = :endereco, bairro = :bairro, cidade = :cidade, estado = :estado,
                                       cep = :cep, descricao = :descricao
                                   WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':localidade', $localidade);
            $stmt->bindValue(':endereco', $_POST['endereco'] ?? null);
            $stmt->bindValue(':bairro', $_POST['bairro'] ?? null);
            $stmt->bindValue(':cidade', $_POST['cidade'] ?? 'Maranguape');
            $stmt->bindValue(':estado', $_POST['estado'] ?? 'CE');
            $stmt->bindValue(':cep', $_POST['cep'] ?? null);
            $stmt->bindValue(':descricao', $_POST['descricao'] ?? null);
            $stmt->execute();
            
            $resposta = ['status' => true, 'mensagem' => 'Localidade atualizada com sucesso!'];
        }
        
        // Excluir localidade
        elseif ($acao === 'excluir_localidade') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para excluir localidades');
            }
            
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID da localidade não informado');
            }
            
            $stmt = $conn->prepare("UPDATE distrito_localidade SET ativo = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $resposta = ['status' => true, 'mensagem' => 'Localidade excluída com sucesso!'];
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

// Lista de distritos de Maranguape
$distritos = [
    'Amanari', 'Antônio Marques', 'Cachoeira', 'Itapebussu', 'Jubaia',
    'Ladeira Grande', 'Lages', 'Lagoa do Juvenal', 'Manoel Guedes',
    'Sede', 'Papara', 'Penedo', 'Sapupara', 'São João do Amanari',
    'Tanques', 'Umarizeiras', 'Vertentes do Lagedo'
];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Localidades por Distrito</title>
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
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestão de Localidades por Distrito</h1>
                <p class="text-gray-600">Cadastre e gerencie as localidades de cada distrito para planejamento de rotas</p>
            </div>
            
            <!-- Seleção de Distrito -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selecionar Distrito</label>
                        <select id="select-distrito" onchange="carregarDistrito()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Selecione um distrito</option>
                            <?php foreach ($distritos as $distrito): ?>
                                <option value="<?= htmlspecialchars($distrito) ?>"><?= htmlspecialchars($distrito) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($podeEditar): ?>
                    <!-- <div class="flex items-end">
                        <button onclick="abrirModalPontoCentral()" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-map-marker-alt mr-2"></i>Definir Ponto Central
                        </button>
                    </div> -->
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lista de Localidades -->
            <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-900">Localidades</h2>
                        <?php if ($podeEditar): ?>
                        <button onclick="abrirModalLocalidade()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Nova Localidade
                        </button>
                        <?php endif; ?>
                    </div>
                <div id="lista-localidades" class="p-4 max-h-[600px] overflow-y-auto">
                    <p class="text-gray-500 text-center py-8">Selecione um distrito para visualizar as localidades</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de Localidade -->
    <div id="modal-localidade" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Cadastrar Localidade</h3>
                <button onclick="fecharModalLocalidade()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-localidade" onsubmit="salvarLocalidade(event)">
                <input type="hidden" id="localidade-id">
                <input type="hidden" id="localidade-distrito">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Localidade *</label>
                    <input type="text" id="localidade-nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Alto das Vassouras, Centro">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                    <input type="text" id="localidade-endereco" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                        <input type="text" id="localidade-bairro" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                        <input type="text" id="localidade-cep" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea id="localidade-descricao" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalLocalidade()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
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
        let distritoSelecionado = '';
        const podeEditar = <?= $podeEditar ? 'true' : 'false' ?>;
        
        function carregarDistrito() {
            distritoSelecionado = document.getElementById('select-distrito').value;
            
            if (!distritoSelecionado) {
                document.getElementById('lista-localidades').innerHTML = '<p class="text-gray-500 text-center py-8">Selecione um distrito para visualizar as localidades</p>';
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'listar_localidades');
            formData.append('distrito', distritoSelecionado);
            
            fetch('gestao_localidades_distrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderizarLocalidades(data.dados);
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar localidades');
            });
        }
        
        function renderizarLocalidades(localidades) {
            const container = document.getElementById('lista-localidades');
            
            if (localidades.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhuma localidade cadastrada para este distrito</p>';
                return;
            }
            
            container.innerHTML = localidades.map(loc => `
                <div class="border border-gray-200 rounded-lg p-4 mb-3 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">${loc.localidade}</h3>
                            ${loc.total_alunos ? `<p class="text-sm text-gray-600">${loc.total_alunos} alunos</p>` : ''}
                        </div>
                        ${podeEditar ? `
                        <div class="flex gap-2">
                            <button onclick="editarLocalidade(${loc.id})" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="excluirLocalidade(${loc.id})" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
        
        function abrirModalLocalidade() {
            if (!distritoSelecionado) {
                alert('Selecione um distrito primeiro');
                return;
            }
            
            // Limpar o formulário e resetar para modo de cadastro
            document.getElementById('form-localidade').reset();
            document.getElementById('localidade-id').value = '';
            document.getElementById('localidade-distrito').value = distritoSelecionado;
            
            // Atualizar o título do modal
            const modalTitle = document.querySelector('#modal-localidade h3');
            if (modalTitle) {
                modalTitle.textContent = 'Cadastrar Localidade';
            }
            
            document.getElementById('modal-localidade').classList.remove('hidden');
        }
        
        function fecharModalLocalidade() {
            document.getElementById('modal-localidade').classList.add('hidden');
            document.getElementById('form-localidade').reset();
            document.getElementById('localidade-id').value = '';
            document.getElementById('localidade-distrito').value = '';
            
            // Resetar o título do modal
            const modalTitle = document.querySelector('#modal-localidade h3');
            if (modalTitle) {
                modalTitle.textContent = 'Cadastrar Localidade';
            }
        }
        
        function salvarLocalidade(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('acao', document.getElementById('localidade-id').value ? 'editar_localidade' : 'cadastrar_localidade');
            if (document.getElementById('localidade-id').value) {
                formData.append('id', document.getElementById('localidade-id').value);
            }
            formData.append('distrito', document.getElementById('localidade-distrito').value);
            formData.append('localidade', document.getElementById('localidade-nome').value);
            formData.append('endereco', document.getElementById('localidade-endereco').value);
            formData.append('bairro', document.getElementById('localidade-bairro').value);
            formData.append('cep', document.getElementById('localidade-cep').value);
            formData.append('descricao', document.getElementById('localidade-descricao').value);
            
            fetch('gestao_localidades_distrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalLocalidade();
                    carregarDistrito();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar localidade');
            });
        }
        
        function editarLocalidade(id) {
            const formData = new FormData();
            formData.append('acao', 'buscar_localidade');
            formData.append('id', id);
            
            fetch('gestao_localidades_distrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status && data.dados) {
                    const loc = data.dados;
                    
                    // Preencher o formulário com os dados da localidade
                    document.getElementById('localidade-id').value = loc.id;
                    document.getElementById('localidade-distrito').value = loc.distrito;
                    document.getElementById('localidade-nome').value = loc.localidade || '';
                    document.getElementById('localidade-endereco').value = loc.endereco || '';
                    document.getElementById('localidade-bairro').value = loc.bairro || '';
                    document.getElementById('localidade-cep').value = loc.cep || '';
                    document.getElementById('localidade-descricao').value = loc.descricao || '';
                    
                    // Atualizar o título do modal
                    const modalTitle = document.querySelector('#modal-localidade h3');
                    if (modalTitle) {
                        modalTitle.textContent = 'Editar Localidade';
                    }
                    
                    // Abrir o modal
                    document.getElementById('modal-localidade').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar dados da localidade: ' + (data.mensagem || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados da localidade');
            });
        }
        
        function excluirLocalidade(id) {
            if (!confirm('Deseja realmente excluir esta localidade?')) return;
            
            const formData = new FormData();
            formData.append('acao', 'excluir_localidade');
            formData.append('id', id);
            
            fetch('gestao_localidades_distrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    carregarDistrito();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir localidade');
            });
        }
        

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

