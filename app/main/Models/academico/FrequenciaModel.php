<?php
/**
 * FrequenciaModel - Model para gerenciamento de frequência
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class FrequenciaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra frequência de aluno
     * @param array $dados Dados da frequência
     * @param bool $permitirAtualizacao Se true, permite atualizar frequência existente (usado internamente)
     * @return bool|array Retorna true em sucesso, ou array com erro se frequência já existe
     */
    public function registrar($dados, $permitirAtualizacao = false) {
        $conn = $this->db->getConnection();
        
        $alunoId = $dados['aluno_id'];
        $turmaId = $dados['turma_id'];
        $data = $dados['data'];
        
        // Verificar se já existe frequência para este aluno nesta data (a menos que seja permitida atualização)
        if (!$permitirAtualizacao) {
            $sqlVerificar = "SELECT id FROM frequencia 
                            WHERE aluno_id = :aluno_id 
                            AND turma_id = :turma_id 
                            AND data = :data
                            LIMIT 1";
            $stmtVerificar = $conn->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':aluno_id', $alunoId);
            $stmtVerificar->bindParam(':turma_id', $turmaId);
            $stmtVerificar->bindParam(':data', $data);
            $stmtVerificar->execute();
            $frequenciaExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            if ($frequenciaExistente) {
                // Buscar nome do aluno para mensagem de erro
                $sqlAluno = "SELECT p.nome FROM aluno a INNER JOIN pessoa p ON a.pessoa_id = p.id WHERE a.id = :aluno_id LIMIT 1";
                $stmtAluno = $conn->prepare($sqlAluno);
                $stmtAluno->bindParam(':aluno_id', $alunoId);
                $stmtAluno->execute();
                $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
                $alunoNome = $aluno ? $aluno['nome'] : 'Aluno';
                
                return [
                    'success' => false,
                    'message' => "Já existe frequência registrada para {$alunoNome} no dia " . date('d/m/Y', strtotime($data)) . ". Use a opção 'Editar' para modificar a frequência existente."
                ];
            }
        }
        
        $sql = "INSERT INTO frequencia (aluno_id, turma_id, data, presenca, observacao, registrado_por, registrado_em)
                VALUES (:aluno_id, :turma_id, :data, :presenca, :observacao, :registrado_por, NOW())
                ON DUPLICATE KEY UPDATE presenca = :presenca, observacao = :observacao, atualizado_em = NOW()";
        
        $stmt = $conn->prepare($sql);
        $presenca = isset($dados['presenca']) ? (int)$dados['presenca'] : 0;
        $observacao = $dados['observacao'] ?? null;
        $registradoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        if ($registradoPor === null) {
            // Fallback: tentar obter usuario.id a partir do pessoa_id da sessão
            $pessoaIdSessao = isset($_SESSION['pessoa_id']) && is_numeric($_SESSION['pessoa_id']) ? (int)$_SESSION['pessoa_id'] : null;
            if ($pessoaIdSessao) {
                $sqlUsuario = "SELECT id FROM usuario WHERE pessoa_id = :pessoa_id LIMIT 1";
                $stmtUsuario = $conn->prepare($sqlUsuario);
                $stmtUsuario->bindParam(':pessoa_id', $pessoaIdSessao);
                $stmtUsuario->execute();
                $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
                if ($usuario && isset($usuario['id'])) {
                    $registradoPor = (int)$usuario['id'];
                }
            }
        }
        // Validar que registrado_por existe em usuario, caso contrário usar NULL para evitar violação de FK
        if (!is_null($registradoPor)) {
            $sqlCheckUsuario = "SELECT 1 FROM usuario WHERE id = :id LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheckUsuario);
            $stmtCheck->bindParam(':id', $registradoPor);
            $stmtCheck->execute();
            if (!$stmtCheck->fetch(PDO::FETCH_ASSOC)) {
                $registradoPor = null;
            }
        }

        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':presenca', $presenca);
        $stmt->bindParam(':observacao', $observacao);
        if (is_null($registradoPor)) {
            $stmt->bindValue(':registrado_por', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':registrado_por', $registradoPor);
        }
        
        $resultado = $stmt->execute();
        
        // Se o resultado for um array (erro), retornar como está, senão retornar boolean
        if (is_array($resultado)) {
            return $resultado;
        }
        
        return $resultado;
    }
    
    /**
     * Registra frequência em lote (toda a turma)
     */
    public function registrarLote($turmaId, $data, $frequencias) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Verificar se já existem frequências registradas para algum aluno nesta data
            $alunosComFrequencia = [];
            foreach ($frequencias as $freq) {
                $alunoId = isset($freq['aluno_id']) ? (int)$freq['aluno_id'] : null;
                if (!$alunoId) {
                    throw new Exception('Aluno inválido ao registrar frequência');
                }
                
                // Verificar se já existe frequência para este aluno nesta data
                $sqlVerificar = "SELECT f.id, p.nome as aluno_nome 
                                FROM frequencia f
                                INNER JOIN aluno a ON f.aluno_id = a.id
                                INNER JOIN pessoa p ON a.pessoa_id = p.id
                                WHERE f.aluno_id = :aluno_id 
                                AND f.turma_id = :turma_id 
                                AND f.data = :data
                                LIMIT 1";
                $stmtVerificar = $conn->prepare($sqlVerificar);
                $stmtVerificar->bindParam(':aluno_id', $alunoId);
                $stmtVerificar->bindParam(':turma_id', $turmaId);
                $stmtVerificar->bindParam(':data', $data);
                $stmtVerificar->execute();
                $frequenciaExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                
                if ($frequenciaExistente) {
                    $alunosComFrequencia[] = $frequenciaExistente['aluno_nome'];
                }
            }
            
            // Se houver alunos com frequência já registrada, retornar erro
            if (!empty($alunosComFrequencia)) {
                $conn->rollBack();
                $mensagem = 'Já existe frequência registrada para o dia ' . date('d/m/Y', strtotime($data)) . ' para os seguintes alunos: ' . implode(', ', $alunosComFrequencia) . '. Use a opção "Editar" para modificar a frequência existente.';
                return ['success' => false, 'message' => $mensagem, 'alunos_com_frequencia' => $alunosComFrequencia];
            }
            
            foreach ($frequencias as $freq) {
                $alunoId = isset($freq['aluno_id']) ? (int)$freq['aluno_id'] : null;
                if (!$alunoId) {
                    throw new Exception('Aluno inválido ao registrar frequência');
                }
                // Mapear chaves vindas do frontend
                $presenca = null;
                if (isset($freq['presenca'])) {
                    $presenca = (int)$freq['presenca'];
                } elseif (isset($freq['presente'])) {
                    $presenca = (int)$freq['presente'];
                } else {
                    $presenca = 0;
                }
                $observacao = null;
                if (array_key_exists('observacao', $freq)) {
                    $observacao = $freq['observacao'];
                } elseif (!empty($freq['justificada'])) {
                    // Usar justificativa quando marcado como falta justificada
                    $observacao = isset($freq['justificativa']) && $freq['justificativa'] !== ''
                        ? $freq['justificativa']
                        : 'Falta justificada';
                }

                $resultado = $this->registrar([
                    'aluno_id' => $alunoId,
                    'turma_id' => $turmaId,
                    'data' => $data,
                    'presenca' => $presenca,
                    'observacao' => $observacao
                ]);
                
                // Se retornou array, é um erro
                if (is_array($resultado) && isset($resultado['success']) && !$resultado['success']) {
                    throw new Exception($resultado['message'] ?? 'Falha ao salvar frequência para aluno ID ' . $alunoId);
                }
                
                // Se retornou false, também é erro
                if ($resultado === false) {
                    throw new Exception('Falha ao salvar frequência para aluno ID ' . $alunoId);
                }
            }
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Busca frequência de aluno
     */
    public function buscarPorAluno($alunoId, $turmaId = null, $periodoInicio = null, $periodoFim = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT f.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                FROM frequencia f
                LEFT JOIN turma t ON f.turma_id = t.id
                WHERE f.aluno_id = :aluno_id";
        
        $params = [':aluno_id' => $alunoId];
        
        if ($turmaId) {
            $sql .= " AND f.turma_id = :turma_id";
            $params[':turma_id'] = $turmaId;
        }
        
        if ($periodoInicio) {
            $sql .= " AND f.data >= :periodo_inicio";
            $params[':periodo_inicio'] = $periodoInicio;
        }
        
        if ($periodoFim) {
            $sql .= " AND f.data <= :periodo_fim";
            $params[':periodo_fim'] = $periodoFim;
        }
        
        $sql .= " ORDER BY f.data DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula percentual de frequência
     */
    public function calcularPercentual($alunoId, $turmaId, $periodoInicio = null, $periodoFim = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT 
                    COUNT(*) as total_dias,
                    SUM(CASE WHEN presenca = 1 THEN 1 ELSE 0 END) as dias_presentes,
                    SUM(CASE WHEN presenca = 0 THEN 1 ELSE 0 END) as dias_faltas
                FROM frequencia
                WHERE aluno_id = :aluno_id AND turma_id = :turma_id";
        
        $params = [':aluno_id' => $alunoId, ':turma_id' => $turmaId];
        
        if ($periodoInicio) {
            $sql .= " AND data >= :periodo_inicio";
            $params[':periodo_inicio'] = $periodoInicio;
        }
        
        if ($periodoFim) {
            $sql .= " AND data <= :periodo_fim";
            $params[':periodo_fim'] = $periodoFim;
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total_dias'] > 0) {
            $percentual = ($result['dias_presentes'] / $result['total_dias']) * 100;
        } else {
            $percentual = 0;
        }
        
        return [
            'total_dias' => $result['total_dias'],
            'dias_presentes' => $result['dias_presentes'],
            'dias_faltas' => $result['dias_faltas'],
            'percentual' => round($percentual, 2)
        ];
    }
    
    /**
     * Busca frequência por turma e data
     */
    public function buscarPorTurmaData($turmaId, $data) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT f.*, 
                p.nome as aluno_nome,
                COALESCE(a.matricula, '') as aluno_matricula
                FROM frequencia f
                INNER JOIN aluno a ON f.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE f.turma_id = :turma_id AND f.data = :data
                ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':data', $data);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca uma frequência específica por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT f.*, 
                p.nome as aluno_nome,
                COALESCE(a.matricula, '') as aluno_matricula
                FROM frequencia f
                INNER JOIN aluno a ON f.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE f.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Atualiza frequência
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE frequencia SET presenca = :presenca, observacao = :observacao, 
                atualizado_em = NOW(), atualizado_por = :atualizado_por
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $presenca = isset($dados['presenca']) ? (int)$dados['presenca'] : 0;
        $observacao = $dados['observacao'] ?? null;
        $atualizadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        
        $stmt->bindParam(':presenca', $presenca);
        $stmt->bindParam(':observacao', $observacao);
        $stmt->bindParam(':atualizado_por', $atualizadoPor);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Valida frequência (GESTAO)
     */
    public function validar($frequenciaId, $validado = true) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE frequencia SET validado = :validado, validado_por = :validado_por,
                data_validacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $validadoParam = $validado ? 1 : 0;
        $validadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $idParam = $frequenciaId;
        $stmt->bindParam(':validado', $validadoParam, PDO::PARAM_BOOL);
        $stmt->bindParam(':validado_por', $validadoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
}

?>

