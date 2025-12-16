<?php
/**
 * PlanoAulaModel - Model para gerenciamento de planos de aula
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class PlanoAulaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista planos de aula
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pa.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                d.nome as disciplina_nome, p.nome as professor_nome, e.nome as escola_nome, e.id as escola_id
                FROM plano_aula pa
                INNER JOIN turma t ON pa.turma_id = t.id
                INNER JOIN disciplina d ON pa.disciplina_id = d.id
                INNER JOIN escola e ON t.escola_id = e.id
                INNER JOIN professor prof ON pa.professor_id = prof.id
                INNER JOIN pessoa p ON prof.pessoa_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['professor_id'])) {
            $sql .= " AND pa.professor_id = :professor_id";
            $params[':professor_id'] = $filtros['professor_id'];
        }
        
        if (!empty($filtros['turma_id'])) {
            $sql .= " AND pa.turma_id = :turma_id";
            $params[':turma_id'] = $filtros['turma_id'];
        }
        
        if (!empty($filtros['disciplina_id'])) {
            $sql .= " AND pa.disciplina_id = :disciplina_id";
            $params[':disciplina_id'] = $filtros['disciplina_id'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND pa.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['data_aula'])) {
            $sql .= " AND pa.data_aula = :data_aula";
            $params[':data_aula'] = $filtros['data_aula'];
        }
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND e.id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['mes'])) {
            $sql .= " AND MONTH(pa.data_aula) = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        
        $sql .= " ORDER BY pa.data_aula DESC, pa.criado_em DESC";
        
        // Paginação
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$filtros['limit'];
            $params[':offset'] = (int)$filtros['offset'];
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo plano de aula
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO plano_aula (turma_id, disciplina_id, professor_id, titulo, conteudo, objetivos,
                metodologia, recursos, avaliacao, atividades_flexibilizadas, data_aula, bimestre, observacoes,
                observacoes_complementares, secoes_temas, atividade_permanente, habilidades,
                competencias_socioemocionais, competencias_especificas, competencias_gerais,
                disciplinas_componentes, status, criado_por, criado_em)
                VALUES (:turma_id, :disciplina_id, :professor_id, :titulo, :conteudo, :objetivos,
                :metodologia, :recursos, :avaliacao, :atividades_flexibilizadas, :data_aula, :bimestre, :observacoes,
                :observacoes_complementares, :secoes_temas, :atividade_permanente, :habilidades,
                :competencias_socioemocionais, :competencias_especificas, :competencias_gerais,
                :disciplinas_componentes, 'RASCUNHO', :criado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $turmaId = $dados['turma_id'];
        $disciplinaId = $dados['disciplina_id'];
        $professorId = $dados['professor_id'];
        $titulo = $dados['titulo'];
        $conteudo = $dados['conteudo'] ?? null;
        $objetivos = $dados['objetivos'] ?? null;
        $metodologia = $dados['metodologia'] ?? null;
        $recursos = $dados['recursos'] ?? null;
        $avaliacao = $dados['avaliacao'] ?? null;
        $atividadesFlexibilizadas = $dados['atividades_flexibilizadas'] ?? null;
        $dataAula = $dados['data_aula'];
        $bimestre = $dados['bimestre'] ?? null;
        $observacoes = $dados['observacoes'] ?? null;
        $observacoesComplementares = $dados['observacoes_complementares'] ?? null;
        $secoesTemas = $dados['secoes_temas'] ?? null;
        $atividadePermanente = $dados['atividade_permanente'] ?? null;
        $habilidades = $dados['habilidades'] ?? null;
        $competenciasSocioemocionais = $dados['competencias_socioemocionais'] ?? null;
        $competenciasEspecificas = $dados['competencias_especificas'] ?? null;
        $competenciasGerais = $dados['competencias_gerais'] ?? null;
        $disciplinasComponentes = $dados['disciplinas_componentes'] ?? null;
        
        // Validar se o usuario_id existe na tabela usuario antes de usar
        $criadoPor = null;
        if (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
            $usuarioId = (int)$_SESSION['usuario_id'];
            $sqlCheckUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
            $stmtCheckUsuario = $conn->prepare($sqlCheckUsuario);
            $stmtCheckUsuario->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmtCheckUsuario->execute();
            $usuarioExiste = $stmtCheckUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuarioExiste) {
                $criadoPor = $usuarioId;
            }
        }
        
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':conteudo', $conteudo);
        $stmt->bindParam(':objetivos', $objetivos);
        $stmt->bindParam(':metodologia', $metodologia);
        $stmt->bindParam(':recursos', $recursos);
        $stmt->bindParam(':avaliacao', $avaliacao);
        $stmt->bindParam(':atividades_flexibilizadas', $atividadesFlexibilizadas);
        $stmt->bindParam(':data_aula', $dataAula);
        $stmt->bindParam(':bimestre', $bimestre);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':observacoes_complementares', $observacoesComplementares);
        $stmt->bindParam(':secoes_temas', $secoesTemas);
        $stmt->bindParam(':atividade_permanente', $atividadePermanente);
        $stmt->bindParam(':habilidades', $habilidades);
        $stmt->bindParam(':competencias_socioemocionais', $competenciasSocioemocionais);
        $stmt->bindParam(':competencias_especificas', $competenciasEspecificas);
        $stmt->bindParam(':competencias_gerais', $competenciasGerais);
        $stmt->bindParam(':disciplinas_componentes', $disciplinasComponentes);
        $stmt->bindParam(':criado_por', $criadoPor);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar plano de aula'];
    }
    
    /**
     * Busca plano de aula por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pa.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                d.nome as disciplina_nome, 
                p.nome as professor_nome, 
                e.nome as escola_nome, 
                e.id as escola_id,
                u_aprovador.username as aprovado_por_nome,
                u_criador.username as criado_por_nome
                FROM plano_aula pa
                INNER JOIN turma t ON pa.turma_id = t.id
                INNER JOIN disciplina d ON pa.disciplina_id = d.id
                INNER JOIN escola e ON t.escola_id = e.id
                INNER JOIN professor prof ON pa.professor_id = prof.id
                INNER JOIN pessoa p ON prof.pessoa_id = p.id
                LEFT JOIN usuario u_aprovador ON pa.aprovado_por = u_aprovador.id
                LEFT JOIN usuario u_criador ON pa.criado_por = u_criador.id
                WHERE pa.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Aprova plano de aula (GESTAO)
     */
    public function aprovar($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE plano_aula SET status = 'APROVADO', aprovado_por = :aprovado_por,
                data_aprovacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        
        // Validar se o usuario_id existe na tabela usuario antes de usar
        $aprovadoPor = null;
        if (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
            $usuarioId = (int)$_SESSION['usuario_id'];
            $sqlCheckUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
            $stmtCheckUsuario = $conn->prepare($sqlCheckUsuario);
            $stmtCheckUsuario->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmtCheckUsuario->execute();
            $usuarioExiste = $stmtCheckUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuarioExiste) {
                $aprovadoPor = $usuarioId;
            }
        }
        
        $idParam = $id;
        $stmt->bindParam(':aprovado_por', $aprovadoPor);
        $stmt->bindParam(':id', $idParam);

        return $stmt->execute();
    }
}

?>

