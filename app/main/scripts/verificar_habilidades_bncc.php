<?php
/**
 * Script para verificar quais habilidades da BNCC já estão no banco
 * e quais ainda faltam inserir
 */

require_once(__DIR__ . '/../../config/Database.php');

$db = Database::getInstance();
$conn = $db->getConnection();

// Lista de habilidades fornecida pelo usuário (Ensino Fundamental - Anos Iniciais)
$habilidadesFornecidas = [
    'EF15LP01', 'EF15LP02', 'EF15LP03', 'EF15LP04', 'EF15LP05', 'EF15LP06', 'EF15LP07', 'EF15LP08',
    'EF15LP09', 'EF15LP10', 'EF15LP11', 'EF15LP12', 'EF15LP13', 'EF15LP14', 'EF15LP15', 'EF15LP16',
    'EF15LP17', 'EF15LP18', 'EF15LP19',
    'EF01LP01', 'EF12LP01', 'EF12LP02', 'EF01LP02', 'EF01LP03', 'EF02LP01', 'EF12LP03', 'EF01LP04',
    'EF01LP05', 'EF01LP06', 'EF02LP02', 'EF01LP07', 'EF02LP03', 'EF01LP08', 'EF02LP04', 'EF01LP09',
    'EF02LP05', 'EF01LP10', 'EF02LP06', 'EF01LP11', 'EF02LP07', 'EF01LP12', 'EF02LP08', 'EF01LP13',
    'EF01LP14', 'EF02LP09', 'EF01LP15', 'EF02LP10', 'EF02LP11', 'EF12LP04', 'EF01LP16', 'EF02LP12',
    'EF01LP17', 'EF02LP13', 'EF01LP18', 'EF02LP14', 'EF12LP05', 'EF12LP06', 'EF01LP19', 'EF02LP15',
    'EF12LP07', 'EF01LP20', 'EF02LP16', 'EF02LP17', 'EF12LP08', 'EF12LP09', 'EF12LP10', 'EF12LP11',
    'EF12LP12', 'EF01LP21', 'EF02LP18', 'EF02LP19', 'EF12LP13', 'EF12LP14', 'EF12LP15', 'EF12LP16',
    'EF12LP17', 'EF02LP20', 'EF02LP21', 'EF01LP22', 'EF02LP22', 'EF02LP23', 'EF01LP23', 'EF02LP24',
    'EF01LP24', 'EF02LP25', 'EF02LP26', 'EF12LP18', 'EF01LP25', 'EF02LP27', 'EF01LP26', 'EF02LP28',
    'EF12LP19', 'EF02LP29',
    'EF35LP01', 'EF35LP02', 'EF35LP03', 'EF35LP04', 'EF35LP05', 'EF35LP06', 'EF35LP07', 'EF35LP08',
    'EF35LP09', 'EF35LP10', 'EF35LP11', 'EF35LP12', 'EF03LP01', 'EF04LP01', 'EF05LP01', 'EF03LP02',
    'EF04LP02', 'EF03LP03', 'EF35LP13', 'EF04LP03', 'EF05LP02', 'EF03LP04', 'EF04LP04', 'EF05LP03',
    'EF03LP05', 'EF03LP06', 'EF03LP07', 'EF04LP05', 'EF05LP04', 'EF05LP05', 'EF03LP08', 'EF04LP06',
    'EF05LP06', 'EF03LP09', 'EF04LP07', 'EF35LP14', 'EF05LP07', 'EF03LP10', 'EF04LP08', 'EF05LP08',
    'EF03LP11', 'EF04LP09', 'EF05LP09', 'EF03LP12', 'EF04LP10', 'EF05LP10', 'EF03LP13', 'EF04LP11',
    'EF05LP11', 'EF03LP14', 'EF05LP12', 'EF03LP15', 'EF04LP12', 'EF05LP13', 'EF03LP16', 'EF04LP13',
    'EF05LP14', 'EF03LP17', 'EF03LP18', 'EF04LP14', 'EF05LP15', 'EF03LP19', 'EF04LP15', 'EF05LP16',
    'EF03LP20', 'EF04LP16', 'EF05LP17', 'EF03LP21', 'EF35LP15', 'EF03LP22', 'EF04LP17', 'EF05LP18',
    'EF05LP19', 'EF35LP16', 'EF03LP23', 'EF05LP20', 'EF04LP18', 'EF05LP21', 'EF03LP24', 'EF04LP19',
    'EF05LP22', 'EF04LP20', 'EF05LP23', 'EF35LP17', 'EF03LP25', 'EF04LP21', 'EF05LP24', 'EF04LP22',
    'EF05LP25', 'EF35LP18', 'EF35LP19', 'EF35LP20', 'EF03LP26', 'EF05LP26', 'EF04LP23', 'EF05LP27',
    'EF04LP24', 'EF35LP21', 'EF35LP22', 'EF35LP23', 'EF35LP24', 'EF35LP25', 'EF35LP26', 'EF35LP27',
    'EF35LP28', 'EF03LP27', 'EF04LP25', 'EF35LP29', 'EF35LP30', 'EF35LP31', 'EF04LP26', 'EF05LP28',
    'EF04LP27',
    'EF15AR01', 'EF15AR02', 'EF15AR03', 'EF15AR04', 'EF15AR05', 'EF15AR06', 'EF15AR07', 'EF15AR08',
    'EF15AR09', 'EF15AR10', 'EF15AR11', 'EF15AR12', 'EF15AR13', 'EF15AR14', 'EF15AR15', 'EF15AR16',
    'EF15AR17', 'EF15AR18', 'EF15AR19', 'EF15AR20', 'EF15AR21', 'EF15AR22', 'EF15AR23', 'EF15AR24',
    'EF15AR25', 'EF15AR26',
    'EF12EF01', 'EF12EF02', 'EF12EF03', 'EF12EF04', 'EF12EF05', 'EF12EF06', 'EF12EF07', 'EF12EF08',
    'EF12EF09', 'EF12EF10', 'EF12EF11', 'EF12EF12',
    'EF35EF01', 'EF35EF02', 'EF35EF03', 'EF35EF04', 'EF35EF05', 'EF35EF06', 'EF35EF07', 'EF35EF08',
    'EF35EF09', 'EF35EF10', 'EF35EF11', 'EF35EF12', 'EF35EF13', 'EF35EF14', 'EF35EF15',
    'EF01MA01', 'EF01MA02', 'EF01MA03', 'EF01MA04', 'EF01MA05', 'EF01MA06', 'EF01MA07', 'EF01MA08',
    'EF01MA09', 'EF01MA10', 'EF01MA11', 'EF01MA12', 'EF01MA13', 'EF01MA14', 'EF01MA15', 'EF01MA16',
    'EF01MA17', 'EF01MA18', 'EF01MA19', 'EF01MA20', 'EF01MA21', 'EF01MA22',
    'EF02MA01', 'EF02MA02', 'EF02MA03', 'EF02MA04', 'EF02MA05', 'EF02MA06', 'EF02MA07', 'EF02MA08',
    'EF02MA09', 'EF02MA10', 'EF02MA11', 'EF02MA12', 'EF02MA13', 'EF02MA14', 'EF02MA15', 'EF02MA16',
    'EF02MA17', 'EF02MA18', 'EF02MA19', 'EF02MA20', 'EF02MA21', 'EF02MA22', 'EF02MA23',
    'EF03MA01', 'EF03MA02', 'EF03MA03', 'EF03MA04', 'EF03MA05', 'EF03MA06', 'EF03MA07', 'EF03MA08',
    'EF03MA09', 'EF03MA10', 'EF03MA11', 'EF03MA12', 'EF03MA13', 'EF03MA14', 'EF03MA15', 'EF03MA16',
    'EF03MA17', 'EF03MA18', 'EF03MA19', 'EF03MA20', 'EF03MA21', 'EF03MA22', 'EF03MA23', 'EF03MA24',
    'EF03MA25', 'EF03MA26', 'EF03MA27', 'EF03MA28',
    'EF04MA01', 'EF04MA02', 'EF04MA03', 'EF04MA04', 'EF04MA05', 'EF04MA06', 'EF04MA07', 'EF04MA08',
    'EF04MA09', 'EF04MA10', 'EF04MA11', 'EF04MA12', 'EF04MA13', 'EF04MA14', 'EF04MA15', 'EF04MA16',
    'EF04MA17', 'EF04MA18', 'EF04MA19', 'EF04MA20', 'EF04MA21', 'EF04MA22', 'EF04MA23', 'EF04MA24',
    'EF04MA25', 'EF04MA26', 'EF04MA27', 'EF04MA28',
    'EF05MA01', 'EF05MA02', 'EF05MA03', 'EF05MA04', 'EF05MA05', 'EF05MA06', 'EF05MA07', 'EF05MA08',
    'EF05MA09', 'EF05MA10', 'EF05MA11', 'EF05MA12', 'EF05MA13', 'EF05MA14', 'EF05MA15', 'EF05MA16',
    'EF05MA17', 'EF05MA18', 'EF05MA19', 'EF05MA20', 'EF05MA21', 'EF05MA22', 'EF05MA23', 'EF05MA24',
    'EF05MA25',
    'EF01CI01', 'EF01CI02', 'EF01CI03', 'EF01CI04', 'EF01CI05', 'EF01CI06',
    'EF02CI01', 'EF02CI02', 'EF02CI03', 'EF02CI04', 'EF02CI05', 'EF02CI06', 'EF02CI07', 'EF02CI08',
    'EF03CI01', 'EF03CI02', 'EF03CI03', 'EF03CI04', 'EF03CI05', 'EF03CI06', 'EF03CI07', 'EF03CI08',
    'EF03CI09', 'EF03CI10',
    'EF04CI01', 'EF04CI02', 'EF04CI03', 'EF04CI04', 'EF04CI05', 'EF04CI06', 'EF04CI07', 'EF04CI08',
    'EF04CI09', 'EF04CI10', 'EF04CI11',
    'EF05CI01', 'EF05CI02', 'EF05CI03', 'EF05CI04', 'EF05CI05', 'EF05CI06', 'EF05CI07', 'EF05CI08',
    'EF05CI09', 'EF05CI10', 'EF05CI11', 'EF05CI12', 'EF05CI13',
    'EF01GE01', 'EF01GE02', 'EF01GE03', 'EF01GE04', 'EF01GE05', 'EF01GE06', 'EF01GE07', 'EF01GE08',
    'EF01GE09', 'EF01GE10', 'EF01GE11',
    'EF02GE01', 'EF02GE02', 'EF02GE03', 'EF02GE04', 'EF02GE05', 'EF02GE06', 'EF02GE07', 'EF02GE08',
    'EF02GE09', 'EF02GE10', 'EF02GE11',
    'EF03GE01', 'EF03GE02', 'EF03GE03', 'EF03GE04', 'EF03GE05', 'EF03GE06', 'EF03GE07', 'EF03GE08',
    'EF03GE09', 'EF03GE10', 'EF03GE11',
    'EF04GE01', 'EF04GE02', 'EF04GE03', 'EF04GE04', 'EF04GE05', 'EF04GE06', 'EF04GE07', 'EF04GE08',
    'EF04GE09', 'EF04GE10', 'EF04GE11', 'EF04GE12',
    'EF05GE01', 'EF05GE02', 'EF05GE03', 'EF05GE04', 'EF05GE05', 'EF05GE06', 'EF05GE07', 'EF05GE08',
    'EF05GE09', 'EF05GE10', 'EF05GE11', 'EF05GE12',
    'EF01HI01', 'EF01HI02', 'EF01HI03', 'EF01HI04', 'EF01HI05', 'EF01HI06', 'EF01HI07', 'EF01HI08',
    'EF02HI01', 'EF02HI02', 'EF02HI03', 'EF02HI04', 'EF02HI05', 'EF02HI06', 'EF02HI07', 'EF02HI08',
    'EF02HI09', 'EF02HI10', 'EF02HI11',
    'EF03HI01', 'EF03HI02', 'EF03HI03', 'EF03HI04', 'EF03HI05', 'EF03HI06', 'EF03HI07', 'EF03HI08',
    'EF03HI09', 'EF03HI10', 'EF03HI11', 'EF03HI12',
    'EF04HI01', 'EF04HI02', 'EF04HI03', 'EF04HI04', 'EF04HI05', 'EF04HI06', 'EF04HI07', 'EF04HI08',
    'EF04HI09', 'EF04HI10', 'EF04HI11',
    'EF05HI01', 'EF05HI02', 'EF05HI03', 'EF05HI04', 'EF05HI05', 'EF05HI06', 'EF05HI07', 'EF05HI08',
    'EF05HI09', 'EF05HI10',
    'EF01ER01', 'EF01ER02', 'EF01ER03', 'EF01ER04', 'EF01ER05', 'EF01ER06',
    'EF02ER01', 'EF02ER02', 'EF02ER03', 'EF02ER04', 'EF02ER05', 'EF02ER06', 'EF02ER07',
    'EF03ER01', 'EF03ER02', 'EF03ER03', 'EF03ER04', 'EF03ER05', 'EF03ER06',
    'EF04ER01', 'EF04ER02', 'EF04ER03', 'EF04ER04', 'EF04ER05', 'EF04ER06', 'EF04ER07',
    'EF05ER01', 'EF05ER02', 'EF05ER03', 'EF05ER04', 'EF05ER05', 'EF05ER06', 'EF05ER07'
];

// Buscar todas as habilidades do banco de dados
$sql = "SELECT codigo_bncc FROM habilidades_bncc WHERE etapa = 'Ensino Fundamental – Anos Iniciais'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$habilidadesNoBanco = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Converter para array associativo para busca rápida
$habilidadesNoBancoAssoc = array_flip($habilidadesNoBanco);

// Encontrar habilidades que estão no banco
$habilidadesCadastradas = [];
// Encontrar habilidades que faltam
$habilidadesFaltantes = [];

foreach ($habilidadesFornecidas as $codigo) {
    if (isset($habilidadesNoBancoAssoc[$codigo])) {
        $habilidadesCadastradas[] = $codigo;
    } else {
        $habilidadesFaltantes[] = $codigo;
    }
}

// Verificar se há habilidades no banco que não estão na lista fornecida
$habilidadesExtras = [];
foreach ($habilidadesNoBanco as $codigo) {
    if (!in_array($codigo, $habilidadesFornecidas)) {
        $habilidadesExtras[] = $codigo;
    }
}

// Organizar por componente curricular
function organizarPorComponente($codigos) {
    $organizado = [];
    foreach ($codigos as $codigo) {
        // Extrair componente do código (LP, AR, EF, MA, CI, GE, HI, ER)
        if (preg_match('/EF\d{2}([A-Z]{2})\d{2}/', $codigo, $matches)) {
            $componente = $matches[1];
            $componenteNome = [
                'LP' => 'Língua Portuguesa',
                'AR' => 'Artes',
                'EF' => 'Educação Física',
                'MA' => 'Matemática',
                'CI' => 'Ciências',
                'GE' => 'Geografia',
                'HI' => 'História',
                'ER' => 'Ensino Religioso'
            ];
            $nome = $componenteNome[$componente] ?? $componente;
            if (!isset($organizado[$nome])) {
                $organizado[$nome] = [];
            }
            $organizado[$nome][] = $codigo;
        }
    }
    return $organizado;
}

$faltantesPorComponente = organizarPorComponente($habilidadesFaltantes);
$cadastradasPorComponente = organizarPorComponente($habilidadesCadastradas);

// Gerar relatório
echo "========================================\n";
echo "RELATÓRIO DE HABILIDADES BNCC\n";
echo "Ensino Fundamental - Anos Iniciais\n";
echo "========================================\n\n";

echo "RESUMO GERAL:\n";
echo "Total de habilidades fornecidas: " . count($habilidadesFornecidas) . "\n";
echo "Total de habilidades no banco: " . count($habilidadesNoBanco) . "\n";
echo "Habilidades já cadastradas: " . count($habilidadesCadastradas) . "\n";
echo "Habilidades faltantes: " . count($habilidadesFaltantes) . "\n";
echo "Habilidades extras no banco (não na lista): " . count($habilidadesExtras) . "\n\n";

echo "========================================\n";
echo "HABILIDADES FALTANTES POR COMPONENTE:\n";
echo "========================================\n\n";

foreach ($faltantesPorComponente as $componente => $codigos) {
    echo "--- $componente (" . count($codigos) . " habilidades) ---\n";
    foreach ($codigos as $codigo) {
        echo "  - $codigo\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "LISTA COMPLETA DE HABILIDADES FALTANTES:\n";
echo "========================================\n";
echo implode(', ', $habilidadesFaltantes) . "\n\n";

if (!empty($habilidadesExtras)) {
    echo "========================================\n";
    echo "HABILIDADES NO BANCO QUE NÃO ESTÃO NA LISTA:\n";
    echo "========================================\n";
    echo implode(', ', $habilidadesExtras) . "\n\n";
}

// Gerar SQL para inserir as habilidades faltantes
if (!empty($habilidadesFaltantes)) {
    echo "========================================\n";
    echo "SQL PARA INSERIR HABILIDADES FALTANTES:\n";
    echo "========================================\n";
    echo "-- Total de " . count($habilidadesFaltantes) . " habilidades a inserir\n\n";
    
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
    
    // Mapeamento de descrições (seria necessário ter as descrições completas)
    // Por enquanto, vou gerar um template
    echo "-- IMPORTANTE: Você precisará preencher as descrições manualmente\n";
    echo "-- ou usar um script que busque as descrições da lista fornecida\n\n";
    
    foreach ($faltantesPorComponente as $componente => $codigos) {
        echo "-- $componente\n";
        foreach ($codigos as $codigo) {
            // Extrair ano do código
            preg_match('/EF(\d{2})[A-Z]{2}\d{2}/', $codigo, $matches);
            $ano = (int)$matches[1];
            
            echo "INSERT INTO habilidades_bncc (codigo_bncc, etapa, componente, ano_inicio, ano_fim, descricao) VALUES ";
            echo "('$codigo', 'Ensino Fundamental – Anos Iniciais', '$componente', $ano, $ano, 'DESCRIÇÃO AQUI');\n";
        }
        echo "\n";
    }
}

echo "\n========================================\n";
echo "ANÁLISE CONCLUÍDA\n";
echo "========================================\n";

