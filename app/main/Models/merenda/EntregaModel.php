<?php
/**
 * EntregaModel - Model para acompanhamento de entregas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class EntregaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Cria nova entrega
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO entrega (pedido_cesta_id, escola_id, fornecedor_id, data_prevista,
                    status, transportadora, nota_fiscal, observacoes, registrado_por, registrado_em)
                    VALUES (:pedido_cesta_id, :escola_id, :fornecedor_id, :data_prevista,
                    'AGENDADA', :transportadora, :nota_fiscal, :observacoes, :registrado_por, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':pedido_cesta_id', $dados['pedido_cesta_id'] ?? null);
            $stmt->bindParam(':escola_id', $dados['escola_id']);
            $stmt->bindParam(':fornecedor_id', $dados['fornecedor_id'] ?? null);
            $stmt->bindParam(':data_prevista', $dados['data_prevista']);
            $stmt->bindParam(':transportadora', $dados['transportadora'] ?? null);
            $stmt->bindParam(':nota_fiscal', $dados['nota_fiscal'] ?? null);
            $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
            $stmt->bindParam(':registrado_por', $_SESSION['usuario_id']);
            $stmt->execute();
            
            $entregaId = $conn->lastInsertId();
            
            // Adicionar itens da entrega
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO entrega_item (entrega_id, produto_id, quantidade_solicitada, quantidade_entregue)
                               VALUES (:entrega_id, :produto_id, :quantidade_solicitada, :quantidade_entregue)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':entrega_id', $entregaId);
                    $stmtItem->bindParam(':produto_id', $item['produto_id']);
                    $stmtItem->bindParam(':quantidade_solicitada', $item['quantidade_solicitada']);
                    $stmtItem->bindParam(':quantidade_entregue', $item['quantidade_entregue'] ?? $item['quantidade_solicitada']);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $entregaId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Registra recebimento da entrega
     */
    public function registrarRecebimento($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE entrega SET status = 'ENTREGUE', data_entrega = :data_entrega,
                recebido_por = :recebido_por, observacoes = :observacoes
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':data_entrega', $dados['data_entrega'] ?? date('Y-m-d'));
        $stmt->bindParam(':recebido_por', $_SESSION['usuario_id']);
        $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Lista entregas
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT e.*, esc.nome as escola_nome, f.nome as fornecedor_nome, pc.mes as pedido_mes
                FROM entrega e
                INNER JOIN escola esc ON e.escola_id = esc.id
                LEFT JOIN fornecedor f ON e.fornecedor_id = f.id
                LEFT JOIN pedido_cesta pc ON e.pedido_cesta_id = pc.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND e.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['data_prevista'])) {
            $sql .= " AND e.data_prevista = :data_prevista";
            $params[':data_prevista'] = $filtros['data_prevista'];
        }
        
        $sql .= " ORDER BY e.data_prevista DESC, e.registrado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

