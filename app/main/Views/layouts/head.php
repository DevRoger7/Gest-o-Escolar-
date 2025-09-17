<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Sistema de Gestão Escolar'; ?></title>
    
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon.png" type="image/png">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon.png">
    
    <meta name="description" content="Sistema de Gestão Escolar - Centralize e automatize todos os processos acadêmicos e de merenda">
    <meta name="keywords" content="gestão escolar, educação, merenda, frequência, notas">
    <meta name="author" content="Sistema de Gestão Escolar">
    
    <meta property="og:title" content="<?php echo isset($title) ? $title : 'Sistema de Gestão Escolar'; ?>">
    <meta property="og:description" content="Sistema de Gestão Escolar - Centralize e automatize todos os processos acadêmicos e de merenda">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/icons/favicon.png">
    <meta property="og:type" content="website">
    
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo isset($title) ? $title : 'Sistema de Gestão Escolar'; ?>">
    <meta name="twitter:description" content="Sistema de Gestão Escolar - Centralize e automatize todos os processos acadêmicos e de merenda">
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>/assets/icons/favicon.png">
