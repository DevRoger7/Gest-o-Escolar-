<?php
require_once 'app/main/config/Database.php';

$database = Database::getInstance();
$conn = $database->getConnection();

// Buscar o hash da senha do usuário Roger
$stmt = $conn->prepare("SELECT u.username, u.senha_hash, p.cpf FROM usuario u JOIN pessoa p ON u.pessoa_id = p.id WHERE u.username = 'Roger'");
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    echo "Usuário encontrado:\n";
    echo "Username: " . $usuario['username'] . "\n";
    echo "CPF: " . $usuario['cpf'] . "\n";
    echo "Hash da senha: " . $usuario['senha_hash'] . "\n\n";
    
    // Testar várias senhas possíveis
    $senhas_teste = ['password', '123456', 'admin', 'roger', 'Roger', '12345'];
    
    foreach ($senhas_teste as $senha) {
        $resultado = password_verify($senha, $usuario['senha_hash']);
        echo "Senha '$senha': " . ($resultado ? "CORRETA" : "incorreta") . "\n";
    }
} else {
    echo "Usuário não encontrado\n";
}
?>