<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/AlunoModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO
if ($_SESSION['tipo'] !== 'GESTAO' && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$alunoModel = new AlunoModel();

// Buscar escolas do gestor e escola selecionada
$escolasGestor = [];
$escolaGestorId = null;
$escolaGestor = null;

if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
    $usuarioId = $_SESSION['usuario_id'] ?? null;

    if ($usuarioId) {
        try {
            // Primeiro, buscar o ID do gestor usando pessoa_id
            $pessoaId = $_SESSION['pessoa_id'] ?? null;
            
            if ($pessoaId) {
                $sqlBuscarGestor = "SELECT g.id as gestor_id
                                    FROM gestor g
                                    WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1
                                    LIMIT 1";
                $stmtBuscarGestor = $conn->prepare($sqlBuscarGestor);
                $stmtBuscarGestor->bindParam(':pessoa_id', $pessoaId);
                $stmtBuscarGestor->execute();
                $gestorData = $stmtBuscarGestor->fetch(PDO::FETCH_ASSOC);
            } else {
                $gestorData = null;
            }
            
            if ($gestorData && isset($gestorData['gestor_id'])) {
                $gestorId = (int)$gestorData['gestor_id'];
                
                // Buscar todas as escolas ativas do gestor (sem duplicatas)
                $sqlEscolas = "SELECT DISTINCT 
                                 gl.escola_id, 
                                 e.nome as escola_nome, 
                                 MAX(gl.responsavel) as responsavel,
                                 MAX(gl.inicio) as inicio
                               FROM gestor_lotacao gl
                               INNER JOIN escola e ON gl.escola_id = e.id
                               WHERE gl.gestor_id = :gestor_id
                               AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                               AND e.ativo = 1
                               GROUP BY gl.escola_id, e.nome
                               ORDER BY 
                                 MAX(gl.responsavel) DESC,
                                 MAX(gl.inicio) DESC,
                                 e.nome ASC";
                $stmtEscolas = $conn->prepare($sqlEscolas);
                $stmtEscolas->bindParam(':gestor_id', $gestorId);
                $stmtEscolas->execute();
                $escolasGestor = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
                
                // Verificar se há escola selecionada na sessão
                if (isset($_SESSION['escola_selecionada_id']) && !empty($_SESSION['escola_selecionada_id'])) {
                    $escolaSelecionadaId = (int)$_SESSION['escola_selecionada_id'];
                    
                    // Verificar se a escola selecionada está na lista de escolas do gestor
                    foreach ($escolasGestor as $escola) {
                        if ((int)$escola['escola_id'] === $escolaSelecionadaId) {
                            $escolaGestorId = $escolaSelecionadaId;
                            $escolaGestor = $_SESSION['escola_selecionada_nome'] ?? $escola['escola_nome'];
                            break;
                        }
                    }
                }
                
                // Se não há escola selecionada ou a selecionada não é válida, usar a primeira (priorizando responsável)
                if (!$escolaGestorId && !empty($escolasGestor)) {
                    $escolaGestorId = (int)$escolasGestor[0]['escola_id'];
                    $escolaGestor = $escolasGestor[0]['escola_nome'];
                    
                    // Salvar na sessão
                    $_SESSION['escola_selecionada_id'] = $escolaGestorId;
                    $_SESSION['escola_selecionada_nome'] = $escolaGestor;
                }
            }
        } catch (Exception $e) {
            error_log("ERRO ao buscar escolas do gestor: " . $e->getMessage());
            $escolaGestorId = null;
            $escolaGestor = null;
        }
    }
}

if (!$escolaGestorId && $_SESSION['tipo'] === 'GESTAO') {
    header('Location: dashboard.php?erro=escola_nao_encontrada');
    exit;
}

$mensagem = '';
$tipoMensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'transferir') {
        // Processar transferência de aluno
        $alunoId = $_POST['aluno_id'] ?? null;
        $escolaOrigemId = $_POST['escola_origem_id'] ?? null;
        $escolaDestinoId = $_POST['escola_destino_id'] ?? null;
        $dataTransferencia = $_POST['data_transferencia'] ?? date('Y-m-d');
        
        if ($alunoId && $escolaOrigemId && $escolaDestinoId) {
            // Verificar se a escola de origem é a escola do gestor
            if ($escolaOrigemId != $escolaGestorId) {
                $mensagem = 'Você só pode transferir alunos da sua escola!';
                $tipoMensagem = 'error';
            } elseif ($escolaOrigemId == $escolaDestinoId) {
                $mensagem = 'A escola de origem e destino não podem ser a mesma!';
                $tipoMensagem = 'error';
            } else {
                $resultado = $alunoModel->transferirEscola($alunoId, $escolaOrigemId, $escolaDestinoId, $dataTransferencia);
                
                if ($resultado['success']) {
                    $mensagem = $resultado['message'];
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = $resultado['message'];
                    $tipoMensagem = 'error';
                }
            }
        } else {
            $mensagem = 'Dados incompletos para realizar a transferência!';
            $tipoMensagem = 'error';
        }
    } else {
        // Processar aceitar/recusar transferência
        $alunoId = $_POST['aluno_id'] ?? null;
        
        if (!$alunoId) {
            $mensagem = 'ID do aluno não informado!';
            $tipoMensagem = 'error';
        } else {
            if ($_POST['acao'] === 'aceitar') {
                $resultado = $alunoModel->aceitarTransferencia($alunoId);
                
                if ($resultado['success']) {
                    $mensagem = $resultado['message'];
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = $resultado['message'];
                    $tipoMensagem = 'error';
                }
            } elseif ($_POST['acao'] === 'recusar') {
                $resultado = $alunoModel->recusarTransferencia($alunoId);
                
                if ($resultado['success']) {
                    $mensagem = $resultado['message'];
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = $resultado['message'];
                    $tipoMensagem = 'error';
                }
            }
        }
    }
}

// Buscar todas as escolas para o dropdown de destino
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar alunos transferidos pendentes
$alunosPendentes = [];
if ($escolaGestorId) {
    $alunosPendentes = $alunoModel->buscarTransferidosPendentes($escolaGestorId);
}

// Debug: verificar se escolaGestorId está definido
error_log("DEBUG TRANSFERENCIAS - escolaGestorId: " . ($escolaGestorId ?? 'NULL'));
error_log("DEBUG TRANSFERENCIAS - escolaGestor: " . ($escolaGestor ?? 'NULL'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferências Pendentes - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global-theme.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
    // Incluir sidebar do gestor
    if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'ADM') {
        include('components/sidebar_adm.php');
    } elseif (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
        include('components/sidebar_gestao.php');
    }
    ?>
    
    <!-- Mobile Menu Button -->
    <button onclick="window.toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-50 bg-white p-2 rounded-lg shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Main Content -->
    <main class="lg:ml-64 content-transition min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex-1">
                        <h1 class="text-xl font-semibold text-gray-800">Transferências</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'GESTAO'): ?>
                                <!-- Para GESTAO, mostrar apenas o nome da escola (mudança apenas no dashboard) -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?= !empty($escolaGestor) ? htmlspecialchars($escolaGestor) : 'Escola não encontrada' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- User Profile Button -->
                        <button onclick="window.openUserProfile()" class="p-2 text-gray-600 bg-gray-100 rounded-full hover:bg-gray-200 transition-colors cursor-pointer" title="Perfil do Usuário">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($mensagem): ?>
            <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <div class="flex items-center">
                    <?php if ($tipoMensagem === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($mensagem) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-8 overflow-x-auto">
                    <button onclick="mostrarAba('pendentes')" id="tab-pendentes" class="tab-button py-4 px-1 border-b-2 border-primary-green font-medium text-sm text-primary-green whitespace-nowrap">
                        Transferências Pendentes
                    </button>
                    <button onclick="mostrarAba('transferir')" id="tab-transferir" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        Transferir Aluno
                    </button>
                </nav>
            </div>

            <!-- Aba: Transferências Pendentes -->
            <div id="aba-pendentes" class="tab-content">
            <?php if (empty($alunosPendentes)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma transferência pendente</h3>
                    <p class="text-gray-500">Não há alunos aguardando aprovação de transferência no momento.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Alunos Aguardando Aprovação (<?= count($alunosPendentes) ?>)
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola de Origem</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Transferência</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($alunosPendentes as $aluno): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome']) ?></div>
                                        <?php if (!empty($aluno['email'])): ?>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($aluno['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($aluno['matricula'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($aluno['cpf'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($aluno['escola_origem_nome'] ?? 'Não identificada') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= $aluno['data_matricula'] ? date('d/m/Y', strtotime($aluno['data_matricula'])) : '-' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja ACEITAR a transferência deste aluno?')">
                                                <input type="hidden" name="acao" value="aceitar">
                                                <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>">
                                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                                    Aceitar
                                                </button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja RECUSAR a transferência? O aluno será devolvido para a escola de origem.')">
                                                <input type="hidden" name="acao" value="recusar">
                                                <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>">
                                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                                                    Recusar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Informações -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Sobre as Transferências</h3>
                    <ul class="list-disc list-inside space-y-2 text-blue-800 text-sm">
                        <li><strong>Aceitar:</strong> O aluno será matriculado na sua escola e o status mudará para "MATRICULADO"</li>
                        <li><strong>Recusar:</strong> O aluno será devolvido para a escola de origem e o status voltará para "MATRICULADO" na escola anterior</li>
                        <li>As transferências pendentes aparecem apenas para alunos com status "TRANSFERIDO" que foram transferidos para sua escola</li>
                    </ul>
                </div>
            <?php endif; ?>
            </div>

            <!-- Aba: Transferir Aluno -->
            <div id="aba-transferir" class="tab-content hidden">
                <!-- Formulário de Transferência -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                    <form method="POST" id="form-transferencia" class="space-y-6">
                        <input type="hidden" name="acao" value="transferir">
                        <input type="hidden" name="escola_origem_id" id="escola_origem_id" value="<?= $escolaGestorId ?>">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Escola de Origem (fixa - escola do gestor) -->
                            <div>
                                <label for="escola_origem_display" class="block text-sm font-medium text-gray-700 mb-2">
                                    Escola de Origem <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="escola_origem_display" 
                                       value="<?= htmlspecialchars($escolaGestor) ?>"
                                       disabled
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                            </div>

                            <!-- Escola de Destino -->
                            <div>
                                <label for="escola_destino_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Escola de Destino <span class="text-red-500">*</span>
                                </label>
                                <select name="escola_destino_id" id="escola_destino_id" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                                    <option value="">Selecione a escola de destino...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <?php if ($escola['id'] != $escolaGestorId): ?>
                                            <option value="<?= $escola['id'] ?>">
                                                <?= htmlspecialchars($escola['nome']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Aluno -->
                        <div class="relative">
                            <label for="busca_aluno" class="block text-sm font-medium text-gray-700 mb-2">
                                Buscar Aluno <span class="text-red-500">*</span>
                                <?php if ($escolaGestorId): ?>
                                    <span class="text-xs text-gray-500 font-normal">(Escola ID: <?= $escolaGestorId ?>)</span>
                                <?php endif; ?>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       id="busca_aluno" 
                                       name="busca_aluno"
                                       placeholder="Digite o nome, CPF ou matrícula do aluno..."
                                       class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors"
                                       autocomplete="off"
                                       <?= !$escolaGestorId ? 'disabled' : '' ?>>
                                <input type="hidden" name="aluno_id" id="aluno_id" required>
                                <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <!-- Lista de sugestões -->
                            <div id="sugestoes-alunos" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <!-- Sugestões serão inseridas aqui via JavaScript -->
                            </div>
                            <p id="mensagem-busca" class="mt-1 text-sm text-gray-500 <?= !$escolaGestorId ? '' : 'hidden' ?>">
                                <?= !$escolaGestorId ? 'Escola não encontrada. Não é possível buscar alunos.' : '' ?>
                            </p>
                        </div>

                        <!-- Informações do Aluno -->
                        <div id="info-aluno" class="bg-gray-50 rounded-lg p-4 hidden">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Informações do Aluno</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Nome</p>
                                    <p class="text-sm font-medium text-gray-900" id="info-nome">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">CPF</p>
                                    <p class="text-sm font-medium text-gray-900" id="info-cpf">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Matrícula</p>
                                    <p class="text-sm font-medium text-gray-900" id="info-matricula">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Data de Transferência -->
                        <div>
                            <label for="data_transferencia" class="block text-sm font-medium text-gray-700 mb-2">
                                Data de Transferência <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="data_transferencia" id="data_transferencia" 
                                   value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors">
                        </div>

                        <!-- Botões -->
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                            <button type="button" onclick="limparFormulario()" 
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Limpar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                                Transferir Aluno
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Instruções -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Como transferir um aluno:</h3>
                    <ol class="list-decimal list-inside space-y-2 text-blue-800">
                        <li>Digite o nome, CPF ou matrícula do aluno no campo de busca</li>
                        <li>Selecione o aluno da lista de sugestões que aparecerá</li>
                        <li>Escolha a escola de destino</li>
                        <li>Confirme a data de transferência</li>
                        <li>Clique em "Transferir Aluno" para concluir</li>
                    </ol>
                    <p class="mt-4 text-sm text-blue-700">
                        <strong>Observação:</strong> Você só pode transferir alunos que estão matriculados na sua escola. Ao transferir, o aluno será automaticamente desvinculado da turma atual e sua situação será alterada para "TRANSFERIDO".
                    </p>
                </div>
            </div>
        </div>
    </main>

    <style>
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
            color: #2D5A27;
        }
        .menu-item.active svg {
            color: #2D5A27;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .bg-primary-green {
            background-color: #2D5A27;
        }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>

    <script>
        // Função para toggle do sidebar (mobile)
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

        // Funções de logout
        window.confirmLogout = function() {
            if (confirm('Tem certeza que deseja sair do sistema?')) {
                window.location.href = '../auth/logout.php';
            }
        };

        // Sistema de abas
        function mostrarAba(aba) {
            // Esconder todas as abas
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover estilo ativo de todas as tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-primary-green', 'text-primary-green');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar aba selecionada
            document.getElementById('aba-' + aba).classList.remove('hidden');
            
            // Ativar tab selecionada
            const tabButton = document.getElementById('tab-' + aba);
            tabButton.classList.remove('border-transparent', 'text-gray-500');
            tabButton.classList.add('border-primary-green', 'text-primary-green');
            
            // Se for a aba de transferir, carregar alunos
            if (aba === 'transferir') {
                setTimeout(carregarAlunos, 100);
            }
        }

        // Funcionalidade de busca de alunos (similar a transferencia_alunos.php)
        let alunosDisponiveis = [];
        let timeoutBusca = null;
        let indiceSugestaoAtiva = -1;
        const escolaOrigemId = <?= $escolaGestorId ? json_encode($escolaGestorId) : 'null' ?>;
        
        console.log('escolaOrigemId definido:', escolaOrigemId);

        function carregarAlunos() {
            const inputBusca = document.getElementById('busca_aluno');
            const mensagemBusca = document.getElementById('mensagem-busca');
            
            if (!inputBusca || !mensagemBusca) {
                console.error('Elementos não encontrados');
                return;
            }
            
            if (!escolaOrigemId || escolaOrigemId === null || escolaOrigemId === '') {
                console.error('escolaOrigemId não definido:', escolaOrigemId);
                inputBusca.disabled = true;
                inputBusca.placeholder = 'Escola não encontrada...';
                mensagemBusca.textContent = 'Erro: Escola não encontrada.';
                mensagemBusca.classList.remove('hidden');
                return;
            }

            inputBusca.disabled = false;
            inputBusca.placeholder = 'Digite o nome, CPF ou matrícula do aluno...';
            mensagemBusca.textContent = 'Carregando alunos...';
            mensagemBusca.classList.remove('hidden');
            
            // Limpar alunos anteriores
            alunosDisponiveis = [];
            
            // Carregar todos os alunos da escola para busca local
            const url = `../../Controllers/academico/AlunoController.php?action=buscar_por_escola&escola_id=${escolaOrigemId}&situacao=MATRICULADO`;
            console.log('Carregando alunos da URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (data.success && data.data && data.data.length > 0) {
                        alunosDisponiveis = data.data;
                        mensagemBusca.textContent = `${alunosDisponiveis.length} aluno(s) encontrado(s) nesta escola. Digite para buscar...`;
                        mensagemBusca.classList.remove('hidden');
                        inputBusca.disabled = false;
                    } else {
                        console.warn('Nenhum aluno encontrado ou resposta vazia:', data);
                        mensagemBusca.textContent = 'Nenhum aluno encontrado nesta escola.';
                        mensagemBusca.classList.remove('hidden');
                        inputBusca.disabled = false; // Manter habilitado para permitir tentar novamente
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar alunos:', error);
                    mensagemBusca.textContent = 'Erro ao carregar alunos. Verifique o console para mais detalhes.';
                    mensagemBusca.classList.remove('hidden');
                    inputBusca.disabled = false;
                });
        }

        function buscarAlunos(termo) {
            const sugestoesDiv = document.getElementById('sugestoes-alunos');
            const inputAlunoId = document.getElementById('aluno_id');
            const infoAluno = document.getElementById('info-aluno');
            
            if (!termo || termo.length < 2) {
                sugestoesDiv.classList.add('hidden');
                inputAlunoId.value = '';
                infoAluno.classList.add('hidden');
                return;
            }

            termo = termo.toLowerCase();
            const resultados = alunosDisponiveis.filter(aluno => {
                const nome = (aluno.nome || '').toLowerCase();
                const cpf = (aluno.cpf || '').replace(/\D/g, '');
                const matricula = (aluno.matricula || '').toLowerCase();
                const termoLimpo = termo.replace(/\D/g, '');
                
                return nome.includes(termo) || 
                       cpf.includes(termoLimpo) || 
                       matricula.includes(termo);
            });

            if (resultados.length === 0) {
                sugestoesDiv.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum aluno encontrado</div>';
                sugestoesDiv.classList.remove('hidden');
                inputAlunoId.value = '';
                infoAluno.classList.add('hidden');
                return;
            }

            const resultadosLimitados = resultados.slice(0, 10);
            
            sugestoesDiv.innerHTML = '';
            resultadosLimitados.forEach((aluno, index) => {
                const item = document.createElement('div');
                item.className = 'p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 aluno-item';
                item.dataset.alunoId = aluno.id;
                item.dataset.index = index;
                
                const nome = aluno.nome || 'Sem nome';
                const matricula = aluno.matricula ? ` - Matrícula: ${aluno.matricula}` : '';
                const cpf = aluno.cpf ? ` - CPF: ${aluno.cpf}` : '';
                
                item.innerHTML = `
                    <div class="font-medium text-gray-900">${nome}</div>
                    <div class="text-sm text-gray-500">${matricula}${cpf}</div>
                `;
                
                item.addEventListener('click', () => selecionarAluno(aluno));
                item.addEventListener('mouseenter', () => {
                    indiceSugestaoAtiva = index;
                    atualizarDestaqueSugestoes();
                });
                
                sugestoesDiv.appendChild(item);
            });
            
            sugestoesDiv.classList.remove('hidden');
            indiceSugestaoAtiva = -1;
        }

        function selecionarAluno(aluno) {
            const inputBusca = document.getElementById('busca_aluno');
            const inputAlunoId = document.getElementById('aluno_id');
            const sugestoesDiv = document.getElementById('sugestoes-alunos');
            const infoAluno = document.getElementById('info-aluno');
            
            inputBusca.value = aluno.nome + (aluno.matricula ? ` - Matrícula: ${aluno.matricula}` : '');
            inputAlunoId.value = aluno.id;
            
            document.getElementById('info-nome').textContent = aluno.nome || '-';
            document.getElementById('info-cpf').textContent = aluno.cpf || '-';
            document.getElementById('info-matricula').textContent = aluno.matricula || '-';
            
            sugestoesDiv.classList.add('hidden');
            infoAluno.classList.remove('hidden');
        }

        function atualizarDestaqueSugestoes() {
            const itens = document.querySelectorAll('.aluno-item');
            itens.forEach((item, index) => {
                if (index === indiceSugestaoAtiva) {
                    item.classList.add('bg-gray-100');
                } else {
                    item.classList.remove('bg-gray-100');
                }
            });
        }

        function limparFormulario() {
            document.getElementById('busca_aluno').value = '';
            document.getElementById('aluno_id').value = '';
            document.getElementById('escola_destino_id').value = '';
            document.getElementById('data_transferencia').value = '<?= date('Y-m-d') ?>';
            document.getElementById('info-aluno').classList.add('hidden');
            document.getElementById('sugestoes-alunos').classList.add('hidden');
        }

        // Event listeners para busca de alunos
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar alunos automaticamente se a aba de transferir já estiver visível
            const abaTransferir = document.getElementById('aba-transferir');
            if (abaTransferir && !abaTransferir.classList.contains('hidden')) {
                setTimeout(carregarAlunos, 200);
            }

            const inputBusca = document.getElementById('busca_aluno');
            if (inputBusca) {
                // Busca ao digitar
                inputBusca.addEventListener('input', function(e) {
                    clearTimeout(timeoutBusca);
                    const termo = e.target.value;
                    
                    timeoutBusca = setTimeout(() => {
                        buscarAlunos(termo);
                    }, 300);
                });

                // Navegação com teclado
                inputBusca.addEventListener('keydown', function(e) {
                    const sugestoesDiv = document.getElementById('sugestoes-alunos');
                    const itens = document.querySelectorAll('.aluno-item');
                    
                    if (!sugestoesDiv.classList.contains('hidden') && itens.length > 0) {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            indiceSugestaoAtiva = Math.min(indiceSugestaoAtiva + 1, itens.length - 1);
                            atualizarDestaqueSugestoes();
                            itens[indiceSugestaoAtiva].scrollIntoView({ block: 'nearest' });
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            indiceSugestaoAtiva = Math.max(indiceSugestaoAtiva - 1, -1);
                            atualizarDestaqueSugestoes();
                        } else if (e.key === 'Enter' && indiceSugestaoAtiva >= 0) {
                            e.preventDefault();
                            const alunoId = itens[indiceSugestaoAtiva].dataset.alunoId;
                            const aluno = alunosDisponiveis.find(a => a.id == alunoId);
                            if (aluno) {
                                selecionarAluno(aluno);
                            }
                        } else if (e.key === 'Escape') {
                            sugestoesDiv.classList.add('hidden');
                            indiceSugestaoAtiva = -1;
                        }
                    }
                });
            }

            // Fechar sugestões ao clicar fora
            document.addEventListener('click', function(e) {
                const sugestoesDiv = document.getElementById('sugestoes-alunos');
                const inputBusca = document.getElementById('busca_aluno');
                
                if (sugestoesDiv && inputBusca && !sugestoesDiv.contains(e.target) && e.target !== inputBusca) {
                    sugestoesDiv.classList.add('hidden');
                }
            });

            // Validação do formulário de transferência
            const formTransferencia = document.getElementById('form-transferencia');
            if (formTransferencia) {
                formTransferencia.addEventListener('submit', function(e) {
                    const escolaOrigem = document.getElementById('escola_origem_id').value;
                    const escolaDestino = document.getElementById('escola_destino_id').value;
                    const aluno = document.getElementById('aluno_id').value;

                    if (escolaOrigem === escolaDestino) {
                        e.preventDefault();
                        alert('A escola de origem e destino não podem ser a mesma!');
                        return false;
                    }

                    if (!aluno) {
                        e.preventDefault();
                        alert('Por favor, selecione um aluno para transferir!');
                        return false;
                    }

                    if (!confirm('Tem certeza que deseja transferir este aluno? Esta ação não pode ser desfeita facilmente.')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    </script>
    <style>
        .aluno-item:first-child { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
        .aluno-item:last-child { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; border-bottom: none; }
    </style>
</body>
</html>

