<?php
/**
 * Script de diagnóstico para verificar problema com usuario_id no cardápio
 */

require_once(__DIR__ . '/../../config/Database.php');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>Diagnóstico - Usuário Cardápio</h2>";

// 1. Verificar sessão
echo "<h3>1. Dados da Sessão</h3>";
echo "<pre>";
echo "usuario_id: " . var_export($_SESSION['usuario_id'] ?? 'não definido', true) . "\n";
echo "tipo: " . var_export($_SESSION['tipo'] ?? 'não definido', true) . "\n";
echo "logado: " . var_export($_SESSION['logado'] ?? 'não definido', true) . "\n";
echo "nome: " . var_export($_SESSION['nome'] ?? 'não definido', true) . "\n";
echo "</pre>";

// 2. Verificar se há usuários no banco
echo "<h3>2. Usuários no Banco de Dados</h3>";
try {
    $sql = "SELECT id, username, role, ativo, pessoa_id FROM usuario ORDER BY id ASC LIMIT 10";
    $stmt = $conn->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Ativo</th><th>Pessoa ID</th></tr>";
    foreach ($usuarios as $u) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($u['id']) . "</td>";
        echo "<td>" . htmlspecialchars($u['username']) . "</td>";
        echo "<td>" . htmlspecialchars($u['role']) . "</td>";
        echo "<td>" . ($u['ativo'] ? 'Sim' : 'Não') . "</td>";
        echo "<td>" . htmlspecialchars($u['pessoa_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p>Total de usuários encontrados: " . count($usuarios) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>ERRO ao buscar usuários: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. Verificar se o usuario_id da sessão existe no banco
if (isset($_SESSION['usuario_id'])) {
    echo "<h3>3. Verificação do Usuário da Sessão</h3>";
    $usuarioId = (int)$_SESSION['usuario_id'];
    
    try {
        $sql = "SELECT id, username, role, ativo, pessoa_id FROM usuario WHERE id = :usuario_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo "<p style='color: green;'>✓ Usuário encontrado no banco:</p>";
            echo "<pre>";
            print_r($usuario);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>✗ ERRO: Usuário ID $usuarioId NÃO encontrado no banco de dados!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>ERRO ao verificar usuário: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<h3>3. Verificação do Usuário da Sessão</h3>";
    echo "<p style='color: orange;'>⚠ usuario_id não está definido na sessão</p>";
}

// 4. Verificar estrutura da tabela cardapio
echo "<h3>4. Estrutura da Tabela cardapio</h3>";
try {
    $sql = "SHOW CREATE TABLE cardapio";
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['Create Table'])) {
        echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERRO ao verificar estrutura: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 5. Verificar constraint da foreign key
echo "<h3>5. Constraints da Tabela cardapio</h3>";
try {
    $sql = "SELECT 
                CONSTRAINT_NAME,
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cardapio'
            AND REFERENCED_TABLE_NAME IS NOT NULL";
    $stmt = $conn->query($sql);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($constraints) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Constraint</th><th>Tabela</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        foreach ($constraints as $c) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($c['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($c['TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($c['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($c['REFERENCED_TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($c['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma constraint encontrada.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERRO ao verificar constraints: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='../Views/dashboard/cardapios_merenda.php'>Voltar para Cardápios</a></p>";
?>

