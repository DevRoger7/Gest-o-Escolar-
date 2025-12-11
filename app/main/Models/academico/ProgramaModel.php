<?php
/**
 * ProgramaModel - Model para gerenciamento de programas educacionais
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class ProgramaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os programas
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM programa WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (nome LIKE :busca OR descricao LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca programa por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM programa WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo programa
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO programa (nome, descricao, ativo, criado_em, atualizado_em)
                    VALUES (:nome, :descricao, :ativo, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $ativo = isset($dados['ativo']) ? (int)$dados['ativo'] : 1;
            $stmt->bindParam(':ativo', $ativo, PDO::PARAM_INT);
            
            $stmt->execute();
            $id = $conn->lastInsertId();
            
            $conn->commit();
            return ['success' => true, 'id' => $id];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro ao criar programa: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Atualiza programa existente
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "UPDATE programa SET nome = :nome, descricao = :descricao, ativo = :ativo, atualizado_em = NOW()
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $ativo = isset($dados['ativo']) ? (int)$dados['ativo'] : 1;
            $stmt->bindParam(':ativo', $ativo, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro ao atualizar programa: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Exclui programa (soft delete - desativa)
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "UPDATE programa SET ativo = 0, atualizado_em = NOW() WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro ao excluir programa: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

?>


