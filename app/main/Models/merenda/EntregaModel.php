<?php
/**
 * EntregaModel - Model para acompanhamento de entregas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            $pedidoCestaId = isset($dados['pedido_cesta_id']) ? $dados['pedido_cesta_id'] : null;
            $escolaId = $dados['escola_id'];
            $fornecedorId = isset($dados['fornecedor_id']) ? $dados['fornecedor_id'] : null;
            $dataPrevista = $dados['data_prevista'];
            $transportadora = $dados['transportadora'] ?? null;
            $notaFiscal = $dados['nota_fiscal'] ?? null;
            $observacoes = $dados['observacoes'] ?? null;
            
            // Validar se o usuario_id existe na tabela usuario antes de usar
            $registradoPor = null;
            if (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
                $usuarioId = (int)$_SESSION['usuario_id'];
                // Verificar se o usuário existe na tabela
                $sqlCheck = "SELECT id FROM usuario WHERE id = :id LIMIT 1";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bindParam(':id', $usuarioId, PDO::PARAM_INT);
                $stmtCheck->execute();
                if ($stmtCheck->fetch()) {
                    $registradoPor = $usuarioId;
                }
            }
            
            $stmt->bindParam(':pedido_cesta_id', $pedidoCestaId);
            $stmt->bindParam(':escola_id', $escolaId);
            $stmt->bindParam(':fornecedor_id', $fornecedorId);
            $stmt->bindParam(':data_prevista', $dataPrevista);
            $stmt->bindParam(':transportadora', $transportadora);
            $stmt->bindParam(':nota_fiscal', $notaFiscal);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':registrado_por', $registradoPor);
            $stmt->execute();
            
            $entregaId = $conn->lastInsertId();
            
            // Adicionar itens da entrega
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO entrega_item (entrega_id, produto_id, quantidade_solicitada, quantidade_entregue)
                               VALUES (:entrega_id, :produto_id, :quantidade_solicitada, :quantidade_entregue)";
                    $stmtItem = $conn->prepare($sqlItem);
                    
                    // Criar variáveis explícitas para bindParam (precisa ser por referência)
                    $itemEntregaId = $entregaId;
                    $itemProdutoId = $item['produto_id'];
                    $itemQuantidadeSolicitada = $item['quantidade_solicitada'];
                    $itemQuantidadeEntregue = $item['quantidade_entregue'] ?? $item['quantidade_solicitada'];
                    
                    $stmtItem->bindParam(':entrega_id', $itemEntregaId);
                    $stmtItem->bindParam(':produto_id', $itemProdutoId);
                    $stmtItem->bindParam(':quantidade_solicitada', $itemQuantidadeSolicitada);
                    $stmtItem->bindParam(':quantidade_entregue', $itemQuantidadeEntregue);
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
        $dataEntrega = $dados['data_entrega'] ?? date('Y-m-d');
        
        // Validar se o usuario_id existe na tabela usuario antes de usar
        $recebidoPor = null;
        if (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
            $usuarioId = (int)$_SESSION['usuario_id'];
            // Verificar se o usuário existe na tabela
            $sqlCheck = "SELECT id FROM usuario WHERE id = :id LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $usuarioId, PDO::PARAM_INT);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                $recebidoPor = $usuarioId;
            }
        }
        
        $observacoes = $dados['observacoes'] ?? null;
        $idParam = $id;
        $stmt->bindParam(':data_entrega', $dataEntrega);
        $stmt->bindParam(':recebido_por', $recebidoPor);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':id', $idParam);
        
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

