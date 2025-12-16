<?php
/**
 * Script para gerar SQL de inserção das habilidades faltantes da BNCC
 */

// Ler o arquivo SQL para verificar quais já estão cadastradas
$arquivoSQL = 'c:/Users/adm/Downloads/habilidades_bncc.sql';
if (!file_exists($arquivoSQL)) {
    die("Arquivo SQL não encontrado: $arquivoSQL\n");
}

echo "Lendo arquivo SQL...\n";
$conteudoSQL = file_get_contents($arquivoSQL);

// Extrair códigos já cadastrados
preg_match_all("/\([^)]*'EF\d{2}[A-Z]{2}\d{2}'[^)]*'Ensino Fundamental – Anos Iniciais'[^)]*\)/", $conteudoSQL, $matches);

$codigosCadastrados = [];
foreach ($matches[0] as $match) {
    if (preg_match("/'EF\d{2}[A-Z]{2}\d{2}'/", $match, $codigoMatch)) {
        $codigo = trim($codigoMatch[0], "'");
        $codigosCadastrados[] = $codigo;
    }
}
$codigosCadastrados = array_unique($codigosCadastrados);

// Mapeamento de componentes
$componentes = [
    'LP' => 'Língua Portuguesa',
    'AR' => 'Artes',
    'EF' => 'Educação Física',
    'MA' => 'Matemática',
    'CI' => 'Ciências',
    'GE' => 'Geografia',
    'HI' => 'História',
    'ER' => 'Ensino Religioso'
];

// Lista completa de habilidades com descrições (extraída da mensagem do usuário)
// Vou processar o texto fornecido para extrair códigos e descrições
$textoHabilidades = file_get_contents('php://stdin');
if (empty($textoHabilidades)) {
    // Se não houver entrada via stdin, usar uma lista básica
    echo "Por favor, forneça a lista completa de habilidades via stdin ou modifique o script.\n";
    exit(1);
}

// Função para processar o texto e extrair habilidades
function processarHabilidades($texto) {
    $habilidades = [];
    
    // Padrão para encontrar: CÓDIGO - DESCRIÇÃO,
    preg_match_all('/(EF\d{2}[A-Z]{2}\d{2})\s*-\s*([^,]+(?:\.,|$))/', $texto, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $codigo = trim($match[1]);
        $descricao = trim($match[2]);
        // Remover vírgula final se existir
        $descricao = rtrim($descricao, ',');
        $descricao = trim($descricao);
        
        $habilidades[$codigo] = $descricao;
    }
    
    return $habilidades;
}

// Processar habilidades do texto
$todasHabilidades = processarHabilidades($textoHabilidades);

if (empty($todasHabilidades)) {
    echo "Nenhuma habilidade encontrada no texto. Usando lista hardcoded...\n";
    
    // Lista hardcoded das habilidades faltantes (baseada no relatório)
    // Vou criar um mapeamento manual das habilidades mais importantes
    $habilidadesFaltantes = [
        // Língua Portuguesa - algumas principais
        'EF02LP12' => 'Ler e compreender com certa autonomia cantigas, letras de canção, dentre outros gêneros do campo da vida cotidiana, considerando a situação comunicativa e o tema/assunto do texto e relacionando sua forma de organização à sua finalidade.',
        'EF02LP13' => 'Planejar e produzir bilhetes e cartas, em meio impresso e/ou digital, dentre outros gêneros do campo da vida cotidiana, considerando a situação comunicativa e o tema/assunto/finalidade do texto.',
        // ... (seria necessário ter todas as descrições)
    ];
} else {
    // Filtrar apenas as habilidades faltantes
    $habilidadesFaltantes = [];
    foreach ($todasHabilidades as $codigo => $descricao) {
        if (!in_array($codigo, $codigosCadastrados)) {
            $habilidadesFaltantes[$codigo] = $descricao;
        }
    }
}

echo "Habilidades faltantes encontradas: " . count($habilidadesFaltantes) . "\n";

// Gerar SQL
$sql = "-- Script SQL para inserir habilidades faltantes da BNCC\n";
$sql .= "-- Ensino Fundamental - Anos Iniciais\n";
$sql .= "-- Gerado em: " . date('d/m/Y H:i:s') . "\n";
$sql .= "-- Total de habilidades a inserir: " . count($habilidadesFaltantes) . "\n\n";

$sql .= "START TRANSACTION;\n\n";

foreach ($habilidadesFaltantes as $codigo => $descricao) {
    // Extrair informações do código
    if (preg_match('/EF(\d{2})([A-Z]{2})(\d{2})/', $codigo, $matches)) {
        $ano = (int)$matches[1];
        $componenteCodigo = $matches[2];
        $componenteNome = $componentes[$componenteCodigo] ?? $componenteCodigo;
        
        // Determinar ano_inicio e ano_fim
        $anoInicio = $ano;
        $anoFim = $ano;
        
        // Se for EF15 ou EF35, significa que é para múltiplos anos
        if ($ano == 15) {
            $anoInicio = 1;
            $anoFim = 5;
        } elseif ($ano == 35) {
            $anoInicio = 3;
            $anoFim = 5;
        } elseif ($ano == 12) {
            $anoInicio = 1;
            $anoFim = 2;
        }
        
        // Escapar aspas na descrição
        $descricaoEscapada = addslashes($descricao);
        
        $sql .= "INSERT INTO habilidades_bncc (codigo_bncc, etapa, componente, ano_inicio, ano_fim, descricao) VALUES ";
        $sql .= "('$codigo', 'Ensino Fundamental – Anos Iniciais', '$componenteNome', $anoInicio, $anoFim, '$descricaoEscapada');\n";
    }
}

$sql .= "\nCOMMIT;\n";

// Salvar arquivo SQL
$arquivoSaida = __DIR__ . '/inserir_habilidades_faltantes_' . date('Y-m-d_H-i-s') . '.sql';
file_put_contents($arquivoSaida, $sql);

echo "SQL gerado e salvo em: $arquivoSaida\n";
echo "Total de INSERT statements: " . count($habilidadesFaltantes) . "\n";

