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
        // Cadastrar Motorista
        if ($acao === 'cadastrar_motorista') {
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
                    
                    // Criar motorista
                    $stmt = $conn->prepare("INSERT INTO motorista (pessoa_id, cnh, categoria_cnh, validade_cnh, data_admissao, observacoes, criado_por) 
                                           VALUES (:pessoa_id, :cnh, :categoria_cnh, :validade_cnh, :data_admissao, :observacoes, :criado_por)");
                    $stmt->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                    $stmt->bindParam(':cnh', $cnh);
                    $stmt->bindValue(':categoria_cnh', $_POST['categoria_cnh'] ?? null);
                    $stmt->bindValue(':validade_cnh', !empty($_POST['validade_cnh']) ? $_POST['validade_cnh'] : null);
                    $stmt->bindValue(':data_admissao', !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : null);
                    $stmt->bindValue(':observacoes', $_POST['observacoes'] ?? null);
                    $stmt->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
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
        
        // Editar Motorista
        elseif ($acao === 'editar_motorista') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para editar motoristas');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do motorista não informado');
            }
            
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            $cnh = preg_replace('/[^0-9A-Z]/', '', strtoupper($_POST['cnh'] ?? ''));
            
            // Buscar motorista
            $stmt = $conn->prepare("SELECT m.*, p.id as pessoa_id FROM motorista m INNER JOIN pessoa p ON m.pessoa_id = p.id WHERE m.id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $motorista = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$motorista) {
                throw new Exception('Motorista não encontrado');
            }
            
            // Verificar se CPF já existe em outra pessoa
            $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id");
            $stmt->bindParam(':cpf', $cpf);
            $stmt->bindParam(':pessoa_id', $motorista['pessoa_id'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'CPF já cadastrado em outra pessoa.'];
            } else {
                // Verificar se CNH já existe em outro motorista
                $stmt = $conn->prepare("SELECT id FROM motorista WHERE cnh = :cnh AND id != :id");
                $stmt->bindParam(':cnh', $cnh);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $resposta = ['status' => false, 'mensagem' => 'CNH já cadastrada em outro motorista.'];
                } else {
                    $conn->beginTransaction();
                    
                    // Atualizar pessoa
                    $stmt = $conn->prepare("UPDATE pessoa SET 
                                           nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, data_nascimento = :data_nascimento
                                           WHERE id = :id");
                    $stmt->bindParam(':id', $motorista['pessoa_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':nome', $_POST['nome']);
                    $stmt->bindParam(':cpf', $cpf);
                    $stmt->bindValue(':email', $_POST['email'] ?? null);
                    $stmt->bindValue(':telefone', $_POST['telefone'] ?? null);
                    $stmt->bindValue(':data_nascimento', !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null);
                    $stmt->execute();
                    
                    // Atualizar motorista
                    $stmt = $conn->prepare("UPDATE motorista SET 
                                           cnh = :cnh, categoria_cnh = :categoria_cnh, validade_cnh = :validade_cnh, 
                                           data_admissao = :data_admissao, observacoes = :observacoes
                                           WHERE id = :id");
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->bindParam(':cnh', $cnh);
                    $stmt->bindValue(':categoria_cnh', $_POST['categoria_cnh'] ?? null);
                    $stmt->bindValue(':validade_cnh', !empty($_POST['validade_cnh']) ? $_POST['validade_cnh'] : null);
                    $stmt->bindValue(':data_admissao', !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : null);
                    $stmt->bindValue(':observacoes', $_POST['observacoes'] ?? null);
                    $stmt->execute();
                    
                    $conn->commit();
                    $resposta = ['status' => true, 'mensagem' => 'Motorista atualizado com sucesso!'];
                }
            }
        }
        
        // Excluir Motorista
        elseif ($acao === 'excluir_motorista') {
            if (!$podeEditar) {
                throw new Exception('Você não tem permissão para excluir motoristas');
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do motorista não informado');
            }
            
            $stmt = $conn->prepare("UPDATE motorista SET ativo = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resposta = ['status' => true, 'mensagem' => 'Motorista excluído com sucesso!'];
        }
        
        // Buscar Motorista
        elseif ($acao === 'buscar_motorista') {
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do motorista não informado');
            }
            
            $stmt = $conn->prepare("SELECT m.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento 
                                   FROM motorista m 
                                   INNER JOIN pessoa p ON m.pessoa_id = p.id 
                                   WHERE m.id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $motorista = $stmt->fetch(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $motorista ?: null];
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
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
    <title>Gestão de Motoristas - SIGAE</title>
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
        <h1 class="text-lg font-bold text-gray-800">Motoristas</h1>
        <div class="w-10"></div>
    </header>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen pt-16 lg:pt-0">
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestão de Motoristas</h1>
                <p class="text-gray-600">Cadastre e gerencie os motoristas do transporte escolar</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Motoristas Cadastrados</h2>
                    <?php if ($podeEditar): ?>
                    <button onclick="abrirModalCriarMotorista()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Cadastrar Motorista
                    </button>
                    <?php endif; ?>
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
    </main>

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

    <!-- Modal Editar Motorista -->
    <div id="modalEditarMotorista" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Editar Motorista</h3>
                <button onclick="fecharModalEditarMotorista()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formEditarMotorista" onsubmit="editarMotorista(event)">
                <input type="hidden" id="editar-motorista-id" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                        <input type="text" id="editar-nome" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                        <input type="text" id="editar-cpf" name="cpf" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="editar-email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" id="editar-telefone" name="telefone" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                        <input type="date" id="editar-data-nascimento" name="data_nascimento" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CNH *</label>
                        <input type="text" id="editar-cnh" name="cnh" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria CNH</label>
                        <select id="editar-categoria-cnh" name="categoria_cnh" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Validade CNH</label>
                        <input type="date" id="editar-validade-cnh" name="validade_cnh" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão</label>
                        <input type="date" id="editar-data-admissao" name="data_admissao" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea id="editar-observacoes" name="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="fecharModalEditarMotorista()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
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
        function abrirModalCriarMotorista() {
            document.getElementById('modalCriarMotorista').classList.remove('hidden');
        }

        function fecharModalCriarMotorista() {
            document.getElementById('modalCriarMotorista').classList.add('hidden');
            document.getElementById('formCriarMotorista').reset();
        }

        function abrirModalEditarMotorista(id) {
            // Buscar dados do motorista
            const formData = new FormData();
            formData.append('acao', 'buscar_motorista');
            formData.append('id', id);
            
            fetch('gestao_motoristas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status && data.dados) {
                    const m = data.dados;
                    document.getElementById('editar-motorista-id').value = m.id;
                    document.getElementById('editar-nome').value = m.nome || '';
                    document.getElementById('editar-cpf').value = m.cpf || '';
                    document.getElementById('editar-email').value = m.email || '';
                    document.getElementById('editar-telefone').value = m.telefone || '';
                    document.getElementById('editar-data-nascimento').value = m.data_nascimento || '';
                    document.getElementById('editar-cnh').value = m.cnh || '';
                    document.getElementById('editar-categoria-cnh').value = m.categoria_cnh || '';
                    document.getElementById('editar-validade-cnh').value = m.validade_cnh || '';
                    document.getElementById('editar-data-admissao').value = m.data_admissao || '';
                    document.getElementById('editar-observacoes').value = m.observacoes || '';
                    document.getElementById('modalEditarMotorista').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar dados do motorista');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados do motorista');
            });
        }

        function fecharModalEditarMotorista() {
            document.getElementById('modalEditarMotorista').classList.add('hidden');
            document.getElementById('formEditarMotorista').reset();
        }

        // Criar Motorista
        function criarMotorista(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('acao', 'cadastrar_motorista');
            
            fetch('gestao_motoristas.php', {
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

        // Editar Motorista
        function editarMotorista(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('acao', 'editar_motorista');
            
            fetch('gestao_motoristas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    fecharModalEditarMotorista();
                    carregarMotoristas();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao editar motorista');
            });
        }

        // Excluir Motorista
        function excluirMotorista(id) {
            if (!confirm('Tem certeza que deseja excluir este motorista?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'excluir_motorista');
            formData.append('id', id);
            
            fetch('gestao_motoristas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(data.mensagem);
                    carregarMotoristas();
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir motorista');
            });
        }

        // Carregar Motoristas
        function carregarMotoristas() {
            const busca = document.getElementById('buscar-motorista')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'listar_motoristas');
            formData.append('busca', busca);
            
            fetch('gestao_motoristas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const tbody = document.getElementById('lista-motoristas');
                    if (data.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum motorista cadastrado</td></tr>';
                    } else {
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
                                    ${podeEditar ? `
                                    <button onclick="abrirModalEditarMotorista(${m.id})" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="excluirMotorista(${m.id})" class="text-red-600 hover:text-red-900">
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
        document.getElementById('buscar-motorista')?.addEventListener('input', function() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(carregarMotoristas, 500);
        });

        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            carregarMotoristas();
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

