<?php
// Script para gerar hash da senha "123456"
echo "Hash da senha '123456': " . password_hash('123456', PASSWORD_DEFAULT) . PHP_EOL;
echo "Hash da senha 'password': " . password_hash('password', PASSWORD_DEFAULT) . PHP_EOL;
?>
