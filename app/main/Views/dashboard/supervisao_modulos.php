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

// ==================== FUNÇÕES DE SUPERVISÃO ====================

// Estatísticas do Módulo Acadêmico
function obterEstatisticasModuloAcademico() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $estatisticas = [];
    
    try {
        // Total de alunos
        $stmt = $conn->query("SELECT COUNT(*) as total FROM aluno WHERE ativo = 1");
        $estatisticas['total_alunos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de professores
        $stmt = $conn->query("SELECT COUNT(*) as total FROM professor WHERE ativo = 1");
        $estatisticas['total_professores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de turmas
        $stmt = $conn->query("SELECT COUNT(*) as total FROM turma WHERE ativo = 1");
        $estatisticas['total_turmas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de escolas
        $stmt = $conn->query("SELECT COUNT(*) as total FROM escola");
        $estatisticas['total_escolas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Matrículas no mês atual
        $stmt = $conn->query("SELECT COUNT(*) as total FROM aluno WHERE MONTH(data_matricula) = MONTH(CURDATE()) AND YEAR(data_matricula) = YEAR(CURDATE())");
        $estatisticas['matriculas_mes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Frequências registradas hoje
        $stmt = $conn->query("SELECT COUNT(DISTINCT turma_id) as total FROM frequencia WHERE data = CURDATE()");
        $estatisticas['frequencias_hoje'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Notas lançadas hoje
        $stmt = $conn->query("SELECT COUNT(*) as total FROM nota WHERE DATE(lancado_em) = CURDATE()");
        $estatisticas['notas_hoje'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Avaliações pendentes
        $stmt = $conn->query("SELECT COUNT(DISTINCT a.id) as total FROM avaliacao a LEFT JOIN nota n ON a.id = n.avaliacao_id WHERE n.id IS NULL AND a.data <= CURDATE()");
        $estatisticas['avaliacoes_pendentes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Média geral de notas
        $stmt = $conn->query("SELECT AVG(nota) as media FROM nota");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estatisticas['media_geral'] = $result['media'] ? number_format($result['media'], 2) : '0.00';
        
        // Taxa de frequência geral
        $stmt = $conn->query("SELECT 
                                COUNT(*) as total,
                                SUM(CASE WHEN presenca = 1 THEN 1 ELSE 0 END) as presentes
                              FROM frequencia");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estatisticas['taxa_frequencia'] = $result['total'] > 0 ? number_format(($result['presentes'] / $result['total']) * 100, 2) : '0.00';
        
    } catch (PDOException $e) {
        error_log("Erro ao obter estatísticas acadêmicas: " . $e->getMessage());
    }
    
    return $estatisticas;
}

// Estatísticas do Módulo de Alimentação
function obterEstatisticasModuloAlimentacao() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $estatisticas = [];
    
    try {
        // Total de produtos no estoque
        $stmt = $conn->query("SELECT COUNT(*) as total FROM produto WHERE ativo = 1");
        $estatisticas['total_produtos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Valor total do estoque
        $stmt = $conn->query("SELECT SUM(quantidade * preco_unitario) as total FROM estoque_central WHERE quantidade > 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $estatisticas['valor_estoque'] = $result['total'] ? number_format($result['total'], 2, ',', '.') : '0,00';
        
        // Cardápios cadastrados
        $stmt = $conn->query("SELECT COUNT(*) as total FROM cardapio");
        $estatisticas['total_cardapios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Cardápios do mês atual
        $stmt = $conn->query("SELECT COUNT(*) as total FROM cardapio WHERE mes = MONTH(CURDATE()) AND ano = YEAR(CURDATE())");
        $estatisticas['cardapios_mes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Movimentações hoje
        $stmt = $conn->query("SELECT COUNT(*) as total FROM estoque_central WHERE DATE(ultima_atualizacao) = CURDATE()");
        $estatisticas['movimentacoes_hoje'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Produtos com estoque baixo
        $stmt = $conn->query("SELECT COUNT(*) as total FROM estoque_central WHERE quantidade < estoque_minimo AND quantidade > 0");
        $estatisticas['produtos_estoque_baixo'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Produtos sem estoque
        $stmt = $conn->query("SELECT COUNT(*) as total FROM estoque_central WHERE quantidade = 0");
        $estatisticas['produtos_sem_estoque'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
    } catch (PDOException $e) {
        error_log("Erro ao obter estatísticas de alimentação: " . $e->getMessage());
    }
    
    return $estatisticas;
}

// Atividades recentes do módulo acadêmico
function obterAtividadesRecentesAcademicas($limit = 10) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $atividades = [];
    
    try {
        // Matrículas recentes
        $sql = "SELECT al.id, al.data_matricula, p.nome as aluno_nome, 
                       CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome
                FROM aluno al
                INNER JOIN pessoa p ON al.pessoa_id = p.id
                LEFT JOIN aluno_turma at ON al.id = at.aluno_id
                LEFT JOIN turma t ON at.turma_id = t.id
                WHERE al.data_matricula IS NOT NULL
                ORDER BY al.data_matricula DESC
                LIMIT " . intval($limit);
        
        $stmt = $conn->query($sql);
        $matriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($matriculas as $matricula) {
            $atividades[] = [
                'tipo' => 'matricula',
                'titulo' => 'Nova matrícula',
                'descricao' => $matricula['aluno_nome'] . ($matricula['turma_nome'] ? ' - ' . $matricula['turma_nome'] : ''),
                'data' => $matricula['data_matricula'],
                'icone' => 'user-add'
            ];
        }
        
        // Frequências registradas hoje
        $sql = "SELECT f.turma_id, f.data, MAX(f.registrado_em) as registrado_em, 
                       CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                       COUNT(CASE WHEN f.presenca = 1 THEN 1 END) as presentes
                FROM frequencia f
                INNER JOIN turma t ON f.turma_id = t.id
                WHERE f.data = CURDATE()
                GROUP BY f.turma_id, f.data, t.serie, t.letra
                ORDER BY registrado_em DESC
                LIMIT " . intval($limit);
        
        $stmt = $conn->query($sql);
        $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($frequencias as $freq) {
            $atividades[] = [
                'tipo' => 'frequencia',
                'titulo' => 'Frequência registrada',
                'descricao' => $freq['turma_nome'] . ' - ' . $freq['presentes'] . ' alunos presentes',
                'data' => $freq['registrado_em'],
                'icone' => 'check-circle'
            ];
        }
        
        // Notas lançadas hoje
        $sql = "SELECT MAX(n.lancado_em) as lancado_em, 
                       CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome, 
                       d.nome as disciplina_nome
                FROM nota n
                INNER JOIN avaliacao a ON n.avaliacao_id = a.id
                INNER JOIN turma t ON a.turma_id = t.id
                INNER JOIN turma_professor tp ON t.id = tp.turma_id
                INNER JOIN disciplina d ON tp.disciplina_id = d.id
                WHERE DATE(n.lancado_em) = CURDATE()
                GROUP BY DATE(n.lancado_em), t.id, t.serie, t.letra, d.id, d.nome
                ORDER BY lancado_em DESC
                LIMIT " . intval($limit);
        
        $stmt = $conn->query($sql);
        $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notas as $nota) {
            $atividades[] = [
                'tipo' => 'nota',
                'titulo' => 'Notas lançadas',
                'descricao' => ($nota['disciplina_nome'] ?? 'Disciplina') . ' - ' . $nota['turma_nome'],
                'data' => $nota['lancado_em'],
                'icone' => 'document-text'
            ];
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($atividades, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        return array_slice($atividades, 0, $limit);
        
    } catch (PDOException $e) {
        error_log("Erro ao obter atividades acadêmicas: " . $e->getMessage());
        return [];
    }
}

// Atividades recentes do módulo de alimentação
function obterAtividadesRecentesAlimentacao($limit = 10) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $atividades = [];
    
    try {
        // Cardápios criados recentemente
        $sql = "SELECT c.id, c.mes, c.ano, c.criado_em, e.nome as escola_nome
                FROM cardapio c
                INNER JOIN escola e ON c.escola_id = e.id
                ORDER BY c.criado_em DESC
                LIMIT " . intval($limit);
        
        $stmt = $conn->query($sql);
        $cardapios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cardapios as $cardapio) {
            $atividades[] = [
                'tipo' => 'cardapio',
                'titulo' => 'Cardápio criado',
                'descricao' => $cardapio['escola_nome'] . ' - ' . $cardapio['mes'] . '/' . $cardapio['ano'],
                'data' => $cardapio['criado_em'],
                'icone' => 'calendar'
            ];
        }
        
        // Movimentações de estoque recentes
        $sql = "SELECT ec.id, ec.produto_id, ec.quantidade, ec.ultima_atualizacao, p.nome as produto_nome
                FROM estoque_central ec
                INNER JOIN produto p ON ec.produto_id = p.id
                WHERE ec.ultima_atualizacao IS NOT NULL
                ORDER BY ec.ultima_atualizacao DESC
                LIMIT " . intval($limit);
        
        $stmt = $conn->query($sql);
        $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($movimentacoes as $mov) {
            $atividades[] = [
                'tipo' => 'estoque',
                'titulo' => 'Movimentação de estoque',
                'descricao' => $mov['produto_nome'] . ' - Qtd: ' . $mov['quantidade'],
                'data' => $mov['ultima_atualizacao'],
                'icone' => 'cube'
            ];
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($atividades, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        return array_slice($atividades, 0, $limit);
        
    } catch (PDOException $e) {
        error_log("Erro ao obter atividades de alimentação: " . $e->getMessage());
        return [];
    }
}

// Status geral do sistema
function obterStatusGeral() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $status = [
        'sistema_online' => true,
        'ultima_atualizacao' => date('Y-m-d H:i:s'),
        'total_usuarios_ativos' => 0,
        'total_operacoes_hoje' => 0
    ];
    
    try {
        // Total de usuários ativos
        $stmt = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE ativo = 1");
        $status['total_usuarios_ativos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de operações hoje (matrículas + frequências + notas + movimentações)
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM aluno WHERE DATE(data_matricula) = CURDATE()) +
                    (SELECT COUNT(*) FROM frequencia WHERE data = CURDATE()) +
                    (SELECT COUNT(*) FROM nota WHERE DATE(lancado_em) = CURDATE()) +
                    (SELECT COUNT(*) FROM estoque_central WHERE DATE(ultima_atualizacao) = CURDATE())
                as total";
        $stmt = $conn->query($sql);
        $status['total_operacoes_hoje'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
    } catch (PDOException $e) {
        error_log("Erro ao obter status geral: " . $e->getMessage());
    }
    
    return $status;
}

// Buscar dados
$estatisticasAcademico = obterEstatisticasModuloAcademico();
$estatisticasAlimentacao = obterEstatisticasModuloAlimentacao();
$atividadesAcademicas = obterAtividadesRecentesAcademicas(5);
$atividadesAlimentacao = obterAtividadesRecentesAlimentacao(5);
$statusGeral = obterStatusGeral();

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisão de Módulos - SIGAE</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Supervisão de Módulos</h1>
                        <p class="text-sm text-gray-500 mt-1">Monitoramento e supervisão dos módulos do sistema</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="gestao_usuarios.php" class="text-gray-600 hover:text-gray-900">Usuários</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Status Geral -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Status Geral do Sistema</h2>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                        <?= $statusGeral['sistema_online'] ? 'Online' : 'Offline' ?>
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-sm text-blue-600 font-medium">Usuários Ativos</div>
                        <div class="text-2xl font-bold text-blue-900 mt-1"><?= $statusGeral['total_usuarios_ativos'] ?></div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="text-sm text-purple-600 font-medium">Operações Hoje</div>
                        <div class="text-2xl font-bold text-purple-900 mt-1"><?= $statusGeral['total_operacoes_hoje'] ?></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600 font-medium">Última Atualização</div>
                        <div class="text-sm font-medium text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($statusGeral['ultima_atualizacao'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Módulo Acadêmico -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Módulo Acadêmico</h2>
                        <p class="text-sm text-gray-500 mt-1">Gestão pedagógica e administrativa</p>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Ativo</span>
                </div>

                <!-- Estatísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-xs text-blue-600 font-medium mb-1">Alunos</div>
                        <div class="text-2xl font-bold text-blue-900"><?= $estatisticasAcademico['total_alunos'] ?? 0 ?></div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-xs text-green-600 font-medium mb-1">Professores</div>
                        <div class="text-2xl font-bold text-green-900"><?= $estatisticasAcademico['total_professores'] ?? 0 ?></div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="text-xs text-purple-600 font-medium mb-1">Turmas</div>
                        <div class="text-2xl font-bold text-purple-900"><?= $estatisticasAcademico['total_turmas'] ?? 0 ?></div>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="text-xs text-orange-600 font-medium mb-1">Escolas</div>
                        <div class="text-2xl font-bold text-orange-900"><?= $estatisticasAcademico['total_escolas'] ?? 0 ?></div>
                    </div>
                    <div class="bg-pink-50 rounded-lg p-4">
                        <div class="text-xs text-pink-600 font-medium mb-1">Matrículas (Mês)</div>
                        <div class="text-2xl font-bold text-pink-900"><?= $estatisticasAcademico['matriculas_mes'] ?? 0 ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-600 font-medium mb-1">Frequências Hoje</div>
                        <div class="text-xl font-bold text-gray-900"><?= $estatisticasAcademico['frequencias_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-600 font-medium mb-1">Notas Lançadas Hoje</div>
                        <div class="text-xl font-bold text-gray-900"><?= $estatisticasAcademico['notas_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="text-xs text-yellow-600 font-medium mb-1">Avaliações Pendentes</div>
                        <div class="text-xl font-bold text-yellow-900"><?= $estatisticasAcademico['avaliacoes_pendentes'] ?? 0 ?></div>
                    </div>
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <div class="text-xs text-indigo-600 font-medium mb-1">Média Geral</div>
                        <div class="text-xl font-bold text-indigo-900"><?= $estatisticasAcademico['media_geral'] ?? '0.00' ?></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-sm text-gray-600 font-medium mb-2">Taxa de Frequência Geral</div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-green-600 h-4 rounded-full" style="width: <?= $estatisticasAcademico['taxa_frequencia'] ?? 0 ?>%"></div>
                    </div>
                    <div class="text-sm text-gray-700 mt-1"><?= $estatisticasAcademico['taxa_frequencia'] ?? '0.00' ?>%</div>
                </div>

                <!-- Atividades Recentes -->
                <div class="mt-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Atividades Recentes</h3>
                    <div class="space-y-2">
                        <?php if (empty($atividadesAcademicas)): ?>
                        <p class="text-sm text-gray-500">Nenhuma atividade recente</p>
                        <?php else: ?>
                        <?php foreach ($atividadesAcademicas as $atividade): ?>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($atividade['titulo']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($atividade['descricao']) ?></div>
                            </div>
                            <div class="text-xs text-gray-400"><?= calcularTempoRelativo($atividade['data']) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Módulo de Alimentação -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Módulo de Alimentação Escolar</h2>
                        <p class="text-sm text-gray-500 mt-1">Gestão de cardápios, estoque e distribuição</p>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Ativo</span>
                </div>

                <!-- Estatísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="text-xs text-orange-600 font-medium mb-1">Produtos</div>
                        <div class="text-2xl font-bold text-orange-900"><?= $estatisticasAlimentacao['total_produtos'] ?? 0 ?></div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-xs text-green-600 font-medium mb-1">Valor do Estoque</div>
                        <div class="text-xl font-bold text-green-900">R$ <?= $estatisticasAlimentacao['valor_estoque'] ?? '0,00' ?></div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-xs text-blue-600 font-medium mb-1">Cardápios</div>
                        <div class="text-2xl font-bold text-blue-900"><?= $estatisticasAlimentacao['total_cardapios'] ?? 0 ?></div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="text-xs text-purple-600 font-medium mb-1">Cardápios (Mês)</div>
                        <div class="text-2xl font-bold text-purple-900"><?= $estatisticasAlimentacao['cardapios_mes'] ?? 0 ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-600 font-medium mb-1">Movimentações Hoje</div>
                        <div class="text-xl font-bold text-gray-900"><?= $estatisticasAlimentacao['movimentacoes_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="text-xs text-yellow-600 font-medium mb-1">Estoque Baixo</div>
                        <div class="text-xl font-bold text-yellow-900"><?= $estatisticasAlimentacao['produtos_estoque_baixo'] ?? 0 ?></div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="text-xs text-red-600 font-medium mb-1">Sem Estoque</div>
                        <div class="text-xl font-bold text-red-900"><?= $estatisticasAlimentacao['produtos_sem_estoque'] ?? 0 ?></div>
                    </div>
                </div>

                <!-- Atividades Recentes -->
                <div class="mt-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Atividades Recentes</h3>
                    <div class="space-y-2">
                        <?php if (empty($atividadesAlimentacao)): ?>
                        <p class="text-sm text-gray-500">Nenhuma atividade recente</p>
                        <?php else: ?>
                        <?php foreach ($atividadesAlimentacao as $atividade): ?>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($atividade['titulo']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($atividade['descricao']) ?></div>
                            </div>
                            <div class="text-xs text-gray-400"><?= calcularTempoRelativo($atividade['data']) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Alertas e Avisos -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Alertas e Avisos</h2>
                <div class="space-y-3">
                    <?php if (($estatisticasAcademico['avaliacoes_pendentes'] ?? 0) > 0): ?>
                    <div class="flex items-center space-x-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-yellow-800">Avaliações Pendentes</div>
                            <div class="text-xs text-yellow-600">Existem <?= $estatisticasAcademico['avaliacoes_pendentes'] ?> avaliações aguardando lançamento de notas</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (($estatisticasAlimentacao['produtos_estoque_baixo'] ?? 0) > 0): ?>
                    <div class="flex items-center space-x-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-yellow-800">Estoque Baixo</div>
                            <div class="text-xs text-yellow-600"><?= $estatisticasAlimentacao['produtos_estoque_baixo'] ?> produtos com estoque abaixo do mínimo</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (($estatisticasAlimentacao['produtos_sem_estoque'] ?? 0) > 0): ?>
                    <div class="flex items-center space-x-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-red-800">Produtos Sem Estoque</div>
                            <div class="text-xs text-red-600"><?= $estatisticasAlimentacao['produtos_sem_estoque'] ?> produtos estão sem estoque</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (($estatisticasAcademico['avaliacoes_pendentes'] ?? 0) == 0 && ($estatisticasAlimentacao['produtos_estoque_baixo'] ?? 0) == 0 && ($estatisticasAlimentacao['produtos_sem_estoque'] ?? 0) == 0): ?>
                    <div class="flex items-center space-x-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-green-800">Tudo em Ordem</div>
                            <div class="text-xs text-green-600">Nenhum alerta ou aviso pendente no momento</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Ações Rápidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="gestao_usuarios.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-primary-green hover:bg-green-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Gerenciar Usuários</span>
                        </div>
                    </a>
                    <a href="gestao_escolas.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-primary-green hover:bg-green-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Gerenciar Escolas</span>
                        </div>
                    </a>
                    <a href="gestao_turmas_disciplinas.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-primary-green hover:bg-green-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Turmas e Disciplinas</span>
                        </div>
                    </a>
                    <a href="gestao_estoque_central.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-primary-green hover:bg-green-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Estoque Central</span>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

