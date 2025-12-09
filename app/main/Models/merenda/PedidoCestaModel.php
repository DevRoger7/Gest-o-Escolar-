<?php
/**
 * PedidoCestaModel - Model para gerenciamento de pedidos de cesta
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class PedidoCestaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista pedidos
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pc.*, e.nome as escola_nome, u.username as nutricionista_nome,
                       u2.username as aprovado_por_nome
                FROM pedido_cesta pc
                INNER JOIN escola e ON pc.escola_id = e.id
                LEFT JOIN usuario u ON pc.nutricionista_id = u.id
                LEFT JOIN usuario u2 ON pc.aprovado_por = u2.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND pc.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND pc.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['mes'])) {
            $sql .= " AND pc.mes = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        
        if (!empty($filtros['nutricionista_id'])) {
            $sql .= " AND pc.nutricionista_id = :nutricionista_id";
            $params[':nutricionista_id'] = $filtros['nutricionista_id'];
        }
        
        $sql .= " ORDER BY pc.data_criacao DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca pedido por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pc.*, e.nome as escola_nome
                FROM pedido_cesta pc
                INNER JOIN escola e ON pc.escola_id = e.id
                WHERE pc.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pedido) {
            // Buscar itens do pedido
            $sqlItens = "SELECT pi.*, p.nome as produto_nome, p.unidade_medida
                        FROM pedido_item pi
                        INNER JOIN produto p ON pi.produto_id = p.id
                        WHERE pi.pedido_id = :pedido_id
                        ORDER BY p.nome ASC";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':pedido_id', $id);
            $stmtItens->execute();
            $pedido['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $pedido;
    }
    
    /**
     * Cria novo pedido
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO pedido_cesta (escola_id, mes, nutricionista_id, status, data_criacao)
                    VALUES (:escola_id, :mes, :nutricionista_id, 'RASCUHO', NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $dados['escola_id']);
            $stmt->bindParam(':mes', $dados['mes']);
            $stmt->bindParam(':nutricionista_id', $_SESSION['usuario_id']);
            $stmt->execute();
            
            $pedidoId = $conn->lastInsertId();
            
            // Adicionar itens
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO pedido_item (pedido_id, produto_id, quantidade_solicitada, obs)
                               VALUES (:pedido_id, :produto_id, :quantidade_solicitada, :obs)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':pedido_id', $pedidoId);
                    $stmtItem->bindParam(':produto_id', $item['produto_id']);
                    $stmtItem->bindParam(':quantidade_solicitada', $item['quantidade_solicitada']);
                    $stmtItem->bindParam(':obs', $item['obs'] ?? null);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $pedidoId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Envia pedido (NUTRICIONISTA)
     */
    public function enviar($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE pedido_cesta SET status = 'ENVIADO', data_envio = NOW() WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Aprova pedido (ADM_MERENDA)
     */
    public function aprovar($id, $observacoes = null) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE pedido_cesta SET status = 'APROVADO', aprovado_por = :aprovado_por,
                data_aprovacao = NOW(), observacoes = :observacoes WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $aprovadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $obs = $observacoes ?? null;
        $idParam = $id;
        $stmt->bindParam(':aprovado_por', $aprovadoPor);
        $stmt->bindParam(':observacoes', $obs);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
    
    /**
     * Rejeita pedido (ADM_MERENDA)
     */
    public function rejeitar($id, $motivoRejeicao) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE pedido_cesta SET status = 'REJEITADO', motivo_rejeicao = :motivo_rejeicao
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':motivo_rejeicao', $motivoRejeicao);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

?>

