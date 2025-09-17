<?php
class sessions {
    
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function autenticar_session() {
        // Verifica se o usuário está logado
        if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
            // Redireciona para a página de login se não estiver logado
            header('Location: ../auth/login.php');
            exit();
        }
    }
    
    public function tempo_session() {
        // Define tempo limite da sessão (30 minutos)
        $tempo_limite = 30 * 60; // 30 minutos em segundos
        
        if (isset($_SESSION['ultimo_acesso'])) {
            $tempo_inativo = time() - $_SESSION['ultimo_acesso'];
            
            if ($tempo_inativo > $tempo_limite) {
                // Sessão expirou
                $this->destruir_session();
                header('Location: ../../auth/login.php?erro=sessao_expirada');
                exit();
            }
        }
        
        // Atualiza o último acesso
        $_SESSION['ultimo_acesso'] = time();
    }
    
    public function destruir_session() {
        // Destrói todas as variáveis de sessão
        $_SESSION = array();
        
        // Se for desejado destruir a sessão, também delete o cookie de sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finalmente, destrói a sessão
        session_destroy();
    }
    
    public function criar_session($dados_usuario) {
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $dados_usuario['id'];
        $_SESSION['nome'] = $dados_usuario['nome'];
        $_SESSION['email'] = $dados_usuario['email'];
        $_SESSION['setor'] = $dados_usuario['setor'] ?? 'Professor';
        $_SESSION['ultimo_acesso'] = time();
        
        // Define permissões baseadas no setor/tipo do usuário
        $this->definir_permissoes($dados_usuario);
    }
    
    private function definir_permissoes($dados_usuario) {
        // Limpa permissões anteriores
        $permissoes = ['Gerenciador de Usuarios', 'Estoque', 'Biblioteca', 'Entrada/saída'];
        foreach ($permissoes as $permissao) {
            unset($_SESSION[$permissao]);
        }
        
        // Define permissões baseadas no setor ou tipo de usuário
        $setor = $dados_usuario['setor'] ?? '';
        $tipo = $dados_usuario['tipo'] ?? '';
        
        // Administradores têm acesso a tudo
        if ($setor === 'Administração' || $tipo === 'admin') {
            $_SESSION['Gerenciador de Usuarios'] = true;
            $_SESSION['Estoque'] = true;
            $_SESSION['Biblioteca'] = true;
            $_SESSION['Entrada/saída'] = true;
        }
        // Coordenadores têm acesso limitado
        elseif ($setor === 'Coordenação' || $tipo === 'coordenador') {
            $_SESSION['Estoque'] = true;
            $_SESSION['Biblioteca'] = true;
            $_SESSION['Entrada/saída'] = true;
        }
        // Professores têm acesso básico
        elseif ($setor === 'Professor' || $tipo === 'professor') {
            $_SESSION['Biblioteca'] = true;
        }
        // Funcionários da merenda
        elseif ($setor === 'Merenda' || $tipo === 'merenda') {
            $_SESSION['Estoque'] = true;
            $_SESSION['Entrada/saída'] = true;
        }
    }
}

// Verifica se foi solicitado logout
if (isset($_GET['sair'])) {
    $session = new sessions();
    $session->destruir_session();
    header('Location: ../../views/auth/login.php');
    exit();
}
?>