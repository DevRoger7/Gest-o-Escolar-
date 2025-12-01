<?php
require_once('../../config/Database.php');

class RecuperarSenhaModel {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        
        // Criar tabela de tokens se não existir
        $this->criarTabelaTokens();
    }
    
    /**
     * Cria a tabela de tokens de recuperação se não existir
     */
    private function criarTabelaTokens() {
        $sql = "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `usuario_id` bigint(20) NOT NULL,
            `token` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `expira_em` datetime NOT NULL,
            `usado` tinyint(1) DEFAULT 0,
            `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `usuario_id` (`usuario_id`),
            KEY `expira_em` (`expira_em`),
            KEY `usado` (`usado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        try {
            $this->conn->exec($sql);
        } catch (PDOException $e) {
            // Tabela já existe ou erro ao criar
            error_log("Erro ao criar tabela password_reset_tokens: " . $e->getMessage());
        }
    }
    
    /**
     * Busca usuário por CPF e email
     */
    public function buscarUsuarioPorCpfEmail($cpf, $email) {
        $sql = "SELECT u.id as usuario_id, u.pessoa_id, p.nome, p.email, p.cpf
                FROM usuario u
                INNER JOIN pessoa p ON u.pessoa_id = p.id
                WHERE p.cpf = :cpf AND p.email = :email AND u.ativo = 1
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gera token de recuperação de senha
     */
    public function gerarTokenRecuperacao($usuarioId) {
        // Buscar dados do usuário
        $sql = "SELECT u.id, p.email
                FROM usuario u
                INNER JOIN pessoa p ON u.pessoa_id = p.id
                WHERE u.id = :usuario_id
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return false;
        }
        
        // Invalidar tokens anteriores do usuário
        $this->invalidarTokensAnteriores($usuarioId);
        
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        
        // Definir expiração (24 horas)
        $expiraEm = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Inserir token
        $sql = "INSERT INTO password_reset_tokens (usuario_id, token, email, expira_em, usado)
                VALUES (:usuario_id, :token, :email, :expira_em, 0)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $usuario['email']);
        $stmt->bindParam(':expira_em', $expiraEm);
        
        if ($stmt->execute()) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Invalida tokens anteriores do usuário
     */
    private function invalidarTokensAnteriores($usuarioId) {
        $sql = "UPDATE password_reset_tokens 
                SET usado = 1 
                WHERE usuario_id = :usuario_id AND usado = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
    }
    
    /**
     * Verifica se o token é válido
     */
    public function verificarToken($token) {
        $sql = "SELECT * FROM password_reset_tokens
                WHERE token = :token
                AND usado = 0
                AND expira_em > NOW()
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado !== false;
    }
    
    /**
     * Redefine a senha usando o token
     */
    public function redefinirSenha($token, $novaSenha) {
        // Verificar token
        $sql = "SELECT * FROM password_reset_tokens
                WHERE token = :token
                AND usado = 0
                AND expira_em > NOW()
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenData) {
            return false;
        }
        
        try {
            $this->conn->beginTransaction();
            
            // Gerar hash da nova senha
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            // Atualizar senha do usuário
            $sql = "UPDATE usuario 
                    SET senha_hash = :senha_hash 
                    WHERE id = :usuario_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':senha_hash', $senhaHash);
            $stmt->bindParam(':usuario_id', $tokenData['usuario_id']);
            $stmt->execute();
            
            // Marcar token como usado
            $sql = "UPDATE password_reset_tokens 
                    SET usado = 1 
                    WHERE token = :token";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Erro ao redefinir senha: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Redefine senha diretamente sem token (para desenvolvimento)
     */
    public function redefinirSenhaDireta($usuarioId, $novaSenha) {
        try {
            // Gerar hash da nova senha
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            // Atualizar senha do usuário
            $sql = "UPDATE usuario 
                    SET senha_hash = :senha_hash 
                    WHERE id = :usuario_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':senha_hash', $senhaHash);
            $stmt->bindParam(':usuario_id', $usuarioId);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erro ao redefinir senha: " . $e->getMessage());
            return false;
        }
    }
}

?>

