<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// ==================== FUNÇÕES DE VALIDAÇÃO ====================

// Criar tabela de validação se não existir
function criarTabelaValidacao() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `validacao` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `tipo` enum('NOTA','FREQUENCIA','MATRICULA','AVALIACAO','CARDAPIO','ESTOQUE') NOT NULL,
        `registro_id` bigint(20) NOT NULL,
        `status` enum('PENDENTE','APROVADO','REJEITADO') DEFAULT 'PENDENTE',
        `validado_por` bigint(20) DEFAULT NULL,
        `validado_em` timestamp NULL DEFAULT NULL,
        `observacao` text DEFAULT NULL,
        `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `tipo_registro` (`tipo`, `registro_id`),
        KEY `status` (`status`),
        KEY `validado_por` (`validado_por`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    try {
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de validação: " . $e->getMessage());
        return false;
    }
}

// Listar informações pendentes de validação
function listarInformacoesPendentes($tipo = null, $limit = 50) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $informacoes = [];
    
    try {
        // Notas pendentes
        if (!$tipo || $tipo === 'NOTA') {
            $sql = "SELECT DISTINCT n.id, n.nota, n.lancado_em, n.comentario,
                           a.titulo as avaliacao_titulo, a.data as avaliacao_data,
                           CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                           (SELECT d.nome FROM turma_professor tp2 
                            INNER JOIN disciplina d ON tp2.disciplina_id = d.id 
                            WHERE tp2.turma_id = t.id LIMIT 1) as disciplina_nome,
                           p.nome as aluno_nome,
                           u.id as lancado_por_id,
                           p2.nome as lancado_por_nome,
                           (SELECT status FROM validacao WHERE tipo = 'NOTA' AND registro_id = n.id ORDER BY criado_em DESC LIMIT 1) as status_validacao
                    FROM nota n
                    INNER JOIN avaliacao a ON n.avaliacao_id = a.id
                    INNER JOIN aluno al ON n.aluno_id = al.id
                    INNER JOIN pessoa p ON al.pessoa_id = p.id
                    INNER JOIN turma t ON a.turma_id = t.id
                    LEFT JOIN usuario u ON n.lancado_por = u.id
                    LEFT JOIN pessoa p2 ON u.pessoa_id = p2.id
                    WHERE NOT EXISTS (SELECT 1 FROM validacao WHERE tipo = 'NOTA' AND registro_id = n.id AND status IN ('APROVADO', 'REJEITADO'))
                       OR EXISTS (SELECT 1 FROM validacao WHERE tipo = 'NOTA' AND registro_id = n.id AND status = 'PENDENTE')
                    ORDER BY n.lancado_em DESC
                    LIMIT " . intval($limit);
            
            $stmt = $conn->query($sql);
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($notas as $nota) {
                $informacoes[] = [
                    'tipo' => 'NOTA',
                    'id' => $nota['id'],
                    'titulo' => 'Nota Lançada',
                    'descricao' => $nota['aluno_nome'] . ' - ' . ($nota['disciplina_nome'] ?? 'Disciplina') . ' - Nota: ' . number_format($nota['nota'], 1),
                    'detalhes' => [
                        'Avaliação' => $nota['avaliacao_titulo'],
                        'Turma' => $nota['turma_nome'],
                        'Disciplina' => $nota['disciplina_nome'],
                        'Nota' => number_format($nota['nota'], 1),
                        'Lançado por' => $nota['lancado_por_nome'] ?? 'Sistema',
                        'Data' => date('d/m/Y H:i', strtotime($nota['lancado_em']))
                    ],
                    'data' => $nota['lancado_em'],
                    'status' => $nota['status_validacao'] ?? 'PENDENTE'
                ];
            }
        }
        
        // Frequências pendentes
        if (!$tipo || $tipo === 'FREQUENCIA') {
            $sql = "SELECT f.id, f.data, f.presenca, f.registrado_em,
                           CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                           p.nome as aluno_nome,
                           u.id as registrado_por_id,
                           p2.nome as registrado_por_nome,
                           (SELECT status FROM validacao WHERE tipo = 'FREQUENCIA' AND registro_id = f.id ORDER BY criado_em DESC LIMIT 1) as status_validacao
                    FROM frequencia f
                    INNER JOIN aluno al ON f.aluno_id = al.id
                    INNER JOIN pessoa p ON al.pessoa_id = p.id
                    INNER JOIN turma t ON f.turma_id = t.id
                    LEFT JOIN usuario u ON f.registrado_por = u.id
                    LEFT JOIN pessoa p2 ON u.pessoa_id = p2.id
                    WHERE NOT EXISTS (SELECT 1 FROM validacao WHERE tipo = 'FREQUENCIA' AND registro_id = f.id AND status IN ('APROVADO', 'REJEITADO'))
                       OR EXISTS (SELECT 1 FROM validacao WHERE tipo = 'FREQUENCIA' AND registro_id = f.id AND status = 'PENDENTE')
                    ORDER BY f.registrado_em DESC
                    LIMIT " . intval($limit);
            
            $stmt = $conn->query($sql);
            $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($frequencias as $freq) {
                $informacoes[] = [
                    'tipo' => 'FREQUENCIA',
                    'id' => $freq['id'],
                    'titulo' => 'Frequência Registrada',
                    'descricao' => $freq['aluno_nome'] . ' - ' . $freq['turma_nome'] . ' - ' . ($freq['presenca'] ? 'Presente' : 'Falta'),
                    'detalhes' => [
                        'Aluno' => $freq['aluno_nome'],
                        'Turma' => $freq['turma_nome'],
                        'Data' => date('d/m/Y', strtotime($freq['data'])),
                        'Status' => $freq['presenca'] ? 'Presente' : 'Falta',
                        'Registrado por' => $freq['registrado_por_nome'] ?? 'Sistema',
                        'Registrado em' => date('d/m/Y H:i', strtotime($freq['registrado_em']))
                    ],
                    'data' => $freq['registrado_em'],
                    'status' => $freq['status_validacao'] ?? 'PENDENTE'
                ];
            }
        }
        
        // Matrículas pendentes
        if (!$tipo || $tipo === 'MATRICULA') {
            $sql = "SELECT al.id, al.data_matricula,
                           p.nome as aluno_nome,
                           CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                           (SELECT status FROM validacao WHERE tipo = 'MATRICULA' AND registro_id = al.id ORDER BY criado_em DESC LIMIT 1) as status_validacao
                    FROM aluno al
                    INNER JOIN pessoa p ON al.pessoa_id = p.id
                    LEFT JOIN aluno_turma at ON al.id = at.aluno_id
                    LEFT JOIN turma t ON at.turma_id = t.id
                    WHERE al.data_matricula IS NOT NULL
                      AND (NOT EXISTS (SELECT 1 FROM validacao WHERE tipo = 'MATRICULA' AND registro_id = al.id AND status IN ('APROVADO', 'REJEITADO'))
                       OR EXISTS (SELECT 1 FROM validacao WHERE tipo = 'MATRICULA' AND registro_id = al.id AND status = 'PENDENTE'))
                    ORDER BY al.data_matricula DESC
                    LIMIT " . intval($limit);
            
            $stmt = $conn->query($sql);
            $matriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($matriculas as $matricula) {
                $informacoes[] = [
                    'tipo' => 'MATRICULA',
                    'id' => $matricula['id'],
                    'titulo' => 'Nova Matrícula',
                    'descricao' => $matricula['aluno_nome'] . ($matricula['turma_nome'] ? ' - ' . $matricula['turma_nome'] : ''),
                    'detalhes' => [
                        'Aluno' => $matricula['aluno_nome'],
                        'Turma' => $matricula['turma_nome'] ?? 'Não atribuída',
                        'Data de Matrícula' => date('d/m/Y', strtotime($matricula['data_matricula']))
                    ],
                    'data' => $matricula['data_matricula'],
                    'status' => $matricula['status_validacao'] ?? 'PENDENTE'
                ];
            }
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($informacoes, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        return array_slice($informacoes, 0, $limit);
        
    } catch (PDOException $e) {
        error_log("Erro ao listar informações pendentes: " . $e->getMessage());
        return [];
    }
}

// Validar informação
function validarInformacao($tipo, $registroId, $status, $observacao = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Verificar se já existe validação
        $stmt = $conn->prepare("SELECT id FROM validacao WHERE tipo = :tipo AND registro_id = :registro_id ORDER BY criado_em DESC LIMIT 1");
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':registro_id', $registroId);
        $stmt->execute();
        $validacaoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($validacaoExistente) {
            // Atualizar validação existente
            $stmt = $conn->prepare("UPDATE validacao SET 
                                    status = :status, 
                                    validado_por = :validado_por, 
                                    validado_em = NOW(), 
                                    observacao = :observacao 
                                    WHERE id = :id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':validado_por', $_SESSION['usuario_id']);
            $stmt->bindParam(':observacao', $observacao);
            $stmt->bindParam(':id', $validacaoExistente['id']);
            $stmt->execute();
        } else {
            // Criar nova validação
            $stmt = $conn->prepare("INSERT INTO validacao (tipo, registro_id, status, validado_por, validado_em, observacao) 
                                    VALUES (:tipo, :registro_id, :status, :validado_por, NOW(), :observacao)");
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':registro_id', $registroId);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':validado_por', $_SESSION['usuario_id']);
            $stmt->bindParam(':observacao', $observacao);
            $stmt->execute();
        }
        
        $conn->commit();
        return ['status' => true, 'mensagem' => 'Informação ' . ($status === 'APROVADO' ? 'aprovada' : 'rejeitada') . ' com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao validar informação: ' . $e->getMessage()];
    }
}

// Listar histórico de validações
function listarHistoricoValidacoes($tipo = null, $limit = 50) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT v.*, p.nome as validado_por_nome
            FROM validacao v
            LEFT JOIN usuario u ON v.validado_por = u.id
            LEFT JOIN pessoa p ON u.pessoa_id = p.id
            WHERE 1=1";
    
    if ($tipo) {
        $sql .= " AND v.tipo = :tipo";
    }
    
    $sql .= " ORDER BY v.validado_em DESC LIMIT " . intval($limit);
    
    $stmt = $conn->prepare($sql);
    
    if ($tipo) {
        $stmt->bindParam(':tipo', $tipo);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== PROCESSAMENTO ====================

// Criar tabela se não existir
criarTabelaValidacao();

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'validar') {
        $tipo = $_POST['tipo'] ?? '';
        $registroId = $_POST['registro_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $observacao = $_POST['observacao'] ?? null;
        
        $resultado = validarInformacao($tipo, $registroId, $status, $observacao);
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = $resultado['status'] ? 'success' : 'error';
    }
}

// Buscar dados
$tipoFiltro = $_GET['tipo'] ?? null;
$informacoesPendentes = listarInformacoesPendentes($tipoFiltro, 50);
$historicoValidacoes = listarHistoricoValidacoes($tipoFiltro, 20);

// Função para calcular tempo relativo
function calcularTempoRelativo($data) {
    if (empty($data)) return 'Data não disponível';
    
    $timestamp = strtotime($data);
    $agora = time();
    $diferenca = $agora - $timestamp;
    
    if ($diferenca < 60) {
        return 'há menos de 1 minuto';
    } elseif ($diferenca < 3600) {
        $minutos = floor($diferenca / 60);
        return "há {$minutos} minuto" . ($minutos > 1 ? 's' : '');
    } elseif ($diferenca < 86400) {
        $horas = floor($diferenca / 3600);
        return "há {$horas} hora" . ($horas > 1 ? 's' : '');
    } elseif ($diferenca < 2592000) {
        $dias = floor($diferenca / 86400);
        return "há {$dias} dia" . ($dias > 1 ? 's' : '');
    } else {
        return date('d/m/Y', $timestamp);
    }
}

$tipos = [
    'NOTA' => 'Notas',
    'FREQUENCIA' => 'Frequências',
    'MATRICULA' => 'Matrículas',
    'AVALIACAO' => 'Avaliações',
    'CARDAPIO' => 'Cardápios',
    'ESTOQUE' => 'Estoque'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Informações - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#22c55e',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Validar Informações</h1>
                        <p class="text-sm text-gray-500 mt-1">Revisar e validar informações lançadas por outros usuários</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="supervisao_modulos.php" class="text-gray-600 hover:text-gray-900">Supervisão</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md p-4 <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conteúdo Principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h2>
                <div class="flex flex-wrap gap-4">
                    <a href="?tipo=" class="px-4 py-2 rounded-lg <?= !$tipoFiltro ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Todas
                    </a>
                    <?php foreach ($tipos as $tipoKey => $tipoNome): ?>
                    <a href="?tipo=<?= $tipoKey ?>" class="px-4 py-2 rounded-lg <?= $tipoFiltro === $tipoKey ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <?= htmlspecialchars($tipoNome) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Informações Pendentes -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Informações Pendentes de Validação</h2>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                        <?= count($informacoesPendentes) ?> pendente(s)
                    </span>
                </div>
                
                <div class="space-y-4">
                    <?php if (empty($informacoesPendentes)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">Nenhuma informação pendente de validação</p>
                        <p class="text-sm">Todas as informações foram validadas ou não há informações para validar.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($informacoesPendentes as $info): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                        <?= htmlspecialchars($tipos[$info['tipo']] ?? $info['tipo']) ?>
                                    </span>
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($info['titulo']) ?></h3>
                                </div>
                                <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($info['descricao']) ?></p>
                                
                                <!-- Detalhes -->
                                <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                        <?php foreach ($info['detalhes'] as $chave => $valor): ?>
                                        <div>
                                            <span class="font-medium text-gray-700"><?= htmlspecialchars($chave) ?>:</span>
                                            <span class="text-gray-600 ml-1"><?= htmlspecialchars($valor) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="text-xs text-gray-500">
                                    <?= calcularTempoRelativo($info['data']) ?>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2 ml-4">
                                <button onclick="abrirModalValidacao('<?= $info['tipo'] ?>', <?= $info['id'] ?>, 'APROVADO', '<?= htmlspecialchars($info['titulo']) ?>')" 
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                                    Aprovar
                                </button>
                                <button onclick="abrirModalValidacao('<?= $info['tipo'] ?>', <?= $info['id'] ?>, 'REJEITADO', '<?= htmlspecialchars($info['titulo']) ?>')" 
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                                    Rejeitar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Histórico de Validações -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Histórico de Validações</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validado por</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observação</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($historicoValidacoes)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhuma validação registrada.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($historicoValidacoes as $validacao): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($tipos[$validacao['tipo']] ?? $validacao['tipo']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= 
                                        $validacao['status'] === 'APROVADO' ? 'bg-green-100 text-green-800' : 
                                        ($validacao['status'] === 'REJEITADO' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                                    ?>">
                                        <?= htmlspecialchars($validacao['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($validacao['validado_por_nome'] ?? 'Sistema') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $validacao['validado_em'] ? date('d/m/Y H:i', strtotime($validacao['validado_em'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($validacao['observacao'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Validação -->
    <div id="modalValidacao" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="bg-primary-green text-white p-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold" id="modalValidacaoTitulo">Validar Informação</h3>
                    <button onclick="fecharModalValidacao()" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form id="formValidacao" method="POST" class="p-6">
                <input type="hidden" name="acao" value="validar">
                <input type="hidden" name="tipo" id="validacaoTipo">
                <input type="hidden" name="registro_id" id="validacaoRegistroId">
                <input type="hidden" name="status" id="validacaoStatus">
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Você está prestes a <span id="acaoValidacao" class="font-semibold"></span> a seguinte informação:</p>
                    <p class="text-sm font-medium text-gray-900" id="infoTitulo"></p>
                </div>
                
                <div class="mb-4">
                    <label for="observacao" class="block text-sm font-medium text-gray-700 mb-2">Observação (opcional)</label>
                    <textarea id="observacao" name="observacao" rows="3" 
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green"
                              placeholder="Adicione uma observação sobre a validação..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="fecharModalValidacao()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalValidacao(tipo, registroId, status, titulo) {
            document.getElementById('validacaoTipo').value = tipo;
            document.getElementById('validacaoRegistroId').value = registroId;
            document.getElementById('validacaoStatus').value = status;
            document.getElementById('infoTitulo').textContent = titulo;
            document.getElementById('acaoValidacao').textContent = status === 'APROVADO' ? 'aprovar' : 'rejeitar';
            document.getElementById('modalValidacaoTitulo').textContent = status === 'APROVADO' ? 'Aprovar Informação' : 'Rejeitar Informação';
            document.getElementById('observacao').value = '';
            document.getElementById('modalValidacao').classList.remove('hidden');
        }
        
        function fecharModalValidacao() {
            document.getElementById('modalValidacao').classList.add('hidden');
        }
    </script>
</body>
</html>

