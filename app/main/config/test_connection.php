<?php
header('Content-Type: application/json');

try {
    require_once 'Database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Testar se as tabelas existem
    $tables = ['pessoas', 'usuarios'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $existingTables[] = $table;
        }
    }
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Conexão com banco de dados estabelecida',
        'tabelas_existentes' => $existingTables,
        'total_tabelas' => count($existingTables)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
?>