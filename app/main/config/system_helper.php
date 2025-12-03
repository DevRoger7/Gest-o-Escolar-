<?php
/**
 * Helper para obter configurações do sistema
 */

require_once(__DIR__ . '/Database.php');

/**
 * Obtém o nome do sistema do banco de dados
 * Se não estiver configurado, retorna o valor padrão
 */
function getNomeSistema() {
    static $nomeSistema = null;
    
    // Cache para evitar múltiplas consultas
    if ($nomeSistema !== null) {
        return $nomeSistema;
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar se a tabela configuracao existe
        $sqlCheck = "SHOW TABLES LIKE 'configuracao'";
        $stmtCheck = $conn->query($sqlCheck);
        
        if ($stmtCheck->rowCount() > 0) {
            // Buscar nome do sistema
            $sql = "SELECT valor FROM configuracao WHERE chave = 'nome_sistema' LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['valor'])) {
                $nomeSistema = $result['valor'];
                return $nomeSistema;
            }
        }
    } catch (Exception $e) {
        // Em caso de erro, usar valor padrão
    }
    
    // Valor padrão
    $nomeSistema = 'SIGEA - Sistema de Gestão e Alimentação Escolar';
    return $nomeSistema;
}

/**
 * Obtém apenas o nome curto do sistema (sem descrição)
 */
function getNomeSistemaCurto() {
    $nomeCompleto = getNomeSistema();
    // Se contém " - ", pega apenas a parte antes
    if (strpos($nomeCompleto, ' - ') !== false) {
        return explode(' - ', $nomeCompleto)[0];
    }
    return $nomeCompleto;
}

/**
 * Obtém uma configuração específica do sistema
 */
function getConfiguracao($chave, $valorPadrao = null) {
    static $configCache = [];
    
    // Verificar cache
    if (isset($configCache[$chave])) {
        return $configCache[$chave];
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar se a tabela configuracao existe
        $sqlCheck = "SHOW TABLES LIKE 'configuracao'";
        $stmtCheck = $conn->query($sqlCheck);
        
        if ($stmtCheck->rowCount() > 0) {
            $sql = "SELECT valor FROM configuracao WHERE chave = :chave LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':chave', $chave);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['valor'])) {
                $configCache[$chave] = $result['valor'];
                return $result['valor'];
            }
        }
    } catch (Exception $e) {
        // Em caso de erro, usar valor padrão
    }
    
    $configCache[$chave] = $valorPadrao;
    return $valorPadrao;
}

/**
 * Gera o título da página com o nome do sistema
 */
function getPageTitle($pagina) {
    $nomeSistema = getNomeSistemaCurto();
    return htmlspecialchars($pagina . ' - ' . $nomeSistema);
}

