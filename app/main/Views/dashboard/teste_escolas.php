<?php
// Script de teste para verificar se as escolas estão sendo carregadas
require_once('../../config/Database.php');

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>Teste de Carregamento de Escolas</h1>";

// Teste 1: Verificar conexão
echo "<h2>1. Teste de Conexão</h2>";
try {
    $test = $conn->query("SELECT 1");
    echo "✓ Conexão OK<br>";
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 2: Verificar se a tabela existe
echo "<h2>2. Verificar Tabela 'escola'</h2>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'escola'");
    $exists = $stmt->rowCount() > 0;
    if ($exists) {
        echo "✓ Tabela 'escola' existe<br>";
    } else {
        echo "✗ Tabela 'escola' NÃO existe<br>";
        exit;
    }
} catch (Exception $e) {
    echo "✗ Erro ao verificar tabela: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 3: Contar escolas
echo "<h2>3. Contar Escolas</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM escola");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de escolas no banco: <strong>" . $result['total'] . "</strong><br>";
} catch (Exception $e) {
    echo "✗ Erro ao contar: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 4: Buscar todas as escolas (igual ao código)
echo "<h2>4. Buscar Todas as Escolas (Query do Código)</h2>";
try {
    $sql = "SELECT id, nome, ativo 
            FROM escola 
            ORDER BY ativo DESC, nome ASC";
    $stmt = $conn->query($sql);
    $todasEscolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total encontrado: <strong>" . count($todasEscolas) . "</strong><br><br>";
    
    if (!empty($todasEscolas)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Ativo</th></tr>";
        foreach ($todasEscolas as $escola) {
            echo "<tr>";
            echo "<td>" . $escola['id'] . "</td>";
            echo "<td>" . htmlspecialchars($escola['nome']) . "</td>";
            echo "<td>" . ($escola['ativo'] == 1 ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "✗ Nenhuma escola encontrada!<br>";
    }
} catch (Exception $e) {
    echo "✗ Erro na query: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<br><br><a href='estoque_nutricionista.php'>Voltar para Estoque Nutricionista</a>";
?>

