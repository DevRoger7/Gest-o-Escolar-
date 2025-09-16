<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Sistema de Gestão Escolar'; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo isset($title) ? htmlspecialchars($title) : 'Sistema de Gestão Escolar'; ?></h1>
        
        <?php if (isset($message)): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <p>A estrutura MVC foi criada com sucesso! O sistema está pronto para desenvolvimento.</p>
        
        <h2>Estrutura Criada:</h2>
        <ul>
            <li>✅ Padrão MVC implementado</li>
            <li>✅ Sistema de roteamento</li>
            <li>✅ Conexão com banco de dados</li>
            <li>✅ Controllers base</li>
            <li>✅ Models base</li>
            <li>✅ Configurações de segurança</li>
        </ul>
    </div>
</body>
</html>