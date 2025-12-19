<?php
$basePath = dirname(__DIR__);
require_once($basePath . '/config/Database.php');

$db = Database::getInstance();
$conn = $db->getConnection();

// Hash válido para senha "123456"
$hashValido = '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC';

// Verificar se o hash está correto
if (!password_verify('123456', $hashValido)) {
    die("ERRO: Hash inválido!\n");
}

echo "=== ATUALIZANDO SENHAS DOS USUÁRIOS ===\n\n";

try {
    // Atualizar todos os usuários com o hash válido
    $sql = "UPDATE usuario SET senha_hash = :hash WHERE role IN ('ADM', 'GESTAO', 'PROFESSOR')";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hash', $hashValido);
    $stmt->execute();
    
    $linhasAfetadas = $stmt->rowCount();
    echo "✓ Senhas atualizadas: {$linhasAfetadas} usuários\n\n";
    
    // Listar os usuários atualizados
    $sql = "SELECT u.id, u.username, u.role, p.nome, p.cpf 
            FROM usuario u 
            INNER JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.role IN ('ADM', 'GESTAO', 'PROFESSOR')
            ORDER BY u.role, p.nome";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== USUÁRIOS ATUALIZADOS ===\n";
    foreach ($usuarios as $usuario) {
        echo "ID: {$usuario['id']} | {$usuario['role']} | {$usuario['nome']} | CPF: {$usuario['cpf']} | Username: {$usuario['username']}\n";
    }
    
    echo "\n=== CREDENCIAIS DE ACESSO ===\n";
    echo "Senha padrão para todos: 123456\n";
    echo "Login pode ser feito com CPF ou email\n\n";
    
    echo "=== CONCLUÍDO COM SUCESSO! ===\n";
    
} catch (PDOException $e) {
    die("ERRO: " . $e->getMessage() . "\n");
}
?>

