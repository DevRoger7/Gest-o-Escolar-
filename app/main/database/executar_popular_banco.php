<?php
/**
 * Script para executar o SQL de popular banco de dados
 * Execute via navegador ou linha de comando
 */

// Ajustar caminho baseado na localização do arquivo
$basePath = dirname(__DIR__);
require_once($basePath . '/config/Database.php');

// Verificar se está em ambiente de desenvolvimento
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isDev = strpos($host, 'localhost') !== false || 
         strpos($host, '127.0.0.1') !== false || 
         strpos($host, 'dev') !== false ||
         php_sapi_name() === 'cli';

if (!$isDev) {
    die('Este script só pode ser executado em ambiente de desenvolvimento!');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Ler o arquivo SQL (usar versão final)
$sqlFile = __DIR__ . '/popular_banco_teste_FINAL.sql';
if (!file_exists($sqlFile)) {
    // Fallback para versão corrigida
    $sqlFile = __DIR__ . '/popular_banco_teste_corrigido.sql';
    if (!file_exists($sqlFile)) {
        // Fallback para versão original
        $sqlFile = __DIR__ . '/popular_banco_teste.sql';
    }
}
if (!file_exists($sqlFile)) {
    die("Arquivo SQL não encontrado: $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Remover comentários e dividir em comandos
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Dividir por ponto e vírgula, mas manter dentro de strings
$commands = [];
$current = '';
$inString = false;
$stringChar = '';

for ($i = 0; $i < strlen($sql); $i++) {
    $char = $sql[$i];
    
    if (!$inString && ($char === '"' || $char === "'")) {
        $inString = true;
        $stringChar = $char;
        $current .= $char;
    } elseif ($inString && $char === $stringChar) {
        // Verificar se não é escape
        if ($i > 0 && $sql[$i-1] !== '\\') {
            $inString = false;
            $stringChar = '';
        }
        $current .= $char;
    } elseif (!$inString && $char === ';') {
        $cmd = trim($current);
        if (!empty($cmd)) {
            $commands[] = $cmd;
        }
        $current = '';
    } else {
        $current .= $char;
    }
}

if (!empty(trim($current))) {
    $commands[] = trim($current);
}

echo "=== POPULANDO BANCO DE DADOS COM DADOS DE TESTE ===\n\n";

$totalCommands = count($commands);
$executados = 0;
$erros = 0;
$contadores = [];

try {
    $conn->beginTransaction();
    
    foreach ($commands as $index => $command) {
        $command = trim($command);
        
        // Pular comandos vazios ou apenas SET
        if (empty($command) || 
            stripos($command, 'SET ') === 0 || 
            stripos($command, 'START TRANSACTION') === 0 ||
            stripos($command, 'COMMIT') === 0 ||
            stripos($command, 'SET FOREIGN_KEY_CHECKS') === 0) {
            continue;
        }
        
        try {
            // Detectar tipo de comando para contagem
            if (stripos($command, 'INSERT INTO `escola`') !== false) {
                $tipo = 'escolas';
            } elseif (stripos($command, 'INSERT INTO `serie`') !== false) {
                $tipo = 'series';
            } elseif (stripos($command, 'INSERT INTO `disciplina`') !== false) {
                $tipo = 'disciplinas';
            } elseif (stripos($command, 'INSERT INTO `turma`') !== false) {
                $tipo = 'turmas';
            } elseif (stripos($command, 'INSERT INTO `pessoa`') !== false) {
                $tipo = 'pessoas';
            } elseif (stripos($command, 'INSERT INTO `usuario`') !== false) {
                $tipo = 'usuarios';
            } elseif (stripos($command, 'INSERT INTO `aluno`') !== false) {
                $tipo = 'alunos';
            } elseif (stripos($command, 'INSERT INTO `professor`') !== false) {
                $tipo = 'professores';
            } elseif (stripos($command, 'INSERT INTO `gestor`') !== false) {
                $tipo = 'gestores';
            } elseif (stripos($command, 'INSERT INTO `funcionario`') !== false) {
                $tipo = 'funcionarios';
            } elseif (stripos($command, 'INSERT INTO `professor_lotacao`') !== false) {
                $tipo = 'lotacoes_professores';
            } elseif (stripos($command, 'INSERT INTO `gestor_lotacao`') !== false) {
                $tipo = 'lotacoes_gestores';
            } elseif (stripos($command, 'INSERT INTO `funcionario_lotacao`') !== false) {
                $tipo = 'lotacoes_funcionarios';
            } elseif (stripos($command, 'INSERT INTO `aluno_turma`') !== false) {
                $tipo = 'aluno_turma';
            } elseif (stripos($command, 'INSERT INTO `turma_professor`') !== false) {
                $tipo = 'turma_professor';
            } elseif (stripos($command, 'INSERT INTO `nota`') !== false) {
                $tipo = 'notas';
            } elseif (stripos($command, 'INSERT INTO `frequencia`') !== false) {
                $tipo = 'frequencias';
            } elseif (stripos($command, 'INSERT INTO `plano_aula`') !== false) {
                $tipo = 'planos_aula';
            } elseif (stripos($command, 'INSERT INTO `habilidades_bncc`') !== false) {
                $tipo = 'habilidades_bncc';
            } else {
                $tipo = 'outros';
            }
            
            $stmt = $conn->prepare($command);
            $stmt->execute();
            
            $rows = $stmt->rowCount();
            if (!isset($contadores[$tipo])) {
                $contadores[$tipo] = 0;
            }
            $contadores[$tipo] += $rows;
            
            $executados++;
            
            if ($executados % 10 == 0) {
                echo "Processando... ($executados/$totalCommands comandos)\n";
            }
            
        } catch (PDOException $e) {
            // Ignorar erros de duplicata (ON DUPLICATE KEY UPDATE)
            if (strpos($e->getMessage(), 'Duplicate entry') === false && 
                strpos($e->getMessage(), '1062') === false) {
                $erros++;
                echo "ERRO no comando " . ($index + 1) . ": " . $e->getMessage() . "\n";
                echo "Comando: " . substr($command, 0, 100) . "...\n\n";
            }
        }
    }
    
    $conn->commit();
    
    echo "\n=== RESULTADO ===\n\n";
    echo "✓ Comandos executados: $executados\n";
    echo "✗ Erros: $erros\n\n";
    
    echo "=== DADOS INSERIDOS ===\n";
    foreach ($contadores as $tipo => $qtd) {
        if ($qtd > 0) {
            echo "  $tipo: $qtd\n";
        }
    }
    
    echo "\n=== CREDENCIAIS DE ACESSO ===\n";
    echo "Admin: username: admin, senha: 123456\n";
    echo "Gestor: username: roberto.alves, senha: 123456\n";
    echo "Professor: username: maria.silva, senha: 123456\n";
    echo "\n=== CONCLUÍDO COM SUCESSO! ===\n";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "\n✗ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

