<?php
/**
 * Gerador de Hash de Senha para SIGAE
 * Use este arquivo para gerar hashes de senha para inserir no banco
 */

// Senha que você quer usar
$senha = '123456'; // ALTERE AQUI para a senha desejada

// Gerar hash
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "========================================\n";
echo "SENHA: " . $senha . "\n";
echo "HASH:  " . $hash . "\n";
echo "========================================\n\n";

echo "Use este hash no SQL:\n";
echo "INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) \n";
echo "VALUES (\n";
echo "    @pessoa_id,\n";
echo "    'username',\n";
echo "    '" . $hash . "',\n";
echo "    'ALUNO',\n";
echo "    1\n";
echo ");\n\n";

// Verificar se funciona
echo "Para verificar a senha no PHP:\n";
echo "password_verify('" . $senha . "', '" . $hash . "');\n";
echo "// Retorna: " . (password_verify($senha, $hash) ? 'true' : 'false') . "\n";

?>

