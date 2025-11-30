<?php
/**
 * RouteProtection - Middleware para Proteção de Rotas
 * 
 * Protege rotas baseado em permissões e tipos de usuário
 * 
 * Uso:
 * require_once('../../Middleware/RouteProtection.php');
 * RouteProtection::protegerRota(['cadastrar_pessoas', 'gerenciar_escolas']);
 * RouteProtection::protegerPorTipo('adm');
 */

require_once(__DIR__ . '/../Models/permissions/PermissionManager.php');
require_once(__DIR__ . '/../Models/sessao/sessions.php');

class RouteProtection {
    
    /**
     * Protege uma rota exigindo uma ou mais permissões
     */
    public static function protegerRota($permissoes, $redirectUrl = '../auth/login.php?erro=sem_permissao') {
        // Verificar se está logado
        $session = new sessions();
        $session->autenticar_session();
        $session->tempo_session();
        
        // Verificar permissões
        if (!PermissionManager::temAlgumaPermissao($permissoes)) {
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    /**
     * Protege uma rota exigindo todas as permissões
     */
    public static function protegerRotaTodasPermissoes($permissoes, $redirectUrl = '../auth/login.php?erro=sem_permissao') {
        // Verificar se está logado
        $session = new sessions();
        $session->autenticar_session();
        $session->tempo_session();
        
        // Verificar permissões
        if (!PermissionManager::temTodasPermissoes($permissoes)) {
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    /**
     * Protege uma rota exigindo um tipo específico de usuário
     */
    public static function protegerPorTipo($tipos, $redirectUrl = '../auth/login.php?erro=sem_permissao') {
        // Verificar se está logado
        $session = new sessions();
        $session->autenticar_session();
        $session->tempo_session();
        
        // Converter para array se necessário
        if (!is_array($tipos)) {
            $tipos = [$tipos];
        }
        
        $tipoUsuario = PermissionManager::getTipoUsuario();
        
        // Verificar se o tipo do usuário está na lista permitida
        $permitido = false;
        foreach ($tipos as $tipo) {
            if ($tipoUsuario === strtolower(trim($tipo))) {
                $permitido = true;
                break;
            }
        }
        
        if (!$permitido) {
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    /**
     * Protege uma rota para Administrador Geral apenas
     */
    public static function protegerAdm($redirectUrl = '../auth/login.php?erro=sem_permissao') {
        self::protegerPorTipo('adm', $redirectUrl);
    }
    
    /**
     * Protege uma rota para Gestão ou ADM
     */
    public static function protegerGestaoOuAdm($redirectUrl = '../auth/login.php?erro=sem_permissao') {
        self::protegerPorTipo(['gestao', 'adm'], $redirectUrl);
    }
    
    /**
     * Protege uma rota para Professor ou superior
     */
    public static function protegerProfessorOuSuperior($redirectUrl = '../auth/login.php?erro=sem_permissao') {
        self::protegerPorTipo(['professor', 'gestao', 'adm'], $redirectUrl);
    }
}

?>

