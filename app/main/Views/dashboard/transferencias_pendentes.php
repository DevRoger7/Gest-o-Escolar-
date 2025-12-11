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

// Buscar escola do gestor logado
$escolaGestorId = null;
$escolaGestor = null;

if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    
    if ($usuarioId) {
        try {
            // Buscar gestor através do usuario_id
            // Query simplificada - buscar qualquer lotação recente, priorizando as sem data de fim
            $sqlGestor = "SELECT g.id as gestor_id, gl.escola_id, e.nome as escola_nome, gl.responsavel, gl.fim, gl.inicio
                          FROM gestor g
                          INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                          INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                          INNER JOIN escola e ON gl.escola_id = e.id
                          WHERE u.id = :usuario_id AND g.ativo = 1
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
                // Verificar se a lotação está realmente ativa
                $fimLotacao = $gestorEscola['fim'];
                $lotacaoAtiva = ($fimLotacao === null || $fimLotacao === '' || $fimLotacao === '0000-00-00' || (strtotime($fimLotacao) !== false && strtotime($fimLotacao) >= strtotime('today')));
                
                if ($lotacaoAtiva) {
                    $escolaGestorId = (int)$gestorEscola['escola_id'];
                    $escolaGestor = $gestorEscola['escola_nome'];
                }
            }
            
            // Se ainda não encontrou, tentar query alternativa
            if (!$escolaGestorId) {
                // Tentar buscar sem a condição de fim (caso o campo esteja com valor diferente)
                $sqlGestor2 = "SELECT g.id as gestor_id, gl.escola_id, e.nome as escola_nome, gl.responsavel, gl.fim, gl.inicio
                               FROM gestor g
                               INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                               INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                               INNER JOIN escola e ON gl.escola_id = e.id
                               WHERE u.id = :usuario_id AND g.ativo = 1
                               ORDER BY gl.responsavel DESC, gl.inicio DESC, gl.id DESC
                               LIMIT 1";
                $stmtGestor2 = $conn->prepare($sqlGestor2);
                $stmtGestor2->bindParam(':usuario_id', $usuarioId);
                $stmtGestor2->execute();
                $gestorEscola2 = $stmtGestor2->fetch(PDO::FETCH_ASSOC);
                
                if ($gestorEscola2) {
                    $fimLotacao2 = $gestorEscola2['fim'];
                    $lotacaoAtiva2 = ($fimLotacao2 === null || $fimLotacao2 === '' || $fimLotacao2 === '0000-00-00' || (strtotime($fimLotacao2) !== false && strtotime($fimLotacao2) >= strtotime('today')));
                    
                    if ($lotacaoAtiva2) {
                        $escolaGestorId = (int)$gestorEscola2['escola_id'];
                        $escolaGestor = $gestorEscola2['escola_nome'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("ERRO ao buscar escola do gestor: " . $e->getMessage());
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

// Buscar alunos transferidos pendentes
$alunosPendentes = [];
if ($escolaGestorId) {
    $alunosPendentes = $alunoModel->buscarTransferidosPendentes($escolaGestorId);
}
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
    } else {
        // Sidebar padrão para GESTAO - reutilizar do gestao_escolar.php
        // Buscar escola do gestor novamente para o sidebar
        $escolaGestorSidebar = null;
        $escolaGestorIdSidebar = null;
        if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            if ($usuarioId) {
                $sqlGestor = "SELECT g.id as gestor_id, gl.escola_id, e.nome as escola_nome
                              FROM gestor g
                              INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id
                              INNER JOIN gestor_lotacao gl ON g.id = gl.gestor_id
                              INNER JOIN escola e ON gl.escola_id = e.id
                              WHERE u.id = :usuario_id AND g.ativo = 1
                              AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')
                              ORDER BY gl.responsavel DESC, gl.inicio DESC
                              LIMIT 1";
                $stmtGestor = $conn->prepare($sqlGestor);
                $stmtGestor->bindParam(':usuario_id', $usuarioId);
                $stmtGestor->execute();
                $gestorEscola = $stmtGestor->fetch(PDO::FETCH_ASSOC);
                if ($gestorEscola) {
                    $escolaGestorIdSidebar = (int)$gestorEscola['escola_id'];
                    $escolaGestorSidebar = $gestorEscola['escola_nome'];
                }
            }
        }
    ?>
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>

    <!-- Sidebar padrão para GESTAO -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile flex flex-col">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">SIGEA</h1>
                    <p class="text-xs text-gray-500">Gestão Escolar</p>
                </div>
            </div>
        </div>
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center flex-shrink-0" style="aspect-ratio: 1; min-width: 2.5rem; min-height: 2.5rem; overflow: hidden;">
                    <span class="text-sm font-bold text-white">
                        <?php
                        $nome = $_SESSION['nome'] ?? '';
                        $iniciais = '';
                        if (strlen($nome) >= 2) {
                            $iniciais = strtoupper(substr($nome, 0, 2));
                        } elseif (strlen($nome) == 1) {
                            $iniciais = strtoupper($nome);
                        } else {
                            $iniciais = 'US';
                        }
                        echo $iniciais;
                        ?>
                    </span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                    <p class="text-xs text-gray-500"><?= $_SESSION['tipo'] ?? 'Gestão' ?></p>
                </div>
            </div>
        </div>
        <nav class="p-4 overflow-y-auto flex-1" style="max-height: calc(100vh - 200px);">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="gestao_escolar.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span>Gestão Escolar</span>
                    </a>
                </li>
                <li>
                    <a href="transferencias_pendentes.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 active">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span>Transferências</span>
                    </a>
                </li>
                <li>
                    <a href="relatorios_professor.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Relatórios</span>
                    </a>
                </li>
                <?php if ($_SESSION['tipo'] === 'GESTAO' && $escolaGestorIdSidebar): ?>
                    <li>
                        <a href="gestao_escolar.php?aba=cardapio" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span>Cardápio</span>
                        </a>
                    </li>
                    <li>
                        <a href="gestao_escolar.php?acao=abrir_desperdicio" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Registrar Desperdício</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Logout Button -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
            <button onclick="window.confirmLogout()" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Sair</span>
            </button>
        </div>
    </aside>
    <?php } ?>
    
    <!-- Mobile Menu Button -->
    <button onclick="window.toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-50 bg-white p-2 rounded-lg shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Main Content -->
    <main class="lg:ml-64 content-transition min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Transferências Pendentes</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            <?php if ($escolaGestor): ?>
                                Alunos transferidos para <?= htmlspecialchars($escolaGestor) ?>
                            <?php else: ?>
                                Gerencie as transferências de alunos
                            <?php endif; ?>
                        </p>
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
    </script>
</body>
</html>

