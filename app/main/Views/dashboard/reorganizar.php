<?php
/**
 * Script de Reorganização da Pasta Dashboard
 * 
 * Este script reorganiza os arquivos da pasta dashboard em uma estrutura modular
 * 
 * ATENÇÃO: Execute este script apenas uma vez e faça backup antes!
 */

// Mapeamento de arquivos antigos para novos caminhos
$reorganizacao = [
    // Módulo Aluno
    'aluno_notas.php' => 'aluno/notas.php',
    'aluno_frequencia.php' => 'aluno/frequencia.php',
    'aluno_boletins.php' => 'aluno/boletins.php',
    
    // Módulo Professor
    'notas_professor.php' => 'professor/notas.php',
    'frequencia_professor.php' => 'professor/frequencia.php',
    'comunicados_professor.php' => 'professor/comunicados.php',
    'observacoes_professor.php' => 'professor/observacoes.php',
    'relatorios_professor.php' => 'professor/relatorios.php',
    
    // Módulo ADM - Gestão
    'gestao_alunos_adm.php' => 'adm/gestao/alunos.php',
    'gestao_professores_adm.php' => 'adm/gestao/professores.php',
    'gestao_funcionarios_adm.php' => 'adm/gestao/funcionarios.php',
    'gestao_gestores_adm.php' => 'adm/gestao/gestores.php',
    'gestao_usuarios.php' => 'adm/gestao/usuarios.php',
    'gestao_escolas.php' => 'adm/gestao/escolas.php',
    'gestao_turmas_adm.php' => 'adm/gestao/turmas.php',
    'gestao_series_adm.php' => 'adm/gestao/series.php',
    'gestao_disciplinas_adm.php' => 'adm/gestao/disciplinas.php',
    
    // Módulo ADM - Supervisão
    'supervisao_academica_adm.php' => 'adm/supervisao/academica.php',
    'supervisao_alimentacao_adm.php' => 'adm/supervisao/alimentacao.php',
    
    // Módulo ADM - Relatórios
    'relatorios_pedagogicos_adm.php' => 'adm/relatorios/pedagogicos.php',
    'relatorios_financeiros_adm.php' => 'adm/relatorios/financeiros.php',
    
    // Módulo ADM - Outros
    'permissoes_adm.php' => 'adm/permissoes.php',
    'validacao_lancamentos_adm.php' => 'adm/validacao.php',
    'configuracoes_seguranca_adm.php' => 'adm/configuracoes.php',
    
    // Módulo Merenda
    'cardapios_merenda.php' => 'merenda/cardapios.php',
    'estoque_merenda.php' => 'merenda/estoque.php',
    'consumo_merenda.php' => 'merenda/consumo.php',
    'pedidos_merenda.php' => 'merenda/pedidos.php',
    'fornecedores_merenda.php' => 'merenda/fornecedores.php',
    'entregas_merenda.php' => 'merenda/entregas.php',
    'custos_merenda.php' => 'merenda/custos.php',
    'desperdicio_merenda.php' => 'merenda/desperdicio.php',
    'gestao_estoque_central.php' => 'merenda/estoque_central.php',
    
    // Módulo Nutricionista
    'cardapios_nutricionista.php' => 'nutricionista/cardapios.php',
    'pedidos_nutricionista.php' => 'nutricionista/pedidos.php',
    'avaliacao_estoque_nutricionista.php' => 'nutricionista/estoque.php',
    'substituicoes_nutricionista.php' => 'nutricionista/substituicoes.php',
    'indicadores_nutricionais.php' => 'nutricionista/indicadores.php',
    'relatorios_nutricionais.php' => 'nutricionista/relatorios.php',
    
    // Módulo Gestão
    'gestao_escolar.php' => 'gestao/escolar.php',
    'lotacao_professores.php' => 'gestao/lotacao.php',
];

// Pastas a criar
$pastas = [
    'aluno',
    'professor',
    'adm/gestao',
    'adm/supervisao',
    'adm/relatorios',
    'merenda',
    'nutricionista',
    'gestao',
    'shared'
];

$baseDir = __DIR__;
$log = [];

echo "=== REORGANIZAÇÃO DA PASTA DASHBOARD ===\n\n";
echo "Este script irá:\n";
echo "1. Criar as novas pastas\n";
echo "2. Mover os arquivos\n";
echo "3. Criar arquivos de redirecionamento (opcional)\n\n";
echo "ATENÇÃO: Faça backup antes de executar!\n\n";
echo "Deseja continuar? (s/n): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 's' && trim($line) !== 'S') {
    echo "Operação cancelada.\n";
    exit;
}

// Criar pastas
echo "\n1. Criando pastas...\n";
foreach ($pastas as $pasta) {
    $caminhoCompleto = $baseDir . '/' . $pasta;
    if (!is_dir($caminhoCompleto)) {
        if (mkdir($caminhoCompleto, 0755, true)) {
            echo "   ✓ Criada: $pasta\n";
            $log[] = "Pasta criada: $pasta";
        } else {
            echo "   ✗ Erro ao criar: $pasta\n";
            $log[] = "ERRO ao criar pasta: $pasta";
        }
    } else {
        echo "   - Já existe: $pasta\n";
    }
}

// Mover arquivos
echo "\n2. Movendo arquivos...\n";
foreach ($reorganizacao as $arquivoAntigo => $arquivoNovo) {
    $caminhoAntigo = $baseDir . '/' . $arquivoAntigo;
    $caminhoNovo = $baseDir . '/' . $arquivoNovo;
    
    if (file_exists($caminhoAntigo)) {
        // Criar diretório de destino se não existir
        $dirNovo = dirname($caminhoNovo);
        if (!is_dir($dirNovo)) {
            mkdir($dirNovo, 0755, true);
        }
        
        if (rename($caminhoAntigo, $caminhoNovo)) {
            echo "   ✓ Movido: $arquivoAntigo -> $arquivoNovo\n";
            $log[] = "Arquivo movido: $arquivoAntigo -> $arquivoNovo";
            
            // Criar arquivo de redirecionamento (opcional)
            $redirectContent = "<?php\n// Redirecionamento automático\nheader('Location: " . $arquivoNovo . "');\nexit;\n?>";
            file_put_contents($caminhoAntigo, $redirectContent);
            echo "   ✓ Criado redirecionamento: $arquivoAntigo\n";
        } else {
            echo "   ✗ Erro ao mover: $arquivoAntigo\n";
            $log[] = "ERRO ao mover: $arquivoAntigo";
        }
    } else {
        echo "   - Não encontrado: $arquivoAntigo\n";
    }
}

// Mover arquivos compartilhados
echo "\n3. Movendo arquivos compartilhados...\n";
$sharedFiles = ['dashboard.php', 'calendar.php', 'dashboard_footer.php'];
foreach ($sharedFiles as $arquivo) {
    $caminhoAntigo = $baseDir . '/' . $arquivo;
    $caminhoNovo = $baseDir . '/shared/' . $arquivo;
    
    if (file_exists($caminhoAntigo) && $arquivo !== 'dashboard.php') {
        if (rename($caminhoAntigo, $caminhoNovo)) {
            echo "   ✓ Movido: $arquivo -> shared/$arquivo\n";
            $log[] = "Arquivo movido: $arquivo -> shared/$arquivo";
        }
    }
}

echo "\n=== REORGANIZAÇÃO CONCLUÍDA ===\n\n";
echo "PRÓXIMOS PASSOS:\n";
echo "1. Atualizar todos os includes/requires nos arquivos\n";
echo "2. Atualizar todos os links href nos arquivos\n";
echo "3. Atualizar redirecionamentos header('Location: ...')\n";
echo "4. Testar todas as funcionalidades\n";
echo "5. Remover os arquivos de redirecionamento após confirmar que tudo funciona\n\n";

// Salvar log
file_put_contents($baseDir . '/reorganizacao_log.txt', implode("\n", $log));
echo "Log salvo em: reorganizacao_log.txt\n";

