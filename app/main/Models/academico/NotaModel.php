<?php
/**
 * NotaModel - Model para gerenciamento de notas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class NotaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lança nota de aluno
     * Verifica duplicatas antes de inserir
     */
    public function lancar($dados) {
        $conn = $this->db->getConnection();
        
        $avaliacaoId = (isset($dados['avaliacao_id']) && $dados['avaliacao_id'] !== '') ? $dados['avaliacao_id'] : null;
        $disciplinaId = $dados['disciplina_id'];
        $turmaId = $dados['turma_id'];
        $alunoId = $dados['aluno_id'];
        $bimestre = (isset($dados['bimestre']) && $dados['bimestre'] !== '') ? $dados['bimestre'] : null;
        
        // Log para debug - remover depois
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        error_log("LANCAR chamado para aluno_id: {$alunoId}, avaliacao_id: {$avaliacaoId}, bimestre: {$bimestre} - Arquivo: " . ($trace[1]['file'] ?? '') . " Linha: " . ($trace[1]['line'] ?? ''));
        
        if ($avaliacaoId) {
            $sqlVerificar = "SELECT id FROM nota 
                            WHERE aluno_id = :aluno_id 
                            AND avaliacao_id = :avaliacao_id 
                            AND disciplina_id = :disciplina_id 
                            AND turma_id = :turma_id 
                            AND bimestre = :bimestre
                            LIMIT 1";
            $stmtVerificar = $conn->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':aluno_id', $alunoId);
            $stmtVerificar->bindParam(':avaliacao_id', $avaliacaoId);
            $stmtVerificar->bindParam(':disciplina_id', $disciplinaId);
            $stmtVerificar->bindParam(':turma_id', $turmaId);
            $stmtVerificar->bindParam(':bimestre', $bimestre);
            $stmtVerificar->execute();
            $notaExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            if ($notaExistente) {
                return false;
            }
            // Buscar tipo da avaliação para verificação adicional
            $sqlTipo = "SELECT tipo FROM avaliacao WHERE id = :avaliacao_id LIMIT 1";
            $stmtTipo = $conn->prepare($sqlTipo);
            $stmtTipo->bindValue(':avaliacao_id', $avaliacaoId);
            $stmtTipo->execute();
            $avaliacaoTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
            
            if ($avaliacaoTipo && isset($avaliacaoTipo['tipo'])) {
                // Usar lock para evitar inserções simultâneas
                $lockKey = 'nota_' . $alunoId . '_' . $turmaId . '_' . $disciplinaId . '_' . $bimestre . '_' . $avaliacaoTipo['tipo'];
                $stmtLock = $conn->prepare("SELECT GET_LOCK(:lock_key, 5)");
                $stmtLock->bindValue(':lock_key', $lockKey, PDO::PARAM_STR);
                $stmtLock->execute();
                
                // Verificar se já existe nota do mesmo tipo
                $sqlVerificarTipo = "SELECT n.id FROM nota n
                                     INNER JOIN avaliacao av ON n.avaliacao_id = av.id
                                     WHERE n.aluno_id = :aluno_id
                                     AND n.disciplina_id = :disciplina_id
                                     AND n.turma_id = :turma_id
                                     AND n.bimestre = :bimestre
                                     AND av.tipo = :tipo
                                     LIMIT 1";
                $stmtVerificarTipo = $conn->prepare($sqlVerificarTipo);
                $stmtVerificarTipo->bindValue(':aluno_id', $alunoId);
                $stmtVerificarTipo->bindValue(':disciplina_id', $disciplinaId);
                $stmtVerificarTipo->bindValue(':turma_id', $turmaId);
                $stmtVerificarTipo->bindValue(':bimestre', $bimestre);
                $stmtVerificarTipo->bindValue(':tipo', $avaliacaoTipo['tipo']);
                $stmtVerificarTipo->execute();
                $notaMesmoTipo = $stmtVerificarTipo->fetch(PDO::FETCH_ASSOC);
                if ($notaMesmoTipo) {
                    // Liberar lock antes de retornar
                    $stmtUnlock = $conn->prepare("SELECT RELEASE_LOCK(:lock_key)");
                    $stmtUnlock->bindValue(':lock_key', $lockKey, PDO::PARAM_STR);
                    $stmtUnlock->execute();
                    return false;
                }
            }
        }
        
        // Verificação final antes de inserir - garantir que não existe
        $sqlVerificarFinal = "SELECT id FROM nota 
                             WHERE aluno_id = :aluno_id 
                             AND avaliacao_id = :avaliacao_id 
                             AND disciplina_id = :disciplina_id 
                             AND turma_id = :turma_id 
                             AND bimestre = :bimestre
                             LIMIT 1";
        $stmtVerificarFinal = $conn->prepare($sqlVerificarFinal);
        $stmtVerificarFinal->bindValue(':aluno_id', $alunoId);
        $stmtVerificarFinal->bindValue(':avaliacao_id', $avaliacaoId);
        $stmtVerificarFinal->bindValue(':disciplina_id', $disciplinaId);
        $stmtVerificarFinal->bindValue(':turma_id', $turmaId);
        $stmtVerificarFinal->bindValue(':bimestre', $bimestre);
        $stmtVerificarFinal->execute();
        $notaExistenteFinal = $stmtVerificarFinal->fetch(PDO::FETCH_ASSOC);
        if ($notaExistenteFinal) {
            // Liberar lock se existir
            if (isset($lockKey)) {
                try {
                    $stmtUnlock = $conn->prepare("SELECT RELEASE_LOCK(:lock_key)");
                    $stmtUnlock->bindValue(':lock_key', $lockKey, PDO::PARAM_STR);
                    $stmtUnlock->execute();
                } catch (Exception $e) {
                    error_log("Erro ao liberar lock: " . $e->getMessage());
                }
            }
            return false; // Já existe, não inserir
        }
        
        // Preparar valores antes de criar o statement
        $notaValor = $dados['nota'];
        $recuperacao = $dados['recuperacao'] ?? 0;
        $comentario = $dados['comentario'] ?? null;
        
        // Validar e obter usuario_id válido
        $lancadoPor = null;
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            $usuarioIdParam = (int)$_SESSION['usuario_id'];
            // Verificar se o usuário existe na tabela
            $sqlVerificarUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
            $stmtVerificarUsuario = $conn->prepare($sqlVerificarUsuario);
            $stmtVerificarUsuario->bindValue(':usuario_id', $usuarioIdParam);
            $stmtVerificarUsuario->execute();
            $usuarioExiste = $stmtVerificarUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuarioExiste) {
                $lancadoPor = $usuarioIdParam;
            }
        }

        // Usar INSERT IGNORE ou verificação final antes de inserir
        // Criar statement único para este INSERT - usar valores diretos para evitar problemas
        $sql = "INSERT INTO nota (avaliacao_id, disciplina_id, turma_id, aluno_id, nota, bimestre, recuperacao, comentario, lancado_por, lancado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        // Preparar statement com placeholders numéricos (mais seguro)
        $stmt = $conn->prepare($sql);
        
        // Executar com valores diretos - uma única vez
        $resultado = $stmt->execute([
            $avaliacaoId,
            $disciplinaId,
            $turmaId,
            $alunoId,
            $notaValor,
            $bimestre,
            $recuperacao,
            $comentario,
            $lancadoPor
        ]);
        
        // Verificar se realmente inseriu apenas uma linha
        $rowsAffected = $stmt->rowCount();
        if ($resultado) {
            if ($rowsAffected !== 1) {
                error_log("ERRO CRÍTICO: Inserção de nota retornou " . $rowsAffected . " linhas afetadas ao invés de 1 para aluno_id: {$alunoId}, avaliacao_id: {$avaliacaoId}, bimestre: {$bimestre}");
                // Se inseriu mais de uma linha, fazer rollback se possível
                if ($rowsAffected > 1) {
                    try {
                        $conn->rollBack();
                    } catch (Exception $e) {
                        // Ignorar se não estiver em transação
                    }
                    return false;
                }
            }
        }
        
        // Destruir o statement imediatamente após uso para evitar reutilização
        $stmt = null;
        unset($stmt);
        
        // Liberar lock se existir
        if (isset($lockKey)) {
            try {
                $stmtUnlock = $conn->prepare("SELECT RELEASE_LOCK(:lock_key)");
                $stmtUnlock->bindValue(':lock_key', $lockKey, PDO::PARAM_STR);
                $stmtUnlock->execute();
                unset($stmtUnlock);
            } catch (Exception $e) {
                error_log("Erro ao liberar lock: " . $e->getMessage());
            }
        }
        
        return $resultado;
    }
    
    /**
     * Lança notas em lote
     */
    public function lancarLote($notas) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            foreach ($notas as $nota) {
                $this->lancar($nota);
            }
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Busca notas de aluno
     */
    public function buscarPorAluno($alunoId, $turmaId = null, $disciplinaId = null, $bimestre = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT n.*, d.nome as disciplina_nome, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                a.titulo as avaliacao_titulo
                FROM nota n
                LEFT JOIN disciplina d ON n.disciplina_id = d.id
                LEFT JOIN turma t ON n.turma_id = t.id
                LEFT JOIN avaliacao a ON n.avaliacao_id = a.id
                WHERE n.aluno_id = :aluno_id";
        
        $params = [':aluno_id' => $alunoId];
        
        if ($turmaId) {
            $sql .= " AND n.turma_id = :turma_id";
            $params[':turma_id'] = $turmaId;
        }
        
        if ($disciplinaId) {
            $sql .= " AND n.disciplina_id = :disciplina_id";
            $params[':disciplina_id'] = $disciplinaId;
        }
        
        if ($bimestre) {
            $sql .= " AND n.bimestre = :bimestre";
            $params[':bimestre'] = $bimestre;
        }
        
        $sql .= " ORDER BY n.bimestre ASC, d.nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula média do aluno
     */
    public function calcularMedia($alunoId, $disciplinaId, $turmaId, $bimestre = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT AVG(nota) as media, COUNT(*) as total_notas
                FROM nota
                WHERE aluno_id = :aluno_id AND disciplina_id = :disciplina_id AND turma_id = :turma_id
                AND recuperacao = 0";
        
        $params = [
            ':aluno_id' => $alunoId,
            ':disciplina_id' => $disciplinaId,
            ':turma_id' => $turmaId
        ];
        
        if ($bimestre) {
            $sql .= " AND bimestre = :bimestre";
            $params[':bimestre'] = $bimestre;
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'media' => round($result['media'] ?? 0, 2),
            'total_notas' => $result['total_notas'] ?? 0
        ];
    }
    
    /**
     * Atualiza nota
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE nota SET nota = :nota, bimestre = :bimestre, recuperacao = :recuperacao,
                comentario = :comentario, atualizado_em = NOW(), atualizado_por = :atualizado_por
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $notaValor = $dados['nota'];
        $bimestre = isset($dados['bimestre']) ? $dados['bimestre'] : null;
        $recuperacao = $dados['recuperacao'] ?? 0;
        $comentario = $dados['comentario'] ?? null;
        
        // Validar e obter usuario_id válido
        $atualizadoPor = null;
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            $usuarioIdParam = (int)$_SESSION['usuario_id'];
            // Verificar se o usuário existe na tabela
            $sqlVerificarUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
            $stmtVerificarUsuario = $conn->prepare($sqlVerificarUsuario);
            $stmtVerificarUsuario->bindParam(':usuario_id', $usuarioIdParam);
            $stmtVerificarUsuario->execute();
            $usuarioExiste = $stmtVerificarUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuarioExiste) {
                $atualizadoPor = $usuarioIdParam;
            }
        }
        
        $idParam = $id;
        $stmt->bindParam(':nota', $notaValor);
        $stmt->bindParam(':bimestre', $bimestre);
        $stmt->bindParam(':recuperacao', $recuperacao, PDO::PARAM_BOOL);
        $stmt->bindParam(':comentario', $comentario);
        $stmt->bindParam(':atualizado_por', $atualizadoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
    
    /**
     * Busca notas por turma e disciplina
     */
    public function buscarPorTurmaDisciplina($turmaId, $disciplinaId, $bimestre = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT n.*, 
                p.nome as aluno_nome, 
                COALESCE(al.matricula, '') as aluno_matricula,
                av.titulo as avaliacao_titulo,
                av.tipo as avaliacao_tipo
                FROM nota n
                INNER JOIN aluno al ON n.aluno_id = al.id
                INNER JOIN pessoa p ON al.pessoa_id = p.id
                LEFT JOIN avaliacao av ON n.avaliacao_id = av.id
                WHERE n.turma_id = :turma_id AND n.disciplina_id = :disciplina_id";
        
        $params = [
            ':turma_id' => $turmaId,
            ':disciplina_id' => $disciplinaId
        ];
        
        if ($bimestre) {
            $sql .= " AND n.bimestre = :bimestre";
            $params[':bimestre'] = $bimestre;
        }
        
        $sql .= " ORDER BY p.nome ASC, n.bimestre ASC, n.lancado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca uma nota específica por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT n.*, 
                p.nome as aluno_nome,
                COALESCE(al.matricula, '') as aluno_matricula,
                d.nome as disciplina_nome,
                av.titulo as avaliacao_titulo
                FROM nota n
                INNER JOIN aluno al ON n.aluno_id = al.id
                INNER JOIN pessoa p ON al.pessoa_id = p.id
                LEFT JOIN disciplina d ON n.disciplina_id = d.id
                LEFT JOIN avaliacao av ON n.avaliacao_id = av.id
                WHERE n.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Valida nota (GESTAO)
     */
    public function validar($notaId, $validado = true) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE nota SET validado = :validado, validado_por = :validado_por,
                data_validacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $validadoParam = $validado ? 1 : 0;
        
        // Validar e obter usuario_id válido
        $validadoPor = null;
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            $usuarioIdParam = (int)$_SESSION['usuario_id'];
            // Verificar se o usuário existe na tabela
            $sqlVerificarUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
            $stmtVerificarUsuario = $conn->prepare($sqlVerificarUsuario);
            $stmtVerificarUsuario->bindParam(':usuario_id', $usuarioIdParam);
            $stmtVerificarUsuario->execute();
            $usuarioExiste = $stmtVerificarUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuarioExiste) {
                $validadoPor = $usuarioIdParam;
            }
        }
        
        $idParam = $notaId;
        $stmt->bindParam(':validado', $validadoParam, PDO::PARAM_BOOL);
        $stmt->bindParam(':validado_por', $validadoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
    
    /**
     * Verifica se já existem notas para alunos em um bimestre específico
     * Retorna array com IDs dos alunos que já possuem notas
     */
    public function verificarNotasExistentes($turmaId, $disciplinaId, $bimestre, $alunoIds = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT DISTINCT n.aluno_id, 
                GROUP_CONCAT(DISTINCT av.tipo ORDER BY av.tipo SEPARATOR ', ') as tipos_avaliacao
                FROM nota n
                LEFT JOIN avaliacao av ON n.avaliacao_id = av.id
                WHERE n.turma_id = :turma_id 
                AND n.disciplina_id = :disciplina_id 
                AND n.bimestre = :bimestre";
        
        $params = [
            ':turma_id' => $turmaId,
            ':disciplina_id' => $disciplinaId,
            ':bimestre' => $bimestre
        ];
        
        // Se foram fornecidos IDs específicos de alunos, filtrar apenas esses
        if (!empty($alunoIds) && is_array($alunoIds)) {
            $placeholders = [];
            foreach ($alunoIds as $index => $alunoId) {
                $key = ':aluno_id_' . $index;
                $placeholders[] = $key;
                $params[$key] = $alunoId;
            }
            $sql .= " AND n.aluno_id IN (" . implode(', ', $placeholders) . ")";
        }
        
        $sql .= " GROUP BY n.aluno_id";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca notas existentes para alunos em um bimestre específico
     * Retorna notas organizadas por aluno e tipo de avaliação
     */
    public function buscarNotasPorBimestre($turmaId, $disciplinaId, $bimestre, $alunoIds = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT n.*, 
                p.nome as aluno_nome,
                COALESCE(al.matricula, '') as aluno_matricula,
                av.tipo as avaliacao_tipo,
                av.titulo as avaliacao_titulo
                FROM nota n
                INNER JOIN aluno al ON n.aluno_id = al.id
                INNER JOIN pessoa p ON al.pessoa_id = p.id
                LEFT JOIN avaliacao av ON n.avaliacao_id = av.id
                WHERE n.turma_id = :turma_id 
                AND n.disciplina_id = :disciplina_id 
                AND n.bimestre = :bimestre";
        
        $params = [
            ':turma_id' => $turmaId,
            ':disciplina_id' => $disciplinaId,
            ':bimestre' => $bimestre
        ];
        
        // Se foram fornecidos IDs específicos de alunos, filtrar apenas esses
        if (!empty($alunoIds) && is_array($alunoIds)) {
            $placeholders = [];
            foreach ($alunoIds as $index => $alunoId) {
                $key = ':aluno_id_' . $index;
                $placeholders[] = $key;
                $params[$key] = $alunoId;
            }
            $sql .= " AND n.aluno_id IN (" . implode(', ', $placeholders) . ")";
        }
        
        $sql .= " ORDER BY p.nome ASC, av.tipo ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

