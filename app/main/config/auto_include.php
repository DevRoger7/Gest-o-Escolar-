<?php
/**
 * Arquivo de auto-inclusão que garante que system_helper.php seja carregado
 * quando Database.php for incluído
 */

// Auto-incluir system_helper se não foi incluído
if (!function_exists('getNomeSistema')) {
    require_once(__DIR__ . '/system_helper.php');
}

