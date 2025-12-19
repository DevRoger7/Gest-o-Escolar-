<?php
/**
 * Script para criar usuários para todos os alunos existentes
 * Alunos fazem login com CPF (login e senha)
 */

$basePath = dirname(__DIR__);
require_once($basePath . '/config/Database.php');

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== CRIANDO USUÁRIOS PARA ALUNOS ===\n\n";

try {
    // Buscar todos os alunos que não têm usuário
    $sql = "SELECT a.id as aluno_id, a.pessoa_id, p.cpf, p.nome
            FROM aluno a
            INNER JOIN pessoa p ON a.pessoa_id = p.id
            LEFT JOIN usuario u ON u.pessoa_id = p.id AND u.role = 'ALUNO'
            WHERE u.id IS NULL
            AND a.ativo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($alunos)) {
        echo "✓ Todos os alunos já possuem usuários criados.\n";
        exit;
    }
    
    echo "Encontrados " . count($alunos) . " alunos sem usuário.\n\n";
    
    $conn->beginTransaction();
    $criados = 0;
    $erros = 0;
    
    foreach ($alunos as $aluno) {
        try {
            // Gerar username a partir do CPF (sem formatação)
            $cpfLimpo = preg_replace('/[^0-9]/', '', $aluno['cpf']);
            $username = 'aluno_' . $cpfLimpo;
            
            // Para alunos, a senha é validada comparando CPF diretamente
            // Mas o campo senha_hash é obrigatório, então criamos um hash qualquer
            // (a validação real é feita no modelLogin comparando CPF)
            $senhaHash = password_hash('aluno_cpf', PASSWORD_BCRYPT);
            
            $sqlUsuario = "INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo, email_verificado, created_at)
                          VALUES (:pessoa_id, :username, :senha_hash, 'ALUNO', 1, 1, NOW())";
            
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $aluno['pessoa_id']);
            $stmtUsuario->bindParam(':username', $username);
            $stmtUsuario->bindParam(':senha_hash', $senhaHash);
            $stmtUsuario->execute();
            
            $criados++;
            echo "✓ Usuário criado para: {$aluno['nome']} (CPF: {$aluno['cpf']})\n";
            
        } catch (PDOException $e) {
            $erros++;
            echo "✗ Erro ao criar usuário para {$aluno['nome']}: " . $e->getMessage() . "\n";
        }
    }
    
    $conn->commit();
    
    echo "\n=== RESULTADO ===\n";
    echo "✓ Usuários criados: {$criados}\n";
    if ($erros > 0) {
        echo "✗ Erros: {$erros}\n";
    }
    
    echo "\n=== CREDENCIAIS DE ACESSO ===\n";
    echo "Os alunos podem fazer login usando:\n";
    echo "- Login: CPF (com ou sem formatação)\n";
    echo "- Senha: CPF (com ou sem formatação)\n\n";
    
    echo "=== CONCLUÍDO COM SUCESSO! ===\n";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>

