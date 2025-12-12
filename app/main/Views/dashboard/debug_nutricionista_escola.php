<?php
/**
 * Script de diagnóstico para verificar lotação de nutricionista
 * Acesse: http://localhost/GitHub/Gest-o-Escolar-/app/main/Views/dashboard/debug_nutricionista_escola.php
 */

require_once('../../Models/sessao/sessions.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();

if (!isset($_SESSION['usuario_id']) || strtoupper($_SESSION['tipo'] ?? '') !== 'NUTRICIONISTA') {
    die("Acesso negado. Este script é apenas para nutricionistas.");
}

$db = Database::getInstance();
$conn = $db->getConnection();
$usuarioId = $_SESSION['usuario_id'];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico - Lotação Nutricionista</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2D5A27; }
        h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #2D5A27; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #2D5A27; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Lotação - Nutricionista</h1>
        <p><strong>Usuário ID:</strong> <?= htmlspecialchars($usuarioId) ?></p>
        <p><strong>Nome:</strong> <?= htmlspecialchars($_SESSION['nome'] ?? 'N/A') ?></p>
        <p><strong>Tipo:</strong> <?= htmlspecialchars($_SESSION['tipo'] ?? 'N/A') ?></p>
        
        <h2>1. Dados do Usuário</h2>
        <?php
        $sqlUsuario = "SELECT * FROM usuario WHERE id = :usuario_id";
        $stmtUsuario = $conn->prepare($sqlUsuario);
        $stmtUsuario->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmtUsuario->execute();
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo "<table>";
            foreach ($usuario as $key => $value) {
                echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>Usuário não encontrado!</p>";
        }
        ?>
        
        <h2>2. Dados da Pessoa</h2>
        <?php
        if ($usuario && isset($usuario['pessoa_id'])) {
            $pessoaId = $usuario['pessoa_id'];
            $sqlPessoa = "SELECT * FROM pessoa WHERE id = :pessoa_id";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmtPessoa->execute();
            $pessoa = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
            
            if ($pessoa) {
                echo "<table>";
                foreach ($pessoa as $key => $value) {
                    echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>Pessoa não encontrada! (ID: $pessoaId)</p>";
            }
        } else {
            echo "<p class='error'>Usuário não tem pessoa_id associado!</p>";
        }
        ?>
        
        <h2>3. Dados do Nutricionista</h2>
        <?php
        if (isset($pessoaId)) {
            $sqlNutricionista = "SELECT * FROM nutricionista WHERE pessoa_id = :pessoa_id";
            $stmtNutricionista = $conn->prepare($sqlNutricionista);
            $stmtNutricionista->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmtNutricionista->execute();
            $nutricionista = $stmtNutricionista->fetch(PDO::FETCH_ASSOC);
            
            if ($nutricionista) {
                echo "<table>";
                foreach ($nutricionista as $key => $value) {
                    echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
                echo "</table>";
                
                $nutricionistaId = $nutricionista['id'];
            } else {
                echo "<p class='error'>Nutricionista não encontrado para pessoa_id: $pessoaId</p>";
            }
        }
        ?>
        
        <h2>4. Todas as Lotações do Nutricionista</h2>
        <?php
        if (isset($nutricionistaId)) {
            $sqlLotacoes = "SELECT nl.*, e.nome as escola_nome, e.ativo as escola_ativo
                           FROM nutricionista_lotacao nl
                           LEFT JOIN escola e ON nl.escola_id = e.id
                           WHERE nl.nutricionista_id = :nutricionista_id
                           ORDER BY nl.inicio DESC";
            $stmtLotacoes = $conn->prepare($sqlLotacoes);
            $stmtLotacoes->bindParam(':nutricionista_id', $nutricionistaId, PDO::PARAM_INT);
            $stmtLotacoes->execute();
            $lotacoes = $stmtLotacoes->fetchAll(PDO::FETCH_ASSOC);
            
            if ($lotacoes) {
                echo "<table>";
                echo "<tr>";
                echo "<th>ID</th><th>Escola ID</th><th>Escola Nome</th><th>Escola Ativa</th>";
                echo "<th>Início</th><th>Fim</th><th>Responsável</th><th>Status</th>";
                echo "</tr>";
                
                foreach ($lotacoes as $lot) {
                    $fim = $lot['fim'] ?? null;
                    $escolaAtiva = $lot['escola_ativo'] ?? 0;
                    $hoje = date('Y-m-d');
                    
                    $status = '';
                    if ($fim && $fim != '0000-00-00' && $fim < $hoje) {
                        $status = "<span class='error'>Encerrada</span>";
                    } elseif ($fim && $fim != '0000-00-00' && $fim >= $hoje) {
                        $status = "<span class='warning'>Ativa (fim futuro)</span>";
                    } elseif (!$fim || $fim == '0000-00-00' || $fim == '') {
                        $status = "<span class='success'>Ativa (sem fim)</span>";
                    }
                    
                    if ($escolaAtiva != 1) {
                        $status .= " <span class='error'>- Escola Inativa</span>";
                    }
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($lot['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($lot['escola_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($lot['escola_nome'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($escolaAtiva == 1 ? '<span class="success">Sim</span>' : '<span class="error">Não</span>') . "</td>";
                    echo "<td>" . htmlspecialchars($lot['inicio'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($fim ?? 'NULL') . "</td>";
                    echo "<td>" . ($lot['responsavel'] == 1 ? 'Sim' : 'Não') . "</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>Nenhuma lotação encontrada para nutricionista_id: $nutricionistaId</p>";
            }
        }
        ?>
        
        <h2>5. Query de Busca da Escola (como no código)</h2>
        <?php
        if (isset($usuarioId)) {
            $sql = "SELECT nl.escola_id, e.nome as escola_nome, nl.fim, e.ativo as escola_ativo, n.ativo as nutricionista_ativo
                    FROM nutricionista_lotacao nl
                    INNER JOIN escola e ON nl.escola_id = e.id
                    INNER JOIN nutricionista n ON nl.nutricionista_id = n.id
                    INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id
                    WHERE u.id = :usuario_id
                    AND e.ativo = 1
                    AND n.ativo = 1
                    AND (nl.fim IS NULL OR nl.fim = '' OR nl.fim = '0000-00-00' OR nl.fim >= CURDATE())
                    ORDER BY nl.responsavel DESC, nl.inicio DESC
                    LIMIT 1";
            
            echo "<pre>" . htmlspecialchars($sql) . "</pre>";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado) {
                echo "<p class='success'>✓ Escola encontrada!</p>";
                echo "<table>";
                foreach ($resultado as $key => $value) {
                    echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>✗ Nenhuma escola encontrada com os critérios da query</p>";
                
                // Tentar sem os filtros
                $sqlSemFiltros = "SELECT nl.escola_id, e.nome as escola_nome, nl.fim, e.ativo as escola_ativo, n.ativo as nutricionista_ativo
                                 FROM nutricionista_lotacao nl
                                 INNER JOIN escola e ON nl.escola_id = e.id
                                 INNER JOIN nutricionista n ON nl.nutricionista_id = n.id
                                 INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id
                                 WHERE u.id = :usuario_id
                                 ORDER BY nl.inicio DESC
                                 LIMIT 1";
                $stmtSemFiltros = $conn->prepare($sqlSemFiltros);
                $stmtSemFiltros->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtSemFiltros->execute();
                $resultadoSemFiltros = $stmtSemFiltros->fetch(PDO::FETCH_ASSOC);
                
                if ($resultadoSemFiltros) {
                    echo "<p class='warning'>⚠ Encontrada lotação sem filtros:</p>";
                    echo "<table>";
                    foreach ($resultadoSemFiltros as $key => $value) {
                        echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                    }
                    echo "</table>";
                }
            }
        }
        ?>
    </div>
</body>
</html>

