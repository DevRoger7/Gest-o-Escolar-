<?php
/**
 * CustoMerendaModel - Model para monitoramento de custos
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class CustoMerendaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra custo
     */
    public function registrar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO custo_merenda (escola_id, tipo, descricao, produto_id, fornecedor_id,
                quantidade, valor_unitario, valor_total, data, mes, ano, observacoes, registrado_por, registrado_em)
                VALUES (:escola_id, :tipo, :descricao, :produto_id, :fornecedor_id,
                :quantidade, :valor_unitario, :valor_total, :data, :mes, :ano, :observacoes, :registrado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $dados['escola_id'] ?? null);
        $stmt->bindParam(':tipo', $dados['tipo'] ?? 'OUTROS');
        $stmt->bindParam(':descricao', $dados['descricao'] ?? null);
        $stmt->bindParam(':produto_id', $dados['produto_id'] ?? null);
        $stmt->bindParam(':fornecedor_id', $dados['fornecedor_id'] ?? null);
        $stmt->bindParam(':quantidade', $dados['quantidade'] ?? null);
        $stmt->bindParam(':valor_unitario', $dados['valor_unitario'] ?? null);
        $stmt->bindParam(':valor_total', $dados['valor_total']);
        $stmt->bindParam(':data', $dados['data']);
        $stmt->bindParam(':mes', $dados['mes'] ?? date('n'));
        $stmt->bindParam(':ano', $dados['ano'] ?? date('Y'));
        $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
        $stmt->bindParam(':registrado_por', $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao registrar custo'];
    }
    
    /**
     * Lista custos
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, e.nome as escola_nome, p.nome as produto_nome, f.nome as fornecedor_nome
                FROM custo_merenda c
                LEFT JOIN escola e ON c.escola_id = e.id
                LEFT JOIN produto p ON c.produto_id = p.id
                LEFT JOIN fornecedor f ON c.fornecedor_id = f.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND c.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['tipo'])) {
            $sql .= " AND c.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        
        if (!empty($filtros['mes'])) {
            $sql .= " AND c.mes = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        
        if (!empty($filtros['ano'])) {
            $sql .= " AND c.ano = :ano";
            $params[':ano'] = $filtros['ano'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND c.data >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND c.data <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        $sql .= " ORDER BY c.data DESC, c.registrado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula total de custos por período
     */
    public function calcularTotal($escolaId = null, $mes = null, $ano = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT 
                    SUM(valor_total) as total_custos,
                    tipo,
                    COUNT(*) as total_registros
                FROM custo_merenda
                WHERE 1=1";
        
        $params = [];
        
        if ($escolaId) {
            $sql .= " AND escola_id = :escola_id";
            $params[':escola_id'] = $escolaId;
        }
        
        if ($mes) {
            $sql .= " AND mes = :mes";
            $params[':mes'] = $mes;
        }
        
        if ($ano) {
            $sql .= " AND ano = :ano";
            $params[':ano'] = $ano;
        }
        
        $sql .= " GROUP BY tipo";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

