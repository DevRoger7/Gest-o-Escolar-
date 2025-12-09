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
        
        try {
            $escolaId = isset($dados['escola_id']) && $dados['escola_id'] !== '' ? (int)$dados['escola_id'] : null;
            if ($escolaId !== null) {
                $stmtChkEscola = $conn->prepare("SELECT id FROM escola WHERE id = :id LIMIT 1");
                $stmtChkEscola->bindValue(':id', $escolaId, PDO::PARAM_INT);
                $stmtChkEscola->execute();
                if (!$stmtChkEscola->fetch(PDO::FETCH_ASSOC)) {
                    $escolaId = null;
                }
            }
            $produtoId = isset($dados['produto_id']) && $dados['produto_id'] !== '' ? (int)$dados['produto_id'] : null;
            if ($produtoId !== null) {
                $stmtChkProd = $conn->prepare("SELECT id FROM produto WHERE id = :id LIMIT 1");
                $stmtChkProd->bindValue(':id', $produtoId, PDO::PARAM_INT);
                $stmtChkProd->execute();
                if (!$stmtChkProd->fetch(PDO::FETCH_ASSOC)) {
                    $produtoId = null;
                }
            }
            $fornecedorId = isset($dados['fornecedor_id']) && $dados['fornecedor_id'] !== '' ? (int)$dados['fornecedor_id'] : null;
            if ($fornecedorId !== null) {
                $stmtChkForn = $conn->prepare("SELECT id FROM fornecedor WHERE id = :id LIMIT 1");
                $stmtChkForn->bindValue(':id', $fornecedorId, PDO::PARAM_INT);
                $stmtChkForn->execute();
                if (!$stmtChkForn->fetch(PDO::FETCH_ASSOC)) {
                    $fornecedorId = null;
                }
            }
            $tipoValido = ['COMPRA_PRODUTOS','DISTRIBUICAO','PREPARO','DESPERDICIO','OUTROS'];
            $tipo = (isset($dados['tipo']) && in_array($dados['tipo'], $tipoValido)) ? $dados['tipo'] : 'OUTROS';
            $descricao = isset($dados['descricao']) && $dados['descricao'] !== '' ? $dados['descricao'] : null;
            $quantidade = isset($dados['quantidade']) && $dados['quantidade'] !== '' ? $dados['quantidade'] : null;
            $valorUnitario = isset($dados['valor_unitario']) && $dados['valor_unitario'] !== '' ? $dados['valor_unitario'] : null;
            $valorTotal = isset($dados['valor_total']) ? $dados['valor_total'] : 0;
            $data = $dados['data'];
            $observacoes = isset($dados['observacoes']) && $dados['observacoes'] !== '' ? $dados['observacoes'] : null;
            $mes = isset($dados['mes']) && $dados['mes'] !== '' ? (int)$dados['mes'] : (int)date('n', strtotime($data));
            $ano = isset($dados['ano']) && $dados['ano'] !== '' ? (int)$dados['ano'] : (int)date('Y', strtotime($data));
            $usuarioId = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
            if ($usuarioId !== null) {
                $stmtUsu = $conn->prepare("SELECT id FROM usuario WHERE id = :id LIMIT 1");
                $stmtUsu->bindValue(':id', $usuarioId, PDO::PARAM_INT);
                $stmtUsu->execute();
                if (!$stmtUsu->fetch(PDO::FETCH_ASSOC)) {
                    $usuarioId = null;
                }
            }
            $sql = "INSERT INTO custo_merenda (escola_id, tipo, descricao, produto_id, fornecedor_id,
                    quantidade, valor_unitario, valor_total, data, mes, ano, observacoes, registrado_por, registrado_em)
                    VALUES (:escola_id, :tipo, :descricao, :produto_id, :fornecedor_id,
                    :quantidade, :valor_unitario, :valor_total, :data, :mes, :ano, :observacoes, :registrado_por, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':escola_id', $escolaId);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':produto_id', $produtoId);
            $stmt->bindValue(':fornecedor_id', $fornecedorId);
            $stmt->bindValue(':quantidade', $quantidade);
            $stmt->bindValue(':valor_unitario', $valorUnitario);
            $stmt->bindValue(':valor_total', $valorTotal);
            $stmt->bindValue(':data', $data);
            $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
            $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
            $stmt->bindValue(':observacoes', $observacoes);
            $stmt->bindValue(':registrado_por', $usuarioId);
            $stmt->execute();
            return ['success' => true, 'id' => $conn->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Lista custos
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, 
                CASE 
                    WHEN c.escola_id IS NULL THEN 'Compra Centralizada'
                    ELSE e.nome 
                END as escola_nome,
                p.nome as produto_nome, f.nome as fornecedor_nome
                FROM custo_merenda c
                LEFT JOIN escola e ON c.escola_id = e.id
                LEFT JOIN produto p ON c.produto_id = p.id
                LEFT JOIN fornecedor f ON c.fornecedor_id = f.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            // Se filtrar por escola, mostra custos daquela escola OU compras centralizadas
            $sql .= " AND (c.escola_id = :escola_id OR c.escola_id IS NULL)";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['tipo'])) {
            $sql .= " AND c.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        
        // Preferir filtro por intervalo de data quando mes/ano são informados,
        // pois registros antigos podem ter mes NULL, mas data sempre existe.
        if (!empty($filtros['mes']) && !empty($filtros['ano'])) {
            $inicio = date('Y-m-01', strtotime(sprintf('%04d-%02d-01', (int)$filtros['ano'], (int)$filtros['mes'])));
            $fim = date('Y-m-t', strtotime($inicio));
            $sql .= " AND c.data BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $inicio;
            $params[':data_fim'] = $fim;
        } elseif (!empty($filtros['ano'])) {
            $inicio = sprintf('%04d-01-01', (int)$filtros['ano']);
            $fim = sprintf('%04d-12-31', (int)$filtros['ano']);
            $sql .= " AND c.data BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $inicio;
            $params[':data_fim'] = $fim;
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
        
        if ($mes && $ano) {
            $inicio = date('Y-m-01', strtotime(sprintf('%04d-%02d-01', (int)$ano, (int)$mes)));
            $fim = date('Y-m-t', strtotime($inicio));
            $sql .= " AND data BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $inicio;
            $params[':data_fim'] = $fim;
        } elseif ($ano) {
            $inicio = sprintf('%04d-01-01', (int)$ano);
            $fim = sprintf('%04d-12-31', (int)$ano);
            $sql .= " AND data BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $inicio;
            $params[':data_fim'] = $fim;
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

