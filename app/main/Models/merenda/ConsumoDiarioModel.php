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
            // Tentar localizar registro existente pela chave única
            $sqlFind = "SELECT id FROM consumo_diario 
                        WHERE escola_id = :escola_id AND data = :data "
                        . (isset($dados['turno']) && $dados['turno'] !== null && $dados['turno'] !== ''
                            ? "AND turno = :turno "
                            : "AND turno IS NULL ")
                        . (isset($dados['turma_id']) && $dados['turma_id'] !== '' && $dados['turma_id'] !== null 
                            ? "AND turma_id = :turma_id " 
                            : "AND turma_id IS NULL ")
                        . "LIMIT 1";
            $stmtFind = $conn->prepare($sqlFind);
            $stmtFind->bindValue(':escola_id', $dados['escola_id']);
            $stmtFind->bindValue(':data', $dados['data']);
            if (isset($dados['turno']) && $dados['turno'] !== null && $dados['turno'] !== '') {
                $stmtFind->bindValue(':turno', $dados['turno']);
            }
            if (isset($dados['turma_id']) && $dados['turma_id'] !== '' && $dados['turma_id'] !== null) {
                $stmtFind->bindValue(':turma_id', $dados['turma_id']);
            }
            $stmtFind->execute();
            $existente = $stmtFind->fetch(PDO::FETCH_ASSOC);

            if ($existente && isset($existente['id'])) {
                // Atualizar registro existente
                $consumoId = (int)$existente['id'];
                $sqlUpdate = "UPDATE consumo_diario SET 
                              total_alunos = :total_alunos,
                              alunos_atendidos = :alunos_atendidos,
                              observacoes = :observacoes,
                              atualizado_em = NOW(),
                              atualizado_por = :atualizado_por
                              WHERE id = :id";
                $stmtUpd = $conn->prepare($sqlUpdate);
                $stmtUpd->bindValue(':total_alunos', isset($dados['total_alunos']) ? (int)$dados['total_alunos'] : 0, PDO::PARAM_INT);
                $stmtUpd->bindValue(':alunos_atendidos', isset($dados['alunos_atendidos']) ? (int)$dados['alunos_atendidos'] : 0, PDO::PARAM_INT);
                $stmtUpd->bindValue(':observacoes', isset($dados['observacoes']) ? $dados['observacoes'] : null);
                // Validar usuario atualizado_por
                $usuarioId = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
                if ($usuarioId !== null) {
                    $stmtUsu = $conn->prepare("SELECT id FROM usuario WHERE id = :id LIMIT 1");
                    $stmtUsu->bindValue(':id', $usuarioId, PDO::PARAM_INT);
                    $stmtUsu->execute();
                    if (!$stmtUsu->fetch(PDO::FETCH_ASSOC)) {
                        $usuarioId = null;
                    }
                }
                $stmtUpd->bindValue(':atualizado_por', $usuarioId);
                $stmtUpd->bindValue(':id', $consumoId, PDO::PARAM_INT);
                $stmtUpd->execute();
            } else {
                // Inserir novo registro
                $sqlIns = "INSERT INTO consumo_diario (escola_id, turma_id, data, turno, total_alunos,
                          alunos_atendidos, observacoes, registrado_por, registrado_em)
                          VALUES (:escola_id, :turma_id, :data, :turno, :total_alunos,
                          :alunos_atendidos, :observacoes, :registrado_por, NOW())";
                $stmtIns = $conn->prepare($sqlIns);
                $stmtIns->bindValue(':escola_id', $dados['escola_id']);
                $stmtIns->bindValue(':turma_id', isset($dados['turma_id']) && $dados['turma_id'] !== '' ? $dados['turma_id'] : null);
                $stmtIns->bindValue(':data', $dados['data']);
                $stmtIns->bindValue(':turno', isset($dados['turno']) ? $dados['turno'] : null);
                $stmtIns->bindValue(':total_alunos', isset($dados['total_alunos']) ? (int)$dados['total_alunos'] : 0, PDO::PARAM_INT);
                $stmtIns->bindValue(':alunos_atendidos', isset($dados['alunos_atendidos']) ? (int)$dados['alunos_atendidos'] : 0, PDO::PARAM_INT);
                $stmtIns->bindValue(':observacoes', isset($dados['observacoes']) ? $dados['observacoes'] : null);
                // Validar usuario registrado_por
                $usuarioId = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
                if ($usuarioId !== null) {
                    $stmtUsu = $conn->prepare("SELECT id FROM usuario WHERE id = :id LIMIT 1");
                    $stmtUsu->bindValue(':id', $usuarioId, PDO::PARAM_INT);
                    $stmtUsu->execute();
                    if (!$stmtUsu->fetch(PDO::FETCH_ASSOC)) {
                        $usuarioId = null;
                    }
                }
                $stmtIns->bindValue(':registrado_por', $usuarioId);
                $stmtIns->execute();
                $consumoId = (int)$conn->lastInsertId();
            }
            
            // Adicionar itens consumidos
            if (!empty($dados['itens'])) {
                // Remover itens antigos
                $sqlDelete = "DELETE FROM consumo_item WHERE consumo_diario_id = :consumo_id";
                $stmtDelete = $conn->prepare($sqlDelete);
                $stmtDelete->bindValue(':consumo_id', $consumoId, PDO::PARAM_INT);
                $stmtDelete->execute();
                
                // Inserir novos itens
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO consumo_item (consumo_diario_id, produto_id, quantidade, unidade_medida)
                               VALUES (:consumo_diario_id, :produto_id, :quantidade, :unidade_medida)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindValue(':consumo_diario_id', $consumoId, PDO::PARAM_INT);
                    $stmtItem->bindValue(':produto_id', $item['produto_id']);
                    $stmtItem->bindValue(':quantidade', isset($item['quantidade']) ? $item['quantidade'] : null);
                    $stmtItem->bindValue(':unidade_medida', isset($item['unidade_medida']) ? $item['unidade_medida'] : null);
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

