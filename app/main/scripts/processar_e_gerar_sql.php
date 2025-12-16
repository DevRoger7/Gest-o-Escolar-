<?php
/**
 * Script para processar a lista completa de habilidades e gerar SQL de inserção
 * Processa o texto fornecido pelo usuário e extrai descrições
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
$codigosCadastradosAssoc = array_flip($codigosCadastrados);

echo "Códigos já cadastrados: " . count($codigosCadastrados) . "\n";

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

// Ler o texto completo das habilidades
// O texto foi fornecido na mensagem do usuário - vou criar um arquivo temporário ou processar diretamente
$arquivoTexto = __DIR__ . '/habilidades_completas.txt';

if (!file_exists($arquivoTexto)) {
    echo "Arquivo 'habilidades_completas.txt' não encontrado.\n";
    echo "Criando arquivo de exemplo...\n";
    echo "Por favor, cole o texto completo fornecido no arquivo: $arquivoTexto\n";
    exit(1);
}

$textoCompleto = file_get_contents($arquivoTexto);

// Processar o texto para extrair códigos e descrições
function processarHabilidades($texto) {
    $habilidades = [];
    
    // Padrão para capturar: CÓDIGO - DESCRIÇÃO,
    // Usar um padrão mais robusto que lida com vírgulas dentro da descrição
    $pattern = '/(EF\d{2}[A-Z]{2}\d{2})\s*-\s*([^,]+(?:\.,|$))/';
    
    // Dividir por vírgulas que seguem um padrão específico (código - descrição)
    // Melhor abordagem: encontrar todos os padrões EF##XX## - ...
    preg_match_all('/(EF\d{2}[A-Z]{2}\d{2})\s*-\s*([^,]+(?:\.,|$))/', $texto, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $codigo = trim($match[1]);
        $descricao = trim($match[2]);
        // Remover vírgula final se existir
        $descricao = rtrim($descricao, ',');
        $descricao = trim($descricao);
        
        if (!empty($codigo) && !empty($descricao)) {
            $habilidades[$codigo] = $descricao;
        }
    }
    
    return $habilidades;
}

echo "Processando texto completo...\n";
$todasHabilidades = processarHabilidades($textoCompleto);

echo "Habilidades processadas: " . count($todasHabilidades) . "\n";

// Filtrar apenas as habilidades faltantes
$habilidadesFaltantes = [];
foreach ($todasHabilidades as $codigo => $descricao) {
    if (!isset($codigosCadastradosAssoc[$codigo])) {
        $habilidadesFaltantes[$codigo] = $descricao;
    }
}

echo "Habilidades faltantes encontradas: " . count($habilidadesFaltantes) . "\n";

if (empty($habilidadesFaltantes)) {
    echo "Nenhuma habilidade faltante encontrada ou todas já estão cadastradas.\n";
    exit(0);
}

// Gerar SQL
$sql = "-- Script SQL para inserir habilidades faltantes da BNCC\n";
$sql .= "-- Ensino Fundamental - Anos Iniciais\n";
$sql .= "-- Gerado em: " . date('d/m/Y H:i:s') . "\n";
$sql .= "-- Total de habilidades a inserir: " . count($habilidadesFaltantes) . "\n\n";

$sql .= "START TRANSACTION;\n\n";

$contador = 0;
foreach ($habilidadesFaltantes as $codigo => $descricao) {
    // Extrair informações do código
    if (preg_match('/EF(\d{2})([A-Z]{2})(\d{2})/', $codigo, $matches)) {
        $ano = (int)$matches[1];
        $componenteCodigo = $matches[2];
        $componenteNome = $componentes[$componenteCodigo] ?? $componenteCodigo;
        
        // Determinar ano_inicio e ano_fim
        $anoInicio = $ano;
        $anoFim = $ano;
        
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
        
        $contador++;
        if ($contador % 50 == 0) {
            echo "Processadas $contador habilidades...\n";
        }
    }
}

$sql .= "\nCOMMIT;\n";

// Salvar arquivo SQL
$arquivoSaida = __DIR__ . '/inserir_habilidades_faltantes_' . date('Y-m-d_H-i-s') . '.sql';
file_put_contents($arquivoSaida, $sql);

echo "\nSQL gerado com sucesso!\n";
echo "Arquivo salvo em: $arquivoSaida\n";
echo "Total de INSERT statements: " . count($habilidadesFaltantes) . "\n";

