<?php
header('Content-Type: application/json');

try {
    // Conectar ao MySQL sem especificar banco
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ler o arquivo SQL
    $sql = file_get_contents('database_setup.sql');
    
    if ($sql === false) {
        throw new Exception('Não foi possível ler o arquivo database_setup.sql');
    }
    
    // Dividir em comandos individuais
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    $executedCommands = 0;
    $errors = [];
    
    foreach ($commands as $command) {
        if (!empty($command)) {
            try {
                $pdo->exec($command);
                $executedCommands++;
            } catch (PDOException $e) {
                // Ignorar erros de "já existe" mas registrar outros
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Banco de dados configurado com sucesso',
        'comandos_executados' => $executedCommands,
        'erros' => $errors
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
?>