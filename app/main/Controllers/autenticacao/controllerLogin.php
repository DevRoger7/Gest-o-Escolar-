<?php

if(isset($_POST["cpf"]) && isset($_POST["senha"]))
{
    // Remove pontos e hífens do CPF, mantendo apenas números
    $cpf = preg_replace('/[^0-9]/', '', $_POST["cpf"]);
    $senha = $_POST["senha"];
    require_once("../../Models/autenticacao/modelLogin.php");
    $modelLogin = new ModelLogin();
    $resultado = $modelLogin->login($cpf, $senha);
    
    if($resultado)
    {
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