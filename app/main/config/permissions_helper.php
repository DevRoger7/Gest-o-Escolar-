<?php
/**
 * Helper de Permissões - Funções auxiliares para uso em Views
 * 
 * Uso:
 * require_once('../../config/permissions_helper.php');
 * 
 * if (temPermissao('cadastrar_pessoas')) { ... }
 * if (eTipo('adm')) { ... }
 */

require_once(__DIR__ . '/../Models/permissions/PermissionManager.php');

/**
 * Verifica se o usuário tem uma permissão específica
 */
function temPermissao($permissao) {
    return PermissionManager::temPermissao($permissao);
}

/**
 * Verifica se o usuário tem pelo menos uma das permissões
 */
function temAlgumaPermissao($permissoes) {
    return PermissionManager::temAlgumaPermissao($permissoes);
}

/**
 * Verifica se o usuário tem todas as permissões
 */
function temTodasPermissoes($permissoes) {
    return PermissionManager::temTodasPermissoes($permissoes);
}

/**
 * Verifica se o usuário é de um tipo específico
 */
function eTipo($tipo) {
    return PermissionManager::eTipo($tipo);
}

/**
 * Retorna o tipo de usuário atual
 */
function getTipoUsuario() {
    return PermissionManager::getTipoUsuario();
}

/**
 * Verifica se o usuário é Administrador Geral
 */
function eAdm() {
    return eTipo('adm');
}

/**
 * Verifica se o usuário é Gestão
 */
function eGestao() {
    return eTipo('gestao');
}

/**
 * Verifica se o usuário é Professor
 */
function eProfessor() {
    return eTipo('professor');
}

/**
 * Verifica se o usuário é Aluno
 */
function eAluno() {
    return eTipo('aluno');
}

/**
 * Verifica se o usuário é Responsável
 */
function eResponsavel() {
    return eTipo('responsavel');
}

/**
 * Verifica se o usuário é Nutricionista
 */
function eNutricionista() {
    return eTipo('nutricionista');
}

/**
 * Verifica se o usuário é Administrador de Merenda
 */
function eAdmMerenda() {
    return eTipo('adm_merenda');
}

/**
 * Redireciona se não tiver permissão
 */
function requerPermissao($permissao, $redirectUrl = '../auth/login.php') {
    if (!temPermissao($permissao)) {
        header('Location: ' . $redirectUrl . '?erro=sem_permissao');
        exit();
    }
}

/**
 * Redireciona se não for do tipo especificado
 */
function requerTipo($tipo, $redirectUrl = '../auth/login.php') {
    if (!eTipo($tipo)) {
        header('Location: ' . $redirectUrl . '?erro=sem_permissao');
        exit();
    }
}

?>

