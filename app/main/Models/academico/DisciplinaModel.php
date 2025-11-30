<?php
/**
 * DisciplinaModel - Model para gerenciamento de disciplinas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class DisciplinaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todas as disciplinas
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM disciplina WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (nome LIKE :busca OR codigo LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        if (!empty($filtros['area_conhecimento'])) {
            $sql .= " AND area_conhecimento = :area_conhecimento";
            $params[':area_conhecimento'] = $filtros['area_conhecimento'];
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
     * Busca disciplina por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM disciplina WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria nova disciplina
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO disciplina (codigo, nome, carga_horaria, descricao, area_conhecimento, ativo)
                VALUES (:codigo, :nome, :carga_horaria, :descricao, :area_conhecimento, 1)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':codigo', $dados['codigo'] ?? null);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':carga_horaria', $dados['carga_horaria'] ?? null);
        $stmt->bindParam(':descricao', $dados['descricao'] ?? null);
        $stmt->bindParam(':area_conhecimento', $dados['area_conhecimento'] ?? null);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar disciplina'];
    }
    
    /**
     * Atualiza disciplina
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE disciplina SET codigo = :codigo, nome = :nome, carga_horaria = :carga_horaria,
                descricao = :descricao, area_conhecimento = :area_conhecimento, ativo = :ativo
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':codigo', $dados['codigo'] ?? null);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':carga_horaria', $dados['carga_horaria'] ?? null);
        $stmt->bindParam(':descricao', $dados['descricao'] ?? null);
        $stmt->bindParam(':area_conhecimento', $dados['area_conhecimento'] ?? null);
        $stmt->bindParam(':ativo', $dados['ativo'] ?? 1);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Exclui disciplina
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE disciplina SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

?>

