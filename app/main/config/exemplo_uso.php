<?php
/**
 * Exemplo de Uso da Conexão com Banco de Dados
 * Sistema de Gestão Escolar - Merenda
 */

// Inclui o arquivo de inicialização
require_once 'init.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemplo de Uso - Conexão BD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h1 class="text-center mb-4">Sistema de Gestão Escolar</h1>
                <h2 class="text-center mb-4">Exemplo de Uso da Conexão</h2>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Status da Conexão</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Testa a conexão
                            $db = getDatabase();
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-check-circle"></i> ';
                            echo 'Conexão com banco de dados estabelecida com sucesso!';
                            echo '</div>';
                            
                            // Exemplo de consulta
                            echo '<h6>Exemplo de Consulta:</h6>';
                            echo '<pre class="bg-light p-3">';
                            echo '$usuarios = dbQuery("SELECT * FROM usuario WHERE ativo = ?", [true]);';
                            echo '</pre>';
                            
                            // Tenta executar uma consulta simples
                            $result = dbQuery("SELECT COUNT(*) as total FROM usuario");
                            if ($result) {
                                echo '<div class="alert alert-info">';
                                echo 'Total de usuários cadastrados: ' . $result[0]['total'];
                                echo '</div>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-warning">';
                            echo '<i class="fas fa-exclamation-triangle"></i> ';
                            echo 'Erro de conexão: ' . $e->getMessage();
                            echo '</div>';
                            
                            echo '<div class="alert alert-info">';
                            echo '<strong>Para configurar o banco de dados:</strong><br>';
                            echo '1. Certifique-se que o MySQL/XAMPP está rodando<br>';
                            echo '2. Configure as credenciais em config.php<br>';
                            echo '3. Execute o script database_setup.sql<br>';
                            echo '4. Teste novamente a conexão';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Exemplos de Código</h5>
                    </div>
                    <div class="card-body">
                        <h6>1. Consultar Dados:</h6>
                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars('<?php
// Buscar todos os usuários ativos
$usuarios = dbQuery("SELECT * FROM usuario WHERE ativo = ?", [true]);

foreach ($usuarios as $usuario) {
    echo "Nome: " . $usuario["nome"] . "<br>";
    echo "Email: " . $usuario["email"] . "<br><br>";
}
?>'); ?></code></pre>

                        <h6>2. Inserir Dados:</h6>
                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars('<?php
// Inserir novo usuário
$sucesso = dbExecute("INSERT INTO usuario (nome, email, senha, tipo, ativo) VALUES (?, ?, ?, ?, ?)", [
    "João Silva",
    "joao@escola.com", 
    hashPassword("123456"),
    "funcionario",
    true
]);

if ($sucesso) {
    $ultimoId = dbLastInsertId();
    echo "Usuário criado com ID: " . $ultimoId;
}
?>'); ?></code></pre>

                        <h6>3. Atualizar Dados:</h6>
                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars('<?php
// Atualizar dados do usuário
$sucesso = dbExecute("UPDATE usuario SET nome = ?, email = ? WHERE id = ?", [
    "João Santos Silva",
    "joao.santos@escola.com",
    1
]);

if ($sucesso) {
    echo "Usuário atualizado com sucesso!";
}
?>'); ?></code></pre>

                        <h6>4. Usar Transações:</h6>
                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars('<?php
$db = getDatabase();

try {
    $db->beginTransaction();
    
    // Inserir escola
    dbExecute("INSERT INTO escolas (nome, endereco) VALUES (?, ?)", [
        "Escola Municipal ABC",
        "Rua das Flores, 123"
    ]);
    $escolaId = dbLastInsertId();
    
    // Inserir estoque inicial
    dbExecute("INSERT INTO estoque (escola_id, alimento_id, quantidade) VALUES (?, ?, ?)", [
        $escolaId,
        1, // ID do alimento
        100 // Quantidade inicial
    ]);
    
    $db->commit();
    echo "Escola e estoque criados com sucesso!";
    
} catch (Exception $e) {
    $db->rollback();
    echo "Erro: " . $e->getMessage();
}
?>'); ?></code></pre>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Funções Helper Disponíveis</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Banco de Dados:</h6>
                                <ul>
                                    <li><code>getDatabase()</code></li>
                                    <li><code>dbQuery($sql, $params)</code></li>
                                    <li><code>dbExecute($sql, $params)</code></li>
                                    <li><code>dbLastInsertId()</code></li>
                                </ul>
                                
                                <h6>Segurança:</h6>
                                <ul>
                                    <li><code>sanitize($data)</code></li>
                                    <li><code>hashPassword($password)</code></li>
                                    <li><code>verifyPassword($password, $hash)</code></li>
                                    <li><code>isValidEmail($email)</code></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Utilitários:</h6>
                                <ul>
                                    <li><code>redirect($url)</code></li>
                                    <li><code>showMessage($message, $type)</code></li>
                                </ul>
                                
                                <h6>Configurações:</h6>
                                <ul>
                                    <li><code>APP_NAME</code> - Nome da aplicação</li>
                                    <li><code>DEBUG_MODE</code> - Modo debug</li>
                                    <li><code>SESSION_NAME</code> - Nome da sessão</li>
                                    <li><code>DB_*</code> - Configurações do banco</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="test_connection.php" class="btn btn-primary">Testar Conexão</a>
                    <a href="../" class="btn btn-secondary">Voltar ao Sistema</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>