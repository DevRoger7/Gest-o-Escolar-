<?php
/**
 * Script para atualizar todos os títulos e ocorrências de SIGEA
 * Execute: php app/main/scripts/update_all_titles.php
 */

require_once(__DIR__ . '/../../config/Database.php');

$baseDir = __DIR__ . '/../../Views';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$updated = 0;
$patterns = [
    // Títulos
    '/<title>([^<]+)\s*-\s*SIGEA<\/title>/i' => function($matches) {
        $pagina = trim($matches[1]);
        return "<title><?= getPageTitle('" . addslashes($pagina) . "') ?></title>";
    },
    // Headers h1
    '/<h1([^>]*)>SIGEA<\/h1>/i' => '<h1$1><?= htmlspecialchars(getNomeSistemaCurto()) ?></h1>',
    // Textos completos do sistema
    '/Sistema Integrado de Gestão Escolar e Alimentação Escolar/i' => '<?= htmlspecialchars(getNomeSistema()) ?>',
];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getRealPath();
        
        // Pular arquivos específicos
        if (strpos($filePath, 'system_helper.php') !== false ||
            strpos($filePath, 'update_all_titles.php') !== false ||
            strpos($filePath, 'update_system_names.php') !== false) {
            continue;
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $modified = false;
        $needsHelper = false;
        
        // Aplicar padrões
        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $newContent = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content);
            }
            
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
                $needsHelper = true;
            }
        }
        
        // Adicionar require do helper se necessário
        if ($needsHelper && $modified) {
            // Verificar se já tem o require
            if (strpos($content, 'system_helper.php') === false) {
                // Adicionar após Database.php se existir
                if (preg_match("/(require_once\([^)]*Database\.php[^)]*\);)/", $content, $matches)) {
                    $content = str_replace(
                        $matches[0],
                        $matches[0] . "\nrequire_once('../../config/system_helper.php');",
                        $content
                    );
                } elseif (preg_match("/(require_once\([^)]*Database\.php[^)]*\);)/", $content)) {
                    // Tentar outro padrão
                    $content = preg_replace(
                        "/(require_once\([^)]*Database\.php[^)]*\);)/",
                        "$1\nrequire_once('../../config/system_helper.php');",
                        $content,
                        1
                    );
                } else {
                    // Adicionar no início após <?php
                    $content = preg_replace(
                        '/^(\s*<\?php\s*)/',
                        "$1\nrequire_once(__DIR__ . '/../../config/system_helper.php');\n",
                        $content,
                        1
                    );
                }
            }
        }
        
        if ($modified) {
            file_put_contents($filePath, $content);
            $updated++;
            echo "✓ " . str_replace(__DIR__ . '/../../', '', $filePath) . "\n";
        }
    }
}

echo "\n✅ Total de arquivos atualizados: $updated\n";
echo "Script concluído com sucesso!\n";

