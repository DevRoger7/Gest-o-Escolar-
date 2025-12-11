<?php

if(isset($_POST["cpf"]) && isset($_POST["senha"]))
{
    // Aceita CPF ou email
    $cpfOuEmail = trim($_POST["cpf"]);
    $senha = $_POST["senha"];
    require_once("../../Models/autenticacao/modelLogin.php");
    $modelLogin = new ModelLogin();
    $resultado = $modelLogin->login($cpfOuEmail, $senha);
    
    if($resultado)
    {
        // Verificar se é código especial de escola removida
        if (is_array($resultado) && isset($resultado['sem_escola'])) {
            // Redirecionar para página de sem acesso
            header("Location: ../../Views/auth/sem_acesso.php");
            exit();
        }
        
        // Login bem-sucedido - as sessões já foram criadas no model
        // Redireciona para o dashboard
        header("Location: ../../Views/dashboard/dashboard.php");
        exit();
    }
    else
    {
        // Login falhou - redireciona de volta para o login com erro
        header("Location: ../../Views/auth/login.php?erro=1");
        exit();
    }
}
else
{
    // Dados não fornecidos - redireciona para o login
    header("Location: ../../Views/auth/login.php");
    exit();
}

?>