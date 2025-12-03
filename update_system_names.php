<?php
/**
 * Script para atualizar todos os arquivos PHP substituindo "SIGEA" hardcoded
 * por chamadas à função getNomeSistema()
 * 
 * ATENÇÃO: Execute este script apenas uma vez após criar o system_helper.php
 */

require_once(__DIR__ . '/app/main/config/system_helper.php');

$baseDir = __DIR__ . '/app/main/Views';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$updated = 0;
$skipped = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Pular arquivos que já foram atualizados ou são helpers
        if (strpos($content, 'system_helper.php') !== false || 
            strpos($filePath, 'system_helper.php') !== false ||
            strpos($filePath, 'update_system_names.php') !== false) {
            continue;
        }
        
        $modified = false;
        
        // Verificar se precisa incluir o helper
        $needsHelper = false;
        
        // Substituir títulos <title>Página - SIGEA</title>
        if (preg_match('/<title>([^<]+)\s*-\s*SIGEA<\/title>/i', $content)) {
            $needsHelper = true;
            $content = preg_replace_callback(
                '/<title>([^<]+)\s*-\s*SIGEA<\/title>/i',
                function($matches) {
                    $pagina = trim($matches[1]);
                    return "<title><?= getPageTitle('" . addslashes($pagina) . "') ?></title>";
                },
                $content
            );
            $modified = true;
        }
        
        // Substituir <h1>SIGEA</h1> ou <h1 class="...">SIGEA</h1>
        if (preg_match('/<h1[^>]*>SIGEA<\/h1>/i', $content)) {
            $needsHelper = true;
            $content = preg_replace(
                '/<h1([^>]*)>SIGEA<\/h1>/i',
                '<h1$1><?= htmlspecialchars(getNomeSistemaCurto()) ?></h1>',
                $content
            );
            $modified = true;
        }
        
        // Substituir "SIGEA" em textos simples (com cuidado)
        // Apenas em contextos específicos para evitar substituições indesejadas
        if (preg_match('/Sistema Integrado de Gestão Escolar e Alimentação Escolar/i', $content)) {
            $needsHelper = true;
            $content = preg_replace(
                '/Sistema Integrado de Gestão Escolar e Alimentação Escolar/i',
                '<?= htmlspecialchars(getNomeSistema()) ?>',
                $content
            );
            $modified = true;
        }
        
        // Adicionar require do helper no início do arquivo (após <?php)
        if ($needsHelper && $modified) {
            // Verificar se já tem require/include
            if (strpos($content, 'require_once') === false && strpos($content, 'require ') === false) {
                // Adicionar após a primeira tag <?php
                $content = preg_replace(
                    '/^(\s*<\?php\s*)/',
                    "$1\nrequire_once(__DIR__ . '/../../config/system_helper.php');\n",
                    $content,
                    1
                );
            } else {
                // Adicionar após o último require/include antes de outras linhas
                $content = preg_replace(
                    '/(require_once|require|include_once|include)[^;]+;\s*\n/',
                    "$0" . "require_once(__DIR__ . '/../../config/system_helper.php');\n",
                    $content,
                    1
                );
            }
        }
        
        if ($modified) {
            file_put_contents($filePath, $content);
            $updated++;
            echo "Atualizado: " . str_replace(__DIR__ . '/', '', $filePath) . "\n";
        }
    }
}

echo "\nTotal de arquivos atualizados: $updated\n";
echo "Script concluído!\n";

