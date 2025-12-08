<?php
/**
 * BoletimModel - Model para gerenciamento de boletins
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');
require_once(__DIR__ . '/NotaModel.php');
require_once(__DIR__ . '/FrequenciaModel.php');

class BoletimModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Gera boletim para aluno
     */
    public function gerar($alunoId, $turmaId, $anoLetivo, $bimestre) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Verificar se já existe
            $sqlCheck = "SELECT id FROM boletim WHERE aluno_id = :aluno_id AND turma_id = :turma_id 
                        AND ano_letivo = :ano_letivo AND bimestre = :bimestre";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':aluno_id', $alunoId);
            $stmtCheck->bindParam(':turma_id', $turmaId);
            $stmtCheck->bindParam(':ano_letivo', $anoLetivo);
            $stmtCheck->bindParam(':bimestre', $bimestre);
            $stmtCheck->execute();
            $existe = $stmtCheck->fetch();
            
            if ($existe) {
                $boletimId = $existe['id'];
            } else {
                // Calcular média geral e frequência
                $notaModel = new NotaModel();
                $frequenciaModel = new FrequenciaModel();
                
                // Buscar todas as disciplinas da turma
                $sqlDisc = "SELECT DISTINCT d.id, d.nome
                           FROM turma_professor tp
                           INNER JOIN disciplina d ON tp.disciplina_id = d.id
                           WHERE tp.turma_id = :turma_id";
                $stmtDisc = $conn->prepare($sqlDisc);
                $stmtDisc->bindParam(':turma_id', $turmaId);
                $stmtDisc->execute();
                $disciplinas = $stmtDisc->fetchAll(PDO::FETCH_ASSOC);
                
                $medias = [];
                $totalFaltas = 0;
                
                foreach ($disciplinas as $disc) {
                    $media = $notaModel->calcularMedia($alunoId, $disc['id'], $turmaId, $bimestre);
                    $medias[] = $media['media'];
                    
                    // Calcular faltas do bimestre
                    $periodoInicio = $this->getInicioBimestre($bimestre, $anoLetivo);
                    $periodoFim = $this->getFimBimestre($bimestre, $anoLetivo);
                    $freq = $frequenciaModel->calcularPercentual($alunoId, $turmaId, $periodoInicio, $periodoFim);
                    $totalFaltas += $freq['dias_faltas'];
                }
                
                $mediaGeral = count($medias) > 0 ? array_sum($medias) / count($medias) : 0;
                
                // Calcular frequência percentual
                $freq = $frequenciaModel->calcularPercentual($alunoId, $turmaId, $periodoInicio, $periodoFim);
                
                // Determinar situação
                $situacao = 'PENDENTE';
                if ($mediaGeral >= 7 && $freq['percentual'] >= 75) {
                    $situacao = 'APROVADO';
                } elseif ($mediaGeral < 5 || $freq['percentual'] < 75) {
                    $situacao = 'REPROVADO';
                } elseif ($mediaGeral >= 5 && $mediaGeral < 7) {
                    $situacao = 'RECUPERACAO';
                }
                
                // Criar boletim
                $sqlBoletim = "INSERT INTO boletim (aluno_id, turma_id, ano_letivo, bimestre, media_geral,
                              frequencia_percentual, total_faltas, situacao, gerado_por, gerado_em)
                              VALUES (:aluno_id, :turma_id, :ano_letivo, :bimestre, :media_geral,
                              :frequencia_percentual, :total_faltas, :situacao, :gerado_por, NOW())";
                $stmtBoletim = $conn->prepare($sqlBoletim);
                $stmtBoletim->bindParam(':aluno_id', $alunoId);
                $stmtBoletim->bindParam(':turma_id', $turmaId);
                $stmtBoletim->bindParam(':ano_letivo', $anoLetivo);
                $stmtBoletim->bindParam(':bimestre', $bimestre);
                $stmtBoletim->bindParam(':media_geral', $mediaGeral);
                $stmtBoletim->bindParam(':frequencia_percentual', $freq['percentual']);
                $stmtBoletim->bindParam(':total_faltas', $totalFaltas);
                $stmtBoletim->bindParam(':situacao', $situacao);
                $geradoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
                $stmtBoletim->bindParam(':gerado_por', $geradoPor);
                $stmtBoletim->execute();
                
                $boletimId = $conn->lastInsertId();
                
                // Criar itens do boletim
                foreach ($disciplinas as $disc) {
                    $media = $notaModel->calcularMedia($alunoId, $disc['id'], $turmaId, $bimestre);
                    
                    $sqlItem = "INSERT INTO boletim_item (boletim_id, disciplina_id, media, faltas, situacao)
                               VALUES (:boletim_id, :disciplina_id, :media, :faltas, :situacao)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':boletim_id', $boletimId);
                    $stmtItem->bindParam(':disciplina_id', $disc['id']);
                    $stmtItem->bindParam(':media', $media['media']);
                    $stmtItem->bindParam(':faltas', $freq['dias_faltas']);
                    $situacaoItem = $media['media'] >= 7 ? 'APROVADO' : ($media['media'] >= 5 ? 'RECUPERACAO' : 'REPROVADO');
                    $stmtItem->bindParam(':situacao', $situacaoItem);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $boletimId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Busca boletim
     */
    public function buscar($alunoId, $turmaId, $anoLetivo, $bimestre) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT b.*, bi.*, d.nome as disciplina_nome
                FROM boletim b
                LEFT JOIN boletim_item bi ON b.id = bi.boletim_id
                LEFT JOIN disciplina d ON bi.disciplina_id = d.id
                WHERE b.aluno_id = :aluno_id AND b.turma_id = :turma_id
                AND b.ano_letivo = :ano_letivo AND b.bimestre = :bimestre
                ORDER BY d.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':ano_letivo', $anoLetivo);
        $stmt->bindParam(':bimestre', $bimestre);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($result)) {
            return null;
        }
        
        // Organizar resultado
        $boletim = $result[0];
        $boletim['itens'] = [];
        
        foreach ($result as $row) {
            if ($row['disciplina_id']) {
                $boletim['itens'][] = [
                    'disciplina_id' => $row['disciplina_id'],
                    'disciplina_nome' => $row['disciplina_nome'],
                    'media' => $row['media'],
                    'faltas' => $row['faltas'],
                    'situacao' => $row['situacao']
                ];
            }
        }
        
        return $boletim;
    }
    
    /**
     * Lista boletins de aluno
     */
    public function listarPorAluno($alunoId, $anoLetivo = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT b.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                FROM boletim b
                INNER JOIN turma t ON b.turma_id = t.id
                WHERE b.aluno_id = :aluno_id";
        
        $params = [':aluno_id' => $alunoId];
        
        if ($anoLetivo) {
            $sql .= " AND b.ano_letivo = :ano_letivo";
            $params[':ano_letivo'] = $anoLetivo;
        }
        
        $sql .= " ORDER BY b.ano_letivo DESC, b.bimestre DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getInicioBimestre($bimestre, $ano) {
        $meses = [
            1 => ['mes' => 2, 'dia' => 1],   // Fevereiro
            2 => ['mes' => 4, 'dia' => 1],   // Abril
            3 => ['mes' => 7, 'dia' => 1],    // Julho
            4 => ['mes' => 9, 'dia' => 1]    // Setembro
        ];
        
        $mes = $meses[$bimestre]['mes'] ?? 2;
        return "$ano-$mes-01";
    }
    
    private function getFimBimestre($bimestre, $ano) {
        $meses = [
            1 => ['mes' => 3, 'dia' => 31],  // Março
            2 => ['mes' => 6, 'dia' => 30],  // Junho
            3 => ['mes' => 8, 'dia' => 31],   // Agosto
            4 => ['mes' => 12, 'dia' => 31]   // Dezembro
        ];
        
        $mes = $meses[$bimestre]['mes'] ?? 3;
        $dia = $meses[$bimestre]['dia'] ?? 31;
        return "$ano-$mes-$dia";
    }
}

?>

