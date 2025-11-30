<?php
/**
 * SerieModel - Model para gerenciamento de séries
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class SerieModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todas as séries
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM serie WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['nivel_ensino'])) {
            $sql .= " AND nivel_ensino = :nivel_ensino";
            $params[':nivel_ensino'] = $filtros['nivel_ensino'];
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " ORDER BY ordem ASC, nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca série por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM serie WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria nova série
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO serie (nome, codigo, nivel_ensino, ordem, idade_minima, idade_maxima, descricao, ativo, criado_por)
                VALUES (:nome, :codigo, :nivel_ensino, :ordem, :idade_minima, :idade_maxima, :descricao, 1, :criado_por)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':codigo', $dados['codigo'] ?? null);
        $stmt->bindParam(':nivel_ensino', $dados['nivel_ensino'] ?? 'ENSINO_FUNDAMENTAL');
        $stmt->bindParam(':ordem', $dados['ordem'] ?? null);
        $stmt->bindParam(':idade_minima', $dados['idade_minima'] ?? null);
        $stmt->bindParam(':idade_maxima', $dados['idade_maxima'] ?? null);
        $stmt->bindParam(':descricao', $dados['descricao'] ?? null);
        $stmt->bindParam(':criado_por', $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar série'];
    }
    
    /**
     * Atualiza série
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE serie SET nome = :nome, codigo = :codigo, nivel_ensino = :nivel_ensino,
                ordem = :ordem, idade_minima = :idade_minima, idade_maxima = :idade_maxima,
                descricao = :descricao, ativo = :ativo
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':codigo', $dados['codigo'] ?? null);
        $stmt->bindParam(':nivel_ensino', $dados['nivel_ensino']);
        $stmt->bindParam(':ordem', $dados['ordem'] ?? null);
        $stmt->bindParam(':idade_minima', $dados['idade_minima'] ?? null);
        $stmt->bindParam(':idade_maxima', $dados['idade_maxima'] ?? null);
        $stmt->bindParam(':descricao', $dados['descricao'] ?? null);
        $stmt->bindParam(':ativo', $dados['ativo'] ?? 1);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Exclui série
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE serie SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

?>

