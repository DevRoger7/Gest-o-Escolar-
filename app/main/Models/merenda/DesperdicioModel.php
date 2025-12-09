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
        
        try {
            $stmtChkEscola = $conn->prepare("SELECT id FROM escola WHERE id = :id LIMIT 1");
            $stmtChkEscola->bindValue(':id', (int)$dados['escola_id'], PDO::PARAM_INT);
            $stmtChkEscola->execute();
            if (!$stmtChkEscola->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Escola inválida'];
            }
            $turno = (isset($dados['turno']) && $dados['turno'] !== '') ? $dados['turno'] : null;
            $produtoId = (isset($dados['produto_id']) && $dados['produto_id'] !== '') ? (int)$dados['produto_id'] : null;
            if ($produtoId !== null) {
                $stmtChkProd = $conn->prepare("SELECT id FROM produto WHERE id = :id LIMIT 1");
                $stmtChkProd->bindValue(':id', $produtoId, PDO::PARAM_INT);
                $stmtChkProd->execute();
                if (!$stmtChkProd->fetch(PDO::FETCH_ASSOC)) {
                    $produtoId = null;
                }
            }
            $quantidade = (isset($dados['quantidade']) && $dados['quantidade'] !== '') ? $dados['quantidade'] : null;
            $unidadeMedida = (isset($dados['unidade_medida']) && $dados['unidade_medida'] !== '') ? $dados['unidade_medida'] : null;
            $pesoKg = (isset($dados['peso_kg']) && $dados['peso_kg'] !== '') ? $dados['peso_kg'] : null;
            $motivoValido = ['EXCESSO_PREPARO','REJEICAO_ALUNOS','VALIDADE_VENCIDA','PREPARO_INCORRETO','OUTROS'];
            $motivo = (isset($dados['motivo']) && in_array($dados['motivo'], $motivoValido)) ? $dados['motivo'] : 'OUTROS';
            $motivoDetalhado = (isset($dados['motivo_detalhado']) && $dados['motivo_detalhado'] !== '') ? $dados['motivo_detalhado'] : null;
            $observacoes = (isset($dados['observacoes']) && $dados['observacoes'] !== '') ? $dados['observacoes'] : null;
            $sql = "INSERT INTO desperdicio (escola_id, data, turno, produto_id, quantidade, unidade_medida,
                    peso_kg, motivo, motivo_detalhado, observacoes, registrado_por, registrado_em)
                    VALUES (:escola_id, :data, :turno, :produto_id, :quantidade, :unidade_medida,
                    :peso_kg, :motivo, :motivo_detalhado, :observacoes, :registrado_por, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':escola_id', (int)$dados['escola_id'], PDO::PARAM_INT);
            $stmt->bindValue(':data', $dados['data']);
            $stmt->bindValue(':turno', $turno);
            $stmt->bindValue(':produto_id', $produtoId);
            $stmt->bindValue(':quantidade', $quantidade);
            $stmt->bindValue(':unidade_medida', $unidadeMedida);
            $stmt->bindValue(':peso_kg', $pesoKg);
            $stmt->bindValue(':motivo', $motivo);
            $stmt->bindValue(':motivo_detalhado', $motivoDetalhado);
            $stmt->bindValue(':observacoes', $observacoes);
            $usuarioId = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
            if ($usuarioId !== null) {
                $stmtUsu = $conn->prepare("SELECT id FROM usuario WHERE id = :id LIMIT 1");
                $stmtUsu->bindValue(':id', $usuarioId, PDO::PARAM_INT);
                $stmtUsu->execute();
                if (!$stmtUsu->fetch(PDO::FETCH_ASSOC)) {
                    $usuarioId = null;
                }
            }
            $stmt->bindValue(':registrado_por', $usuarioId);
            $stmt->execute();
            return ['success' => true, 'id' => $conn->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
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

