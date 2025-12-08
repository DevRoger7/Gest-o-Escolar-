<?php
/**
 * AlunoModel - Model para gerenciamento de alunos
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class AlunoModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os alunos com filtros
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, p.sexo,
                       e.nome as escola_nome, pes_resp.nome as responsavel_nome
                FROM aluno a
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON a.escola_id = e.id
                LEFT JOIN pessoa pes_resp ON a.responsavel_id = pes_resp.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR a.matricula LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND a.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['situacao'])) {
            $sql .= " AND a.situacao = :situacao";
            $params[':situacao'] = $filtros['situacao'];
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND a.ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " ORDER BY p.nome ASC";
        
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $filtros['limit'];
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca aluno por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT a.*, 
                       p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, p.sexo,
                       e.nome as escola_nome, 
                       pes_resp.nome as responsavel_nome
                FROM aluno a
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON a.escola_id = e.id
                LEFT JOIN pessoa pes_resp ON a.responsavel_id = pes_resp.id
                WHERE a.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo aluno
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            if (!empty($dados['email'])) {
                $stmtChkEmail = $conn->prepare("SELECT id FROM pessoa WHERE email = :email LIMIT 1");
                $stmtChkEmail->bindParam(':email', $dados['email']);
                $stmtChkEmail->execute();
                if ($stmtChkEmail->fetch()) {
                    throw new Exception('Email já cadastrado no sistema');
                }
            }
            
            // 1. Criar pessoa
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone, 'ALUNO', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $dados['cpf']);
            $stmtPessoa->bindParam(':nome', $dados['nome']);
            $stmtPessoa->bindParam(':data_nascimento', $dados['data_nascimento']);
            $stmtPessoa->bindParam(':sexo', $dados['sexo']);
            $stmtPessoa->bindParam(':email', $dados['email']);
            $stmtPessoa->bindParam(':telefone', $dados['telefone']);
            $stmtPessoa->bindParam(':criado_por', $_SESSION['usuario_id']);
            $stmtPessoa->execute();
            
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar aluno
            $matricula = $dados['matricula'] ?? '';
            $nis = !empty($dados['nis']) ? $dados['nis'] : null;
            $responsavelId = !empty($dados['responsavel_id']) ? $dados['responsavel_id'] : null;
            $escolaId = !empty($dados['escola_id']) ? $dados['escola_id'] : null;
            $dataMatricula = !empty($dados['data_matricula']) ? $dados['data_matricula'] : date('Y-m-d');
            $situacao = !empty($dados['situacao']) ? $dados['situacao'] : 'MATRICULADO';
            $criadoPor = $_SESSION['usuario_id'];
            
            $sqlAluno = "INSERT INTO aluno (pessoa_id, matricula, nis, responsavel_id, escola_id, data_matricula, situacao, ativo, criado_por)
                        VALUES (:pessoa_id, :matricula, :nis, :responsavel_id, :escola_id, :data_matricula, :situacao, 1, :criado_por)";
            $stmtAluno = $conn->prepare($sqlAluno);
            $stmtAluno->bindParam(':pessoa_id', $pessoaId);
            $stmtAluno->bindParam(':matricula', $matricula);
            $stmtAluno->bindParam(':nis', $nis);
            $stmtAluno->bindParam(':responsavel_id', $responsavelId);
            $stmtAluno->bindParam(':escola_id', $escolaId);
            $stmtAluno->bindParam(':data_matricula', $dataMatricula);
            $stmtAluno->bindParam(':situacao', $situacao);
            $stmtAluno->bindParam(':criado_por', $criadoPor);
            $stmtAluno->execute();
            
            $alunoId = $conn->lastInsertId();
            
            $conn->commit();
            return ['success' => true, 'id' => $alunoId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Atualiza aluno
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Buscar aluno
            $aluno = $this->buscarPorId($id);
            if (!$aluno) {
                throw new Exception('Aluno não encontrado');
            }
            $pessoaId = $aluno['pessoa_id'];
            if (!empty($dados['email']) && $dados['email'] !== ($aluno['email'] ?? '')) {
                $stmtChkEmail = $conn->prepare("SELECT id FROM pessoa WHERE email = :email AND id != :pessoa_id LIMIT 1");
                $stmtChkEmail->bindParam(':email', $dados['email']);
                $stmtChkEmail->bindParam(':pessoa_id', $pessoaId);
                $stmtChkEmail->execute();
                if ($stmtChkEmail->fetch()) {
                    throw new Exception('Email já cadastrado para outro usuário');
                }
            }
            
            // 1. Atualizar pessoa
            $sqlPessoa = "UPDATE pessoa SET nome = :nome, data_nascimento = :data_nascimento, 
                          sexo = :sexo, email = :email, telefone = :telefone
                          WHERE id = :pessoa_id";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':nome', $dados['nome']);
            $stmtPessoa->bindParam(':data_nascimento', $dados['data_nascimento']);
            $stmtPessoa->bindParam(':sexo', $dados['sexo']);
            $stmtPessoa->bindParam(':email', $dados['email']);
            $stmtPessoa->bindParam(':telefone', $dados['telefone']);
            
            $stmtPessoa->bindParam(':pessoa_id', $pessoaId);
            $stmtPessoa->execute();
            
            // 2. Atualizar aluno
            $matricula = $dados['matricula'] ?? '';
            $nis = $dados['nis'] ?? null;
            $responsavelId = $dados['responsavel_id'] ?? null;
            $escolaId = $dados['escola_id'] ?? null;
            $situacao = $dados['situacao'] ?? 'MATRICULADO';
            $ativo = $dados['ativo'] ?? 1;
            
            $sqlAluno = "UPDATE aluno SET matricula = :matricula, nis = :nis, 
                        responsavel_id = :responsavel_id, escola_id = :escola_id,
                        situacao = :situacao, ativo = :ativo
                        WHERE id = :id";
            $stmtAluno = $conn->prepare($sqlAluno);
            $stmtAluno->bindParam(':matricula', $matricula);
            $stmtAluno->bindParam(':nis', $nis);
            $stmtAluno->bindParam(':responsavel_id', $responsavelId);
            $stmtAluno->bindParam(':escola_id', $escolaId);
            $stmtAluno->bindParam(':situacao', $dados['situacao'] ?? 'MATRICULADO');
            $stmtAluno->bindParam(':ativo', $dados['ativo'] ?? 1);
            $stmtAluno->bindParam(':id', $id);
            $stmtAluno->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Exclui aluno (soft delete)
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE aluno SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Matricula aluno em turma
     */
    public function matricularEmTurma($alunoId, $turmaId, $dataInicio = null) {
        $conn = $this->db->getConnection();
        
        $dataInicio = $dataInicio ?? date('Y-m-d');
        
        $sql = "INSERT INTO aluno_turma (aluno_id, turma_id, inicio, status, criado_em)
                VALUES (:aluno_id, :turma_id, :inicio, 'MATRICULADO', NOW())
                ON DUPLICATE KEY UPDATE status = 'MATRICULADO', inicio = :inicio";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':inicio', $dataInicio);
        
        return $stmt->execute();
    }
    
    /**
     * Transfere aluno entre turmas
     */
    public function transferirTurma($alunoId, $turmaAntigaId, $turmaNovaId) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Finalizar matrícula na turma antiga
            $sql1 = "UPDATE aluno_turma SET fim = CURDATE(), status = 'TRANSFERIDO' 
                    WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND fim IS NULL";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bindParam(':aluno_id', $alunoId);
            $stmt1->bindParam(':turma_id', $turmaAntigaId);
            $stmt1->execute();
            
            // Matricular na nova turma
            $this->matricularEmTurma($alunoId, $turmaNovaId);
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Busca alunos por turma
     */
    public function buscarPorTurma($turmaId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT a.*, p.nome, p.cpf, at.status, at.inicio
                FROM aluno_turma at
                INNER JOIN aluno a ON at.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE at.turma_id = :turma_id AND at.fim IS NULL AND a.ativo = 1
                ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

