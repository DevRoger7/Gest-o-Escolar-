<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/FrequenciaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$frequenciaModel = new FrequenciaModel();

// Buscar professor_id
$professorId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
error_log("DEBUG frequencia_professor: pessoa_id da sessão = " . ($pessoaId ?? 'NULL'));

if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $pessoaIdParam = $pessoaId;
    $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
    error_log("DEBUG frequencia_professor: professor_id encontrado = " . ($professorId ?? 'NULL'));
}

// Fallback: tentar obter pessoa_id via usuario_id e CPF se necessário
if (!$professorId) {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$pessoaId && $usuarioId) {
        $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
        $stmtPessoa = $conn->prepare($sqlPessoa);
        $usuarioIdParam = $usuarioId;
        $stmtPessoa->bindParam(':usuario_id', $usuarioIdParam);
        $stmtPessoa->execute();
        $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
        $pessoaId = $usuario['pessoa_id'] ?? null;
    }
    if (!$pessoaId) {
        $cpf = $_SESSION['cpf'] ?? null;
        if ($cpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            $sqlPessoaCpf = "SELECT id FROM pessoa WHERE cpf = :cpf LIMIT 1";
            $stmtPessoaCpf = $conn->prepare($sqlPessoaCpf);
            $stmtPessoaCpf->bindParam(':cpf', $cpfLimpo);
            $stmtPessoaCpf->execute();
            $pessoa = $stmtPessoaCpf->fetch(PDO::FETCH_ASSOC);
            $pessoaId = $pessoa['id'] ?? null;
        }
    }
    if ($pessoaId) {
        $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
        $stmtProfessor = $conn->prepare($sqlProfessor);
        $pessoaIdParam = $pessoaId;
        $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
        $stmtProfessor->execute();
        $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
        $professorId = $professor['id'] ?? null;
    }
}

// Buscar turmas e disciplinas do professor
$turmasProfessor = [];
if ($professorId) {
    // Log para debug
    error_log("DEBUG frequencia_professor: Buscando turmas para professor_id = $professorId");
    
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    t.serie,
                    t.letra,
                    t.turno,
                    d.id as disciplina_id,
                    d.nome as disciplina_nome,
                    e.id as escola_id,
                    e.nome as escola_nome
                  FROM turma_professor tp
                  INNER JOIN turma t ON tp.turma_id = t.id
                  INNER JOIN disciplina d ON tp.disciplina_id = d.id
                  INNER JOIN escola e ON t.escola_id = e.id
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1";
    
    // Remover filtro de escola selecionada - professor deve ver todas as suas turmas
    // O filtro estava impedindo que turmas aparecessem se a escola da sessão não correspondesse
    $sqlTurmas .= " ORDER BY t.serie, t.letra, d.nome";
    
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    $stmtTurmas->execute();
    $turmasProfessor = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para debug
    error_log("DEBUG frequencia_professor: Encontradas " . count($turmasProfessor) . " turmas para o professor");
    if (count($turmasProfessor) > 0) {
        foreach ($turmasProfessor as $turma) {
            error_log("DEBUG frequencia_professor: Turma encontrada - ID: {$turma['turma_id']}, Nome: {$turma['turma_nome']}, Disciplina: {$turma['disciplina_nome']}");
        }
    }
} else {
    error_log("DEBUG frequencia_professor: professorId não encontrado!");
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'lancar_frequencia') {
        if (!$professorId) {
            echo json_encode(['success' => false, 'message' => 'Professor não encontrado']);
            exit;
        }
        $turmaId = $_POST['turma_id'] ?? null;
        $disciplinaId = $_POST['disciplina_id'] ?? null;
        $data = $_POST['data'] ?? date('Y-m-d');
        $frequencias = json_decode($_POST['frequencias'] ?? '[]', true);
        
        if ($turmaId && $disciplinaId && !empty($frequencias)) {
            $resultado = $frequenciaModel->registrarLote($turmaId, $data, $frequencias);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_alunos_turma' && !empty($_GET['turma_id'])) {
        $turmaId = $_GET['turma_id'];
        
        // Validar se o professor tem acesso a esta turma
        if ($professorId) {
            $sqlValidar = "SELECT COUNT(*) as total
                          FROM turma_professor tp
                          WHERE tp.turma_id = :turma_id 
                          AND tp.professor_id = :professor_id 
                          AND tp.fim IS NULL";
            $stmtValidar = $conn->prepare($sqlValidar);
            $stmtValidar->bindParam(':turma_id', $turmaId);
            $stmtValidar->bindParam(':professor_id', $professorId);
            $stmtValidar->execute();
            $validacao = $stmtValidar->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacao || $validacao['total'] == 0) {
                error_log("Professor $professorId tentou acessar turma $turmaId sem permissão");
                echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta turma']);
                exit;
            }
        } else {
            error_log("Erro: professorId não encontrado ao buscar alunos da turma $turmaId");
            echo json_encode(['success' => false, 'message' => 'Professor não identificado']);
            exit;
        }
        
        $sql = "SELECT a.id, p.nome, COALESCE(a.matricula, '') as matricula
                FROM aluno_turma at
                INNER JOIN aluno a ON at.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE at.turma_id = :turma_id AND at.fim IS NULL AND a.ativo = 1
                ORDER BY p.nome ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Busca de alunos da turma $turmaId retornou " . count($alunos) . " alunos");
        
        echo json_encode(['success' => true, 'alunos' => $alunos]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_frequencia_data' && !empty($_GET['turma_id']) && !empty($_GET['data'])) {
        $turmaId = $_GET['turma_id'];
        $data = $_GET['data'];
        
        try {
            $frequencias = $frequenciaModel->buscarPorTurmaData($turmaId, $data);
            echo json_encode(['success' => true, 'frequencias' => $frequencias]);
        } catch (Exception $e) {
            error_log("Erro ao buscar frequência: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar frequência: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_frequencia' && !empty($_GET['frequencia_id'])) {
        $frequenciaId = $_GET['frequencia_id'];
        $frequencia = $frequenciaModel->buscarPorId($frequenciaId);
        if ($frequencia) {
            echo json_encode(['success' => true, 'frequencia' => $frequencia]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Frequência não encontrada']);
        }
        exit;
    }
}

// Processar atualização de frequência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_frequencia' && $professorId) {
    header('Content-Type: application/json');
    
    $frequenciaId = $_POST['frequencia_id'] ?? null;
    $presenca = $_POST['presenca'] ?? null;
    $observacao = $_POST['observacao'] ?? null;
    
    if (!$frequenciaId || $presenca === null) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }
    
    try {
        $result = $frequenciaModel->atualizar($frequenciaId, [
            'presenca' => $presenca,
            'observacao' => $observacao
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Frequência atualizada com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar frequência']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Frequência - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'primary-light': '#4CAF50',
                    }
                }
            }
        }
    </script>
    <!-- Script inline para garantir que as funções estejam disponíveis antes do HTML -->
    <script>
        // Função para fechar o modal - DEVE estar antes do HTML
        window.fecharModalLancarFrequencia = function() {
            console.log('Fechando modal...');
            const modal = document.getElementById('modal-lancar-frequencia');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                console.log('Modal fechado');
            } else {
                console.error('Modal não encontrado para fechar');
            }
        };
        
        // Função principal para abrir o modal - DEVE estar antes do HTML
        window.abrirModalLancarFrequencia = function(turmaId = null, disciplinaId = null, turmaNome = '', disciplinaNome = '') {
            console.log('=== abrirModalLancarFrequencia CHAMADO ===');
            console.log('Parâmetros:', { turmaId, disciplinaId, turmaNome, disciplinaNome });
            
            const modal = document.getElementById('modal-lancar-frequencia');
            if (!modal) {
                console.error('❌ Modal não encontrado!');
                alert('Erro: Modal não encontrado. Recarregue a página.');
                return;
            }
            
            console.log('✅ Modal encontrado!');
            
            // Remover classe hidden
            modal.classList.remove('hidden');
            
            // Forçar display flex com !important para sobrescrever qualquer CSS
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('visibility', 'visible', 'important');
            modal.style.setProperty('opacity', '1', 'important');
            modal.style.setProperty('z-index', '60', 'important');
            
            if (turmaId && disciplinaId) {
                const turmaIdElement = document.getElementById('frequencia-turma-id');
                const disciplinaIdElement = document.getElementById('frequencia-disciplina-id');
                
                if (turmaIdElement) {
                    turmaIdElement.value = turmaId;
                }
                
                if (disciplinaIdElement) {
                    disciplinaIdElement.value = disciplinaId;
                }
                
                const infoElement = document.getElementById('frequencia-info-turma');
                if (infoElement && turmaNome && disciplinaNome) {
                    infoElement.textContent = turmaNome + ' - ' + disciplinaNome;
                } else if (infoElement && window.buscarInfoTurma) {
                    window.buscarInfoTurma(turmaId, disciplinaId);
                }
                
                console.log('Carregando alunos para turma:', turmaId);
                // Aguardar um pouco para garantir que o segundo script foi carregado
                // A função carregarAlunosParaFrequencia está no segundo script
                setTimeout(function() {
                    if (window.carregarAlunosParaFrequencia) {
                        console.log('Chamando carregarAlunosParaFrequencia...');
                        window.carregarAlunosParaFrequencia(turmaId);
                    } else {
                        console.warn('Função carregarAlunosParaFrequencia ainda não está disponível, aguardando mais...');
                        // Tentar novamente após mais um delay
                        setTimeout(function() {
                            if (window.carregarAlunosParaFrequencia) {
                                console.log('Chamando carregarAlunosParaFrequencia (segunda tentativa)...');
                                window.carregarAlunosParaFrequencia(turmaId);
                            } else {
                                console.error('Função carregarAlunosParaFrequencia não encontrada após múltiplas tentativas. Verifique se o script foi carregado completamente.');
                            }
                        }, 1000);
                    }
                }, 200);
            }
        };
        
        // Função wrapper para ser chamada do onclick inline
        window.abrirModalLancarFrequenciaFromButton = function(button) {
            const turmaId = button.getAttribute('data-turma-id');
            const disciplinaId = button.getAttribute('data-disciplina-id');
            const turmaNome = button.getAttribute('data-turma-nome');
            const disciplinaNome = button.getAttribute('data-disciplina-nome');
            
            window.abrirModalLancarFrequencia(turmaId, disciplinaId, turmaNome, disciplinaNome);
        };
    </script>
    <!-- Estilos minimalistas e profissionais -->
    <style>
        .sidebar-transition { transition: all 0.2s ease; }
        .content-transition { transition: margin-left 0.2s ease; }
        .menu-item.active {
            background: rgba(45, 90, 39, 0.08);
            border-right: 2px solid #2D5A27;
        }
        .menu-item:hover {
            background: rgba(45, 90, 39, 0.05);
        }
        .mobile-menu-overlay {
            transition: opacity 0.2s ease;
        }
        
        /* Cards minimalistas */
        /* Modal fullscreen minimalista */
        .modal-fullscreen {
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Botão de presença redondo minimalista */
        .presenca-toggle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 3px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .presenca-toggle:hover {
            border-color: #9ca3af;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .presenca-toggle.presente {
            border-color: #2D5A27;
            background: #2D5A27;
        }
        .presenca-toggle.ausente {
            border-color: #dc2626;
            background: #dc2626;
        }
        /* Adicionando estilo para falta justificada (amarelo) */
        .presenca-toggle.justificada {
            border-color: #d97706;
            background: #d97706;
        }
        .presenca-toggle svg {
            width: 20px;
            height: 20px;
            color: #9ca3af;
        }
        .presenca-toggle.presente svg,
        .presenca-toggle.ausente svg,
        .presenca-toggle.justificada svg {
            color: #fff;
        }
        
        /* Card do aluno moderno */
        .aluno-row {
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .aluno-row:hover {
            border-color: #d1d5db;
            border-left-color: #2D5A27;
            transform: translateX(2px);
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.1);
        }
        .aluno-row.ausente {
            background: #fef2f2;
            border-color: #fecaca;
            border-left-color: #dc2626;
        }
        .aluno-row.presente {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        /* Estilo do card quando falta justificada */
        .aluno-row.justificada {
            background: #fffbeb;
            border-color: #fde68a;
        }
        
        /* Campo de justificativa */
        .justificativa-field {
            display: none;
            width: 100%;
            margin-top: 10px;
            padding-left: 66px;
            animation: slideDown 0.2s ease;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .justificativa-field.show {
            display: block;
        }
        .justificativa-field input {
            width: 100%;
            padding: 10px 14px;
            font-size: 13px;
            border: 2px solid #fde68a;
            border-radius: 8px;
            background: #fffbeb;
            color: #92400e;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }
        .justificativa-field input:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1);
            background: #fff;
        }
        .justificativa-field input::placeholder {
            color: #a3a3a3;
        }
        
        /* Aluno row com wrapper para justificativa */
        .aluno-wrapper {
            display: flex;
            flex-direction: column;
        }
        
        /* Grid responsivo */
        .alunos-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 8px;
        }
        @media (max-width: 640px) {
            .alunos-list {
                grid-template-columns: 1fr;
            }
        }
        
        /* Scrollbar discreta */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }
        
        /* Animação para modal de sucesso */
        @keyframes bounce-in {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-sucesso-icon {
            animation: bounce-in 0.6s ease-out;
        }
        
        .modal-sucesso-content {
            transition: transform 0.2s ease-out;
        }
        .turma-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .turma-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-left-color: #2D5A27;
        }
        .header-section {
            background: linear-gradient(135deg, #fff 0%, #f0fdf4 100%);
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_professor.php'; ?>
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left flex items-center gap-3">
                        <div class="hidden lg:block p-2 bg-green-100 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">Frequência</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php echo !empty($_SESSION['escola_atual']) ? htmlspecialchars($_SESSION['escola_atual']) : 'N/A'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-6 sm:p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Registrar Frequência</h1>
                            <p class="text-sm text-gray-600 mt-1">Registre a presença dos alunos nas suas turmas</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Minhas Turmas</h2>
                            <p class="text-sm text-gray-500 mt-1"><?= count($turmasProfessor) ?> turma(s) atribuída(s)</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Debug: verificar se turmasProfessor está vazio
                    error_log("DEBUG frequencia_professor: Total de turmas para exibir = " . count($turmasProfessor));
                    if (empty($turmasProfessor)): ?>
                        <div class="text-center py-16">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 font-medium">Você não possui turmas atribuídas no momento.</p>
                            <?php if (!$professorId): ?>
                                <p class="text-sm text-red-500 mt-2">Erro: Professor não identificado. Verifique se você está logado corretamente.</p>
                            <?php else: ?>
                                <p class="text-sm text-gray-500 mt-2">Entre em contato com o gestor da escola para receber atribuições.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <div class="turma-card bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                                    <div class="mb-4">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-900 text-base mb-1"><?= htmlspecialchars($turma['turma_nome']) ?></h3>
                                                <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                    </svg>
                                                    <span><?= htmlspecialchars($turma['disciplina_nome']) ?></span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                    <span class="truncate"><?= htmlspecialchars($turma['escola_nome']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 pt-3 border-t border-gray-100">
                                        <button onclick="abrirModalHistoricoFrequencia(<?= $turma['turma_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>')" class="flex-1 flex items-center justify-center gap-2 text-blue-600 hover:text-blue-700 font-medium text-sm py-2.5 border border-blue-200 rounded-lg hover:bg-blue-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Histórico
                                        </button>
                                        <button 
                                            class="btn-registrar-frequencia flex-1 flex items-center justify-center gap-2 text-white bg-green-600 hover:bg-green-700 font-medium text-sm py-2.5 rounded-lg transition-all shadow-sm hover:shadow"
                                            data-turma-id="<?= $turma['turma_id'] ?>"
                                            data-disciplina-id="<?= $turma['disciplina_id'] ?>"
                                            data-turma-nome="<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>"
                                            data-disciplina-nome="<?= htmlspecialchars($turma['disciplina_nome'], ENT_QUOTES) ?>"
                                            onclick="window.abrirModalLancarFrequenciaFromButton(this); return false;">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Registrar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal fullscreen moderno -->
    <div id="modal-lancar-frequencia" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col">
            <!-- Header Moderno -->
            <div class="header-section border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <div class="flex items-center gap-4">
                    <button onclick="window.fecharModalLancarFrequencia()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Registrar Frequência
                        </h3>
                        <p id="frequencia-info-turma" class="text-sm text-gray-500 mt-0.5">Selecione uma turma</p>
                    </div>
                </div>
                <button onclick="salvarFrequencia()" class="px-5 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all shadow-sm hover:shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Salvar
                </button>
            </div>
            
            <!-- Barra de controles melhorada -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-b border-green-100 px-6 py-4">
                <div class="flex items-center justify-between gap-6 flex-wrap">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Data:</label>
                            <input type="date" id="frequencia-data" value="<?= date('Y-m-d') ?>" class="text-sm px-4 py-2 border-2 border-green-200 rounded-lg bg-white focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200 font-medium" onchange="recarregarFrequenciaComData()">
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-green-200">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="text-gray-600"><span id="alunos-count" class="font-bold text-gray-900">0</span> alunos</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-green-200">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-gray-600"><span id="presentes-count" class="font-bold text-green-600">0</span> P</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-red-200">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span class="text-gray-600"><span id="ausentes-count" class="font-bold text-red-600">0</span> F</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-amber-200">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span class="text-gray-600"><span id="justificadas-count" class="font-bold text-amber-600">0</span> FJ</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="marcarTodosPresentes()" class="px-3 py-1.5 text-xs font-semibold text-green-700 bg-green-50 hover:bg-green-100 rounded-lg transition-all border border-green-200">
                                Todos P
                            </button>
                            <button onclick="marcarTodosAusentes()" class="px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-all border border-red-200">
                                Todos F
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Segure o botão para marcar falta justificada</span>
                </div>
                <input type="hidden" id="frequencia-turma-id">
                <input type="hidden" id="frequencia-disciplina-id">
            </div>
            
            <!-- Lista de alunos -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                <div id="frequencia-alunos-container" class="alunos-list max-w-4xl mx-auto">
                    <!-- Estado vazio -->
                    <div class="text-center py-20 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-medium">Selecione uma turma para carregar os alunos</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-white border-t border-gray-200 px-6 py-4 rounded-b-2xl">
                <div class="max-w-4xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Clique para alternar presença/ausência. Segure para falta justificada.</span>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="window.fecharModalLancarFrequencia()" class="px-5 py-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all border border-gray-200">
                            Cancelar
                        </button>
                        <button onclick="salvarFrequencia()" class="px-5 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all shadow-sm hover:shadow-md flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Frequência
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Histórico de Frequência -->
    <div id="modal-historico-frequencia" class="fixed inset-0 bg-gray-50 z-[60] hidden flex flex-col">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="fecharModalHistoricoFrequencia()" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Histórico de Frequência</h3>
                    <p id="historico-turma-info" class="text-xs text-gray-500"></p>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="bg-white border-b border-gray-100 px-4 py-3">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Selecione a Data</label>
                    <input type="date" id="historico-data" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-green-500" onchange="carregarFrequenciaPorData()">
                </div>
            </div>
            <input type="hidden" id="historico-turma-id">
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto py-4 px-4">
                <div id="historico-frequencia-container" class="space-y-2">
                    <div class="text-center py-16 text-gray-400">
                        <p class="text-sm">Selecione uma data para ver o histórico de frequência</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Frequência -->
    <div id="modal-editar-frequencia" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Editar Frequência</h3>
                <button onclick="fecharModalEditarFrequencia()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                    <p id="editar-frequencia-aluno" class="text-sm text-gray-600"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="flex gap-3">
                        <button onclick="selecionarStatusFrequencia('presente')" id="btn-status-presente" class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg font-medium transition-colors hover:bg-green-50">
                            Presente
                        </button>
                        <button onclick="selecionarStatusFrequencia('ausente')" id="btn-status-ausente" class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg font-medium transition-colors hover:bg-red-50">
                            Ausente
                        </button>
                        <button onclick="selecionarStatusFrequencia('justificada')" id="btn-status-justificada" class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg font-medium transition-colors hover:bg-amber-50">
                            Justificada
                        </button>
                    </div>
                </div>
                
                <div id="editar-frequencia-justificativa-container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Justificativa</label>
                    <textarea id="editar-frequencia-justificativa" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg" placeholder="Digite a justificativa..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button onclick="fecharModalEditarFrequencia()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEdicaoFrequencia()" class="flex-1 px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors">
                    Salvar
                </button>
            </div>
            
            <input type="hidden" id="editar-frequencia-id">
            <input type="hidden" id="editar-frequencia-status">
        </div>
    </div>
    
    <!-- Modal de Sucesso -->
    <div id="modal-sucesso" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-2xl modal-sucesso-content scale-95">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 modal-sucesso-icon">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sucesso!</h3>
                <p id="modal-sucesso-mensagem" class="text-sm text-gray-600 mb-6"></p>
                <button onclick="fecharModalSucesso()" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
    
    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full shadow-lg">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Confirmar Saída</h3>
                    <p class="text-sm text-gray-500 mt-1">Tem certeza que deseja sair do sistema?</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                    Cancelar
                </button>
                <button onclick="window.logout()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors">
                    Sair
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Garantir que as funções estejam no escopo global
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
        
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        };
        
        window.logout = function() {
            window.location.href = '../auth/logout.php';
        };
        
        let longPressTimer = null;
        const LONG_PRESS_DURATION = 500; // 500ms para ativar falta justificada
        
        // Adicionar event listeners aos botões de registrar frequência
        function attachRegistrarButtons() {
            const buttons = document.querySelectorAll('.btn-registrar-frequencia');
            console.log('Encontrados', buttons.length, 'botões de registrar frequência');
            
            buttons.forEach(function(button) {
                // Remover event listeners anteriores para evitar duplicação
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const turmaId = this.getAttribute('data-turma-id');
                    const disciplinaId = this.getAttribute('data-disciplina-id');
                    const turmaNome = this.getAttribute('data-turma-nome');
                    const disciplinaNome = this.getAttribute('data-disciplina-nome');
                    
                    console.log('Botão clicado!', { turmaId, disciplinaId, turmaNome, disciplinaNome });
                    
                    if (window.abrirModalLancarFrequencia) {
                        window.abrirModalLancarFrequencia(turmaId, disciplinaId, turmaNome, disciplinaNome);
                    } else {
                        console.error('Função abrirModalLancarFrequencia não encontrada!');
                        alert('Erro: Função não encontrada. Recarregue a página.');
                    }
                });
            });
        }
        
        // Tentar anexar imediatamente e também quando o DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', attachRegistrarButtons);
        } else {
            attachRegistrarButtons();
        }
        
        // Fallback: tentar novamente após um pequeno delay
        setTimeout(attachRegistrarButtons, 500);
        
        window.recarregarFrequenciaComData = function() {
            const turmaId = document.getElementById('frequencia-turma-id').value;
            if (turmaId) {
                window.carregarAlunosParaFrequencia(turmaId);
            }
        };
        
        window.buscarInfoTurma = function(turmaId, disciplinaId) {
            fetch('?acao=buscar_info_turma&turma_id=' + turmaId + '&disciplina_id=' + disciplinaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const infoElement = document.getElementById('frequencia-info-turma');
                        if (infoElement && data.turma_nome && data.disciplina_nome) {
                            infoElement.textContent = data.turma_nome + ' - ' + data.disciplina_nome;
                        }
                    }
                })
                .catch(error => console.error('Erro ao buscar info da turma:', error));
        };
        
        // Função fecharModalLancarFrequencia já definida no início, não duplicar
        
        window.carregarAlunosParaFrequencia = function(turmaId) {
            console.log('=== INICIANDO carregarAlunosParaFrequencia ===');
            console.log('Turma ID:', turmaId);
            
            if (!turmaId) {
                console.error('Turma ID não fornecido!');
                return;
            }
            
            var dataElement = document.getElementById('frequencia-data');
            if (!dataElement) {
                console.error('Elemento frequencia-data não encontrado!');
                return;
            }
            
            var data = dataElement.value || new Date().toISOString().split('T')[0];
            console.log('Data:', data);
            
            var container = document.getElementById('frequencia-alunos-container');
            if (!container) {
                console.error('Container não encontrado!');
                return;
            }
            
            container.innerHTML = '<div class="text-center py-8"><p>Carregando alunos...</p></div>';
            
            var urlAlunos = '?acao=buscar_alunos_turma&turma_id=' + encodeURIComponent(turmaId);
            var urlFrequencias = '?acao=buscar_frequencia_data&turma_id=' + encodeURIComponent(turmaId) + '&data=' + encodeURIComponent(data);
            
            console.log('URL Alunos:', urlAlunos);
            console.log('URL Frequências:', urlFrequencias);
            
            var xhrAlunos = new XMLHttpRequest();
            var xhrFrequencias = new XMLHttpRequest();
            var alunosData = null;
            var frequenciasData = null;
            var alunosLoaded = false;
            var frequenciasLoaded = false;
            
            function processarDados() {
                if (!alunosLoaded || !frequenciasLoaded) {
                    return;
                }
                
                console.log('Processando dados...');
                console.log('Alunos:', alunosData);
                console.log('Frequências:', frequenciasData);
                
                if (!alunosData || !alunosData.success) {
                    var msg = alunosData && alunosData.message ? alunosData.message : 'Erro ao carregar alunos';
                    container.innerHTML = '<div class="col-span-full text-center py-12">' +
                        '<div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">' +
                        '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                        '</svg></div>' +
                        '<p class="text-sm font-medium text-red-600">Erro ao carregar alunos</p>' +
                        '<p class="text-xs text-gray-500 mt-1">' + msg + '</p>' +
                        '</div>';
                    return;
                }
                
                if (!alunosData.alunos || alunosData.alunos.length === 0) {
                    container.innerHTML = '<div class="col-span-full text-center py-12">' +
                        '<div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">' +
                        '<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>' +
                        '</svg></div>' +
                        '<p class="text-sm font-medium text-gray-600">Nenhum aluno encontrado</p>' +
                        '<p class="text-xs text-gray-500 mt-1">Esta turma não possui alunos cadastrados</p>' +
                        '</div>';
                    return;
                }
                
                console.log('Total de alunos:', alunosData.alunos.length);
                container.innerHTML = '';
                
                var totalAlunos = alunosData.alunos.length;
                var alunosCountEl = document.getElementById('alunos-count');
                if (alunosCountEl) {
                    alunosCountEl.textContent = totalAlunos + ' aluno' + (totalAlunos !== 1 ? 's' : '');
                }
                
                var frequenciasMap = {};
                if (frequenciasData && frequenciasData.success && frequenciasData.frequencias) {
                    for (var i = 0; i < frequenciasData.frequencias.length; i++) {
                        var freq = frequenciasData.frequencias[i];
                        frequenciasMap[freq.aluno_id] = freq;
                    }
                }
                
                for (var i = 0; i < alunosData.alunos.length; i++) {
                    var aluno = alunosData.alunos[i];
                    
                    var frequencia = frequenciasMap[aluno.id];
                    var status = 'presente';
                    var justificativa = '';
                    var statusClass = 'presente';
                    
                    if (frequencia) {
                        if (frequencia.presenca == 1) {
                            status = 'presente';
                            statusClass = 'presente';
                        } else if (frequencia.observacao) {
                            status = 'justificada';
                            statusClass = 'justificada';
                            justificativa = frequencia.observacao || '';
                        } else {
                            status = 'ausente';
                            statusClass = 'ausente';
                        }
                    }
                    
                    var nomeParts = aluno.nome ? aluno.nome.split(' ') : [];
                    var iniciais = '';
                    if (nomeParts.length > 0) {
                        iniciais = nomeParts[0][0].toUpperCase();
                        if (nomeParts.length > 1 && nomeParts[1][0]) {
                            iniciais += nomeParts[1][0].toUpperCase();
                        }
                    }
                    
                    var alunoNomeEscapado = (aluno.nome || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    var alunoMatriculaEscapada = (aluno.matricula || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    
                    var iconCheckClass = status === 'presente' ? '' : 'hidden';
                    var iconXClass = status === 'ausente' ? '' : 'hidden';
                    var iconJustifiedClass = status === 'justificada' ? '' : 'hidden';
                    
                    var matriculaHtml = alunoMatriculaEscapada ? '<p class="text-xs text-gray-400">' + alunoMatriculaEscapada + '</p>' : '';
                    
                    var wrapper = document.createElement('div');
                    wrapper.className = 'aluno-wrapper';
                    
                    var div = document.createElement('div');
                    div.className = 'aluno-row ' + statusClass;
                    div.setAttribute('data-aluno-id', aluno.id);
                    div.setAttribute('data-status', status);
                    div.setAttribute('data-justificativa', justificativa);
                    
                    div.innerHTML = '<div class="flex items-center gap-4 flex-1 min-w-0">' +
                        '<div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-semibold text-sm text-gray-700">' + iniciais + '</div>' +
                        '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-semibold text-gray-900 truncate">' + alunoNomeEscapado + '</p>' +
                        matriculaHtml +
                        '</div></div>' +
                        '<button type="button" class="presenca-toggle flex-shrink-0 ' + statusClass + '" data-aluno-id="' + aluno.id + '">' +
                        '<svg class="icon-check ' + iconCheckClass + '" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>' +
                        '</svg>' +
                        '<svg class="icon-x ' + iconXClass + '" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>' +
                        '</svg>' +
                        '<svg class="icon-justified ' + iconJustifiedClass + '" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>' +
                        '</svg>' +
                        '</button>';
                    
                    var justificativaField = document.createElement('div');
                    justificativaField.className = status === 'justificada' ? 'justificativa-field show' : 'justificativa-field';
                    
                    var inputJustificativa = document.createElement('input');
                    inputJustificativa.type = 'text';
                    inputJustificativa.placeholder = 'Motivo da falta (opcional)';
                    inputJustificativa.setAttribute('data-aluno-id', aluno.id);
                    inputJustificativa.value = justificativa;
                    inputJustificativa.addEventListener('change', function() {
                        if (window.atualizarJustificativa) {
                            window.atualizarJustificativa(this, aluno.id);
                        }
                    });
                    justificativaField.appendChild(inputJustificativa);
                    
                    wrapper.appendChild(div);
                    wrapper.appendChild(justificativaField);
                    container.appendChild(wrapper);
                    
                    var button = div.querySelector('.presenca-toggle');
                    if (button && window.setupLongPress) {
                        window.setupLongPress(button, aluno.id);
                    }
                }
                
                if (window.atualizarContadores) {
                    window.atualizarContadores();
                }
                
                console.log('Alunos carregados com sucesso!');
            }
            
            xhrAlunos.onreadystatechange = function() {
                if (xhrAlunos.readyState === 4) {
                    alunosLoaded = true;
                    if (xhrAlunos.status === 200) {
                        try {
                            alunosData = JSON.parse(xhrAlunos.responseText);
                            console.log('Alunos recebidos:', alunosData);
                        } catch (e) {
                            console.error('Erro ao parsear JSON de alunos:', e);
                            alunosData = { success: false, message: 'Erro ao processar resposta do servidor' };
                        }
                    } else {
                        console.error('Erro HTTP ao buscar alunos:', xhrAlunos.status);
                        alunosData = { success: false, message: 'Erro HTTP: ' + xhrAlunos.status };
                    }
                    processarDados();
                }
            };
            
            xhrFrequencias.onreadystatechange = function() {
                if (xhrFrequencias.readyState === 4) {
                    frequenciasLoaded = true;
                    if (xhrFrequencias.status === 200) {
                        try {
                            frequenciasData = JSON.parse(xhrFrequencias.responseText);
                            console.log('Frequências recebidas:', frequenciasData);
                        } catch (e) {
                            console.error('Erro ao parsear JSON de frequências:', e);
                            frequenciasData = { success: false, frequencias: [] };
                        }
                    } else {
                        console.error('Erro HTTP ao buscar frequências:', xhrFrequencias.status);
                        frequenciasData = { success: false, frequencias: [] };
                    }
                    processarDados();
                }
            };
            
            xhrAlunos.open('GET', urlAlunos, true);
            xhrAlunos.send();
            
            xhrFrequencias.open('GET', urlFrequencias, true);
            xhrFrequencias.send();
        };
        
        console.log('Função carregarAlunosParaFrequencia definida com sucesso!');
        
        window.setupLongPress = function(button, alunoId) {
            let pressTimer = null;
            let isLongPress = false;
            
            const startPress = (e) => {
                isLongPress = false;
                pressTimer = setTimeout(() => {
                    isLongPress = true;
                    marcarFaltaJustificada(button, alunoId);
                }, LONG_PRESS_DURATION);
            };
            
            const endPress = (e) => {
                clearTimeout(pressTimer);
                if (!isLongPress) {
                    togglePresenca(button, alunoId);
                }
            };
            
            const cancelPress = () => {
                clearTimeout(pressTimer);
            };
            
            // Mouse events
            button.addEventListener('mousedown', startPress);
            button.addEventListener('mouseup', endPress);
            button.addEventListener('mouseleave', cancelPress);
            
            // Touch events
            button.addEventListener('touchstart', (e) => {
                e.preventDefault();
                startPress(e);
            });
            button.addEventListener('touchend', (e) => {
                e.preventDefault();
                endPress(e);
            });
            button.addEventListener('touchcancel', cancelPress);
            
            // Prevenir click padrao
            button.addEventListener('click', (e) => {
                e.preventDefault();
            });
        };
        
        window.marcarFaltaJustificada = function(button, alunoId) {
            const row = button.closest('.aluno-row');
            const wrapper = row.closest('.aluno-wrapper');
            const justificativaField = wrapper.querySelector('.justificativa-field');
            const iconCheck = button.querySelector('.icon-check');
            const iconX = button.querySelector('.icon-x');
            const iconJustified = button.querySelector('.icon-justified');
            
            row.setAttribute('data-status', 'justificada');
            row.className = 'aluno-row justificada';
            button.className = 'presenca-toggle justificada';
            
            iconCheck.classList.add('hidden');
            iconX.classList.add('hidden');
            iconJustified.classList.remove('hidden');
            
            // Mostrar campo de justificativa
            justificativaField.classList.add('show');
            justificativaField.querySelector('input').focus();
            
            window.atualizarContadores();
        };
        
        window.atualizarJustificativa = function(input, alunoId) {
            const wrapper = input.closest('.aluno-wrapper');
            const row = wrapper.querySelector('.aluno-row');
            row.setAttribute('data-justificativa', input.value);
        }
        
        window.togglePresenca = function(button, alunoId) {
            const row = button.closest('.aluno-row');
            const wrapper = row.closest('.aluno-wrapper');
            const justificativaField = wrapper.querySelector('.justificativa-field');
            const status = row.getAttribute('data-status');
            const iconCheck = button.querySelector('.icon-check');
            const iconX = button.querySelector('.icon-x');
            const iconJustified = button.querySelector('.icon-justified');
            
            if (status === 'presente') {
                row.setAttribute('data-status', 'ausente');
                row.className = 'aluno-row ausente';
                button.className = 'presenca-toggle ausente';
                iconCheck.classList.add('hidden');
                iconX.classList.remove('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
            } else {
                row.setAttribute('data-status', 'presente');
                row.setAttribute('data-justificativa', '');
                row.className = 'aluno-row presente';
                button.className = 'presenca-toggle presente';
                iconCheck.classList.remove('hidden');
                iconX.classList.add('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
                justificativaField.querySelector('input').value = '';
            }
            
            window.atualizarContadores();
        };
        
        function marcarTodosPresentes() {
            document.querySelectorAll('.aluno-wrapper').forEach(wrapper => {
                const row = wrapper.querySelector('.aluno-row');
                const button = row.querySelector('.presenca-toggle');
                const justificativaField = wrapper.querySelector('.justificativa-field');
                const iconCheck = button.querySelector('.icon-check');
                const iconX = button.querySelector('.icon-x');
                const iconJustified = button.querySelector('.icon-justified');
                
                row.setAttribute('data-status', 'presente');
                row.setAttribute('data-justificativa', '');
                row.className = 'aluno-row presente';
                button.className = 'presenca-toggle presente';
                iconCheck.classList.remove('hidden');
                iconX.classList.add('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
                justificativaField.querySelector('input').value = '';
            });
            window.atualizarContadores();
        }
        
        function marcarTodosAusentes() {
            document.querySelectorAll('.aluno-wrapper').forEach(wrapper => {
                const row = wrapper.querySelector('.aluno-row');
                const button = row.querySelector('.presenca-toggle');
                const justificativaField = wrapper.querySelector('.justificativa-field');
                const iconCheck = button.querySelector('.icon-check');
                const iconX = button.querySelector('.icon-x');
                const iconJustified = button.querySelector('.icon-justified');
                
                row.setAttribute('data-status', 'ausente');
                row.setAttribute('data-justificativa', '');
                row.className = 'aluno-row ausente';
                button.className = 'presenca-toggle ausente';
                iconCheck.classList.add('hidden');
                iconX.classList.remove('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
                justificativaField.querySelector('input').value = '';
            });
            window.atualizarContadores();
        };
        
        window.atualizarContadores = function() {
            const rows = document.querySelectorAll('.aluno-row');
            let presentes = 0;
            let ausentes = 0;
            let justificadas = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (status === 'presente') {
                    presentes++;
                } else if (status === 'ausente') {
                    ausentes++;
                } else if (status === 'justificada') {
                    justificadas++;
                }
            });
            
            document.getElementById('presentes-count').textContent = presentes + ' P';
            document.getElementById('ausentes-count').textContent = ausentes + ' F';
            document.getElementById('justificadas-count').textContent = justificadas + ' FJ';
        }
        
        function salvarFrequencia() {
            const turmaId = document.getElementById('frequencia-turma-id').value;
            const disciplinaId = document.getElementById('frequencia-disciplina-id').value;
            const data = document.getElementById('frequencia-data').value;
            
            if (!turmaId || !disciplinaId || !data) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const frequencias = [];
            document.querySelectorAll('.aluno-row').forEach(row => {
                const status = row.getAttribute('data-status');
                const justificativa = row.getAttribute('data-justificativa') || '';
                
                frequencias.push({
                    aluno_id: row.getAttribute('data-aluno-id'),
                    presente: status === 'presente' ? 1 : 0,
                    justificada: status === 'justificada' ? 1 : 0,
                    justificativa: status === 'justificada' ? justificativa : ''
                });
            });
            
            if (frequencias.length === 0) {
                alert('Nenhum aluno disponível para registrar frequência.');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'lancar_frequencia');
            formData.append('turma_id', turmaId);
            formData.append('disciplina_id', disciplinaId);
            formData.append('data', data);
            formData.append('frequencias', JSON.stringify(frequencias));
            
            fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalSucesso('Frequência registrada com sucesso!');
                    // Fechar o modal de registrar frequência após um pequeno delay
                    setTimeout(function() {
                        if (window.fecharModalLancarFrequencia) {
                            window.fecharModalLancarFrequencia();
                        }
                    }, 1500);
                } else {
                    alert('Erro ao registrar frequência: ' + (data.message || 'Tente novamente.'));
                }
            })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar frequência. Tente novamente.');
                });
        }
        
        // Funções para modal de histórico
        function abrirModalHistoricoFrequencia(turmaId, turmaNome) {
            const modal = document.getElementById('modal-historico-frequencia');
            if (modal) {
                modal.classList.remove('hidden');
                document.getElementById('historico-turma-id').value = turmaId;
                document.getElementById('historico-turma-info').textContent = turmaNome;
                
                // Definir data padrão como hoje
                const hoje = new Date().toISOString().split('T')[0];
                document.getElementById('historico-data').value = hoje;
                
                // Carregar frequência do dia
                setTimeout(() => {
                    carregarFrequenciaPorData();
                }, 100);
            }
        }
        
        function fecharModalHistoricoFrequencia() {
            const modal = document.getElementById('modal-historico-frequencia');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function carregarFrequenciaPorData() {
            const turmaId = document.getElementById('historico-turma-id').value;
            const data = document.getElementById('historico-data').value;
            
            if (!turmaId || !data) {
                return;
            }
            
            fetch(`?acao=buscar_frequencia_data&turma_id=${turmaId}&data=${data}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.frequencias) {
                        const container = document.getElementById('historico-frequencia-container');
                        container.innerHTML = '';
                        
                        if (data.frequencias.length === 0) {
                            container.innerHTML = '<div class="text-center py-16 text-gray-400"><p class="text-sm">Nenhuma frequência registrada para esta data</p></div>';
                            return;
                        }
                        
                        // Header da tabela
                        const header = document.createElement('div');
                        header.className = 'grid grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wide px-3 py-2 border-b border-gray-200 mb-2';
                        header.innerHTML = `
                            <div class="col-span-5">Aluno</div>
                            <div class="col-span-2 text-center">Status</div>
                            <div class="col-span-4">Observação</div>
                            <div class="col-span-1 text-center">Ação</div>
                        `;
                        container.appendChild(header);
                        
                        // Renderizar frequências
                        data.frequencias.forEach(freq => {
                            const div = document.createElement('div');
                            div.className = 'grid grid-cols-12 gap-3 items-center px-3 py-3 bg-white rounded-lg border border-gray-100 hover:bg-gray-50';
                            
                            const status = freq.presenca == 1 ? 'Presente' : (freq.observacao ? 'Justificada' : 'Ausente');
                            const statusClass = freq.presenca == 1 ? 'text-green-600 bg-green-50' : (freq.observacao ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                            
                            div.innerHTML = `
                                <div class="col-span-5">
                                    <div class="text-sm font-medium text-gray-900">${freq.aluno_nome}</div>
                                    ${freq.aluno_matricula ? `<div class="text-xs text-gray-400">${freq.aluno_matricula}</div>` : ''}
                                </div>
                                <div class="col-span-2 text-center">
                                    <span class="px-2 py-1 text-xs font-medium rounded ${statusClass}">${status}</span>
                                </div>
                                <div class="col-span-4 text-sm text-gray-600 truncate" title="${freq.observacao || ''}">${freq.observacao || '-'}</div>
                                <div class="col-span-1 text-center">
                                    <button onclick="editarFrequencia(${freq.id})" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-700 hover:bg-green-50 rounded transition-colors">
                                        Editar
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar frequência:', error);
                    alert('Erro ao carregar frequência');
                });
        }
        
        // Funções para editar frequência
        function editarFrequencia(frequenciaId) {
            fetch(`?acao=buscar_frequencia&frequencia_id=${frequenciaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.frequencia) {
                        const freq = data.frequencia;
                        document.getElementById('editar-frequencia-id').value = frequenciaId;
                        document.getElementById('editar-frequencia-aluno').textContent = freq.aluno_nome + (freq.aluno_matricula ? ' - ' + freq.aluno_matricula : '');
                        
                        // Determinar status atual
                        let statusAtual = 'ausente';
                        if (freq.presenca == 1) {
                            statusAtual = 'presente';
                        } else if (freq.observacao) {
                            statusAtual = 'justificada';
                        }
                        
                        document.getElementById('editar-frequencia-status').value = statusAtual;
                        selecionarStatusFrequencia(statusAtual);
                        
                        if (freq.observacao) {
                            document.getElementById('editar-frequencia-justificativa').value = freq.observacao;
                        }
                        
                        const modal = document.getElementById('modal-editar-frequencia');
                        if (modal) {
                            modal.classList.remove('hidden');
                            modal.style.display = 'flex';
                        }
                    } else {
                        alert('Erro ao carregar dados da frequência');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados da frequência');
                });
        }
        
        function selecionarStatusFrequencia(status) {
            document.getElementById('editar-frequencia-status').value = status;
            
            // Resetar todos os botões
            document.getElementById('btn-status-presente').classList.remove('border-green-500', 'bg-green-50', 'text-green-700');
            document.getElementById('btn-status-presente').classList.add('border-gray-200');
            document.getElementById('btn-status-ausente').classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
            document.getElementById('btn-status-ausente').classList.add('border-gray-200');
            document.getElementById('btn-status-justificada').classList.remove('border-amber-500', 'bg-amber-50', 'text-amber-700');
            document.getElementById('btn-status-justificada').classList.add('border-gray-200');
            
            // Ativar botão selecionado
            if (status === 'presente') {
                document.getElementById('btn-status-presente').classList.add('border-green-500', 'bg-green-50', 'text-green-700');
                document.getElementById('editar-frequencia-justificativa-container').classList.add('hidden');
            } else if (status === 'ausente') {
                document.getElementById('btn-status-ausente').classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                document.getElementById('editar-frequencia-justificativa-container').classList.add('hidden');
            } else if (status === 'justificada') {
                document.getElementById('btn-status-justificada').classList.add('border-amber-500', 'bg-amber-50', 'text-amber-700');
                document.getElementById('editar-frequencia-justificativa-container').classList.remove('hidden');
            }
        }
        
        function fecharModalEditarFrequencia() {
            const modal = document.getElementById('modal-editar-frequencia');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }
        
        // Funções para modal de sucesso
        function mostrarModalSucesso(mensagem) {
            const modal = document.getElementById('modal-sucesso');
            const mensagemElement = document.getElementById('modal-sucesso-mensagem');
            if (modal && mensagemElement) {
                mensagemElement.textContent = mensagem;
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                
                setTimeout(() => {
                    const modalContent = modal.querySelector('.modal-sucesso-content');
                    if (modalContent) {
                        modalContent.classList.remove('scale-95');
                        modalContent.classList.add('scale-100');
                    }
                }, 10);
            }
        }
        
        function fecharModalSucesso() {
            const modal = document.getElementById('modal-sucesso');
            if (modal) {
                const modalContent = modal.querySelector('.modal-sucesso-content');
                if (modalContent) {
                    modalContent.classList.remove('scale-100');
                    modalContent.classList.add('scale-95');
                }
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }, 200);
            }
        }
        
        function salvarEdicaoFrequencia() {
            const frequenciaId = document.getElementById('editar-frequencia-id').value;
            const status = document.getElementById('editar-frequencia-status').value;
            const justificativa = document.getElementById('editar-frequencia-justificativa').value;
            
            if (!frequenciaId || !status) {
                alert('Por favor, selecione um status');
                return;
            }
            
            const presenca = status === 'presente' ? 1 : 0;
            const observacao = (status === 'justificada' && justificativa) ? justificativa : (status === 'justificada' ? 'Falta justificada' : null);
            
            const formData = new FormData();
            formData.append('acao', 'editar_frequencia');
            formData.append('frequencia_id', frequenciaId);
            formData.append('presenca', presenca);
            formData.append('observacao', observacao || '');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalSucesso('Frequência atualizada com sucesso!');
                    fecharModalEditarFrequencia();
                    setTimeout(() => {
                        carregarFrequenciaPorData();
                    }, 1500);
                } else {
                    alert('Erro ao atualizar frequência: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar frequência');
            });
        }
    </script>
</body>
</html>
