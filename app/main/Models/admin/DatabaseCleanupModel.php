<?php
/**
 * DatabaseCleanupModel - Model para limpeza do banco de dados
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class DatabaseCleanupModel {
    private $db;
    private $conn;
    private $admUserId; // ID do usuário ADM geral (nunca será excluído)
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        // Buscar ID do usuário ADM geral
        $this->admUserId = $this->getAdmUserId();
    }
    
    /**
     * Busca o ID do usuário ADM geral
     */
    private function getAdmUserId() {
        try {
            // Primeiro tenta buscar pelo usuário logado se for ADM
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['usuario_id']) && isset($_SESSION['tipo']) && strtolower($_SESSION['tipo']) === 'adm') {
                $stmt = $this->conn->prepare("
                    SELECT id FROM usuario WHERE id = :id AND (UPPER(role) = 'ADM')
                ");
                $stmt->bindParam(':id', $_SESSION['usuario_id'], PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    return (int)$result['id'];
                }
            }
            
            // Se não encontrou, busca qualquer ADM
            $stmt = $this->conn->prepare("
                SELECT u.id 
                FROM usuario u
                WHERE UPPER(u.role) = 'ADM'
                ORDER BY u.id ASC
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Limpa dados acadêmicos (mantém usuários)
     */
    public function limparDadosAcademicos() {
        try {
            $this->conn->beginTransaction();
            
            // Desabilitar verificação de foreign keys temporariamente
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Ordem de exclusão: primeiro itens/relacionamentos, depois principais
            $tabelasItens = [
                'boletim_item',
                'nota',
                'frequencia',
                'observacao_desempenho',
                'avaliacao',
                'historico_escolar',
                'justificativa',
                'plano_aula',
                'aluno_responsavel',
                'aluno_turma',
                'turma_professor',
                'professor_lotacao',
                'gestor_lotacao',
                'funcionario_lotacao'
            ];
            
            $tabelasPrincipais = [
                'boletim',
                'turma',
                'serie',
                'disciplina',
                'aluno',
                'professor',
                'gestor',
                'funcionario'
            ];
            
            $todasTabelas = array_merge($tabelasItens, $tabelasPrincipais);
            
            foreach ($todasTabelas as $tabela) {
                try {
                    $this->conn->exec("DELETE FROM `$tabela`");
                } catch (Exception $e) {
                    // Ignorar se a tabela não existir
                }
            }
            
            // Reabilitar verificação de foreign keys
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Dados acadêmicos limpos com sucesso'];
        } catch (Exception $e) {
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao limpar dados acadêmicos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Limpa dados de merenda (mantém usuários)
     */
    public function limparDadosMerenda() {
        try {
            $this->conn->beginTransaction();
            
            // Desabilitar verificação de foreign keys temporariamente
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Ordem: primeiro itens, depois principais
            $tabelasItens = [
                'pedido_item',
                'cardapio_item',
                'entrega_item',
                'consumo_item',
                'pacote_item',
                'substituicao_alimento',
                'indicador_nutricional',
                'parecer_tecnico',
                'nutricionista_lotacao'
            ];
            
            $tabelasPrincipais = [
                'pedido_cesta',
                'cardapio',
                'entrega',
                'consumo_diario',
                'desperdicio',
                'custo_merenda',
                'movimentacao_estoque',
                'estoque_central',
                'fornecedor',
                'produto',
                'nutricionista',
                'pacote'
            ];
            
            $todasTabelas = array_merge($tabelasItens, $tabelasPrincipais);
            
            foreach ($todasTabelas as $tabela) {
                try {
                    $this->conn->exec("DELETE FROM `$tabela`");
                } catch (Exception $e) {
                    // Ignorar se a tabela não existir
                }
            }
            
            // Reabilitar verificação de foreign keys
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Dados de merenda limpos com sucesso'];
        } catch (Exception $e) {
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao limpar dados de merenda: ' . $e->getMessage()];
        }
    }
    
    /**
     * Limpa todos os dados mas mantém usuários (exceto ADM)
     */
    public function limparTodosDadosMantemUsuarios() {
        try {
            $this->conn->beginTransaction();
            
            // Desabilitar verificação de foreign keys temporariamente
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Tabelas de itens/relacionamentos (dependem de outras)
            $tabelasItens = [
                'boletim_item',
                'pedido_item',
                'cardapio_item',
                'entrega_item',
                'consumo_item',
                'aluno_responsavel',
                'aluno_turma',
                'turma_professor',
                'professor_lotacao',
                'gestor_lotacao',
                'nutricionista_lotacao',
                'funcionario_lotacao',
                'calendar_event_participants',
                'calendar_event_recurrence',
                'calendar_notifications',
                'comunicado_resposta',
                'pacote_item',
                'observacao_desempenho',
                'plano_aula',
                'avaliacao',
                'historico_escolar',
                'indicador_nutricional',
                'justificativa',
                'parecer_tecnico',
                'substituicao_alimento',
                'validacao',
                'relatorio',
                'password_reset_tokens'
            ];
            
            // Tabelas principais acadêmicas
            $tabelasAcademicas = [
                'nota',
                'frequencia',
                'boletim',
                'turma',
                'serie',
                'disciplina',
                'aluno',
                'professor',
                'gestor',
                'funcionario'
            ];
            
            // Tabelas de merenda
            $tabelasMerenda = [
                'pedido_cesta',
                'cardapio',
                'entrega',
                'consumo_diario',
                'desperdicio',
                'custo_merenda',
                'movimentacao_estoque',
                'estoque_central',
                'fornecedor',
                'produto',
                'nutricionista',
                'pacote'
            ];
            
            // Tabelas de calendário e comunicação
            $tabelasCalendario = [
                'calendar_events',
                'calendar_categories',
                'calendar_settings',
                'comunicado'
            ];
            
            // Tabelas principais
            $tabelasPrincipais = [
                'escola'
            ];
            
            // Limpar todas as tabelas
            $todasTabelas = array_merge(
                $tabelasItens,
                $tabelasAcademicas,
                $tabelasMerenda,
                $tabelasCalendario,
                $tabelasPrincipais
            );
            
            foreach ($todasTabelas as $tabela) {
                try {
                    $this->conn->exec("DELETE FROM `$tabela`");
                } catch (Exception $e) {
                    // Ignorar erros de tabelas que não existem
                }
            }
            
            // Reabilitar verificação de foreign keys
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Todos os dados foram limpos (usuários mantidos)'];
        } catch (Exception $e) {
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Limpa tudo incluindo usuários (exceto ADM geral)
     */
    public function limparTudoIncluindoUsuarios() {
        try {
            $this->conn->beginTransaction();
            
            // Desabilitar verificação de foreign keys temporariamente
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Tabelas de itens/relacionamentos (dependem de outras)
            $tabelasItens = [
                'boletim_item',
                'pedido_item',
                'cardapio_item',
                'entrega_item',
                'consumo_item',
                'aluno_responsavel',
                'aluno_turma',
                'turma_professor',
                'professor_lotacao',
                'gestor_lotacao',
                'nutricionista_lotacao',
                'funcionario_lotacao',
                'calendar_event_participants',
                'calendar_event_recurrence',
                'calendar_notifications',
                'comunicado_resposta',
                'pacote_item',
                'observacao_desempenho',
                'plano_aula',
                'avaliacao',
                'historico_escolar',
                'indicador_nutricional',
                'justificativa',
                'parecer_tecnico',
                'substituicao_alimento',
                'validacao',
                'relatorio',
                'password_reset_tokens'
            ];
            
            // Tabelas principais acadêmicas
            $tabelasAcademicas = [
                'nota',
                'frequencia',
                'boletim',
                'turma',
                'serie',
                'disciplina',
                'aluno',
                'professor',
                'gestor',
                'funcionario'
            ];
            
            // Tabelas de merenda
            $tabelasMerenda = [
                'pedido_cesta',
                'cardapio',
                'entrega',
                'consumo_diario',
                'desperdicio',
                'custo_merenda',
                'movimentacao_estoque',
                'estoque_central',
                'fornecedor',
                'produto',
                'nutricionista',
                'pacote'
            ];
            
            // Tabelas de calendário e comunicação
            $tabelasCalendario = [
                'calendar_events',
                'calendar_categories',
                'calendar_settings',
                'comunicado'
            ];
            
            // Tabelas de sistema (exceto configuracao que pode ser mantida)
            $tabelasSistema = [
                'log_sistema',
                'role_permissao'
            ];
            
            // Tabelas principais
            $tabelasPrincipais = [
                'escola'
            ];
            
            // Limpar todas as tabelas
            $todasTabelas = array_merge(
                $tabelasItens,
                $tabelasAcademicas,
                $tabelasMerenda,
                $tabelasCalendario,
                $tabelasSistema,
                $tabelasPrincipais
            );
            
            foreach ($todasTabelas as $tabela) {
                try {
                    $this->conn->exec("DELETE FROM `$tabela`");
                } catch (Exception $e) {
                    // Ignorar erros de tabelas que não existem
                }
            }
            
            // Depois limpar usuários (exceto ADM)
            if ($this->admUserId) {
                // Buscar pessoa_id do ADM antes de deletar
                $stmt = $this->conn->prepare("SELECT pessoa_id FROM usuario WHERE id = :id");
                $stmt->bindParam(':id', $this->admUserId, PDO::PARAM_INT);
                $stmt->execute();
                $admPessoa = $stmt->fetch(PDO::FETCH_ASSOC);
                $admPessoaId = $admPessoa ? $admPessoa['pessoa_id'] : null;
                
                // Deletar todos os usuários exceto o ADM
                $this->conn->exec("DELETE FROM `usuario` WHERE id != {$this->admUserId}");
                
                // Deletar todas as pessoas exceto a do ADM
                if ($admPessoaId) {
                    $this->conn->exec("DELETE FROM `pessoa` WHERE id != {$admPessoaId}");
                } else {
                    // Se não encontrou pessoa do ADM, deletar todas
                    $this->conn->exec("DELETE FROM `pessoa`");
                }
            } else {
                // Se não encontrou ADM, não deletar nada (segurança)
                throw new Exception("Usuário ADM geral não encontrado. Operação cancelada por segurança.");
            }
            
            // Reabilitar verificação de foreign keys
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Banco de dados limpo completamente. Apenas o usuário ADM geral foi mantido.'];
        } catch (Exception $e) {
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Limpa dados específicos por categoria
     */
    public function limparDadosEspecificos($categorias) {
        try {
            $this->conn->beginTransaction();
            
            // Desabilitar verificação de foreign keys temporariamente
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $resultados = [];
            
            if (in_array('academicos', $categorias)) {
                $tabelasItens = ['boletim_item', 'nota', 'frequencia', 'observacao_desempenho', 'avaliacao', 'historico_escolar', 'justificativa', 'plano_aula', 'aluno_responsavel', 'aluno_turma', 'turma_professor', 'professor_lotacao', 'gestor_lotacao', 'funcionario_lotacao'];
                $tabelasPrincipais = ['boletim', 'turma', 'serie', 'disciplina', 'aluno', 'professor', 'gestor', 'funcionario'];
                $tabelas = array_merge($tabelasItens, $tabelasPrincipais);
                foreach ($tabelas as $tabela) {
                    try {
                        $this->conn->exec("DELETE FROM `$tabela`");
                    } catch (Exception $e) {}
                }
                $resultados['academicos'] = ['success' => true, 'message' => 'Dados acadêmicos removidos'];
            }
            
            if (in_array('merenda', $categorias)) {
                $tabelasItens = ['pedido_item', 'cardapio_item', 'entrega_item', 'consumo_item', 'pacote_item', 'substituicao_alimento', 'indicador_nutricional', 'parecer_tecnico', 'nutricionista_lotacao'];
                $tabelasPrincipais = ['pedido_cesta', 'cardapio', 'entrega', 'consumo_diario', 'desperdicio', 'custo_merenda', 'movimentacao_estoque', 'estoque_central', 'fornecedor', 'produto', 'nutricionista', 'pacote'];
                $tabelas = array_merge($tabelasItens, $tabelasPrincipais);
                foreach ($tabelas as $tabela) {
                    try {
                        $this->conn->exec("DELETE FROM `$tabela`");
                    } catch (Exception $e) {}
                }
                $resultados['merenda'] = ['success' => true, 'message' => 'Dados de merenda removidos'];
            }
            
            if (in_array('escolas', $categorias)) {
                try {
                    $this->conn->exec("DELETE FROM `escola`");
                    $resultados['escolas'] = ['success' => true, 'message' => 'Escolas removidas'];
                } catch (Exception $e) {
                    $resultados['escolas'] = ['success' => false, 'message' => 'Erro ao remover escolas: ' . $e->getMessage()];
                }
            }
            
            if (in_array('usuarios', $categorias)) {
                if ($this->admUserId) {
                    try {
                        // Buscar pessoa_id do ADM
                        $stmt = $this->conn->prepare("SELECT pessoa_id FROM usuario WHERE id = :id");
                        $stmt->bindParam(':id', $this->admUserId, PDO::PARAM_INT);
                        $stmt->execute();
                        $admPessoa = $stmt->fetch(PDO::FETCH_ASSOC);
                        $admPessoaId = $admPessoa ? $admPessoa['pessoa_id'] : null;
                        
                        $this->conn->exec("DELETE FROM `usuario` WHERE id != {$this->admUserId}");
                        
                        if ($admPessoaId) {
                            $this->conn->exec("DELETE FROM `pessoa` WHERE id != {$admPessoaId}");
                        } else {
                            $this->conn->exec("DELETE FROM `pessoa`");
                        }
                        
                        $resultados['usuarios'] = ['success' => true, 'message' => 'Usuários removidos (ADM mantido)'];
                    } catch (Exception $e) {
                        $resultados['usuarios'] = ['success' => false, 'message' => 'Erro ao remover usuários: ' . $e->getMessage()];
                    }
                } else {
                    $resultados['usuarios'] = ['success' => false, 'message' => 'ADM não encontrado, operação cancelada'];
                }
            }
            
            if (in_array('comunicados', $categorias)) {
                try {
                    // Primeiro deletar respostas, depois comunicados
                    $this->conn->exec("DELETE FROM `comunicado_resposta`");
                    $this->conn->exec("DELETE FROM `comunicado`");
                    $resultados['comunicados'] = ['success' => true, 'message' => 'Comunicados removidos'];
                } catch (Exception $e) {
                    $resultados['comunicados'] = ['success' => false, 'message' => 'Erro ao remover comunicados: ' . $e->getMessage()];
                }
            }
            
            if (in_array('eventos', $categorias)) {
                try {
                    // Primeiro deletar relacionamentos, depois eventos
                    $this->conn->exec("DELETE FROM `calendar_event_participants`");
                    $this->conn->exec("DELETE FROM `calendar_event_recurrence`");
                    $this->conn->exec("DELETE FROM `calendar_notifications`");
                    $this->conn->exec("DELETE FROM `calendar_events`");
                    $resultados['eventos'] = ['success' => true, 'message' => 'Eventos removidos'];
                } catch (Exception $e) {
                    $resultados['eventos'] = ['success' => false, 'message' => 'Erro ao remover eventos: ' . $e->getMessage()];
                }
            }
            
            // Reabilitar verificação de foreign keys
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Dados específicos limpos com sucesso', 'detalhes' => $resultados];
        } catch (Exception $e) {
            $this->conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtém estatísticas do banco antes da limpeza
     */
    public function getEstatisticas() {
        $estatisticas = [];
        
        try {
            $tabelas = [
                'aluno' => 'Alunos',
                'turma' => 'Turmas',
                'nota' => 'Notas',
                'frequencia' => 'Frequências',
                'cardapio' => 'Cardápios',
                'pedido_cesta' => 'Pedidos',
                'usuario' => 'Usuários',
                'escola' => 'Escolas',
                'produto' => 'Produtos',
                'fornecedor' => 'Fornecedores'
            ];
            
            foreach ($tabelas as $tabela => $nome) {
                try {
                    $stmt = $this->conn->query("SELECT COUNT(*) as total FROM $tabela");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $estatisticas[$tabela] = [
                        'nome' => $nome,
                        'total' => (int)($result['total'] ?? 0)
                    ];
                } catch (Exception $e) {
                    $estatisticas[$tabela] = [
                        'nome' => $nome,
                        'total' => 0,
                        'erro' => 'Tabela não encontrada'
                    ];
                }
            }
            
            return $estatisticas;
        } catch (Exception $e) {
            return [];
        }
    }
}

?>

