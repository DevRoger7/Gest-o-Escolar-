<?php
/**
 * Script para filtrar arquivo SQL removendo habilidades já cadastradas no banco
 */

require_once(__DIR__ . '/../config/Database.php');

$db = Database::getInstance();
$conn = $db->getConnection();

// Arquivo SQL de entrada
$arquivoEntrada = __DIR__ . '/inserir_habilidades_faltantes_2025-12-16_00-47-27.sql';

if (!file_exists($arquivoEntrada)) {
    die("Arquivo não encontrado: $arquivoEntrada\n");
}

echo "Lendo arquivo SQL...\n";
$conteudoSQL = file_get_contents($arquivoEntrada);

// Extrair todos os INSERT statements
preg_match_all("/INSERT INTO habilidades_bncc \(codigo_bncc, etapa, componente, ano_inicio, ano_fim, descricao\) VALUES \('([^']+)', '([^']+)', '([^']+)', (\d+), (\d+), '([^']+)'\);/", $conteudoSQL, $matches, PREG_SET_ORDER);

echo "Encontrados " . count($matches) . " INSERT statements no arquivo.\n";

// Verificar quais já existem no banco
echo "Verificando quais habilidades já estão cadastradas no banco...\n";

$sqlCheck = "SELECT COUNT(*) as total FROM habilidades_bncc WHERE codigo_bncc = :codigo";
$stmtCheck = $conn->prepare($sqlCheck);

$insertsValidos = [];
$duplicadas = [];

foreach ($matches as $match) {
    $codigo = $match[1];
    $etapa = $match[2];
    $componente = $match[3];
    $anoInicio = $match[4];
    $anoFim = $match[5];
    $descricao = $match[6];
    
    $stmtCheck->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmtCheck->execute();
    $resultado = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado['total'] > 0) {
        $duplicadas[] = $codigo;
    } else {
        // Escapar a descrição para SQL
        $descricaoEscapada = addslashes($descricao);
        $insertsValidos[] = "INSERT INTO habilidades_bncc (codigo_bncc, etapa, componente, ano_inicio, ano_fim, descricao) VALUES ('$codigo', '$etapa', '$componente', $anoInicio, $anoFim, '$descricaoEscapada');";
    }
}

echo "\n========================================\n";
echo "RESULTADO DA VERIFICAÇÃO\n";
echo "========================================\n";
echo "Total de INSERTs no arquivo: " . count($matches) . "\n";
echo "Habilidades já cadastradas (duplicadas): " . count($duplicadas) . "\n";
echo "Habilidades realmente faltantes: " . count($insertsValidos) . "\n";

if (!empty($duplicadas)) {
    echo "\nCódigos duplicados encontrados:\n";
    foreach ($duplicadas as $codigo) {
        echo "  - $codigo\n";
    }
}

// Gerar novo arquivo SQL apenas com as válidas
$sqlNovo = "-- Script SQL para inserir habilidades faltantes da BNCC\n";
$sqlNovo .= "-- Ensino Fundamental - Anos Iniciais\n";
$sqlNovo .= "-- Gerado em: " . date('d/m/Y H:i:s') . "\n";
$sqlNovo .= "-- Total de habilidades a inserir: " . count($insertsValidos) . "\n";
$sqlNovo .= "-- Habilidades já cadastradas (removidas): " . count($duplicadas) . "\n\n";

if (!empty($duplicadas)) {
    $sqlNovo .= "-- Códigos já cadastrados (não serão inseridos):\n";
    foreach ($duplicadas as $codigo) {
        $sqlNovo .= "--   - $codigo\n";
    }
    $sqlNovo .= "\n";
}

$sqlNovo .= "START TRANSACTION;\n\n";

foreach ($insertsValidos as $insert) {
    $sqlNovo .= $insert . "\n";
}

$sqlNovo .= "\nCOMMIT;\n";

$arquivoSaida = __DIR__ . '/inserir_habilidades_faltantes_filtrado_' . date('Y-m-d_H-i-s') . '.sql';
file_put_contents($arquivoSaida, $sqlNovo);

echo "\n========================================\n";
echo "ARQUIVO SQL GERADO COM SUCESSO!\n";
echo "========================================\n";
echo "Arquivo: $arquivoSaida\n";
echo "Total de INSERT statements: " . count($insertsValidos) . "\n";
echo "\nEste arquivo contém apenas as habilidades que realmente faltam no banco.\n";
echo "As duplicadas foram removidas automaticamente.\n";
echo "\nVocê pode executar este arquivo SQL sem erros de duplicação.\n";

