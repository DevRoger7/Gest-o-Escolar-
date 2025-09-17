<?php
/**
 * Teste de Conexão com Banco de Dados
 * Sistema de Gestão Escolar - Merenda
 */

// Incluir arquivos de configuração
require_once 'config.php';
require_once 'Database.php';

echo "<h2>Teste de Conexão com Banco de Dados</h2>";
echo "<hr>";

try {
    // Testar conexão
    echo "<p><strong>Tentando conectar ao banco de dados...</strong></p>";
    
    $db = Database::getInstance();
    
    if ($db->isConnected()) {
        echo "<p style='color: green;'>✅ <strong>Conexão estabelecida com sucesso!</strong></p>";
        
        // Informações da conexão
        echo "<h3>Informações da Conexão:</h3>";
        echo "<ul>";
        echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
        echo "<li><strong>Banco:</strong> " . DB_NAME . "</li>";
        echo "<li><strong>Usuário:</strong> " . DB_USER . "</li>";
        echo "<li><strong>Charset:</strong> " . DB_CHARSET . "</li>";
        echo "<li><strong>Collation:</strong> " . DB_COLLATION . "</li>";
        echo "</ul>";
        
        // Testar uma consulta simples
        echo "<h3>Teste de Consulta:</h3>";
        try {
            $result = $db->query("SELECT VERSION() as version");
            if ($result && count($result) > 0) {
                echo "<p style='color: green;'>✅ <strong>Versão do MySQL:</strong> " . $result[0]['version'] . "</p>";
            }
            
            // Testar se o banco existe
            $result = $db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [DB_NAME]);
            if ($result && count($result) > 0) {
                echo "<p style='color: green;'>✅ <strong>Banco de dados '" . DB_NAME . "' encontrado!</strong></p>";
            } else {
                echo "<p style='color: orange;'>⚠️ <strong>Banco de dados '" . DB_NAME . "' não encontrado. Você precisa criá-lo.</strong></p>";
                echo "<p><strong>Execute este comando no MySQL:</strong></p>";
                echo "<code style='background: #f4f4f4; padding: 10px; display: block;'>CREATE DATABASE " . DB_NAME . " CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_COLLATION . ";</code>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ <strong>Erro na consulta:</strong> " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Falha na conexão!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Erro de conexão:</strong> " . $e->getMessage() . "</p>";
    
    echo "<h3>Possíveis soluções:</h3>";
    echo "<ul>";
    echo "<li>Verifique se o MySQL/XAMPP está rodando</li>";
    echo "<li>Confirme as credenciais do banco de dados</li>";
    echo "<li>Verifique se o banco '" . DB_NAME . "' existe</li>";
    echo "<li>Confirme as permissões do usuário</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><em>Teste realizado em: " . date('d/m/Y H:i:s') . "</em></p>";
?>