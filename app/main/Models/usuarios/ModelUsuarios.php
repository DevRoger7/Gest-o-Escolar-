<?php

class ModelUsuarios {
    
    public function listarUsuarios() {
        require_once(__DIR__ . "/../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT u.id, u.tipo, u.ultimo_login, u.bloqueado, u.data_criacao,
                       p.nome, p.cpf, p.email, p.telefone, p.ativo
                FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id 
                ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function cadastrarUsuario($dadosPessoa, $dadosUsuario) {
        require_once(__DIR__ . "/../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        try {
            // Iniciar transação
            $conn->beginTransaction();
            
            // Inserir na tabela pessoa
            $sqlPessoa = "INSERT INTO pessoa (nome, cpf, email, telefone, endereco, data_nascimento) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->execute([
                $dadosPessoa['nome'],
                $dadosPessoa['cpf'],
                $dadosPessoa['email'],
                $dadosPessoa['telefone'] ?? null,
                $dadosPessoa['endereco'] ?? null,
                $dadosPessoa['data_nascimento'] ?? null
            ]);
            
            $pessoaId = $conn->lastInsertId();
            
            // Inserir na tabela usuario
            $sqlUsuario = "INSERT INTO usuario (pessoa_id, senha, tipo) VALUES (?, ?, ?)";
            
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->execute([
                $pessoaId,
                password_hash($dadosUsuario['senha'], PASSWORD_DEFAULT),
                $dadosUsuario['tipo']
            ]);
            
            // Confirmar transação
            $conn->commit();
            
            return [
                'sucesso' => true,
                'mensagem' => 'Usuário cadastrado com sucesso!',
                'usuario_id' => $conn->lastInsertId(),
                'pessoa_id' => $pessoaId
            ];
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $conn->rollback();
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao cadastrar usuário: ' . $e->getMessage()
            ];
        }
    }
    
    public function verificarCpfExistente($cpf) {
        require_once(__DIR__ . "/../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT COUNT(*) FROM pessoa WHERE cpf = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cpf]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function verificarEmailExistente($email) {
        require_once(__DIR__ . "/../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT COUNT(*) FROM pessoa WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function alterarStatusUsuario($usuarioId, $ativo) {
        require_once(__DIR__ . "/../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "UPDATE pessoa p 
                INNER JOIN usuario u ON p.id = u.pessoa_id 
                SET p.ativo = ? 
                WHERE u.id = ?";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$ativo, $usuarioId]);
    }
    
    public function obterUsuarioPorId($usuarioId) {
        require_once(__DIR__ . "/../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT u.id, u.tipo, u.ultimo_login, u.bloqueado, u.data_criacao,
                       p.nome, p.cpf, p.email, p.telefone, p.endereco, p.data_nascimento, p.ativo
                FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id 
                WHERE u.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>