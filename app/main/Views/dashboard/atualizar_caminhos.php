<?php
/**
 * Script para atualizar caminhos após reorganização
 */

$baseDir = __DIR__;

// Mapeamento de arquivos antigos para novos (para atualizar links)
$mapeamentoArquivos = [
    // Aluno
    'aluno_notas.php' => '../aluno/notas.php',
    'aluno_frequencia.php' => '../aluno/frequencia.php',
    'aluno_boletins.php' => '../aluno/boletins.php',
    
    // Professor
    'notas_professor.php' => '../professor/notas.php',
    'frequencia_professor.php' => '../professor/frequencia.php',
    'comunicados_professor.php' => '../professor/comunicados.php',
    'observacoes_professor.php' => '../professor/observacoes.php',
    'relatorios_professor.php' => '../professor/relatorios.php',
    
    // ADM Gestão
    'gestao_alunos_adm.php' => 'alunos.php', // mesmo diretório
    'gestao_professores_adm.php' => 'professores.php',
    'gestao_funcionarios_adm.php' => 'funcionarios.php',
    'gestao_gestores_adm.php' => 'gestores.php',
    'gestao_usuarios.php' => 'usuarios.php',
    'gestao_escolas.php' => 'escolas.php',
    'gestao_turmas_adm.php' => 'turmas.php',
    'gestao_series_adm.php' => 'series.php',
    'gestao_disciplinas_adm.php' => 'disciplinas.php',
    
    // ADM Supervisão
    'supervisao_academica_adm.php' => '../supervisao/academica.php',
    'supervisao_alimentacao_adm.php' => '../supervisao/alimentacao.php',
    
    // ADM Relatórios
    'relatorios_pedagogicos_adm.php' => '../relatorios/pedagogicos.php',
    'relatorios_financeiros_adm.php' => '../relatorios/financeiros.php',
    
    // ADM Outros
    'permissoes_adm.php' => '../permissoes.php',
    'validacao_lancamentos_adm.php' => '../validacao.php',
    'configuracoes_seguranca_adm.php' => '../configuracoes.php',
    
    // Merenda
    'cardapios_merenda.php' => 'cardapios.php',
    'estoque_merenda.php' => 'estoque.php',
    'consumo_merenda.php' => 'consumo.php',
    'pedidos_merenda.php' => 'pedidos.php',
    'fornecedores_merenda.php' => 'fornecedores.php',
    'entregas_merenda.php' => 'entregas.php',
    'custos_merenda.php' => 'custos.php',
    'desperdicio_merenda.php' => 'desperdicio.php',
    'gestao_estoque_central.php' => 'estoque_central.php',
    
    // Nutricionista
    'cardapios_nutricionista.php' => 'cardapios.php',
    'pedidos_nutricionista.php' => 'pedidos.php',
    'avaliacao_estoque_nutricionista.php' => 'estoque.php',
    'substituicoes_nutricionista.php' => 'substituicoes.php',
    'indicadores_nutricionais.php' => 'indicadores.php',
    'relatorios_nutricionais.php' => 'relatorios.php',
    
    // Gestão
    'gestao_escolar.php' => '../gestao/escolar.php',
    'lotacao_professores.php' => '../gestao/lotacao.php',
];

// Função para atualizar um arquivo
function atualizarArquivo($caminhoArquivo, $profundidade) {
    $conteudo = file_get_contents($caminhoArquivo);
    $modificado = false;
    
    // Atualizar require/include (../../ → ../../../ se necessário)
    if ($profundidade === 2) { // adm/gestao, merenda, nutricionista, etc
        // require_once('../../ → require_once('../../../
        $conteudo = preg_replace(
            "/require_once\(['\"]\.\.\/\.\.\//",
            "require_once('../../../",
            $conteudo
        );
        $conteudo = preg_replace(
            "/include\(['\"]\.\.\/\.\.\//",
            "include('../../../",
            $conteudo
        );
        $conteudo = preg_replace(
            "/include_once\(['\"]\.\.\/\.\.\//",
            "include_once('../../../",
            $conteudo
        );
        
        // components/ → ../../components/
        $conteudo = preg_replace(
            "/include\s+['\"]components\//",
            "include '../../components/",
            $conteudo
        );
        $conteudo = preg_replace(
            "/require\s+['\"]components\//",
            "require '../../components/",
            $conteudo
        );
        
        // dashboard.php → ../../dashboard.php
        $conteudo = preg_replace(
            "/header\(['\"]Location:\s*dashboard\.php/",
            "header('Location: ../../dashboard.php",
            $conteudo
        );
        $conteudo = preg_replace(
            "/href=['\"]dashboard\.php/",
            "href=\"../../dashboard.php",
            $conteudo
        );
    }
    
    // Atualizar links para arquivos movidos
    global $mapeamentoArquivos;
    foreach ($mapeamentoArquivos as $antigo => $novo) {
        // href="arquivo.php"
        $conteudo = preg_replace(
            '/href=["\']' . preg_quote($antigo, '/') . '["\']/',
            'href="' . $novo . '"',
            $conteudo
        );
        
        // action="arquivo.php"
        $conteudo = preg_replace(
            '/action=["\']' . preg_quote($antigo, '/') . '["\']/',
            'action="' . $novo . '"',
            $conteudo
        );
        
        // header('Location: arquivo.php')
        $conteudo = preg_replace(
            "/header\(['\"]Location:\s*" . preg_quote($antigo, '/') . "['\"]/",
            "header('Location: " . $novo . "'",
            $conteudo
        );
    }
    
    file_put_contents($caminhoArquivo, $conteudo);
    return $modificado;
}

// Processar todos os arquivos PHP nas subpastas
$pastas = ['aluno', 'professor', 'adm', 'merenda', 'nutricionista', 'gestao'];

foreach ($pastas as $pasta) {
    $caminhoPasta = $baseDir . '/' . $pasta;
    if (is_dir($caminhoPasta)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($caminhoPasta),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $arquivo) {
            if ($arquivo->isFile() && $arquivo->getExtension() === 'php') {
                $caminhoRelativo = str_replace($baseDir . '\\', '', $arquivo->getPathname());
                $profundidade = substr_count($caminhoRelativo, '\\');
                
                echo "Atualizando: $caminhoRelativo (profundidade: $profundidade)\n";
                atualizarArquivo($arquivo->getPathname(), $profundidade);
            }
        }
    }
}

echo "\n=== ATUALIZAÇÃO CONCLUÍDA ===\n";
echo "Todos os caminhos foram atualizados.\n";
echo "Verifique se há algum caminho que precisa de ajuste manual.\n";

