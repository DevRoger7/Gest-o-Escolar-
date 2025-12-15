<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$db = Database::getInstance();
$conn = $db->getConnection();

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Função para obter dados do usuário logado
function obterDadosUsuarioLogado($usuarioId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT 
                    u.id as usuario_id,
                    u.username,
                    u.role as tipo,
                    u.ativo,
                    u.ultimo_login,
                    u.created_at as data_criacao,
                    p.id as pessoa_id,
                    p.nome,
                    p.cpf,
                    p.email,
                    p.telefone,
                    p.data_nascimento,
                    p.sexo,
                    p.endereco,
                    p.cep,
                    p.cidade,
                    p.estado
                FROM usuario u 
                LEFT JOIN pessoa p ON u.pessoa_id = p.id 
                WHERE u.id = :usuario_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            $sqlUsuario = "SELECT 
                            id as usuario_id,
                            username,
                            role as tipo,
                            ativo,
                            ultimo_login,
                            created_at as data_criacao
                        FROM usuario 
                        WHERE id = :usuario_id";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmtUsuario->execute();
            $resultado = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$resultado) {
            return [
                'usuario_id' => $usuarioId,
                'username' => 'Usuário',
                'tipo' => 'USUARIO',
                'ativo' => 1
            ];
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao obter dados do usuário: " . $e->getMessage());
        return null;
    }
}

// Buscar dados do usuário logado
$dadosUsuario = null;
if (isset($_SESSION['usuario_id'])) {
    $dadosUsuario = obterDadosUsuarioLogado($_SESSION['usuario_id']);
}

if (!$dadosUsuario) {
    header('Location: dashboard.php');
    exit;
}

$tipoUsuario = strtoupper($dadosUsuario['tipo'] ?? $_SESSION['tipo'] ?? 'USUARIO');
$nomeUsuario = $dadosUsuario['nome'] ?? $dadosUsuario['username'] ?? 'Usuário';
$pessoaId = $dadosUsuario['pessoa_id'] ?? $_SESSION['pessoa_id'] ?? null;

// Gerar iniciais para avatar
$iniciais = '';
if (strlen($nomeUsuario) >= 2) {
    $iniciais = strtoupper(substr($nomeUsuario, 0, 2));
} elseif (strlen($nomeUsuario) == 1) {
    $iniciais = strtoupper($nomeUsuario);
} else {
    $iniciais = 'US';
}

// Buscar informações específicas por tipo de usuário
$infoEspecifica = [];

try {
    if ($tipoUsuario === 'PROFESSOR' && $pessoaId) {
        // Buscar dados do professor
        $sqlProfessor = "SELECT pr.id, pr.matricula, pr.formacao, pr.ativo 
                        FROM professor pr 
                        WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 
                        LIMIT 1";
        $stmtProfessor = $conn->prepare($sqlProfessor);
        $stmtProfessor->bindParam(':pessoa_id', $pessoaId);
        $stmtProfessor->execute();
        $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
        
        if ($professor) {
            $infoEspecifica['matricula'] = $professor['matricula'] ?? null;
            $infoEspecifica['formacao'] = $professor['formacao'] ?? null;
            
            // Buscar lotações ativas
            $sqlLotacoes = "SELECT e.nome as escola_nome, e.id as escola_id, pl.inicio, pl.carga_horaria
                           FROM professor_lotacao pl
                           INNER JOIN escola e ON pl.escola_id = e.id
                           WHERE pl.professor_id = :professor_id 
                           AND pl.fim IS NULL
                           AND e.ativo = 1
                           ORDER BY pl.inicio DESC";
            $stmtLotacoes = $conn->prepare($sqlLotacoes);
            $stmtLotacoes->bindParam(':professor_id', $professor['id'], PDO::PARAM_INT);
            $stmtLotacoes->execute();
            $infoEspecifica['lotacoes'] = $stmtLotacoes->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($tipoUsuario === 'GESTAO' && $pessoaId) {
        // Buscar dados do gestor
        $sqlGestor = "SELECT g.id 
                     FROM gestor g 
                     WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1 
                     LIMIT 1";
        $stmtGestor = $conn->prepare($sqlGestor);
        $stmtGestor->bindParam(':pessoa_id', $pessoaId);
        $stmtGestor->execute();
        $gestor = $stmtGestor->fetch(PDO::FETCH_ASSOC);
        
        if ($gestor) {
            // Buscar escolas gerenciadas
            $sqlEscolas = "SELECT DISTINCT e.id as escola_id, e.nome as escola_nome, 
                          MAX(gl.responsavel) as responsavel, MAX(gl.inicio) as inicio
                          FROM gestor_lotacao gl
                          INNER JOIN escola e ON gl.escola_id = e.id
                          WHERE gl.gestor_id = :gestor_id
                          AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                          AND e.ativo = 1
                          GROUP BY gl.escola_id, e.nome
                          ORDER BY MAX(gl.responsavel) DESC, MAX(gl.inicio) DESC";
            $stmtEscolas = $conn->prepare($sqlEscolas);
            $stmtEscolas->bindParam(':gestor_id', $gestor['id'], PDO::PARAM_INT);
            $stmtEscolas->execute();
            $infoEspecifica['escolas'] = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($tipoUsuario === 'ALUNO' && $pessoaId) {
        // Buscar dados do aluno
        $sqlAluno = "SELECT a.id, a.matricula, a.ativo 
                    FROM aluno a 
                    WHERE a.pessoa_id = :pessoa_id AND a.ativo = 1 
                    LIMIT 1";
        $stmtAluno = $conn->prepare($sqlAluno);
        $stmtAluno->bindParam(':pessoa_id', $pessoaId);
        $stmtAluno->execute();
        $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
        
        if ($aluno) {
            $infoEspecifica['matricula'] = $aluno['matricula'] ?? null;
            
            // Buscar turma atual
            $sqlTurma = "SELECT t.id, t.nome as turma_nome, s.nome as serie_nome, 
                        e.nome as escola_nome, e.id as escola_id, at.data_entrada
                        FROM aluno_turma at
                        INNER JOIN turma t ON at.turma_id = t.id
                        INNER JOIN serie s ON t.serie_id = s.id
                        INNER JOIN escola e ON t.escola_id = e.id
                        WHERE at.aluno_id = :aluno_id
                        AND (at.data_saida IS NULL OR at.data_saida = '' OR at.data_saida = '0000-00-00')
                        AND t.ativo = 1
                        ORDER BY at.data_entrada DESC
                        LIMIT 1";
            $stmtTurma = $conn->prepare($sqlTurma);
            $stmtTurma->bindParam(':aluno_id', $aluno['id'], PDO::PARAM_INT);
            $stmtTurma->execute();
            $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);
            
            if ($turma) {
                $infoEspecifica['turma'] = $turma;
            }
        }
    } elseif ($tipoUsuario === 'NUTRICIONISTA' && $pessoaId) {
        // Buscar dados do nutricionista
        $sqlNutricionista = "SELECT n.id 
                            FROM nutricionista n 
                            WHERE n.pessoa_id = :pessoa_id AND n.ativo = 1 
                            LIMIT 1";
        $stmtNutricionista = $conn->prepare($sqlNutricionista);
        $stmtNutricionista->bindParam(':pessoa_id', $pessoaId);
        $stmtNutricionista->execute();
        $nutricionista = $stmtNutricionista->fetch(PDO::FETCH_ASSOC);
        
        if ($nutricionista) {
            // Nutricionista pode ter acesso a todas as escolas ou escolas específicas
            // Por enquanto, mostrar todas as escolas ativas
            $sqlEscolas = "SELECT id as escola_id, nome as escola_nome 
                          FROM escola 
                          WHERE ativo = 1 
                          ORDER BY nome ASC";
            $stmtEscolas = $conn->query($sqlEscolas);
            $infoEspecifica['escolas'] = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($tipoUsuario === 'ADM_MERENDA') {
        // ADM_MERENDA tem acesso a todas as escolas
        $sqlEscolas = "SELECT id as escola_id, nome as escola_nome 
                      FROM escola 
                      WHERE ativo = 1 
                      ORDER BY nome ASC";
        $stmtEscolas = $conn->query($sqlEscolas);
        $infoEspecifica['escolas'] = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Erro ao buscar informações específicas do usuário: " . $e->getMessage());
}

// Sempre mostrar apenas o perfil (sem navegação de seções)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?= htmlspecialchars($nomeUsuario) ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <link rel="shortcut icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <link rel="apple-touch-icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Tipografia refinada e sistema de cores profissional */
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Sistema de sombras hierárquico */
        .card-shadow {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }
        
        .card-shadow-hover {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-shadow-hover:hover {
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08), 0 2px 4px 0 rgba(0, 0, 0, 0.04);
            transform: translateY(-2px);
        }
        
        /* Detalhes refinados */
        .info-label {
            font-size: 0.6875rem;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        
        /* Avatar com gradiente sutil */
        .avatar-gradient {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }
        
        /* Divider sutil */
        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e2e8f0, transparent);
        }
    </style>
</head>
<body>
    <!-- Header minimalista com melhor hierarquia visual -->
    <div class="bg-white/80 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="group p-2.5 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100/70 transition-all duration-200">
                    <i class="fas fa-arrow-left text-sm group-hover:-translate-x-0.5 transition-transform duration-200"></i>
                </a>
                <div>
                    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Perfil</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout otimizado com espaçamento profissional -->
    <main class="py-8 px-6">
        <div class="max-w-5xl mx-auto">
            <div class="space-y-5">
                
                <!-- Profile Header com design refinado e equilibrado -->
                <div class="card-shadow bg-white rounded-xl p-8 border border-slate-200/60">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                        <!-- Avatar com tamanho otimizado -->
                        <div class="flex-shrink-0">
                            <div class="w-20 h-20 avatar-gradient rounded-xl flex items-center justify-center text-white text-2xl font-semibold shadow-lg shadow-blue-500/20">
                                <?= htmlspecialchars($iniciais) ?>
                            </div>
                        </div>
                        
                        <div class="flex-1 text-center sm:text-left">
                            <h2 class="text-2xl font-semibold text-slate-900 mb-2 tracking-tight"><?= htmlspecialchars($nomeUsuario) ?></h2>
                            <div class="mb-3">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                    <?php
                                    $tipos = [
                                        'ADM' => 'Administrador',
                                        'GESTAO' => 'Gestor',
                                        'PROFESSOR' => 'Professor',
                                        'ADM_MERENDA' => 'Adm. Merenda',
                                        'NUTRICIONISTA' => 'Nutricionista',
                                        'ALUNO' => 'Aluno',
                                        'RESPONSAVEL' => 'Responsável'
                                    ];
                                    echo $tipos[$tipoUsuario] ?? 'Usuário';
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($dadosUsuario['cidade']) || !empty($dadosUsuario['estado'])): ?>
                            <p class="text-slate-600 text-sm flex items-center justify-center sm:justify-start gap-1.5">
                                <i class="fas fa-map-marker-alt text-slate-400 text-xs"></i>
                                <span><?= htmlspecialchars(trim(($dadosUsuario['cidade'] ?? '') . ', ' . ($dadosUsuario['estado'] ?? ''), ', ')) ?></span>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cards otimizados com melhor densidade de informação -->
                <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                    <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i class="fas fa-user text-slate-600 text-sm"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900">Informações Pessoais</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Primeiro Nome</label>
                            <p class="info-value text-slate-900 font-medium">
                                <?php
                                $nomeCompleto = $dadosUsuario['nome'] ?? '';
                                $nomes = explode(' ', $nomeCompleto);
                                echo htmlspecialchars($nomes[0] ?? $nomeCompleto);
                                ?>
                            </p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Último Nome</label>
                            <p class="info-value text-slate-900 font-medium">
                                <?php
                                $nomes = explode(' ', $nomeCompleto);
                                echo htmlspecialchars(implode(' ', array_slice($nomes, 1)) ?: '-');
                                ?>
                            </p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">E-mail</label>
                            <p class="info-value text-slate-700"><?= htmlspecialchars($dadosUsuario['email'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Telefone</label>
                            <p class="info-value text-slate-700 font-mono text-sm">
                                <?php
                                if (!empty($dadosUsuario['telefone'])) {
                                    $telefone = preg_replace('/\D/', '', $dadosUsuario['telefone']);
                                    if (strlen($telefone) == 11) {
                                        echo preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
                                    } elseif (strlen($telefone) == 10) {
                                        echo preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
                                    } else {
                                        echo htmlspecialchars($dadosUsuario['telefone']);
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card de endereço com layout limpo -->
                <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                    <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i class="fas fa-map-pin text-slate-600 text-sm"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900">Endereço</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">País</label>
                            <p class="info-value text-slate-900 font-medium"><?= htmlspecialchars($dadosUsuario['estado'] ? 'Brasil' : 'N/A') ?></p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Cidade/Estado</label>
                            <p class="info-value text-slate-900 font-medium">
                                <?= htmlspecialchars(trim(($dadosUsuario['cidade'] ?? '') . ', ' . ($dadosUsuario['estado'] ?? ''), ', ') ?: 'N/A') ?>
                            </p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">CEP</label>
                            <p class="info-value text-slate-700 font-mono text-sm">
                                <?php
                                if (!empty($dadosUsuario['cep'])) {
                                    $cep = preg_replace('/\D/', '', $dadosUsuario['cep']);
                                    echo preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">CPF</label>
                            <p class="info-value text-slate-700 font-mono text-sm">
                                <?php
                                if (!empty($dadosUsuario['cpf'])) {
                                    $cpf = preg_replace('/\D/', '', $dadosUsuario['cpf']);
                                    echo preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                        </div>
                        <?php if (!empty($dadosUsuario['endereco'])): ?>
                        <div class="sm:col-span-2">
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Endereço Completo</label>
                            <p class="info-value text-slate-700"><?= htmlspecialchars($dadosUsuario['endereco']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cards específicos por tipo com design consistente e compacto -->
                <?php if ($tipoUsuario === 'PROFESSOR' && !empty($infoEspecifica)): ?>
                    <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                        <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-chalkboard-user text-blue-600 text-sm"></i>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900">Informações Profissionais</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 mb-6">
                            <?php if (!empty($infoEspecifica['matricula'])): ?>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Matrícula</label>
                                <p class="info-value text-slate-900 font-bold"><?= htmlspecialchars($infoEspecifica['matricula']) ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($infoEspecifica['formacao'])): ?>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Formação</label>
                                <p class="info-value text-slate-700"><?= htmlspecialchars($infoEspecifica['formacao']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($infoEspecifica['lotacoes'])): ?>
                        <div>
                            <label class="info-label block text-slate-700 mb-3 uppercase">Escolas Vinculadas</label>
                            <div class="space-y-2.5">
                                <?php foreach ($infoEspecifica['lotacoes'] as $lotacao): ?>
                                <div class="p-4 bg-slate-50/70 rounded-lg border border-slate-200/50 hover:border-slate-300/70 transition-colors duration-200">
                                    <p class="font-medium text-slate-900 text-sm mb-1.5 flex items-center gap-2">
                                        <i class="fas fa-school text-slate-400 text-xs"></i>
                                        <?= htmlspecialchars($lotacao['escola_nome']) ?>
                                    </p>
                                    <div class="flex flex-wrap gap-3 text-xs text-slate-600">
                                        <?php if (!empty($lotacao['inicio'])): ?>
                                        <span>Desde <?= date('d/m/Y', strtotime($lotacao['inicio'])) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($lotacao['carga_horaria'])): ?>
                                        <span class="font-semibold"><?= htmlspecialchars($lotacao['carga_horaria']) ?>h/semana</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($tipoUsuario === 'GESTAO' && !empty($infoEspecifica['escolas'])): ?>
                    <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                        <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-building text-blue-600 text-sm"></i>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900">Escolas Gerenciadas</h3>
                        </div>
                        <div class="space-y-2.5">
                            <?php foreach ($infoEspecifica['escolas'] as $escola): ?>
                            <div class="p-4 bg-slate-50/70 rounded-lg border border-slate-200/50 hover:border-slate-300/70 transition-colors duration-200">
                                <p class="font-medium text-slate-900 text-sm mb-1.5 flex items-center gap-2">
                                    <i class="fas fa-school text-slate-400 text-xs"></i>
                                    <?= htmlspecialchars($escola['escola_nome']) ?>
                                </p>
                                <div class="flex flex-wrap items-center gap-3 text-xs">
                                    <?php if (!empty($escola['inicio'])): ?>
                                    <span class="text-slate-600">Desde <?= date('d/m/Y', strtotime($escola['inicio'])) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($escola['responsavel']) && $escola['responsavel'] == 1): ?>
                                    <span class="inline-flex items-center gap-1 text-green-700 font-semibold">
                                        <i class="fas fa-check-circle text-green-600"></i>
                                        Responsável
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($tipoUsuario === 'ALUNO' && !empty($infoEspecifica)): ?>
                    <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                        <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-book text-blue-600 text-sm"></i>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900">Informações Acadêmicas</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <?php if (!empty($infoEspecifica['matricula'])): ?>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Matrícula</label>
                                <p class="info-value text-slate-900 font-bold"><?= htmlspecialchars($infoEspecifica['matricula']) ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($infoEspecifica['turma'])): ?>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Turma</label>
                                <p class="info-value text-slate-700"><?= htmlspecialchars($infoEspecifica['turma']['turma_nome']) ?></p>
                            </div>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Série</label>
                                <p class="info-value text-slate-700"><?= htmlspecialchars($infoEspecifica['turma']['serie_nome']) ?></p>
                            </div>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Escola</label>
                                <p class="info-value text-slate-700"><?= htmlspecialchars($infoEspecifica['turma']['escola_nome']) ?></p>
                            </div>
                            <?php if (!empty($infoEspecifica['turma']['data_entrada'])): ?>
                            <div>
                                <label class="info-label block text-slate-500 uppercase mb-1.5">Data de Entrada</label>
                                <p class="info-value text-slate-700"><?= date('d/m/Y', strtotime($infoEspecifica['turma']['data_entrada'])) ?></p>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif (in_array($tipoUsuario, ['NUTRICIONISTA', 'ADM_MERENDA']) && !empty($infoEspecifica['escolas'])): ?>
                    <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                        <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                <i class="fas fa-utensils text-green-600 text-sm"></i>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900">Escolas Vinculadas</h3>
                        </div>
                        <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                            <?php foreach ($infoEspecifica['escolas'] as $escola): ?>
                            <div class="p-4 bg-slate-50/70 rounded-lg border border-slate-200/50 hover:border-slate-300/70 transition-colors duration-200">
                                <p class="font-medium text-slate-900 text-sm flex items-center gap-2">
                                    <i class="fas fa-school text-slate-400 text-xs"></i>
                                    <?= htmlspecialchars($escola['escola_nome']) ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Card de conta com status destacado -->
                <div class="card-shadow-hover bg-white rounded-xl p-7 border border-slate-200/60">
                    <div class="flex items-center gap-2.5 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i class="fas fa-lock text-slate-600 text-sm"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900">Informações da Conta</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Username</label>
                            <p class="info-value text-slate-900 font-mono text-sm"><?= htmlspecialchars($dadosUsuario['username'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Tipo de Usuário</label>
                            <p class="info-value text-slate-700">
                                <?php
                                $tipos = [
                                    'ADM' => 'Administrador',
                                    'GESTAO' => 'Gestor',
                                    'PROFESSOR' => 'Professor',
                                    'ADM_MERENDA' => 'Adm. Merenda',
                                    'NUTRICIONISTA' => 'Nutricionista',
                                    'ALUNO' => 'Aluno',
                                    'RESPONSAVEL' => 'Responsável'
                                ];
                                echo $tipos[$tipoUsuario] ?? 'Usuário';
                                ?>
                            </p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Data de Criação</label>
                            <p class="info-value text-slate-700 text-sm">
                                <?php
                                if (!empty($dadosUsuario['data_criacao'])) {
                                    echo date('d/m/Y H:i', strtotime($dadosUsuario['data_criacao']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                        </div>
                        <div>
                            <label class="info-label block text-slate-500 uppercase mb-1.5">Último Login</label>
                            <p class="info-value text-slate-700 text-sm">
                                <?php
                                if (!empty($dadosUsuario['ultimo_login'])) {
                                    echo date('d/m/Y H:i', strtotime($dadosUsuario['ultimo_login']));
                                } else {
                                    echo 'Nunca';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="sm:col-span-2 pt-2">
                            <label class="info-label block text-slate-500 uppercase mb-2">Status da Conta</label>
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold <?= ($dadosUsuario['ativo'] ?? 0) ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
                                <span class="relative flex h-2 w-2">
                                    <?php if ($dadosUsuario['ativo'] ?? 0): ?>
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    <?php else: ?>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                    <?php endif; ?>
                                </span>
                                <?= ($dadosUsuario['ativo'] ?? 0) ? 'Conta Ativa' : 'Conta Inativa' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
