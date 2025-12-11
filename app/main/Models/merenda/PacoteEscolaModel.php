                                                                                                                    <?php
/**
 * PacoteEscolaModel - Model para gerenciamento de pacotes de alimentos para escolas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class PacoteEscolaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os pacotes com filtros
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pe.*, e.nome as escola_nome
                FROM pacote_escola pe
                LEFT JOIN escola e ON pe.escola_id = e.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND pe.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (pe.descricao LIKE :busca OR e.nome LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        $sql .= " ORDER BY pe.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca pacote por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pe.*, e.nome as escola_nome
                FROM pacote_escola pe
                LEFT JOIN escola e ON pe.escola_id = e.id
                WHERE pe.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca itens de um pacote
     */
    public function buscarItens($pacoteId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pei.*, pr.nome as produto_nome, pr.unidade_medida,
                       ec.validade as validade_proxima,
                       ec.lote as lote
                FROM pacote_escola_item pei
                INNER JOIN produto pr ON pei.produto_id = pr.id
                LEFT JOIN estoque_central ec ON pei.estoque_central_id = ec.id
                WHERE pei.pacote_id = :pacote_id
                ORDER BY pr.nome ASC, ec.validade ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':pacote_id', $pacoteId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo pacote
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $descricao = $dados['descricao'] ?? null;
            $escolaId = !empty($dados['escola_id']) ? (int)$dados['escola_id'] : null;
            $dataEnvio = $dados['data_envio'] ?? date('Y-m-d');
            $observacoes = $dados['observacoes'] ?? null;
            $enviadoPor = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
            
            // Validar escola
            if (!$escolaId) {
                throw new Exception('Escola é obrigatória');
            }
            
            $stmtEscola = $conn->prepare("SELECT id FROM escola WHERE id = :id AND ativo = 1 LIMIT 1");
            $stmtEscola->bindParam(':id', $escolaId);
            $stmtEscola->execute();
            if (!$stmtEscola->fetch()) {
                throw new Exception('Escola não encontrada ou inativa');
            }
            
            // Validar itens
            if (empty($dados['itens']) || !is_array($dados['itens'])) {
                throw new Exception('É necessário adicionar pelo menos um item ao pacote');
            }
            
            // Verificar lotes duplicados (mesmo estoque_central_id)
            $estoquesIds = [];
            foreach ($dados['itens'] as $item) {
                if (!empty($item['estoque_id'])) {
                    $estoqueId = (int)$item['estoque_id'];
                    if (in_array($estoqueId, $estoquesIds)) {
                        throw new Exception('Não é permitido adicionar o mesmo lote mais de uma vez');
                    }
                    $estoquesIds[] = $estoqueId;
                }
            }
            
            // Inserir pacote
            $sql = "INSERT INTO pacote_escola (escola_id, descricao, data_envio, observacoes, enviado_por, criado_em)
                    VALUES (:escola_id, :descricao, :data_envio, :observacoes, :enviado_por, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':descricao', $descricao, $descricao ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':data_envio', $dataEnvio);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':enviado_por', $enviadoPor, $enviadoPor ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            $stmt->execute();
            $pacoteId = $conn->lastInsertId();
            
            // Inserir itens
            $sqlItem = "INSERT INTO pacote_escola_item (pacote_id, produto_id, estoque_central_id, quantidade, unidade_medida)
                       VALUES (:pacote_id, :produto_id, :estoque_central_id, :quantidade, :unidade_medida)";
            $stmtItem = $conn->prepare($sqlItem);
            
            foreach ($dados['itens'] as $item) {
                if (!empty($item['produto_id']) && !empty($item['quantidade'])) {
                    $produtoId = (int)$item['produto_id'];
                    $estoqueCentralId = !empty($item['estoque_id']) ? (int)$item['estoque_id'] : null;
                    $quantidade = floatval($item['quantidade']);
                    
                    // Buscar unidade de medida do produto
                    $stmtProd = $conn->prepare("SELECT id, unidade_medida FROM produto WHERE id = :id AND ativo = 1 LIMIT 1");
                    $stmtProd->bindParam(':id', $produtoId);
                    $stmtProd->execute();
                    $produto = $stmtProd->fetch(PDO::FETCH_ASSOC);
                    
                    if ($produto) {
                        $unidadeMedida = $produto['unidade_medida'] ?? null;
                        
                        $stmtItem->bindParam(':pacote_id', $pacoteId, PDO::PARAM_INT);
                        $stmtItem->bindParam(':produto_id', $produtoId, PDO::PARAM_INT);
                        $stmtItem->bindValue(':estoque_central_id', $estoqueCentralId, $estoqueCentralId ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmtItem->bindParam(':quantidade', $quantidade);
                        $stmtItem->bindValue(':unidade_medida', $unidadeMedida, $unidadeMedida ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $stmtItem->execute();
                    }
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $pacoteId, 'message' => 'Pacote criado com sucesso'];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Atualiza pacote
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $descricao = $dados['descricao'] ?? null;
            $escolaId = !empty($dados['escola_id']) ? (int)$dados['escola_id'] : null;
            $dataEnvio = $dados['data_envio'] ?? null;
            $observacoes = $dados['observacoes'] ?? null;
            
            $sql = "UPDATE pacote_escola SET 
                    descricao = :descricao,
                    escola_id = :escola_id,
                    data_envio = :data_envio,
                    observacoes = :observacoes
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':descricao', $descricao, $descricao ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':escola_id', $escolaId, $escolaId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':data_envio', $dataEnvio, $dataEnvio ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Atualizar itens se fornecidos
            if (isset($dados['itens']) && is_array($dados['itens'])) {
                // Verificar lotes duplicados (mesmo estoque_central_id)
                $estoquesIds = [];
                foreach ($dados['itens'] as $item) {
                    if (!empty($item['estoque_id'])) {
                        $estoqueId = (int)$item['estoque_id'];
                        if (in_array($estoqueId, $estoquesIds)) {
                            throw new Exception('Não é permitido adicionar o mesmo lote mais de uma vez');
                        }
                        $estoquesIds[] = $estoqueId;
                    }
                }
                
                // Validar itens
                if (empty($dados['itens'])) {
                    throw new Exception('É necessário adicionar pelo menos um item ao pacote');
                }
                
                // Remover itens antigos
                $sqlDelete = "DELETE FROM pacote_escola_item WHERE pacote_id = :pacote_id";
                $stmtDelete = $conn->prepare($sqlDelete);
                $stmtDelete->bindParam(':pacote_id', $id, PDO::PARAM_INT);
                $stmtDelete->execute();
                
                // Inserir novos itens
                $sqlItem = "INSERT INTO pacote_escola_item (pacote_id, produto_id, estoque_central_id, quantidade, unidade_medida)
                           VALUES (:pacote_id, :produto_id, :estoque_central_id, :quantidade, :unidade_medida)";
                $stmtItem = $conn->prepare($sqlItem);
                
                foreach ($dados['itens'] as $item) {
                    if (!empty($item['produto_id']) && !empty($item['quantidade'])) {
                        $produtoId = (int)$item['produto_id'];
                        $estoqueCentralId = !empty($item['estoque_id']) ? (int)$item['estoque_id'] : null;
                        $quantidade = floatval($item['quantidade']);
                        
                        // Validar produto
                        $stmtProd = $conn->prepare("SELECT id, unidade_medida FROM produto WHERE id = :id AND ativo = 1 LIMIT 1");
                        $stmtProd->bindParam(':id', $produtoId);
                        $stmtProd->execute();
                        $produto = $stmtProd->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$produto) {
                            throw new Exception("Produto ID {$produtoId} não encontrado ou inativo");
                        }
                        
                        $unidadeMedida = $produto['unidade_medida'] ?? null;
                        
                        $stmtItem->bindParam(':pacote_id', $id, PDO::PARAM_INT);
                        $stmtItem->bindParam(':produto_id', $produtoId, PDO::PARAM_INT);
                        $stmtItem->bindValue(':estoque_central_id', $estoqueCentralId, $estoqueCentralId ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmtItem->bindParam(':quantidade', $quantidade);
                        $stmtItem->bindValue(':unidade_medida', $unidadeMedida, $unidadeMedida ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $stmtItem->execute();
                    }
                }
            }
            
            $conn->commit();
            return ['success' => true, 'message' => 'Pacote atualizado com sucesso'];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Exclui pacote
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Excluir itens primeiro
            $sqlItens = "DELETE FROM pacote_escola_item WHERE pacote_id = :id";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtItens->execute();
            
            // Excluir pacote
            $sql = "DELETE FROM pacote_escola WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $conn->commit();
            return ['success' => true, 'message' => 'Pacote excluído com sucesso'];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

