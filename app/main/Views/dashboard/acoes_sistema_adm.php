<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM pode acessar
if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Processar ações
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'reverter_exclusao' && isset($_POST['backup_id'])) {
        $backupId = $_POST['backup_id'];
        
        // Buscar backup
        $stmt = $conn->prepare("SELECT * FROM escola_backup WHERE id = :id AND revertido = 0 AND excluido_permanentemente = 0");
        $stmt->bindParam(':id', $backupId, PDO::PARAM_INT);
        $stmt->execute();
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($backup) {
            try {
                $conn->beginTransaction();
                
                $dadosEscola = json_decode($backup['dados_escola'], true);
                $dadosTurmas = json_decode($backup['dados_turmas'], true) ?: [];
                $dadosLotacoes = json_decode($backup['dados_lotacoes'], true) ?: [];
                
                // Verificar se a escola já existe (pode ter sido restaurada)
                $stmtCheck = $conn->prepare("SELECT id FROM escola WHERE id = :id");
                $stmtCheck->bindParam(':id', $dadosEscola['id'], PDO::PARAM_INT);
                $stmtCheck->execute();
                
                if ($stmtCheck->fetch()) {
                    throw new Exception('Escola já existe no sistema. Pode ter sido restaurada anteriormente.');
                }
                
                // Restaurar escola
                $sqlEscola = "INSERT INTO escola (id, codigo, nome, endereco, numero, complemento, bairro, municipio, estado, cep, telefone, telefone_secundario, email, site, cnpj, diretor_id, qtd_salas, obs, ativo, criado_em, atualizado_em, atualizado_por) 
                             VALUES (:id, :codigo, :nome, :endereco, :numero, :complemento, :bairro, :municipio, :estado, :cep, :telefone, :telefone_secundario, :email, :site, :cnpj, :diretor_id, :qtd_salas, :obs, :ativo, :criado_em, :atualizado_em, :atualizado_por)";
                $stmtEscola = $conn->prepare($sqlEscola);
                $stmtEscola->bindValue(':id', $dadosEscola['id'] ?? null, PDO::PARAM_INT);
                $stmtEscola->bindValue(':codigo', $dadosEscola['codigo'] ?? null);
                $stmtEscola->bindValue(':nome', $dadosEscola['nome'] ?? null);
                $stmtEscola->bindValue(':endereco', $dadosEscola['endereco'] ?? null);
                $stmtEscola->bindValue(':numero', $dadosEscola['numero'] ?? null);
                $stmtEscola->bindValue(':complemento', $dadosEscola['complemento'] ?? null);
                $stmtEscola->bindValue(':bairro', $dadosEscola['bairro'] ?? null);
                $stmtEscola->bindValue(':municipio', $dadosEscola['municipio'] ?? null);
                $stmtEscola->bindValue(':estado', $dadosEscola['estado'] ?? null);
                $stmtEscola->bindValue(':cep', $dadosEscola['cep'] ?? null);
                $stmtEscola->bindValue(':telefone', $dadosEscola['telefone'] ?? null);
                $stmtEscola->bindValue(':telefone_secundario', $dadosEscola['telefone_secundario'] ?? null);
                $stmtEscola->bindValue(':email', $dadosEscola['email'] ?? null);
                $stmtEscola->bindValue(':site', $dadosEscola['site'] ?? null);
                $stmtEscola->bindValue(':cnpj', $dadosEscola['cnpj'] ?? null);
                $stmtEscola->bindValue(':diretor_id', $dadosEscola['diretor_id'] ?? null, PDO::PARAM_INT);
                $stmtEscola->bindValue(':qtd_salas', $dadosEscola['qtd_salas'] ?? null, PDO::PARAM_INT);
                $stmtEscola->bindValue(':obs', $dadosEscola['obs'] ?? null);
                $stmtEscola->bindValue(':ativo', $dadosEscola['ativo'] ?? 1, PDO::PARAM_INT);
                $stmtEscola->bindValue(':criado_em', $dadosEscola['criado_em'] ?? date('Y-m-d H:i:s'));
                $stmtEscola->bindValue(':atualizado_em', $dadosEscola['atualizado_em'] ?? date('Y-m-d H:i:s'));
                $stmtEscola->bindValue(':atualizado_por', $dadosEscola['atualizado_por'] ?? null, PDO::PARAM_INT);
                $stmtEscola->execute();
                
                // Restaurar turmas
                if (!empty($dadosTurmas) && is_array($dadosTurmas)) {
                    foreach ($dadosTurmas as $turma) {
                        if (!is_array($turma)) continue;
                        
                        // Verificar se turma já existe
                        $stmtCheckTurma = $conn->prepare("SELECT id FROM turma WHERE id = :id");
                        $stmtCheckTurma->bindValue(':id', $turma['id'] ?? null, PDO::PARAM_INT);
                        $stmtCheckTurma->execute();
                        
                        if (!$stmtCheckTurma->fetch()) {
                            try {
                                $sqlTurma = "INSERT INTO turma (id, escola_id, nome, serie, turno, ano_letivo, ativo, criado_em, atualizado_em) 
                                             VALUES (:id, :escola_id, :nome, :serie, :turno, :ano_letivo, :ativo, :criado_em, :atualizado_em)";
                                $stmtTurma = $conn->prepare($sqlTurma);
                                $stmtTurma->bindValue(':id', $turma['id'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':escola_id', $turma['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtTurma->bindValue(':nome', $turma['nome'] ?? null);
                                $stmtTurma->bindValue(':serie', $turma['serie'] ?? null);
                                $stmtTurma->bindValue(':turno', $turma['turno'] ?? null);
                                $stmtTurma->bindValue(':ano_letivo', $turma['ano_letivo'] ?? null);
                                $stmtTurma->bindValue(':ativo', $turma['ativo'] ?? 1, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':criado_em', $turma['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmtTurma->bindValue(':atualizado_em', $turma['atualizado_em'] ?? date('Y-m-d H:i:s'));
                                $stmtTurma->execute();
                            } catch (PDOException $e) {
                                // Ignorar erros de turma (pode já existir)
                                error_log("Erro ao restaurar turma: " . $e->getMessage());
                            }
                        }
                    }
                }
                
                // Restaurar lotações de professores
                if (!empty($dadosLotacoes['professores']) && is_array($dadosLotacoes['professores'])) {
                    foreach ($dadosLotacoes['professores'] as $lotacao) {
                        if (!is_array($lotacao)) continue;
                        
                        try {
                            // Verificar se a lotação já existe
                            $stmtCheck = $conn->prepare("SELECT id FROM professor_lotacao WHERE professor_id = :professor_id AND escola_id = :escola_id");
                            $stmtCheck->bindValue(':professor_id', $lotacao['professor_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            
                            if (!$stmtCheck->fetch()) {
                                $sqlLotacao = "INSERT INTO professor_lotacao (professor_id, escola_id, inicio, fim, carga_horaria, observacao) 
                                               VALUES (:professor_id, :escola_id, :inicio, NULL, :carga_horaria, :observacao)";
                                $stmtLotacao = $conn->prepare($sqlLotacao);
                                $stmtLotacao->bindValue(':professor_id', $lotacao['professor_id'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':inicio', $lotacao['inicio'] ?? date('Y-m-d'));
                                $stmtLotacao->bindValue(':carga_horaria', $lotacao['carga_horaria'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':observacao', $lotacao['observacao'] ?? null);
                                $stmtLotacao->execute();
                            }
                        } catch (PDOException $e) {
                            // Ignorar erros de lotação
                            error_log("Erro ao restaurar lotação professor: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar lotações de gestores
                if (!empty($dadosLotacoes['gestores']) && is_array($dadosLotacoes['gestores'])) {
                    foreach ($dadosLotacoes['gestores'] as $lotacao) {
                        if (!is_array($lotacao)) continue;
                        
                        try {
                            // Verificar se a lotação já existe
                            $stmtCheck = $conn->prepare("SELECT id FROM gestor_lotacao WHERE gestor_id = :gestor_id AND escola_id = :escola_id");
                            $stmtCheck->bindValue(':gestor_id', $lotacao['gestor_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            
                            if (!$stmtCheck->fetch()) {
                                $sqlLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, fim, responsavel, tipo, observacoes) 
                                               VALUES (:gestor_id, :escola_id, :inicio, NULL, :responsavel, :tipo, :observacoes)";
                                $stmtLotacao = $conn->prepare($sqlLotacao);
                                $stmtLotacao->bindValue(':gestor_id', $lotacao['gestor_id'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':inicio', $lotacao['inicio'] ?? date('Y-m-d'));
                                $stmtLotacao->bindValue(':responsavel', $lotacao['responsavel'] ?? 0, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':tipo', $lotacao['tipo'] ?? null);
                                $stmtLotacao->bindValue(':observacoes', $lotacao['observacoes'] ?? null);
                                $stmtLotacao->execute();
                            }
                        } catch (PDOException $e) {
                            // Ignorar erros de lotação
                            error_log("Erro ao restaurar lotação gestor: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar lotações de nutricionistas
                if (!empty($dadosLotacoes['nutricionistas']) && is_array($dadosLotacoes['nutricionistas'])) {
                    foreach ($dadosLotacoes['nutricionistas'] as $lotacao) {
                        if (!is_array($lotacao)) continue;
                        
                        try {
                            // Verificar se a lotação já existe
                            $stmtCheck = $conn->prepare("SELECT id FROM nutricionista_lotacao WHERE nutricionista_id = :nutricionista_id AND escola_id = :escola_id");
                            $stmtCheck->bindValue(':nutricionista_id', $lotacao['nutricionista_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            
                            if (!$stmtCheck->fetch()) {
                                $sqlLotacao = "INSERT INTO nutricionista_lotacao (nutricionista_id, escola_id, inicio, fim, responsavel, carga_horaria, observacoes) 
                                               VALUES (:nutricionista_id, :escola_id, :inicio, NULL, :responsavel, :carga_horaria, :observacoes)";
                                $stmtLotacao = $conn->prepare($sqlLotacao);
                                $stmtLotacao->bindValue(':nutricionista_id', $lotacao['nutricionista_id'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':inicio', $lotacao['inicio'] ?? date('Y-m-d'));
                                $stmtLotacao->bindValue(':responsavel', $lotacao['responsavel'] ?? 0, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':carga_horaria', $lotacao['carga_horaria'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':observacoes', $lotacao['observacoes'] ?? null);
                                $stmtLotacao->execute();
                            }
                        } catch (PDOException $e) {
                            // Ignorar erros de lotação
                            error_log("Erro ao restaurar lotação nutricionista: " . $e->getMessage());
                        }
                    }
                }
                
                // Marcar backup como revertido
                $usuarioId = $_SESSION['usuario_id'] ?? null;
                $stmtUpdate = $conn->prepare("UPDATE escola_backup SET revertido = 1, revertido_em = NOW(), revertido_por = :usuario_id WHERE id = :id");
                $stmtUpdate->bindParam(':id', $backupId, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtUpdate->execute();
                
                $conn->commit();
                $mensagem = 'Exclusão revertida com sucesso! A escola foi restaurada.';
                $tipoMensagem = 'success';
            } catch (PDOException $e) {
                $conn->rollBack();
                $mensagem = 'Erro ao reverter exclusão: ' . $e->getMessage();
                $tipoMensagem = 'error';
            }
        } else {
            $mensagem = 'Backup não encontrado ou já foi revertido/excluído.';
            $tipoMensagem = 'error';
        }
    } elseif ($_POST['acao'] === 'excluir_permanentemente' && isset($_POST['backup_id'])) {
        $backupId = $_POST['backup_id'];
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        
        try {
            $stmt = $conn->prepare("UPDATE escola_backup SET excluido_permanentemente = 1 WHERE id = :id");
            $stmt->bindParam(':id', $backupId, PDO::PARAM_INT);
            $stmt->execute();
            
            $mensagem = 'Backup excluído permanentemente.';
            $tipoMensagem = 'success';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao excluir permanentemente: ' . $e->getMessage();
            $tipoMensagem = 'error';
        }
    }
}

// Limpar backups antigos (mais de 30 dias e não revertidos)
try {
    $stmtLimpar = $conn->prepare("UPDATE escola_backup 
                                  SET excluido_permanentemente = 1 
                                  WHERE excluido_em < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                  AND revertido = 0 
                                  AND excluido_permanentemente = 0");
    $stmtLimpar->execute();
} catch (PDOException $e) {
    error_log("Erro ao limpar backups antigos: " . $e->getMessage());
}

// Buscar ações dos últimos 30 dias
$stmtAcoes = $conn->prepare("SELECT eb.*, 
                             p1.nome as excluido_por_nome,
                             p2.nome as revertido_por_nome,
                             DATEDIFF(NOW(), eb.excluido_em) as dias_restantes
                             FROM escola_backup eb
                             LEFT JOIN usuario u1 ON eb.excluido_por = u1.id
                             LEFT JOIN pessoa p1 ON u1.pessoa_id = p1.id
                             LEFT JOIN usuario u2 ON eb.revertido_por = u2.id
                             LEFT JOIN pessoa p2 ON u2.pessoa_id = p2.id
                             WHERE eb.excluido_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                             AND eb.excluido_permanentemente = 0
                             ORDER BY eb.excluido_em DESC");
$stmtAcoes->execute();
$acoes = $stmtAcoes->fetchAll(PDO::FETCH_ASSOC);

// Decodificar dados para exibição
foreach ($acoes as &$acao) {
    $dadosEscola = json_decode($acao['dados_escola'], true);
    $acao['escola_nome'] = $dadosEscola['nome'] ?? 'N/A';
    $acao['escola_codigo'] = $dadosEscola['codigo'] ?? 'N/A';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ações do Sistema - SIGAE</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
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
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Ações do Sistema</h1>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Exclusões de Escolas (Últimos 30 dias)</h2>
                    <p class="text-gray-600 mb-6">
                        Escolas excluídas nos últimos 30 dias podem ser revertidas. Após 30 dias, serão excluídas permanentemente automaticamente.
                    </p>
                    
                    <?php if (empty($acoes)): ?>
                        <div class="text-center py-12">
                            <p class="text-gray-500">Nenhuma exclusão de escola nos últimos 30 dias.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Excluído Por</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Data Exclusão</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Dias Restantes</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acoes as $acao): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div>
                                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($acao['escola_nome']) ?></p>
                                                    <p class="text-sm text-gray-500">Código: <?= htmlspecialchars($acao['escola_codigo']) ?></p>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?= htmlspecialchars($acao['excluido_por_nome'] ?? 'Sistema') ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?= date('d/m/Y H:i', strtotime($acao['excluido_em'])) ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php 
                                                $diasRestantes = 30 - (int)$acao['dias_restantes'];
                                                if ($diasRestantes > 0) {
                                                    echo '<span class="text-orange-600 font-semibold">' . $diasRestantes . ' dias</span>';
                                                } else {
                                                    echo '<span class="text-red-600 font-semibold">Expirado</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($acao['revertido']): ?>
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Revertido
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Excluído
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if (!$acao['revertido']): ?>
                                                    <div class="flex space-x-2">
                                                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja reverter a exclusão desta escola?')">
                                                            <input type="hidden" name="acao" value="reverter_exclusao">
                                                            <input type="hidden" name="backup_id" value="<?= $acao['id'] ?>">
                                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                                                Reverter
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir permanentemente? Esta ação não pode ser desfeita!')">
                                                            <input type="hidden" name="acao" value="excluir_permanentemente">
                                                            <input type="hidden" name="backup_id" value="<?= $acao['id'] ?>">
                                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                                                Excluir Permanentemente
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">Revertido em <?= date('d/m/Y H:i', strtotime($acao['revertido_em'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
    </script>
</body>
</html>

