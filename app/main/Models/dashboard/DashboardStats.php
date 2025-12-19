<?php
/**
 * Classe para buscar estatísticas do dashboard do administrador
 */

require_once('../../config/Database.php');

class DashboardStats {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Conta o total de escolas cadastradas
     */
    public function getTotalEscolas() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM escola");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de usuários cadastrados
     */
    public function getTotalUsuarios() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM usuario WHERE ativo = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de produtos no estoque central
     */
    public function getTotalProdutosEstoque() {
        $stmt = $this->conn->query("SELECT COUNT(DISTINCT produto_id) as total FROM estoque_central");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de eventos no calendário
     */
    public function getTotalEventosCalendario() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM calendar_events WHERE ativo = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de alunos cadastrados
     */
    public function getTotalAlunos($escolaId = null) {
        if ($escolaId) {
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT a.id) as total 
                FROM aluno a
                INNER JOIN aluno_turma at ON a.id = at.aluno_id AND at.fim IS NULL
                INNER JOIN turma t ON at.turma_id = t.id
                WHERE a.ativo = 1 AND t.escola_id = :escola_id
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM aluno WHERE ativo = 1");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de professores cadastrados
     */
    public function getTotalProfessores($escolaId = null) {
        if ($escolaId) {
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT pr.id) as total 
                FROM professor pr
                INNER JOIN professor_lotacao pl ON pr.id = pl.professor_id AND pl.fim IS NULL
                WHERE pr.ativo = 1 AND pl.escola_id = :escola_id
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM professor WHERE ativo = 1");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de gestores cadastrados
     */
    public function getTotalGestores() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM gestor WHERE ativo = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de turmas ativas
     */
    public function getTotalTurmas($escolaId = null) {
        if ($escolaId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM turma WHERE ativo = 1 AND escola_id = :escola_id");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM turma WHERE ativo = 1");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de funcionários (pessoas com tipo FUNCIONARIO)
     */
    public function getTotalFuncionarios() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM pessoa WHERE tipo = 'FUNCIONARIO'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de comunicados
     */
    public function getTotalComunicados() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM comunicado");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de notas lançadas
     */
    public function getTotalNotas() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM nota");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de frequências registradas
     */
    public function getTotalFrequencias() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM frequencia");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de cardápios cadastrados
     */
    public function getTotalCardapios() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM cardapio");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta o total de pedidos de cesta
     */
    public function getTotalPedidosCesta() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM pedido_cesta");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Busca atividades recentes do sistema
     */
    public function getAtividadesRecentes($limit = 5) {
        $atividades = [];

        try {
            // Últimos alunos matriculados
            $stmt = $this->conn->prepare("
                SELECT a.id, p.nome, a.data_matricula, 'aluno_matriculado' as tipo
                FROM aluno a
                JOIN pessoa p ON a.pessoa_id = p.id
                WHERE a.data_matricula IS NOT NULL
                ORDER BY a.data_matricula DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($alunos as $aluno) {
                $atividades[] = [
                    'tipo' => 'aluno_matriculado',
                    'titulo' => 'Novo aluno matriculado',
                    'descricao' => $aluno['nome'] ?? 'Aluno',
                    'data' => $aluno['data_matricula'] ?? date('Y-m-d'),
                    'icon' => 'user',
                    'color' => 'blue'
                ];
            }
        } catch (Exception $e) {
            // Ignorar erro se a tabela não existir ou não houver dados
        }

        try {
            // Últimas notas lançadas
            $stmt = $this->conn->prepare("
                SELECT n.id, p.nome as aluno_nome, n.lancado_em, n.nota
                FROM nota n
                JOIN aluno a ON n.aluno_id = a.id
                JOIN pessoa p ON a.pessoa_id = p.id
                ORDER BY n.lancado_em DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($notas as $nota) {
                $atividades[] = [
                    'tipo' => 'nota_lancada',
                    'titulo' => 'Nota lançada',
                    'descricao' => ($nota['aluno_nome'] ?? 'Aluno') . ' - Nota: ' . ($nota['nota'] ?? 'N/A'),
                    'data' => $nota['lancado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'document',
                    'color' => 'orange'
                ];
            }
        } catch (Exception $e) {
            // Ignorar erro se a tabela não existir ou não houver dados
        }

        try {
            // Últimas frequências registradas
            $stmt = $this->conn->prepare("
                SELECT f.id, p.nome as aluno_nome, f.data, f.registrado_em
                FROM frequencia f
                JOIN aluno a ON f.aluno_id = a.id
                JOIN pessoa p ON a.pessoa_id = p.id
                ORDER BY f.registrado_em DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($frequencias as $freq) {
                $atividades[] = [
                    'tipo' => 'frequencia_registrada',
                    'titulo' => 'Frequência registrada',
                    'descricao' => $freq['aluno_nome'] ?? 'Aluno',
                    'data' => $freq['registrado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'calendar',
                    'color' => 'green'
                ];
            }
        } catch (Exception $e) {
            // Ignorar erro se a tabela não existir ou não houver dados
        }

        // Ordenar por data (mais recente primeiro)
        if (!empty($atividades)) {
            usort($atividades, function($a, $b) {
                $timeA = strtotime($a['data'] ?? '1970-01-01');
                $timeB = strtotime($b['data'] ?? '1970-01-01');
                return $timeB - $timeA;
            });
        }

        return array_slice($atividades, 0, $limit);
    }

    /**
     * Calcula a frequência média dos alunos
     */
    public function getFrequenciaMedia($escolaId = null) {
        if ($escolaId) {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_registros,
                    SUM(CASE WHEN f.presenca = 1 THEN 1 ELSE 0 END) as total_presencas
                FROM frequencia f
                INNER JOIN turma t ON f.turma_id = t.id
                WHERE t.escola_id = :escola_id
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->conn->query("
                SELECT 
                    COUNT(*) as total_registros,
                    SUM(CASE WHEN presenca = 1 THEN 1 ELSE 0 END) as total_presencas
                FROM frequencia
            ");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total_registros'] > 0) {
            $percentual = ($result['total_presencas'] / $result['total_registros']) * 100;
            return round($percentual, 1);
        }
        
        return 0;
    }

    /**
     * Calcula a média geral das notas
     */
    public function getMediaGeralNotas($escolaId = null) {
        if ($escolaId) {
            $stmt = $this->conn->prepare("
                SELECT AVG(n.nota) as media_geral
                FROM nota n
                INNER JOIN turma t ON n.turma_id = t.id
                WHERE n.nota IS NOT NULL AND t.escola_id = :escola_id
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->conn->query("
                SELECT AVG(nota) as media_geral
                FROM nota
                WHERE nota IS NOT NULL
            ");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['media_geral']) {
            return round($result['media_geral'], 1);
        }
        
        return 0;
    }

    /**
     * Calcula a média geral das notas (alias para getMediaGeralNotas)
     */
    public function getMediaGeral() {
        return $this->getMediaGeralNotas();
    }

    /**
     * Conta escolas criadas este mês
     */
    public function getEscolasEsteMes() {
        $stmt = $this->conn->query("
            SELECT COUNT(*) as total
            FROM escola
            WHERE MONTH(criado_em) = MONTH(CURRENT_DATE())
            AND YEAR(criado_em) = YEAR(CURRENT_DATE())
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta usuários criados esta semana
     */
    public function getUsuariosEstaSemana() {
        $stmt = $this->conn->query("
            SELECT COUNT(*) as total
            FROM usuario
            WHERE WEEK(created_at) = WEEK(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta notas lançadas hoje
     */
    public function getNotasHoje() {
        $stmt = $this->conn->query("
            SELECT COUNT(*) as total
            FROM nota
            WHERE DATE(lancado_em) = CURRENT_DATE()
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta comunicados enviados hoje
     */
    public function getComunicadosHoje() {
        $stmt = $this->conn->query("
            SELECT COUNT(*) as total
            FROM comunicado
            WHERE DATE(criado_em) = CURRENT_DATE()
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Conta pedidos pendentes de aprovação
     */
    public function getPedidosPendentes() {
        $stmt = $this->conn->query("
            SELECT COUNT(*) as total
            FROM pedido_cesta
            WHERE status IN ('RASCUHO', 'ENVIADO')
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Formata número para exibição (ex: 2500 -> 2.5k)
     */
    public function formatarNumero($numero) {
        if ($numero >= 1000) {
            return number_format($numero / 1000, 1) . 'k';
        }
        return (string)$numero;
    }

    /**
     * Busca dados completos do usuário logado
     */
    public function getDadosUsuario($usuarioId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.role,
                    u.ativo,
                    u.ultimo_login,
                    u.created_at as data_criacao,
                    p.nome,
                    p.cpf,
                    p.email,
                    p.telefone,
                    p.data_nascimento,
                    p.sexo,
                    p.tipo as tipo_pessoa
                FROM usuario u
                JOIN pessoa p ON u.pessoa_id = p.id
                WHERE u.id = :usuario_id
            ");
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Formatar CPF
                if (!empty($result['cpf'])) {
                    $cpf = $result['cpf'];
                    if (strlen($cpf) == 11) {
                        $result['cpf_formatado'] = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                    } else {
                        $result['cpf_formatado'] = $cpf;
                    }
                }
                
                // Calcular idade se tiver data de nascimento
                if (!empty($result['data_nascimento']) && $result['data_nascimento'] != '0000-00-00') {
                    $nascimento = new DateTime($result['data_nascimento']);
                    $hoje = new DateTime();
                    $result['idade'] = $hoje->diff($nascimento)->y;
                }
                
                // Formatar último login
                if (!empty($result['ultimo_login'])) {
                    $ultimoLogin = new DateTime($result['ultimo_login']);
                    $agora = new DateTime();
                    $diff = $agora->diff($ultimoLogin);
                    
                    if ($diff->days > 0) {
                        $result['ultimo_login_formatado'] = 'Há ' . $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                    } elseif ($diff->h > 0) {
                        $result['ultimo_login_formatado'] = 'Há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                    } elseif ($diff->i > 0) {
                        $result['ultimo_login_formatado'] = 'Há ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
                    } else {
                        $result['ultimo_login_formatado'] = 'Agora mesmo';
                    }
                } else {
                    $result['ultimo_login_formatado'] = 'Nunca';
                }
                
                // Formatar data de criação
                if (!empty($result['data_criacao'])) {
                    $criacao = new DateTime($result['data_criacao']);
                    $result['data_criacao_formatada'] = $criacao->format('d/m/Y');
                }
            }
            
            return $result ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Busca estatísticas pessoais do usuário
     */
    public function getEstatisticasUsuario($usuarioId, $tipoUsuario, $params = []) {
        $estatisticas = [];
        
        try {
            // Estatísticas específicas por tipo de usuário
            switch(strtoupper($tipoUsuario)) {
                case 'ADM':
                    $estatisticas['escolas_gerenciadas'] = $this->getTotalEscolas();
                    $estatisticas['usuarios_ativos'] = $this->getTotalUsuarios();
                    break;
                    
                case 'GESTAO':
                    // Buscar escolas do gestor
                    $stmt = $this->conn->prepare("
                        SELECT COUNT(DISTINCT gl.escola_id) as total
                        FROM gestor_lotacao gl
                        JOIN gestor g ON gl.gestor_id = g.id
                        JOIN pessoa p ON g.pessoa_id = p.id
                        JOIN usuario u ON u.pessoa_id = p.id
                        WHERE u.id = :usuario_id AND gl.fim IS NULL
                    ");
                    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $estatisticas['escolas_gerenciadas'] = (int)($result['total'] ?? 0);
                    break;
                    
                case 'PROFESSOR':
                    // Buscar turmas do professor (filtradas por escola se fornecida)
                    $escolaId = $params['escola_id'] ?? null;
                    if ($escolaId) {
                        $stmt = $this->conn->prepare("
                            SELECT COUNT(DISTINCT tp.turma_id) as total
                            FROM turma_professor tp
                            JOIN professor pr ON tp.professor_id = pr.id
                            JOIN pessoa p ON pr.pessoa_id = p.id
                            JOIN usuario u ON u.pessoa_id = p.id
                            JOIN turma t ON tp.turma_id = t.id
                            WHERE u.id = :usuario_id 
                            AND t.escola_id = :escola_id
                            AND (tp.fim IS NULL OR tp.fim >= CURDATE())
                        ");
                        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                        $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                    } else {
                        $stmt = $this->conn->prepare("
                            SELECT COUNT(DISTINCT tp.turma_id) as total
                            FROM turma_professor tp
                            JOIN professor pr ON tp.professor_id = pr.id
                            JOIN pessoa p ON pr.pessoa_id = p.id
                            JOIN usuario u ON u.pessoa_id = p.id
                            WHERE u.id = :usuario_id AND (tp.fim IS NULL OR tp.fim >= CURDATE())
                        ");
                        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    }
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $estatisticas['turmas_atribuidas'] = (int)($result['total'] ?? 0);
                    break;
            }
        } catch (Exception $e) {
            // Ignorar erros
        }
        
        return $estatisticas;
    }

    /**
     * Busca frequências registradas hoje
     */
    public function getFrequenciasHoje($escolaId = null) {
        try {
            $today = date('Y-m-d');
            if ($escolaId) {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM frequencia f
                    INNER JOIN turma t ON f.turma_id = t.id
                    WHERE DATE(f.data) = :today AND t.escola_id = :escola_id
                ");
                $stmt->bindParam(':today', $today);
                $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            } else {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM frequencia WHERE DATE(data) = :today");
                $stmt->bindParam(':today', $today);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca notas pendentes (sem lançamento)
     */
    public function getNotasPendentes($escolaId = null) {
        try {
            if ($escolaId) {
                // Alunos sem notas na escola específica
                $stmt = $this->conn->prepare("
                    SELECT COUNT(DISTINCT a.id) as total
                    FROM aluno a
                    INNER JOIN aluno_turma at ON a.id = at.aluno_id AND at.fim IS NULL
                    INNER JOIN turma t ON at.turma_id = t.id
                    LEFT JOIN nota n ON a.id = n.aluno_id AND n.turma_id = t.id
                    WHERE a.ativo = 1 AND t.escola_id = :escola_id
                    GROUP BY a.id
                    HAVING COUNT(n.id) = 0
                ");
                $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return count($result);
            } else {
                // Esta é uma estimativa - pode ser ajustada conforme a lógica de negócio
                $stmt = $this->conn->query("
                    SELECT COUNT(DISTINCT a.id) as total
                    FROM aluno a
                    LEFT JOIN nota n ON a.id = n.aluno_id
                    WHERE a.ativo = 1
                    GROUP BY a.id
                    HAVING COUNT(n.id) = 0
                ");
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return count($result);
            }
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta tipos de relatórios disponíveis
     */
    public function getTotalRelatoriosDisponiveis() {
        // Retorna número fixo de tipos de relatórios disponíveis
        // Pode ser expandido para buscar de uma tabela de relatórios se existir
        return 15;
    }

    /**
     * Calcula crescimento percentual de alunos comparado ao mês anterior
     */
    public function getCrescimentoAlunos($escolaId = null) {
        try {
            $currentMonth = date('m');
            $currentYear = date('Y');
            
            // Alunos deste mês
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM aluno 
                WHERE MONTH(data_matricula) = :month 
                AND YEAR(data_matricula) = :year 
                AND ativo = 1
            ");
            $stmt->bindParam(':month', $currentMonth);
            $stmt->bindParam(':year', $currentYear);
            $stmt->execute();
            $atual = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Alunos do mês anterior
            $lastMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
            $lastYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM aluno 
                WHERE MONTH(data_matricula) = :month 
                AND YEAR(data_matricula) = :year 
                AND ativo = 1
            ");
            $stmt->bindParam(':month', $lastMonth);
            $stmt->bindParam(':year', $lastYear);
            $stmt->execute();
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($anterior > 0) {
                $crescimento = (($atual - $anterior) / $anterior) * 100;
                return round($crescimento, 1);
            }
            
            return $atual > 0 ? 100 : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca média de notas de um aluno específico
     */
    public function getMediaAluno($alunoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AVG(nota) as media 
                FROM nota 
                WHERE aluno_id = :aluno_id
            ");
            $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['media'] ? round((float)$result['media'], 1) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca frequência média de um aluno específico
     */
    public function getFrequenciaAluno($alunoId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AVG(presenca) * 100 as frequencia 
                FROM frequencia 
                WHERE aluno_id = :aluno_id
            ");
            $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['frequencia'] ? round((float)$result['frequencia'], 1) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca atividades recentes de um aluno específico
     */
    public function getAtividadesAluno($alunoId, $limit = 3) {
        $atividades = [];
        
        try {
            // Últimas notas do aluno
            $stmt = $this->conn->prepare("
                SELECT n.id, d.nome as disciplina, n.nota, n.lancado_em
                FROM nota n
                JOIN disciplina d ON n.disciplina_id = d.id
                WHERE n.aluno_id = :aluno_id
                ORDER BY n.lancado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($notas as $nota) {
                $dataNota = new DateTime($nota['lancado_em']);
                $agora = new DateTime();
                $diff = $agora->diff($dataNota);
                
                $tempo = '';
                if ($diff->days > 0) {
                    $tempo = $diff->days == 1 ? 'Ontem' : 'Há ' . $diff->days . ' dias';
                } elseif ($diff->h > 0) {
                    $tempo = 'Há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } else {
                    $tempo = 'Agora mesmo';
                }
                
                $atividades[] = [
                    'tipo' => 'nota',
                    'titulo' => 'Nova nota lançada',
                    'descricao' => ($nota['disciplina'] ?? 'Disciplina') . ' - Nota: ' . ($nota['nota'] ?? 'N/A'),
                    'data' => $nota['lancado_em'],
                    'tempo' => $tempo,
                    'color' => 'blue'
                ];
            }
            
            // Últimas frequências do aluno
            $stmt = $this->conn->prepare("
                SELECT f.id, d.nome as disciplina, f.presenca, f.data, f.registrado_em
                FROM frequencia f
                LEFT JOIN disciplina d ON f.disciplina_id = d.id
                WHERE f.aluno_id = :aluno_id
                ORDER BY f.registrado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($frequencias as $freq) {
                $dataFreq = new DateTime($freq['registrado_em']);
                $agora = new DateTime();
                $diff = $agora->diff($dataFreq);
                
                $tempo = '';
                if ($diff->days > 0) {
                    $tempo = $diff->days == 1 ? 'Ontem' : 'Há ' . $diff->days . ' dias';
                } elseif ($diff->h > 0) {
                    $tempo = 'Há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } else {
                    $tempo = 'Agora mesmo';
                }
                
                $status = $freq['presenca'] > 0 ? 'Presente' : 'Falta';
                $atividades[] = [
                    'tipo' => 'frequencia',
                    'titulo' => 'Presença registrada',
                    'descricao' => ($freq['disciplina'] ?? 'Aula') . ' - ' . $status,
                    'data' => $freq['registrado_em'],
                    'tempo' => $tempo,
                    'color' => 'green'
                ];
            }
            
            // Ordenar por data
            usort($atividades, function($a, $b) {
                return strtotime($b['data']) - strtotime($a['data']);
            });
            
            return array_slice($atividades, 0, $limit);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Conta total de itens no estoque central
     */
    public function getTotalItensEstoque() {
        try {
            $stmt = $this->conn->query("SELECT SUM(quantidade) as total FROM estoque_central");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta produtos que entraram hoje no estoque
     */
    public function getProdutosEntradaHoje() {
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT me.produto_id) as total 
                FROM movimentacao_estoque me
                WHERE DATE(me.data_movimentacao) = :today 
                AND me.tipo = 'entrada'
            ");
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta itens com estoque baixo
     */
    public function getItensEstoqueBaixo() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as total 
                FROM estoque_central 
                WHERE quantidade < estoque_minimo
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta fornecedores ativos
     */
    public function getFornecedoresAtivos() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM fornecedor WHERE ativo = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta entregas hoje
     */
    public function getEntregasHoje() {
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM pedido_cesta 
                WHERE DATE(data_entrega) = :today 
                AND status = 'entregue'
            ");
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta matrículas pendentes
     */
    public function getMatriculasPendentes() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as total 
                FROM aluno 
                WHERE status_matricula = 'pendente' OR status_matricula IS NULL
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta novos cadastros hoje
     */
    public function getNovosCadastrosHoje() {
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM aluno 
                WHERE DATE(data_matricula) = :today
            ");
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta aprovações pendentes
     */
    public function getAprovacoesPendentes() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as total 
                FROM aluno 
                WHERE status_matricula = 'pendente'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta revisões pendentes de cardápios
     */
    public function getRevisoesCardapioPendentes() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as total 
                FROM cardapio 
                WHERE status = 'pendente' OR status = 'em_revisao'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Calcula peso total distribuído hoje (em kg)
     */
    public function getPesoDistribuidoHoje() {
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT SUM(pi.quantidade * p.peso_unitario) / 1000 as total_kg
                FROM pedido_item pi
                JOIN produto p ON pi.produto_id = p.id
                JOIN pedido_cesta pc ON pi.pedido_id = pc.id
                WHERE DATE(pc.data_entrega) = :today 
                AND pc.status = 'entregue'
            ");
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_kg'] ? round((float)$result['total_kg'], 1) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta alunos por turno
     */
    public function getAlunosPorTurno($turno) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT a.id) as total
                FROM aluno a
                JOIN aluno_turma at ON a.id = at.aluno_id
                JOIN turma t ON at.turma_id = t.id
                WHERE at.fim IS NULL AND t.turno = :turno AND a.ativo = 1
            ");
            $stmt->bindParam(':turno', $turno);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta relatórios disponíveis por tipo
     */
    public function getRelatoriosPorTipo($tipo = 'mensal') {
        // Retorna número fixo de tipos de relatórios disponíveis
        // Pode ser expandido para buscar de uma tabela de relatórios se existir
        $relatorios = [
            'mensal' => 12,
            'trimestral' => 4,
            'semanal' => 4,
            'diario' => 1
        ];
        return $relatorios[$tipo] ?? 0;
    }

    /**
     * Conta cardápios criados esta semana
     */
    public function getCardapiosEstaSemana() {
        try {
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM cardapio 
                WHERE DATE(criado_em) BETWEEN :start AND :end
            ");
            $stmt->bindParam(':start', $startOfWeek);
            $stmt->bindParam(':end', $endOfWeek);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Calcula peso total distribuído esta semana (em kg)
     */
    public function getPesoDistribuidoEstaSemana() {
        try {
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            
            $stmt = $this->conn->prepare("
                SELECT SUM(pi.quantidade * p.peso_unitario) / 1000 as total_kg
                FROM pedido_item pi
                JOIN produto p ON pi.produto_id = p.id
                JOIN pedido_cesta pc ON pi.pedido_id = pc.id
                WHERE DATE(pc.data_entrega) BETWEEN :start AND :end 
                AND pc.status = 'entregue'
            ");
            $stmt->bindParam(':start', $startOfWeek);
            $stmt->bindParam(':end', $endOfWeek);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_kg'] ? round((float)$result['total_kg'], 1) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta total de itens de insumos no estoque
     */
    public function getTotalItensInsumos() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as total 
                FROM estoque_central ec
                JOIN produto p ON ec.produto_id = p.id
                WHERE p.tipo = 'insumo' OR p.categoria LIKE '%insumo%'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca quantidade de um produto específico no estoque (em kg)
     */
    public function getQuantidadeProduto($nomeProduto) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(ec.quantidade * COALESCE(p.peso_unitario, 1)) / 1000 as total_kg
                FROM estoque_central ec
                JOIN produto p ON ec.produto_id = p.id
                WHERE p.nome LIKE :nome
            ");
            $nome = '%' . $nomeProduto . '%';
            $stmt->bindParam(':nome', $nome);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_kg'] ? round((float)$result['total_kg'], 0) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta total de alunos beneficiados
     */
    public function getTotalAlunosBeneficiados() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(DISTINCT a.id) as total
                FROM aluno a
                WHERE a.ativo = 1 AND a.beneficiario_merenda = 1
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            // Se a coluna não existir, retorna total de alunos ativos
            return $this->getTotalAlunos();
        }
    }

    /**
     * Conta alunos de uma turma específica
     */
    public function getAlunosPorTurma($nomeTurma) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT a.id) as total
                FROM aluno a
                JOIN aluno_turma at ON a.id = at.aluno_id
                JOIN turma t ON at.turma_id = t.id
                WHERE CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) LIKE :nome 
                AND a.ativo = 1 AND at.fim IS NULL
            ");
            $nome = '%' . $nomeTurma . '%';
            $stmt->bindParam(':nome', $nome);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta alunos presentes hoje
     */
    public function getAlunosPresentesHoje() {
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT aluno_id) as total
                FROM frequencia
                WHERE DATE(data) = :today AND presenca = 1
            ");
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta alunos faltosos hoje
     */
    public function getAlunosFaltososHoje() {
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT aluno_id) as total
                FROM frequencia
                WHERE DATE(data) = :today AND presenca = 0
            ");
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca atividades recentes de um gestor específico filtradas por escola
     */
    public function getAtividadesRecentesGestor($escolaId, $limit = 5) {
        $atividades = [];

        try {
            // Últimos alunos matriculados na escola
            $stmt = $this->conn->prepare("
                SELECT DISTINCT a.id, p.nome, a.data_matricula, 'aluno_matriculado' as tipo
                FROM aluno a
                JOIN pessoa p ON a.pessoa_id = p.id
                JOIN aluno_turma at ON a.id = at.aluno_id AND at.fim IS NULL
                JOIN turma t ON at.turma_id = t.id
                WHERE a.data_matricula IS NOT NULL
                AND t.escola_id = :escola_id
                ORDER BY a.data_matricula DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($alunos as $aluno) {
                $atividades[] = [
                    'tipo' => 'aluno_matriculado',
                    'titulo' => 'Novo aluno matriculado',
                    'descricao' => $aluno['nome'] ?? 'Aluno',
                    'data' => $aluno['data_matricula'] ?? date('Y-m-d'),
                    'icon' => 'user',
                    'color' => 'blue'
                ];
            }

            // Últimas notas lançadas na escola
            $stmt = $this->conn->prepare("
                SELECT n.id, p.nome as aluno_nome, n.lancado_em, n.nota
                FROM nota n
                JOIN aluno a ON n.aluno_id = a.id
                JOIN pessoa p ON a.pessoa_id = p.id
                JOIN turma t ON n.turma_id = t.id
                WHERE t.escola_id = :escola_id
                ORDER BY n.lancado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($notas as $nota) {
                $atividades[] = [
                    'tipo' => 'nota_lancada',
                    'titulo' => 'Nota lançada',
                    'descricao' => ($nota['aluno_nome'] ?? 'Aluno') . ' - Nota: ' . ($nota['nota'] ?? 'N/A'),
                    'data' => $nota['lancado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'document',
                    'color' => 'orange'
                ];
            }

            // Últimas frequências registradas na escola
            $stmt = $this->conn->prepare("
                SELECT f.id, p.nome as aluno_nome, f.data, f.registrado_em
                FROM frequencia f
                JOIN aluno a ON f.aluno_id = a.id
                JOIN pessoa p ON a.pessoa_id = p.id
                JOIN turma t ON f.turma_id = t.id
                WHERE t.escola_id = :escola_id
                ORDER BY f.registrado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($frequencias as $freq) {
                $atividades[] = [
                    'tipo' => 'frequencia_registrada',
                    'titulo' => 'Frequência registrada',
                    'descricao' => $freq['aluno_nome'] ?? 'Aluno',
                    'data' => $freq['registrado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'calendar',
                    'color' => 'green'
                ];
            }

            // Ordenar por data (mais recente primeiro)
            if (!empty($atividades)) {
                usort($atividades, function($a, $b) {
                    $timeA = strtotime($a['data'] ?? '1970-01-01');
                    $timeB = strtotime($b['data'] ?? '1970-01-01');
                    return $timeB - $timeA;
                });
            }

            return array_slice($atividades, 0, $limit);
        } catch (Exception $e) {
            error_log("Erro ao buscar atividades do gestor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca atividades recentes de um professor específico filtradas por escola
     */
    public function getAtividadesRecentesProfessor($professorId, $escolaId, $limit = 5) {
        $atividades = [];

        try {
            // Últimas notas lançadas pelo professor na escola selecionada
            $stmt = $this->conn->prepare("
                SELECT n.id, p.nome as aluno_nome, n.lancado_em, n.nota, d.nome as disciplina_nome, t.escola_id
                FROM nota n
                JOIN aluno a ON n.aluno_id = a.id
                JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN disciplina d ON n.disciplina_id = d.id
                JOIN turma t ON n.turma_id = t.id
                JOIN turma_professor tp ON t.id = tp.turma_id
                WHERE tp.professor_id = :professor_id 
                AND t.escola_id = :escola_id
                AND (tp.fim IS NULL OR tp.fim >= CURDATE())
                ORDER BY n.lancado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($notas as $nota) {
                $atividades[] = [
                    'tipo' => 'nota_lancada',
                    'titulo' => 'Nota lançada',
                    'descricao' => ($nota['aluno_nome'] ?? 'Aluno') . ' - ' . ($nota['disciplina_nome'] ?? 'Disciplina') . ' - Nota: ' . ($nota['nota'] ?? 'N/A'),
                    'data' => $nota['lancado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'document',
                    'color' => 'orange'
                ];
            }

            // Últimas frequências registradas pelo professor na escola selecionada
            $stmt = $this->conn->prepare("
                SELECT f.id, p.nome as aluno_nome, f.data, f.registrado_em, f.presenca, t.escola_id
                FROM frequencia f
                JOIN aluno a ON f.aluno_id = a.id
                JOIN pessoa p ON a.pessoa_id = p.id
                JOIN turma t ON f.turma_id = t.id
                JOIN turma_professor tp ON t.id = tp.turma_id
                WHERE tp.professor_id = :professor_id 
                AND t.escola_id = :escola_id
                AND (tp.fim IS NULL OR tp.fim >= CURDATE())
                ORDER BY f.registrado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $frequencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($frequencias as $freq) {
                $status = $freq['presenca'] > 0 ? 'Presente' : 'Falta';
                $atividades[] = [
                    'tipo' => 'frequencia_registrada',
                    'titulo' => 'Frequência registrada',
                    'descricao' => ($freq['aluno_nome'] ?? 'Aluno') . ' - ' . $status,
                    'data' => $freq['registrado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'calendar',
                    'color' => 'green'
                ];
            }

            // Últimos planos de aula criados pelo professor na escola selecionada
            $stmt = $this->conn->prepare("
                SELECT pa.id, pa.titulo, pa.data_aula, pa.criado_em, t.escola_id
                FROM plano_aula pa
                JOIN turma t ON pa.turma_id = t.id
                WHERE pa.professor_id = :professor_id 
                AND t.escola_id = :escola_id
                ORDER BY pa.criado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($planos as $plano) {
                $atividades[] = [
                    'tipo' => 'plano_aula',
                    'titulo' => 'Plano de aula criado',
                    'descricao' => ($plano['titulo'] ?? 'Plano de aula') . ' - ' . date('d/m/Y', strtotime($plano['data_aula'] ?? $plano['criado_em'])),
                    'data' => $plano['criado_em'] ?? date('Y-m-d H:i:s'),
                    'icon' => 'document-text',
                    'color' => 'blue'
                ];
            }

            // Ordenar por data (mais recente primeiro)
            if (!empty($atividades)) {
                usort($atividades, function($a, $b) {
                    $timeA = strtotime($a['data'] ?? '1970-01-01');
                    $timeB = strtotime($b['data'] ?? '1970-01-01');
                    return $timeB - $timeA;
                });
            }

            return array_slice($atividades, 0, $limit);
        } catch (Exception $e) {
            error_log("Erro ao buscar atividades do professor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca atividades recentes de um nutricionista específico
     */
    public function getAtividadesNutricionista($nutricionistaId, $limit = 3) {
        $atividades = [];
        
        try {
            // Últimos cardápios criados pelo nutricionista
            $stmt = $this->conn->prepare("
                SELECT c.id, c.mes, c.ano, c.status, e.nome as escola_nome, c.criado_em
                FROM cardapio c
                INNER JOIN escola e ON c.escola_id = e.id
                WHERE c.criado_por = :nutricionista_id
                ORDER BY c.criado_em DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':nutricionista_id', $nutricionistaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $cardapios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                     'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            
            foreach ($cardapios as $cardapio) {
                $dataCardapio = new DateTime($cardapio['criado_em']);
                $agora = new DateTime();
                $diff = $agora->diff($dataCardapio);
                
                $tempo = '';
                if ($diff->days > 0) {
                    $tempo = $diff->days == 1 ? 'Ontem' : 'Há ' . $diff->days . ' dias';
                } elseif ($diff->h > 0) {
                    $tempo = 'Há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } else {
                    $tempo = 'Agora mesmo';
                }
                
                $mesNome = isset($meses[$cardapio['mes'] - 1]) ? $meses[$cardapio['mes'] - 1] : $cardapio['mes'];
                $statusText = $cardapio['status'] === 'APROVADO' ? 'Aprovado' : 
                             ($cardapio['status'] === 'REJEITADO' ? 'Rejeitado' : 
                             ($cardapio['status'] === 'ENVIADO' ? 'Enviado' : 'Rascunho'));
                
                $atividades[] = [
                    'tipo' => 'cardapio',
                    'titulo' => 'Cardápio criado',
                    'descricao' => ($cardapio['escola_nome'] ?? 'Escola') . ' - ' . $mesNome . '/' . $cardapio['ano'] . ' (' . $statusText . ')',
                    'data' => $cardapio['criado_em'],
                    'tempo' => $tempo,
                    'color' => 'blue'
                ];
            }
            
            // Últimos pedidos criados pelo nutricionista
            $stmt = $this->conn->prepare("
                SELECT pc.id, pc.mes, pc.status, e.nome as escola_nome, pc.data_criacao, pc.data_envio
                FROM pedido_cesta pc
                INNER JOIN escola e ON pc.escola_id = e.id
                WHERE pc.nutricionista_id = :nutricionista_id
                ORDER BY COALESCE(pc.data_envio, pc.data_criacao) DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':nutricionista_id', $nutricionistaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pedidos as $pedido) {
                $dataPedido = new DateTime($pedido['data_envio'] ?? $pedido['data_criacao']);
                $agora = new DateTime();
                $diff = $agora->diff($dataPedido);
                
                $tempo = '';
                if ($diff->days > 0) {
                    $tempo = $diff->days == 1 ? 'Ontem' : 'Há ' . $diff->days . ' dias';
                } elseif ($diff->h > 0) {
                    $tempo = 'Há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } else {
                    $tempo = 'Agora mesmo';
                }
                
                $mesNome = isset($meses[$pedido['mes'] - 1]) ? $meses[$pedido['mes'] - 1] : $pedido['mes'];
                $statusText = $pedido['status'] === 'APROVADO' ? 'Aprovado' : 
                             ($pedido['status'] === 'REJEITADO' ? 'Rejeitado' : 
                             ($pedido['status'] === 'ENVIADO' ? 'Enviado' : 'Rascunho'));
                
                $atividades[] = [
                    'tipo' => 'pedido',
                    'titulo' => 'Pedido de compra',
                    'descricao' => ($pedido['escola_nome'] ?? 'Escola') . ' - ' . $mesNome . ' (' . $statusText . ')',
                    'data' => $pedido['data_envio'] ?? $pedido['data_criacao'],
                    'tempo' => $tempo,
                    'color' => 'green'
                ];
            }
            
            // Ordenar por data
            usort($atividades, function($a, $b) {
                return strtotime($b['data']) - strtotime($a['data']);
            });
            
            return array_slice($atividades, 0, $limit);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Conta cardápios pendentes de um nutricionista específico
     */
    public function getCardapiosPendentesNutricionista($nutricionistaId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM cardapio 
                WHERE criado_por = :nutricionista_id 
                AND (status = 'RASCUNHO' OR status = 'ENVIADO')
            ");
            $stmt->bindParam(':nutricionista_id', $nutricionistaId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Conta pedidos pendentes de um nutricionista específico
     */
    public function getPedidosPendentesNutricionista($nutricionistaId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM pedido_cesta 
                WHERE nutricionista_id = :nutricionista_id 
                AND status = 'ENVIADO'
            ");
            $stmt->bindParam(':nutricionista_id', $nutricionistaId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Busca os últimos pedidos de um nutricionista específico
     */
    public function getUltimosPedidosNutricionista($nutricionistaId, $limit = 3) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pc.*, e.nome as escola_nome
                FROM pedido_cesta pc
                INNER JOIN escola e ON pc.escola_id = e.id
                WHERE pc.nutricionista_id = :nutricionista_id
                ORDER BY COALESCE(pc.data_envio, pc.data_criacao) DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':nutricionista_id', $nutricionistaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

