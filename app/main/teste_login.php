<?php
/**
 * Teste do Sistema de Login
 * Demonstra o funcionamento do INNER JOIN entre pessoa e usuario
 */

require_once 'Models/autenticacao/modelLogin.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Login - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-check"></i> Teste do Sistema de Login</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $cpf = $_POST['cpf'] ?? '';
                            $senha = $_POST['senha'] ?? '';
                            
                            if (!empty($cpf) && !empty($senha)) {
                                $modelLogin = new ModelLogin();
                                $resultado = $modelLogin->login($cpf, $senha);
                                
                                if ($resultado['sucesso']) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle"></i> ' . $resultado['mensagem'];
                                    echo '<hr>';
                                    echo '<strong>Dados do Usuário:</strong><br>';
                                    echo 'ID: ' . $resultado['usuario']['id'] . '<br>';
                                    echo 'Nome: ' . $resultado['usuario']['nome'] . '<br>';
                                    echo 'Email: ' . $resultado['usuario']['email'] . '<br>';
                                    echo 'Tipo: ' . ucfirst($resultado['usuario']['tipo']);
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-danger">';
                                    echo '<i class="fas fa-exclamation-triangle"></i> ' . $resultado['mensagem'];
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<i class="fas fa-exclamation-triangle"></i> Por favor, preencha todos os campos.';
                                echo '</div>';
                            }
                        }
                        ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF:</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" 
                                       placeholder="000.000.000-00" maxlength="14" required>
                                <div class="form-text">Digite o CPF com ou sem pontuação</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha:</label>
                                <input type="password" class="form-control" id="senha" name="senha" 
                                       placeholder="Digite sua senha" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Fazer Login
                            </button>
                        </form>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Usuários de Teste:</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>CPF</th>
                                                <th>Email</th>
                                                <th>Tipo</th>
                                                <th>Senha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Administrador do Sistema</td>
                                                <td>000.000.000-00</td>
                                                <td>admin@escola.com</td>
                                                <td>Admin</td>
                                                <td>password</td>
                                            </tr>
                                            <tr>
                                                <td>Maria Silva Santos</td>
                                                <td>111.222.333-44</td>
                                                <td>maria@escola.com</td>
                                                <td>Funcionário</td>
                                                <td>password</td>
                                            </tr>
                                            <tr>
                                                <td>João Carlos Oliveira</td>
                                                <td>555.666.777-88</td>
                                                <td>joao@escola.com</td>
                                                <td>Nutricionista</td>
                                                <td>password</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Como funciona o INNER JOIN:</h6>
                            <p class="mb-0">
                                O sistema agora usa duas tabelas relacionadas:
                            </p>
                            <ul class="mb-0">
                                <li><strong>pessoa:</strong> Contém dados pessoais (nome, CPF, email, etc.)</li>
                <li><strong>usuario:</strong> Contém credenciais (senha, tipo, controle de acesso)</li>
                <li><strong>Relacionamento:</strong> usuario.pessoa_id → pessoa.id</li>
                                <li><strong>Query:</strong> INNER JOIN entre as tabelas para buscar por CPF e validar senha</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success mt-3">
                            <h6><i class="fas fa-shield-alt"></i> Melhorias de Segurança Implementadas:</h6>
                            <ul class="mb-0">
                                <li>Prepared Statements (previne SQL Injection)</li>
                                <li>Hash de senhas com password_verify()</li>
                                <li>Validação de CPF com algoritmo oficial</li>
                                <li>Controle de tentativas de login</li>
                                <li>Bloqueio automático após 5 tentativas</li>
                                <li>Sanitização de dados de entrada</li>
                                <li>Tratamento de erros com logs</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="config/exemplo_uso.php" class="btn btn-secondary">
                        <i class="fas fa-database"></i> Ver Exemplos de Conexão
                    </a>
                    <a href="config/test_connection.php" class="btn btn-info">
                        <i class="fas fa-plug"></i> Testar Conexão BD
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>