<?php
/**
 * CardapioModel - Model para gerenciamento de cardápios
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class CardapioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista cardápios
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, e.nome as escola_nome, u.username as criado_por_nome
                FROM cardapio c
                INNER JOIN escola e ON c.escola_id = e.id
                LEFT JOIN usuario u ON c.criado_por = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND c.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['mes'])) {
            $sql .= " AND c.mes = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        
        if (!empty($filtros['ano'])) {
            $sql .= " AND c.ano = :ano";
            $params[':ano'] = $filtros['ano'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['criado_por'])) {
            $sql .= " AND c.criado_por = :criado_por";
            $params[':criado_por'] = $filtros['criado_por'];
        }
        
        $sql .= " ORDER BY c.ano DESC, c.mes DESC, c.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca cardápio por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, e.nome as escola_nome
                FROM cardapio c
                INNER JOIN escola e ON c.escola_id = e.id
                WHERE c.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $cardapio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cardapio) {
            // Buscar itens do cardápio
            $sqlItens = "SELECT ci.*, p.nome as produto_nome, p.unidade_medida
                        FROM cardapio_item ci
                        INNER JOIN produto p ON ci.produto_id = p.id
                        WHERE ci.cardapio_id = :cardapio_id
                        ORDER BY p.nome ASC";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $id);
            $stmtItens->execute();
            $cardapio['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $cardapio;
    }
    
    /**
     * Cria novo cardápio
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO cardapio (escola_id, mes, ano, status, criado_por, criado_em)
                    VALUES (:escola_id, :mes, :ano, 'RASCUNHO', :criado_por, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $dados['escola_id']);
            $stmt->bindParam(':mes', $dados['mes']);
            $stmt->bindParam(':ano', $dados['ano']);
            $stmt->bindParam(':criado_por', $_SESSION['usuario_id']);
            $stmt->execute();
            
            $cardapioId = $conn->lastInsertId();
            
            // Adicionar itens
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO cardapio_item (cardapio_id, produto_id, quantidade)
                               VALUES (:cardapio_id, :produto_id, :quantidade)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':cardapio_id', $cardapioId);
                    $stmtItem->bindParam(':produto_id', $item['produto_id']);
                    $stmtItem->bindParam(':quantidade', $item['quantidade']);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $cardapioId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Aprova cardápio (ADM_MERENDA)
     */
    public function aprovar($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE cardapio SET status = 'APROVADO', aprovado_por = :aprovado_por,
                data_aprovacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':aprovado_por', $_SESSION['usuario_id']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Publica cardápio
     */
    public function publicar($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE cardapio SET status = 'PUBLICADO' WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

?>

