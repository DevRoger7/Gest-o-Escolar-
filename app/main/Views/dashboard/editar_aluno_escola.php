<?php
// Iniciar output buffering para evitar problemas com headers
if (!ob_get_level()) {
    ob_start();
}
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/academico/AlunoModel.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO
if ($_SESSION['tipo'] !== 'GESTAO' && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$alunoModel = new AlunoModel();
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar escola do gestor logado (mesma lógica do gestao_escolar.php)
$escolaGestor = null;
$escolaGestorId = null;

error_log("DEBUG GESTOR INICIAL - Tipo: " . ($_SESSION['tipo'] ?? 'NULL') . ", usuario_id: " . ($_SESSION['usuario_id'] ?? 'NULL'));

if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    error_log("DEBUG GESTOR - usuario_id: " . ($usuarioId ?? 'NULL'));
    
    $escolaIdSessao = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
    
    if ($escolaIdSessao) {
        try {
            $sqlVerificarEscola = "SELECT e.id, e.nome, e.ativo
                                   FROM escola e
                                   INNER JOIN gestor_lotacao gl ON e.id = gl.escola_id
                                   INNER JOIN gestor g ON gl.gestor_id = g.id
                                   INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                                   WHERE u.id = :usuario_id 
                                   AND e.id = :escola_id 
                                   AND e.ativo = 1
                                   AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                                   LIMIT 1";
            $stmtVerificar = $conn->prepare($sqlVerificarEscola);
            $stmtVerificar->bindParam(':usuario_id', $usuarioId);
            $stmtVerificar->bindParam(':escola_id', $escolaIdSessao, PDO::PARAM_INT);
            $stmtVerificar->execute();
            $escolaValida = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            if ($escolaValida) {
                $escolaGestorId = (int)$escolaValida['id'];
                $escolaGestor = $escolaValida['nome'];
            }
        } catch (Exception $e) {
            error_log("DEBUG GESTOR - Erro ao validar escola da sessão: " . $e->getMessage());
        }
    }
    
    if (!$escolaGestorId && $usuarioId) {
        try {
            $sqlGestor = "SELECT g.id as gestor_id, gl.escola_id, e.nome as escola_nome, gl.responsavel, gl.fim, gl.inicio
                          FROM gestor g
                          INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                          INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                          INNER JOIN escola e ON gl.escola_id = e.id
                          WHERE u.id = :usuario_id AND g.ativo = 1 AND e.ativo = 1
                          ORDER BY 
                            CASE WHEN gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' THEN 0 ELSE 1 END,
                            gl.responsavel DESC, 
                            gl.inicio DESC,
                            gl.id DESC
                          LIMIT 1";
            $stmtGestor = $conn->prepare($sqlGestor);
            $stmtGestor->bindParam(':usuario_id', $usuarioId);
            $stmtGestor->execute();
            $gestorEscola = $stmtGestor->fetch(PDO::FETCH_ASSOC);
            
            if ($gestorEscola) {
                $escolaGestorId = (int)$gestorEscola['escola_id'];
                $escolaGestor = $gestorEscola['escola_nome'];
            }
        } catch (Exception $e) {
            error_log("DEBUG GESTOR - Erro ao buscar escola do gestor: " . $e->getMessage());
        }
    }
}

// Variável auxiliar que sempre retorna a escola correta
$escolaIdAtual = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? $escolaGestorId ?? null;
$escolaNomeAtual = $_SESSION['escola_selecionada_nome'] ?? $_SESSION['escola_atual'] ?? $escolaGestor ?? null;

if ($escolaIdAtual) {
    $escolaGestorId = $escolaIdAtual;
    $escolaGestor = $escolaNomeAtual;
}

if (!$escolaGestorId && $_SESSION['tipo'] === 'GESTAO') {
    header('Location: gestao_escolar.php?erro=escola_nao_encontrada');
    exit;
}

// Verificar se aluno_id foi passado
$alunoId = $_GET['aluno_id'] ?? null;
if (!$alunoId) {
    header('Location: gestao_escolar.php?erro=aluno_nao_informado');
    exit;
}

// Buscar dados do aluno
$aluno = null;
try {
    $aluno = $alunoModel->buscarPorId($alunoId);
    if (!$aluno) {
        header('Location: gestao_escolar.php?erro=aluno_nao_encontrado');
        exit;
    }
    
    // Buscar campos de transporte e endereço diretamente do banco
    try {
        // Buscar dados de transporte da tabela aluno
        // Verificar se as colunas existem primeiro
        $colunaPrecisaExiste = false;
        $colunaDistritoExiste = false;
        $colunaLocalidadeExiste = false;
        try {
            $checkColPrecisa = $conn->query("SHOW COLUMNS FROM aluno LIKE 'precisa_transporte'");
            $colunaPrecisaExiste = $checkColPrecisa->rowCount() > 0;
            
            $checkColDistrito = $conn->query("SHOW COLUMNS FROM aluno LIKE 'distrito_transporte'");
            $colunaDistritoExiste = $checkColDistrito->rowCount() > 0;
            
            $checkColLocalidade = $conn->query("SHOW COLUMNS FROM aluno LIKE 'localidade_transporte'");
            $colunaLocalidadeExiste = $checkColLocalidade->rowCount() > 0;
        } catch (Exception $e) {
            $colunaPrecisaExiste = false;
            $colunaDistritoExiste = false;
            $colunaLocalidadeExiste = false;
        }
        
        // Buscar dados de transporte se as colunas existirem
        if ($colunaPrecisaExiste || $colunaDistritoExiste || $colunaLocalidadeExiste) {
            $sqlCampos = [];
            if ($colunaPrecisaExiste) $sqlCampos[] = 'precisa_transporte';
            if ($colunaDistritoExiste) $sqlCampos[] = 'distrito_transporte';
            if ($colunaLocalidadeExiste) $sqlCampos[] = 'localidade_transporte';
            
            $sqlTransporte = "SELECT " . implode(', ', $sqlCampos) . " FROM aluno WHERE id = :aluno_id";
            $stmtTransporte = $conn->prepare($sqlTransporte);
            $stmtTransporte->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmtTransporte->execute();
            $dadosTransporte = $stmtTransporte->fetch(PDO::FETCH_ASSOC);
            
            if ($dadosTransporte) {
                if ($colunaPrecisaExiste) {
                    // Garantir que precisa_transporte seja sempre 0 ou 1
                    $precisaTransporte = isset($dadosTransporte['precisa_transporte']) && $dadosTransporte['precisa_transporte'] !== null 
                        ? (int)$dadosTransporte['precisa_transporte'] 
                        : 0;
                    $aluno['precisa_transporte'] = $precisaTransporte === 1 ? 1 : 0;
                } else {
                    $aluno['precisa_transporte'] = 0;
                }
                if ($colunaDistritoExiste) {
                    $aluno['distrito_transporte'] = !empty($dadosTransporte['distrito_transporte']) ? $dadosTransporte['distrito_transporte'] : null;
                } else {
                    $aluno['distrito_transporte'] = null;
                }
                if ($colunaLocalidadeExiste) {
                    $aluno['localidade_transporte'] = !empty($dadosTransporte['localidade_transporte']) ? $dadosTransporte['localidade_transporte'] : null;
                } else {
                    $aluno['localidade_transporte'] = null;
                }
            } else {
                $aluno['precisa_transporte'] = 0;
                $aluno['distrito_transporte'] = null;
                $aluno['localidade_transporte'] = null;
            }
        } else {
            $aluno['precisa_transporte'] = 0;
            $aluno['distrito_transporte'] = null;
            $aluno['localidade_transporte'] = null;
        }
        
        // Buscar dados de endereço da tabela pessoa (se não vierem do model)
        if (empty($aluno['endereco']) && !empty($aluno['pessoa_id'])) {
            $stmtEndereco = $conn->prepare("SELECT endereco, numero, complemento, bairro, cidade, estado, cep FROM pessoa WHERE id = :pessoa_id");
            $stmtEndereco->bindParam(':pessoa_id', $aluno['pessoa_id'], PDO::PARAM_INT);
            $stmtEndereco->execute();
            $dadosEndereco = $stmtEndereco->fetch(PDO::FETCH_ASSOC);
            if ($dadosEndereco) {
                $aluno['endereco'] = $dadosEndereco['endereco'] ?? null;
                $aluno['numero'] = $dadosEndereco['numero'] ?? null;
                $aluno['complemento'] = $dadosEndereco['complemento'] ?? null;
                $aluno['bairro'] = $dadosEndereco['bairro'] ?? null;
                $aluno['cidade'] = $dadosEndereco['cidade'] ?? null;
                $aluno['estado'] = $dadosEndereco['estado'] ?? null;
                $aluno['cep'] = $dadosEndereco['cep'] ?? null;
            }
        }
    } catch (Exception $e) {
        // Se as colunas não existirem, definir valores padrão
        $aluno['precisa_transporte'] = 0;
        $aluno['distrito_transporte'] = null;
        $aluno['localidade_transporte'] = null;
    }
} catch (Exception $e) {
    header('Location: gestao_escolar.php?erro=erro_ao_buscar_aluno');
    exit;
}

// Endpoint para buscar localidades por distrito
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'buscar_localidades' && !empty($_GET['distrito'])) {
    header('Content-Type: application/json');
    try {
        $distrito = $_GET['distrito'];
        
        $sql = "SELECT id, localidade, endereco, bairro, cidade, estado, cep
                FROM distrito_localidade
                WHERE distrito = :distrito AND ativo = 1
                ORDER BY localidade ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':distrito', $distrito);
        $stmt->execute();
        $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'localidades' => $localidades]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Processar requisições POST
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_aluno') {
    header('Content-Type: application/json');
    try {
        $alunoIdPost = $_POST['aluno_id'] ?? null;
        if (empty($alunoIdPost)) {
            throw new Exception('ID do aluno não informado.');
        }
        
        // Buscar aluno existente
        $alunoAtual = $alunoModel->buscarPorId($alunoIdPost);
        if (!$alunoAtual) {
            throw new Exception('Aluno não encontrado.');
        }
        
        // Preparar dados
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
        $cpfAtual = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        if (!empty($cpfAtual) && strlen($cpfAtual) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }
        $emailAtual = !empty($_POST['email']) ? trim($_POST['email']) : '';
        
        // Verificar se CPF já existe em outro aluno
        if (!empty($cpfAtual) && $cpfAtual !== ($alunoAtual['cpf'] ?? '')) {
            $sqlVerificarCPF = "SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id";
            $stmtVerificar = $conn->prepare($sqlVerificarCPF);
            $stmtVerificar->bindParam(':cpf', $cpfAtual);
            $stmtVerificar->bindParam(':pessoa_id', $alunoAtual['pessoa_id']);
            $stmtVerificar->execute();
            if ($stmtVerificar->fetch()) {
                throw new Exception('CPF já cadastrado para outro aluno.');
            }
        }
        
        if (!empty($emailAtual) && $emailAtual !== ($alunoAtual['email'] ?? '')) {
            $sqlVerificarEmail = "SELECT id FROM pessoa WHERE email = :email AND id != :pessoa_id LIMIT 1";
            $stmtVerificarEmail = $conn->prepare($sqlVerificarEmail);
            $stmtVerificarEmail->bindParam(':email', $emailAtual);
            $stmtVerificarEmail->bindParam(':pessoa_id', $alunoAtual['pessoa_id']);
            $stmtVerificarEmail->execute();
            if ($stmtVerificarEmail->fetch()) {
                throw new Exception('Email já cadastrado para outro usuário.');
            }
        }
        
        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'data_nascimento' => $_POST['data_nascimento'] ?? null,
            'sexo' => $_POST['sexo'] ?? null,
            'email' => !empty($emailAtual) ? $emailAtual : null,
            'telefone' => !empty($telefone) ? $telefone : null,
            'endereco' => !empty($_POST['endereco']) ? trim($_POST['endereco']) : null,
            'numero' => !empty($_POST['numero']) ? trim($_POST['numero']) : null,
            'complemento' => !empty($_POST['complemento']) ? trim($_POST['complemento']) : null,
            'bairro' => !empty($_POST['bairro']) ? trim($_POST['bairro']) : null,
            'cidade' => !empty($_POST['cidade']) ? trim($_POST['cidade']) : null,
            'estado' => !empty($_POST['estado']) ? trim($_POST['estado']) : null,
            'cep' => !empty($_POST['cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['cep'])) : null,
            'matricula' => $_POST['matricula'] ?? $alunoAtual['matricula'],
            'nis' => !empty($_POST['nis']) ? preg_replace('/[^0-9]/', '', trim($_POST['nis'])) : null,
            'data_matricula' => $_POST['data_matricula'] ?? $alunoAtual['data_matricula'],
            'situacao' => $_POST['situacao'] ?? 'MATRICULADO',
            'precisa_transporte' => isset($_POST['precisa_transporte']) ? 1 : 0,
            'distrito_transporte' => !empty($_POST['distrito_transporte']) ? trim($_POST['distrito_transporte']) : null,
            'localidade_transporte' => !empty($_POST['localidade_transporte']) ? trim($_POST['localidade_transporte']) : null,
            'ativo' => 1
        ];
        
        // Validar campos obrigatórios
        if (empty($dados['nome'])) {
            throw new Exception('Nome é obrigatório.');
        }
        if (empty($dados['data_nascimento'])) {
            throw new Exception('Data de nascimento é obrigatória.');
        }
        if (empty($dados['sexo'])) {
            throw new Exception('Sexo é obrigatório.');
        }
        
        // Atualizar CPF se foi alterado
        if (!empty($cpfAtual) && $cpfAtual !== ($alunoAtual['cpf'] ?? '')) {
            $sqlUpdateCPF = "UPDATE pessoa SET cpf = :cpf WHERE id = :pessoa_id";
            $stmtUpdateCPF = $conn->prepare($sqlUpdateCPF);
            $stmtUpdateCPF->bindParam(':cpf', $cpfAtual);
            $stmtUpdateCPF->bindParam(':pessoa_id', $alunoAtual['pessoa_id']);
            $stmtUpdateCPF->execute();
        }
        
        // Atualizar endereço na tabela pessoa
        try {
            $sqlUpdateEndereco = "UPDATE pessoa SET 
                endereco = :endereco,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                estado = :estado,
                cep = :cep
                WHERE id = :pessoa_id";
            $stmtUpdateEndereco = $conn->prepare($sqlUpdateEndereco);
            $stmtUpdateEndereco->bindParam(':endereco', $dados['endereco']);
            $stmtUpdateEndereco->bindParam(':numero', $dados['numero']);
            $stmtUpdateEndereco->bindParam(':complemento', $dados['complemento']);
            $stmtUpdateEndereco->bindParam(':bairro', $dados['bairro']);
            $stmtUpdateEndereco->bindParam(':cidade', $dados['cidade']);
            $stmtUpdateEndereco->bindParam(':estado', $dados['estado']);
            $stmtUpdateEndereco->bindParam(':cep', $dados['cep']);
            $stmtUpdateEndereco->bindParam(':pessoa_id', $alunoAtual['pessoa_id']);
            $stmtUpdateEndereco->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar endereço: " . $e->getMessage());
        }
        
        // Usar o model para atualizar o aluno
        $result = $alunoModel->atualizar($alunoIdPost, $dados);
        
        if ($result['success']) {
            // Atualizar campos de transporte se necessário
            try {
                // Verificar se as colunas existem
                $stmtCheckPrecisa = $conn->query("SHOW COLUMNS FROM aluno LIKE 'precisa_transporte'");
                $temPrecisaTransporte = $stmtCheckPrecisa->rowCount() > 0;
                
                $stmtCheckDistrito = $conn->query("SHOW COLUMNS FROM aluno LIKE 'distrito_transporte'");
                $temDistritoTransporte = $stmtCheckDistrito->rowCount() > 0;
                
                $stmtCheckLocalidade = $conn->query("SHOW COLUMNS FROM aluno LIKE 'localidade_transporte'");
                $temLocalidadeTransporte = $stmtCheckLocalidade->rowCount() > 0;
                
                if ($temPrecisaTransporte || $temDistritoTransporte || $temLocalidadeTransporte) {
                    $camposUpdate = [];
                    $paramsUpdate = [':aluno_id' => $alunoIdPost];
                    
                    // SEMPRE salvar precisa_transporte (0 ou 1) se a coluna existir
                    if ($temPrecisaTransporte) {
                        $precisaTransporte = isset($_POST['precisa_transporte']) && $_POST['precisa_transporte'] == '1' ? 1 : 0;
                        $camposUpdate[] = 'precisa_transporte = :precisa_transporte';
                        $paramsUpdate[':precisa_transporte'] = $precisaTransporte;
                    }
                    
                    // Salvar distrito_transporte se a coluna existir
                    if ($temDistritoTransporte) {
                        $precisaTransporte = isset($_POST['precisa_transporte']) && $_POST['precisa_transporte'] == '1';
                        if ($precisaTransporte && isset($dados['distrito_transporte']) && trim($dados['distrito_transporte']) !== '') {
                            $camposUpdate[] = 'distrito_transporte = :distrito_transporte';
                            $paramsUpdate[':distrito_transporte'] = trim($dados['distrito_transporte']);
                        } else {
                            // Se não foi marcado precisa_transporte, limpar o campo
                            $camposUpdate[] = 'distrito_transporte = NULL';
                        }
                    }
                    
                    // Salvar localidade_transporte se a coluna existir
                    if ($temLocalidadeTransporte) {
                        $precisaTransporte = isset($_POST['precisa_transporte']) && $_POST['precisa_transporte'] == '1';
                        if ($precisaTransporte && isset($dados['localidade_transporte']) && trim($dados['localidade_transporte']) !== '') {
                            $camposUpdate[] = 'localidade_transporte = :localidade_transporte';
                            $paramsUpdate[':localidade_transporte'] = trim($dados['localidade_transporte']);
                        } else {
                            // Se não foi marcado precisa_transporte, limpar o campo
                            $camposUpdate[] = 'localidade_transporte = NULL';
                        }
                    }
                    
                    if (!empty($camposUpdate)) {
                        $sqlUpdate = "UPDATE aluno SET " . implode(', ', $camposUpdate) . " WHERE id = :aluno_id";
                        $stmtUpdate = $conn->prepare($sqlUpdate);
                        foreach ($paramsUpdate as $key => $value) {
                            $stmtUpdate->bindValue($key, $value);
                        }
                        $stmtUpdate->execute();
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao atualizar campos de transporte: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Aluno atualizado com sucesso!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Erro ao atualizar aluno.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Funções de formatação
function formatarCPF($cpf) {
    if (!$cpf) return '';
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpfLimpo) !== 11) return $cpf;
    return substr($cpfLimpo, 0, 3) . '.' . substr($cpfLimpo, 3, 3) . '.' . substr($cpfLimpo, 6, 3) . '-' . substr($cpfLimpo, 9, 2);
}

function formatarTelefone($telefone) {
    if (!$telefone) return '';
    $telLimpo = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telLimpo) === 11) {
        return '(' . substr($telLimpo, 0, 2) . ') ' . substr($telLimpo, 2, 5) . '-' . substr($telLimpo, 7, 4);
    } else if (strlen($telLimpo) === 10) {
        return '(' . substr($telLimpo, 0, 2) . ') ' . substr($telLimpo, 2, 4) . '-' . substr($telLimpo, 6, 4);
    }
    return $telefone;
}

function formatarCEP($cep) {
    if (!$cep) return '';
    $cepLimpo = preg_replace('/[^0-9]/', '', $cep);
    if (strlen($cepLimpo) === 8) {
        return substr($cepLimpo, 0, 5) . '-' . substr($cepLimpo, 5, 3);
    }
    return $cep;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Editar Aluno') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                    }
                }
            }
        }
    </script>
    <style>
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>
    
    <!-- Sidebar -->
    <?php if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'ADM') { ?>
        <?php include('components/sidebar_adm.php'); ?>
    <?php } else { ?>
        <?php include('components/sidebar_gestao.php'); ?>
    <?php } ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Editar Aluno</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="gestao_escolar.php" class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                            Voltar
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-4xl mx-auto">
                <!-- Mensagens -->
                <div id="alerta-erro" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"></div>
                <div id="alerta-sucesso" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"></div>
                
                <form id="formEditarAluno" class="space-y-6 bg-white rounded-lg shadow-lg p-6">
                    <input type="hidden" id="aluno-id" name="aluno_id" value="<?= htmlspecialchars($aluno['id'] ?? '') ?>">
                    
                    <!-- Dados Pessoais -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Dados Pessoais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome <span class="text-red-500">*</span></label>
                                <input type="text" name="nome" id="nome" required 
                                       value="<?= htmlspecialchars($aluno['nome'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                                <input type="text" name="cpf" id="cpf" maxlength="14"
                                       value="<?= formatarCPF($aluno['cpf'] ?? '') ?>"
                                       placeholder="000.000.000-00"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                       oninput="formatarCPFInput(this)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento <span class="text-red-500">*</span></label>
                                <input type="date" name="data_nascimento" id="data_nascimento" required 
                                       value="<?= htmlspecialchars($aluno['data_nascimento'] ?? '') ?>"
                                       max="<?= date('Y-m-d') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo <span class="text-red-500">*</span></label>
                                <select name="sexo" id="sexo" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="M" <?= ($aluno['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="F" <?= ($aluno['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                                    <option value="OUTRO" <?= ($aluno['sexo'] ?? '') === 'OUTRO' ? 'selected' : '' ?>>Outro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email"
                                       value="<?= htmlspecialchars($aluno['email'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" name="telefone" id="telefone" maxlength="15"
                                       value="<?= formatarTelefone($aluno['telefone'] ?? '') ?>"
                                       placeholder="(00) 00000-0000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                       oninput="formatarTelefoneInput(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados Escolares -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Dados Escolares</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                                <input type="text" name="matricula" id="matricula"
                                       value="<?= htmlspecialchars($aluno['matricula'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">NIS</label>
                                <input type="text" name="nis" id="nis" maxlength="14"
                                       value="<?= htmlspecialchars($aluno['nis'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Matrícula</label>
                                <input type="date" name="data_matricula" id="data_matricula"
                                       value="<?= htmlspecialchars($aluno['data_matricula'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Situação</label>
                                <select name="situacao" id="situacao"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="MATRICULADO" <?= ($aluno['situacao'] ?? '') === 'MATRICULADO' ? 'selected' : '' ?>>Matriculado</option>
                                    <option value="TRANSFERIDO" <?= ($aluno['situacao'] ?? '') === 'TRANSFERIDO' ? 'selected' : '' ?>>Transferido</option>
                                    <option value="EVADIDO" <?= ($aluno['situacao'] ?? '') === 'EVADIDO' ? 'selected' : '' ?>>Evadido</option>
                                    <option value="FORMADO" <?= ($aluno['situacao'] ?? '') === 'FORMADO' ? 'selected' : '' ?>>Formado</option>
                                    <option value="CONCLUIDO" <?= ($aluno['situacao'] ?? '') === 'CONCLUIDO' ? 'selected' : '' ?>>Concluído</option>
                                    <option value="CANCELADO" <?= ($aluno['situacao'] ?? '') === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Endereço do Aluno -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Endereço</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                <input type="text" name="cep" id="cep" maxlength="9"
                                       value="<?= formatarCEP($aluno['cep'] ?? '') ?>"
                                       placeholder="00000-000"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                       oninput="formatarCEP(this)"
                                       onblur="buscarCEP(this.value, 'endereco', 'bairro', 'cidade', 'estado')">
                                <p class="text-xs text-gray-500 mt-1">Digite o CEP para preencher automaticamente</p>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                <input type="text" name="endereco" id="endereco"
                                       value="<?= htmlspecialchars($aluno['endereco'] ?? '') ?>"
                                       placeholder="Rua, Avenida, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" name="numero" id="numero"
                                       value="<?= htmlspecialchars($aluno['numero'] ?? '') ?>"
                                       placeholder="Número"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                <input type="text" name="complemento" id="complemento"
                                       value="<?= htmlspecialchars($aluno['complemento'] ?? '') ?>"
                                       placeholder="Apartamento, bloco, etc."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                <input type="text" name="bairro" id="bairro"
                                       value="<?= htmlspecialchars($aluno['bairro'] ?? '') ?>"
                                       placeholder="Bairro"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                                <input type="text" name="cidade" id="cidade"
                                       value="<?= htmlspecialchars($aluno['cidade'] ?? '') ?>"
                                       placeholder="Cidade"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="estado" id="estado"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="AC" <?= ($aluno['estado'] ?? '') === 'AC' ? 'selected' : '' ?>>Acre</option>
                                    <option value="AL" <?= ($aluno['estado'] ?? '') === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                                    <option value="AP" <?= ($aluno['estado'] ?? '') === 'AP' ? 'selected' : '' ?>>Amapá</option>
                                    <option value="AM" <?= ($aluno['estado'] ?? '') === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                                    <option value="BA" <?= ($aluno['estado'] ?? '') === 'BA' ? 'selected' : '' ?>>Bahia</option>
                                    <option value="CE" <?= ($aluno['estado'] ?? '') === 'CE' || empty($aluno['estado']) ? 'selected' : '' ?>>Ceará</option>
                                    <option value="DF" <?= ($aluno['estado'] ?? '') === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                                    <option value="ES" <?= ($aluno['estado'] ?? '') === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                                    <option value="GO" <?= ($aluno['estado'] ?? '') === 'GO' ? 'selected' : '' ?>>Goiás</option>
                                    <option value="MA" <?= ($aluno['estado'] ?? '') === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                                    <option value="MT" <?= ($aluno['estado'] ?? '') === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                                    <option value="MS" <?= ($aluno['estado'] ?? '') === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                                    <option value="MG" <?= ($aluno['estado'] ?? '') === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                                    <option value="PA" <?= ($aluno['estado'] ?? '') === 'PA' ? 'selected' : '' ?>>Pará</option>
                                    <option value="PB" <?= ($aluno['estado'] ?? '') === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                                    <option value="PR" <?= ($aluno['estado'] ?? '') === 'PR' ? 'selected' : '' ?>>Paraná</option>
                                    <option value="PE" <?= ($aluno['estado'] ?? '') === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                                    <option value="PI" <?= ($aluno['estado'] ?? '') === 'PI' ? 'selected' : '' ?>>Piauí</option>
                                    <option value="RJ" <?= ($aluno['estado'] ?? '') === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                                    <option value="RN" <?= ($aluno['estado'] ?? '') === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                                    <option value="RS" <?= ($aluno['estado'] ?? '') === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                                    <option value="RO" <?= ($aluno['estado'] ?? '') === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                                    <option value="RR" <?= ($aluno['estado'] ?? '') === 'RR' ? 'selected' : '' ?>>Roraima</option>
                                    <option value="SC" <?= ($aluno['estado'] ?? '') === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                                    <option value="SP" <?= ($aluno['estado'] ?? '') === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                                    <option value="SE" <?= ($aluno['estado'] ?? '') === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                                    <option value="TO" <?= ($aluno['estado'] ?? '') === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de Transporte -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Transporte Escolar</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" name="precisa_transporte" id="precisa_transporte" value="1" 
                                           <?= (isset($aluno['precisa_transporte']) && (int)$aluno['precisa_transporte'] === 1) ? 'checked' : '' ?>
                                           onchange="toggleCamposTransporte()"
                                           class="w-5 h-5 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                    <span class="text-sm font-medium text-gray-700">Aluno precisa de transporte escolar</span>
                                </label>
                            </div>
                            <div id="container-campos-transporte" class="<?= (isset($aluno['precisa_transporte']) && (int)$aluno['precisa_transporte'] === 1) ? '' : 'hidden' ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Distrito de Origem</label>
                                        <div class="autocomplete-container">
                                            <input type="text" name="distrito_transporte" id="distrito_transporte" 
                                                   value="<?= htmlspecialchars($aluno['distrito_transporte'] ?? '') ?>"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" 
                                                   placeholder="Digite o distrito..." autocomplete="off"
                                                   oninput="buscarDistritos(this.value)"
                                                   onchange="if(this.value) { distritoSelecionado = this.value; carregarLocalidades(this.value); }">
                                            <div id="autocomplete-dropdown-transporte" class="autocomplete-dropdown"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Selecione o distrito onde o aluno precisa de transporte</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Localidade</label>
                                        <div class="autocomplete-container">
                                            <input type="text" name="localidade_transporte" id="localidade_transporte" 
                                                   value="<?= htmlspecialchars($aluno['localidade_transporte'] ?? '') ?>"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" 
                                                   placeholder="Digite a localidade..." autocomplete="off"
                                                   oninput="buscarLocalidades(this.value)">
                                            <div id="autocomplete-dropdown-localidade" class="autocomplete-dropdown"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Selecione a localidade do distrito selecionado</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="flex justify-end space-x-3 pt-6 mt-6 border-t border-gray-200">
                        <a href="gestao_escolar.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                            Cancelar
                        </a>
                        <button type="button" onclick="salvarEdicao()" 
                                class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green font-medium transition-colors">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Lista de distritos de Maranguape
        const distritosMaranguape = [
            'Amanari', 'Antônio Marques', 'Cachoeira', 'Itapebussu', 'Jubaia',
            'Ladeira Grande', 'Lages', 'Lagoa do Juvenal', 'Manoel Guedes',
            'Sede', 'Papara', 'Penedo', 'Sapupara', 'São João do Amanari',
            'Tanques', 'Umarizeiras', 'Vertentes do Lagedo'
        ];
        
        // Variáveis globais
        let distritoSelecionado = null;
        let localidadesDisponiveis = [];
        
        function formatarCPFInput(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                input.value = value;
            }
        }
        
        function formatarTelefoneInput(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                input.value = value;
            }
        }
        
        function formatarCEP(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                input.value = value;
            }
        }
        
        function buscarCEP(cep, campoEndereco, campoBairro, campoCidade, campoEstado) {
            // Remover formatação do CEP
            const cepLimpo = cep.replace(/\D/g, '');
            
            // Validar CEP (deve ter 8 dígitos)
            if (cepLimpo.length !== 8) {
                return;
            }
            
            // Mostrar loading
            const inputEndereco = document.getElementById(campoEndereco);
            if (inputEndereco) {
                inputEndereco.disabled = true;
                inputEndereco.placeholder = 'Buscando...';
            }
            
            // Buscar CEP na API ViaCEP
            fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`)
                .then(response => response.json())
                .then(data => {
                    // Reabilitar campos
                    if (inputEndereco) {
                        inputEndereco.disabled = false;
                        inputEndereco.placeholder = 'Rua, Avenida, etc.';
                    }
                    
                    // Verificar se o CEP foi encontrado
                    if (data.erro) {
                        console.log('CEP não encontrado');
                        return;
                    }
                    
                    // Preencher campos
                    if (inputEndereco && data.logradouro) {
                        inputEndereco.value = data.logradouro;
                    }
                    
                    const inputBairro = document.getElementById(campoBairro);
                    if (inputBairro && data.bairro) {
                        inputBairro.value = data.bairro;
                    }
                    
                    const inputCidade = document.getElementById(campoCidade);
                    if (inputCidade && data.localidade) {
                        inputCidade.value = data.localidade;
                    }
                    
                    const inputEstado = document.getElementById(campoEstado);
                    if (inputEstado && data.uf) {
                        inputEstado.value = data.uf;
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    if (inputEndereco) {
                        inputEndereco.disabled = false;
                        inputEndereco.placeholder = 'Rua, Avenida, etc.';
                    }
                });
        }
        
        // Autocomplete distrito
        function buscarDistritos(query) {
            const input = document.getElementById('distrito_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-transporte');
            if (!input || !dropdown) return;
            
            const queryLower = query.trim().toLowerCase();
            
            if (queryLower.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            const filteredDistritos = distritosMaranguape.filter(distrito => 
                distrito.toLowerCase().includes(queryLower)
            );
            
            if (filteredDistritos.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            dropdown.innerHTML = filteredDistritos.map((distrito) => `
                <div class="autocomplete-item" onclick="selecionarDistrito('${distrito}')">
                    <div>${distrito}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        function selecionarDistrito(distrito) {
            const inputDistrito = document.getElementById('distrito_transporte');
            if (inputDistrito) {
                inputDistrito.value = distrito;
            }
            const dropdown = document.getElementById('autocomplete-dropdown-transporte');
            if (dropdown) {
                dropdown.classList.remove('show');
            }
            distritoSelecionado = distrito;
            
            // Carregar localidades do distrito
            carregarLocalidades(distrito);
        }
        
        function carregarLocalidades(distrito) {
            if (!distrito) {
                return;
            }
            
            fetch(`?acao=buscar_localidades&distrito=${encodeURIComponent(distrito)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.localidades && data.localidades.length > 0) {
                        localidadesDisponiveis = data.localidades;
                        // Não limpar o valor se já estiver preenchido
                        const inputLocalidade = document.getElementById('localidade_transporte');
                        if (inputLocalidade && !inputLocalidade.value) {
                            inputLocalidade.value = '';
                        }
                        initAutocompleteLocalidade();
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar localidades:', error);
                });
        }
        
        function buscarLocalidades(query) {
            const input = document.getElementById('localidade_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-localidade');
            if (!input || !dropdown || !distritoSelecionado) return;
            
            const queryLower = query.trim().toLowerCase();
            
            if (queryLower.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            const filteredLocalidades = localidadesDisponiveis.filter(loc => 
                loc.localidade.toLowerCase().includes(queryLower)
            );
            
            if (filteredLocalidades.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            dropdown.innerHTML = filteredLocalidades.map((loc) => `
                <div class="autocomplete-item" onclick="selecionarLocalidade('${loc.localidade.replace(/'/g, "\\'")}')">
                    <div>${loc.localidade}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        function selecionarLocalidade(localidade) {
            document.getElementById('localidade_transporte').value = localidade;
            document.getElementById('autocomplete-dropdown-localidade').classList.remove('show');
        }
        
        function initAutocompleteDistrito() {
            const input = document.getElementById('distrito_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-transporte');
            if (!input || !dropdown) return;
            
            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
        
        function initAutocompleteLocalidade() {
            const input = document.getElementById('localidade_transporte');
            const dropdown = document.getElementById('autocomplete-dropdown-localidade');
            if (!input || !dropdown) return;
            
            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
        
        // Função para toggle dos campos de transporte
        function toggleCamposTransporte() {
            const precisaTransporte = document.getElementById('precisa_transporte').checked;
            const container = document.getElementById('container-campos-transporte');
            
            if (precisaTransporte) {
                container.classList.remove('hidden');
                initAutocompleteDistrito();
                // Se já tem distrito selecionado, carregar localidades
                const distritoAtual = document.getElementById('distrito_transporte')?.value;
                if (distritoAtual) {
                    distritoSelecionado = distritoAtual;
                    carregarLocalidades(distritoAtual);
                }
            } else {
                container.classList.add('hidden');
                // Limpar campos quando desmarcar
                document.getElementById('distrito_transporte').value = '';
                document.getElementById('localidade_transporte').value = '';
                distritoSelecionado = null;
                localidadesDisponiveis = [];
            }
        }
        
        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Garantir que o estado inicial esteja correto
            toggleCamposTransporte();
            
            // Verificar se precisa de transporte está marcado e inicializar
            const precisaTransporte = document.getElementById('precisa_transporte');
            if (precisaTransporte && precisaTransporte.checked) {
                // Se já tem distrito, carregar localidades
                const distritoAtual = document.getElementById('distrito_transporte')?.value;
                if (distritoAtual) {
                    distritoSelecionado = distritoAtual;
                    carregarLocalidades(distritoAtual);
                }
                // Inicializar autocomplete
                initAutocompleteDistrito();
            }
        });
        
        function salvarEdicao() {
            const form = document.getElementById('formEditarAluno');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            formData.append('acao', 'editar_aluno');
            
            // Limpar formatação
            const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
            const telefone = document.getElementById('telefone').value.replace(/\D/g, '');
            
            formData.set('cpf', cpf);
            formData.set('telefone', telefone);
            
            // Mostrar loading
            const btnSalvar = event?.target || document.querySelector('[onclick*="salvarEdicao"]');
            const textoOriginal = btnSalvar?.textContent || 'Salvar Alterações';
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.textContent = 'Salvando...';
            }
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                    btnSalvar.textContent = textoOriginal;
                }
                
                if (data.success) {
                    const alertaSucesso = document.getElementById('alerta-sucesso');
                    if (alertaSucesso) {
                        alertaSucesso.textContent = data.message || 'Aluno atualizado com sucesso!';
                        alertaSucesso.classList.remove('hidden');
                    }
                    
                    setTimeout(() => {
                        window.location.href = 'gestao_escolar.php';
                    }, 1500);
                } else {
                    const alertaErro = document.getElementById('alerta-erro');
                    if (alertaErro) {
                        alertaErro.textContent = data.message || 'Erro ao atualizar aluno';
                        alertaErro.classList.remove('hidden');
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                    btnSalvar.textContent = textoOriginal;
                }
                const alertaErro = document.getElementById('alerta-erro');
                if (alertaErro) {
                    alertaErro.textContent = 'Erro ao atualizar aluno. Tente novamente.';
                    alertaErro.classList.remove('hidden');
                }
            });
        }
    </script>
</body>
</html>

