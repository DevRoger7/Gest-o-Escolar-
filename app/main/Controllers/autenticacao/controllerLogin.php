<?php

if(isset($_POST["cpf"]) && isset($_POST["senha"]))
{
    $cpf = $_POST["cpf"];
    $senha = $_POST["senha"];
    require_once("../../Models/autenticacao/modelLogin.php");
    $modelLogin = new ModelLogin();
    $resultado = $modelLogin->login($cpf, $senha);
    if($resultado)
    {
        echo "logado";
    }
    else
    {
        echo "CPF ou senha incorretos";
    }
}

?>