<?php
/**
 * Script de diagnóstico para problemas de login
 * Verifica conexão, tabelas e estrutura
 */

require_once(__DIR__ . '/../config/Database.php');

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Diagnóstico do Sistema de Login</h2>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✓ Conexão com banco de dados estabelecida\n";
    echo "Banco de dados: escola_merenda\n\n";
    
    // Verificar se a tabela usuario existe
    $sql = "SHOW TABLES LIKE 'usuario'";
    $stmt = $conn->query($sql);
    $tabelaExiste = $stmt->rowCount() > 0;
    
    if ($tabelaExiste) {
        echo "✓ Tabela 'usuario' existe (segundo SHOW TABLES)\n\n";
        
        // Verificar estrutura da tabela
        echo "Estrutura da tabela 'usuario':\n";
        try {
            $sql = "DESCRIBE usuario";
            $stmt = $conn->query($sql);
            $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            foreach ($campos as $campo) {
                echo "  - {$campo['Field']} ({$campo['Type']})";
                if ($campo['Null'] === 'NO') echo " NOT NULL";
                if ($campo['Key'] === 'PRI') echo " PRIMARY KEY";
                if ($campo['Key'] === 'UNI') echo " UNIQUE";
                echo "\n";
            }
            
            echo "\n";
        } catch (PDOException $e) {
            echo "✗ ERRO: Não foi possível acessar a estrutura da tabela!\n";
            echo "  Erro: " . $e->getMessage() . "\n";
            echo "  Isso indica que a tabela pode estar corrompida.\n";
            echo "  Execute o script: reparar_tabela_usuario.php\n\n";
            throw $e;
        }
        
        // Verificar se há registros
        $sql = "SELECT COUNT(*) as total FROM usuario";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de usuários cadastrados: {$result['total']}\n\n";
        
        // Verificar se a tabela pessoa existe
        $sql = "SHOW TABLES LIKE 'pessoa'";
        $stmt = $conn->query($sql);
        $pessoaExiste = $stmt->rowCount() > 0;
        
        if ($pessoaExiste) {
            echo "✓ Tabela 'pessoa' existe\n\n";
            
            // Testar query de login
            echo "Testando query de login (busca por CPF):\n";
            $cpfTeste = '12345678901';
            $sql = "SELECT u.*, p.* FROM usuario u 
                    INNER JOIN pessoa p ON u.pessoa_id = p.id 
                    WHERE p.cpf = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$cpfTeste]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado) {
                echo "✓ Query de login funciona corretamente\n";
                echo "  Usuário encontrado: {$resultado['username']}\n";
            } else {
                echo "⚠ Query executou, mas não encontrou usuário com CPF: $cpfTeste\n";
                echo "  (Isso é normal se não houver usuário com esse CPF)\n";
            }
            
        } else {
            echo "✗ ERRO: Tabela 'pessoa' não existe!\n";
            echo "  A tabela 'pessoa' é necessária para o login funcionar.\n";
        }
        
    } else {
        echo "✗ ERRO: Tabela 'usuario' não existe!\n";
        echo "  Execute o script create_table_usuario.sql para criar a tabela.\n";
    }
    
    // Verificar índices
    echo "\nÍndices da tabela 'usuario':\n";
    $sql = "SHOW INDEX FROM usuario";
    $stmt = $conn->query($sql);
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($indices)) {
        echo "  ⚠ Nenhum índice encontrado\n";
    } else {
        foreach ($indices as $indice) {
            echo "  - {$indice['Key_name']} ({$indice['Column_name']})\n";
        }
    }
    
    // Verificar permissões do usuário do banco
    echo "\nVerificando permissões do usuário do banco:\n";
    $sql = "SHOW GRANTS";
    $stmt = $conn->query($sql);
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($permissoes as $permissao) {
        echo "  " . $permissao['Grants'] . "\n";
    }
    
    echo "\n✓ Diagnóstico concluído!\n";
    
} catch (PDOException $e) {
    echo "✗ ERRO na conexão ou consulta:\n";
    echo "  Código: " . $e->getCode() . "\n";
    echo "  Mensagem: " . $e->getMessage() . "\n";
    echo "  Arquivo: " . $e->getFile() . "\n";
    echo "  Linha: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "✗ ERRO geral:\n";
    echo "  " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

