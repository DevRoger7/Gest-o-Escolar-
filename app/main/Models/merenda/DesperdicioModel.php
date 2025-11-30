<?php
/**
 * DesperdicioModel - Model para monitoramento de desperdício
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class DesperdicioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra desperdício
     */
    public function registrar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO desperdicio (escola_id, data, turno, produto_id, quantidade, unidade_medida,
                peso_kg, motivo, motivo_detalhado, observacoes, registrado_por, registrado_em)
                VALUES (:escola_id, :data, :turno, :produto_id, :quantidade, :unidade_medida,
                :peso_kg, :motivo, :motivo_detalhado, :observacoes, :registrado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':data', $dados['data']);
        $stmt->bindParam(':turno', $dados['turno'] ?? null);
        $stmt->bindParam(':produto_id', $dados['produto_id'] ?? null);
        $stmt->bindParam(':quantidade', $dados['quantidade'] ?? null);
        $stmt->bindParam(':unidade_medida', $dados['unidade_medida'] ?? null);
        $stmt->bindParam(':peso_kg', $dados['peso_kg'] ?? null);
        $stmt->bindParam(':motivo', $dados['motivo'] ?? 'OUTROS');
        $stmt->bindParam(':motivo_detalhado', $dados['motivo_detalhado'] ?? null);
        $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
        $stmt->bindParam(':registrado_por', $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao registrar desperdício'];
    }
    
    /**
     * Lista desperdícios
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT d.*, e.nome as escola_nome, p.nome as produto_nome
                FROM desperdicio d
                INNER JOIN escola e ON d.escola_id = e.id
                LEFT JOIN produto p ON d.produto_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND d.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND d.data >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND d.data <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        if (!empty($filtros['motivo'])) {
            $sql .= " AND d.motivo = :motivo";
            $params[':motivo'] = $filtros['motivo'];
        }
        
        $sql .= " ORDER BY d.data DESC, d.registrado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula total de desperdício por período
     */
    public function calcularTotal($escolaId, $dataInicio, $dataFim) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT 
                    SUM(peso_kg) as total_peso_kg,
                    COUNT(*) as total_registros,
                    motivo,
                    SUM(CASE WHEN motivo = 'EXCESSO_PREPARO' THEN 1 ELSE 0 END) as excesso_preparo,
                    SUM(CASE WHEN motivo = 'REJEICAO_ALUNOS' THEN 1 ELSE 0 END) as rejeicao_alunos
                FROM desperdicio
                WHERE escola_id = :escola_id AND data BETWEEN :data_inicio AND :data_fim
                GROUP BY motivo";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':data_inicio', $dataInicio);
        $stmt->bindParam(':data_fim', $dataFim);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

