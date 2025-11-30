<?php
/**
 * ConsumoDiarioModel - Model para registro de consumo diário
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class ConsumoDiarioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra consumo diário
     */
    public function registrar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO consumo_diario (escola_id, turma_id, data, turno, total_alunos,
                    alunos_atendidos, observacoes, registrado_por, registrado_em)
                    VALUES (:escola_id, :turma_id, :data, :turno, :total_alunos,
                    :alunos_atendidos, :observacoes, :registrado_por, NOW())
                    ON DUPLICATE KEY UPDATE total_alunos = :total_alunos,
                    alunos_atendidos = :alunos_atendidos, observacoes = :observacoes,
                    atualizado_em = NOW()";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $dados['escola_id']);
            $stmt->bindParam(':turma_id', $dados['turma_id'] ?? null);
            $stmt->bindParam(':data', $dados['data']);
            $stmt->bindParam(':turno', $dados['turno'] ?? null);
            $stmt->bindParam(':total_alunos', $dados['total_alunos'] ?? 0);
            $stmt->bindParam(':alunos_atendidos', $dados['alunos_atendidos'] ?? 0);
            $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
            $stmt->bindParam(':registrado_por', $_SESSION['usuario_id']);
            $stmt->execute();
            
            $consumoId = $conn->lastInsertId();
            
            // Adicionar itens consumidos
            if (!empty($dados['itens'])) {
                // Remover itens antigos
                $sqlDelete = "DELETE FROM consumo_item WHERE consumo_diario_id = :consumo_id";
                $stmtDelete = $conn->prepare($sqlDelete);
                $stmtDelete->bindParam(':consumo_id', $consumoId);
                $stmtDelete->execute();
                
                // Inserir novos itens
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO consumo_item (consumo_diario_id, produto_id, quantidade, unidade_medida)
                               VALUES (:consumo_diario_id, :produto_id, :quantidade, :unidade_medida)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':consumo_diario_id', $consumoId);
                    $stmtItem->bindParam(':produto_id', $item['produto_id']);
                    $stmtItem->bindParam(':quantidade', $item['quantidade']);
                    $stmtItem->bindParam(':unidade_medida', $item['unidade_medida'] ?? null);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $consumoId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Lista consumo diário
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT cd.*, e.nome as escola_nome, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                FROM consumo_diario cd
                INNER JOIN escola e ON cd.escola_id = e.id
                LEFT JOIN turma t ON cd.turma_id = t.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND cd.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['data'])) {
            $sql .= " AND cd.data = :data";
            $params[':data'] = $filtros['data'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND cd.data >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND cd.data <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        $sql .= " ORDER BY cd.data DESC, cd.registrado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

