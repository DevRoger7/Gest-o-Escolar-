<?php
/**
 * Script final para gerar SQL de inserção das habilidades faltantes
 * Processa o texto completo fornecido pelo usuário
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

// Ler o texto completo das habilidades de um arquivo
// O usuário deve copiar o texto da mensagem para este arquivo
$arquivoTexto = __DIR__ . '/habilidades_texto_completo.txt';

if (!file_exists($arquivoTexto)) {
    echo "========================================\n";
    echo "ARQUIVO NÃO ENCONTRADO\n";
    echo "========================================\n";
    echo "Por favor, crie um arquivo chamado 'habilidades_texto_completo.txt'\n";
    echo "na pasta: " . __DIR__ . "\n";
    echo "e cole o texto completo fornecido na mensagem do usuário.\n";
    echo "\nO arquivo deve conter o texto no formato:\n";
    echo "EF15LP01 - Descrição da habilidade., EF15LP02 - Outra descrição., ...\n";
    exit(1);
}

echo "Lendo arquivo de texto...\n";
$textoCompleto = file_get_contents($arquivoTexto);

// Processar o texto para extrair códigos e descrições
// O padrão é: CÓDIGO - DESCRIÇÃO,
function processarHabilidades($texto) {
    $habilidades = [];
    
    // Padrão melhorado: EF##XX## - [descrição],
    // A descrição pode conter vírgulas, então precisamos ser cuidadosos
    // Vamos usar um padrão que captura até encontrar ", EF" (início do próximo código)
    $pattern = '/(EF\d{2}[A-Z]{2}\d{2})\s*-\s*([^,]+(?:\.,|$))/';
    
    // Dividir o texto em partes menores para processar
    // Encontrar todos os códigos primeiro
    preg_match_all('/EF\d{2}[A-Z]{2}\d{2}/', $texto, $codigosEncontrados);
    
    // Para cada código, encontrar sua descrição
    foreach ($codigosEncontrados[0] as $codigo) {
        // Encontrar a posição do código
        $posicao = strpos($texto, $codigo);
        if ($posicao !== false) {
            // Encontrar o próximo código (ou fim do texto)
            $proximoCodigo = preg_match('/EF\d{2}[A-Z]{2}\d{2}/', substr($texto, $posicao + strlen($codigo)), $nextMatch);
            
            // Extrair a parte relevante
            $parte = substr($texto, $posicao);
            if ($proximoCodigo && isset($nextMatch[0])) {
                $parte = substr($parte, 0, strpos($parte, $nextMatch[0]));
            }
            
            // Extrair descrição (tudo após " - " até a vírgula final ou fim)
            if (preg_match('/' . preg_quote($codigo, '/') . '\s*-\s*(.+?)(?:,\s*EF\d{2}[A-Z]{2}\d{2}|$)/s', $parte, $match)) {
                $descricao = trim($match[1]);
                $descricao = rtrim($descricao, ',');
                $descricao = trim($descricao);
                
                if (!empty($descricao)) {
                    $habilidades[$codigo] = $descricao;
                }
            }
        }
    }
    
    return $habilidades;
}

echo "Processando texto completo...\n";
$todasHabilidades = processarHabilidades($textoCompleto);

echo "Habilidades processadas: " . count($todasHabilidades) . "\n";

if (count($todasHabilidades) < 100) {
    echo "AVISO: Poucas habilidades foram processadas. Verifique o formato do texto.\n";
    echo "Primeiras 5 habilidades encontradas:\n";
    $count = 0;
    foreach ($todasHabilidades as $codigo => $descricao) {
        if ($count++ < 5) {
            echo "  $codigo: " . substr($descricao, 0, 60) . "...\n";
        }
    }
}

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

echo "\n========================================\n";
echo "SQL GERADO COM SUCESSO!\n";
echo "========================================\n";
echo "Arquivo salvo em: $arquivoSaida\n";
echo "Total de INSERT statements: " . count($habilidadesFaltantes) . "\n";
echo "\nVocê pode executar este arquivo SQL no seu banco de dados.\n";

