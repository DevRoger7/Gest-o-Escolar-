<?php

/**
 * Classe para gerenciar logs do sistema
 * Registra ações importantes no banco de dados na tabela log_sistema
 */
class SystemLogger {
    
    private static $instance = null;
    private $conn = null;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        require_once(__DIR__ . '/../../config/Database.php');
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    /**
     * Obter instância única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obter IP do cliente
     */
    private function getClientIP() {
        $ip = null;
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Se for uma lista de IPs (proxy), pegar o primeiro
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        return $ip ?: '0.0.0.0';
    }
    
    /**
     * Obter User Agent do cliente
     */
    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
    
    /**
     * Obter ID do usuário atual da sessão
     */
    private function getUsuarioId() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['usuario_id'] ?? null;
    }
    
    /**
     * Registrar log no banco de dados
     * 
     * @param string $acao Nome da ação realizada (ex: 'LOGIN', 'LOGOUT', 'CRIAR_USUARIO')
     * @param string $tipo Tipo do log: 'INFO', 'WARNING', 'ERROR', 'SECURITY'
     * @param string|null $descricao Descrição detalhada da ação
     * @param int|null $usuarioId ID do usuário (se null, pega da sessão)
     * @return bool True se registrado com sucesso, False caso contrário
     */
    public function log($acao, $tipo = 'INFO', $descricao = null, $usuarioId = null) {
        try {
            // Se não foi fornecido, pegar da sessão
            if ($usuarioId === null) {
                $usuarioId = $this->getUsuarioId();
            }
            
            $ip = $this->getClientIP();
            $userAgent = $this->getUserAgent();
            
            $sql = "INSERT INTO log_sistema (usuario_id, acao, tipo, descricao, ip, user_agent) 
                    VALUES (:usuario_id, :acao, :tipo, :descricao, :ip, :user_agent)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':acao', $acao);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':user_agent', $userAgent);
            
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            // Log do erro mas não interromper a execução
            error_log("Erro ao registrar log no sistema: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Métodos de conveniência para diferentes tipos de log
     */
    
    /**
     * Registrar log de informação
     */
    public function info($acao, $descricao = null, $usuarioId = null) {
        return $this->log($acao, 'INFO', $descricao, $usuarioId);
    }
    
    /**
     * Registrar log de aviso
     */
    public function warning($acao, $descricao = null, $usuarioId = null) {
        return $this->log($acao, 'WARNING', $descricao, $usuarioId);
    }
    
    /**
     * Registrar log de erro
     */
    public function error($acao, $descricao = null, $usuarioId = null) {
        return $this->log($acao, 'ERROR', $descricao, $usuarioId);
    }
    
    /**
     * Registrar log de segurança
     */
    public function security($acao, $descricao = null, $usuarioId = null) {
        return $this->log($acao, 'SECURITY', $descricao, $usuarioId);
    }
    
    /**
     * Registrar login bem-sucedido
     */
    public function logLogin($usuarioId, $username = null) {
        $descricao = $username ? "Login realizado por: {$username}" : "Login realizado";
        return $this->security('LOGIN', $descricao, $usuarioId);
    }
    
    /**
     * Registrar tentativa de login falha
     */
    public function logLoginFalha($cpfOuEmail, $motivo = null) {
        $descricao = "Tentativa de login falhou para: {$cpfOuEmail}";
        if ($motivo) {
            $descricao .= " - Motivo: {$motivo}";
        }
        return $this->security('LOGIN_FALHA', $descricao, null);
    }
    
    /**
     * Registrar logout
     */
    public function logLogout($usuarioId = null) {
        return $this->security('LOGOUT', 'Usuário realizou logout', $usuarioId);
    }
    
    /**
     * Registrar bloqueio de conta
     */
    public function logBloqueioConta($usuarioId, $motivo = null) {
        $descricao = "Conta bloqueada";
        if ($motivo) {
            $descricao .= " - Motivo: {$motivo}";
        }
        return $this->security('BLOQUEIO_CONTA', $descricao, $usuarioId);
    }
    
    /**
     * Registrar criação de usuário
     */
    public function logCriarUsuario($usuarioId, $novoUsuarioId, $nomeUsuario = null) {
        $descricao = $nomeUsuario ? "Usuário criado: {$nomeUsuario}" : "Novo usuário criado (ID: {$novoUsuarioId})";
        return $this->info('CRIAR_USUARIO', $descricao, $usuarioId);
    }
    
    /**
     * Registrar edição de usuário
     */
    public function logEditarUsuario($usuarioId, $usuarioEditadoId, $nomeUsuario = null) {
        $descricao = $nomeUsuario ? "Usuário editado: {$nomeUsuario}" : "Usuário editado (ID: {$usuarioEditadoId})";
        return $this->info('EDITAR_USUARIO', $descricao, $usuarioId);
    }
    
    /**
     * Registrar exclusão/desativação de usuário
     */
    public function logExcluirUsuario($usuarioId, $usuarioExcluidoId, $nomeUsuario = null) {
        $descricao = $nomeUsuario ? "Usuário desativado: {$nomeUsuario}" : "Usuário desativado (ID: {$usuarioExcluidoId})";
        return $this->warning('EXCLUIR_USUARIO', $descricao, $usuarioId);
    }
    
    /**
     * Registrar criação de escola
     */
    public function logCriarEscola($usuarioId, $escolaId, $nomeEscola = null) {
        $descricao = $nomeEscola ? "Escola criada: {$nomeEscola}" : "Escola criada (ID: {$escolaId})";
        return $this->info('CRIAR_ESCOLA', $descricao, $usuarioId);
    }
    
    /**
     * Registrar edição de escola
     */
    public function logEditarEscola($usuarioId, $escolaId, $nomeEscola = null) {
        $descricao = $nomeEscola ? "Escola editada: {$nomeEscola}" : "Escola editada (ID: {$escolaId})";
        return $this->info('EDITAR_ESCOLA', $descricao, $usuarioId);
    }
    
    /**
     * Registrar exclusão de escola
     */
    public function logExcluirEscola($usuarioId, $escolaId, $nomeEscola = null) {
        $descricao = $nomeEscola ? "Escola excluída: {$nomeEscola}" : "Escola excluída (ID: {$escolaId})";
        return $this->warning('EXCLUIR_ESCOLA', $descricao, $usuarioId);
    }
}

?>

