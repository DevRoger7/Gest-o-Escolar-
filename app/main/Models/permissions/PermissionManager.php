<?php
/**
 * PermissionManager - Sistema Centralizado de Permissões SIGAE
 * 
 * Gerencia todas as permissões do sistema conforme a descrição do SIGAE
 */

class PermissionManager {
    
    /**
     * Define todas as permissões baseadas no tipo de usuário
     */
    public static function definirPermissoes($tipoUsuario) {
        // Limpar permissões anteriores
        self::limparPermissoes();
        
        $tipo = strtolower(trim($tipoUsuario));
        
        switch($tipo) {
            case 'adm':
                self::permissoesAdministradorGeral();
                break;
                
            case 'gestao':
                self::permissoesGestao();
                break;
                
            case 'professor':
                self::permissoesProfessor();
                break;
                
            case 'nutricionista':
                self::permissoesNutricionista();
                break;
                
            case 'adm_merenda':
                self::permissoesAdmMerenda();
                break;
                
            case 'aluno':
                self::permissoesAluno();
                break;
                
            case 'responsavel':
                self::permissoesResponsavel();
                break;
                
            default:
                // Permissões mínimas para usuários não identificados
                $_SESSION['visualizar_comunicados'] = true;
                break;
        }
    }
    
    /**
     * ADMINISTRADOR GERAL - Acesso total ao sistema
     */
    private static function permissoesAdministradorGeral() {
        // Gestão de Pessoas
        $_SESSION['cadastrar_pessoas'] = true;              // Criar/editar usuários, alunos, professores, funcionários, gestores
        $_SESSION['editar_pessoas'] = true;
        $_SESSION['excluir_pessoas'] = true;
        $_SESSION['visualizar_pessoas'] = true;
        
        // Gestão de Escolas
        $_SESSION['gerenciar_escolas'] = true;              // Administrar dados das escolas
        $_SESSION['criar_escolas'] = true;
        $_SESSION['editar_escolas'] = true;
        $_SESSION['excluir_escolas'] = true;
        
        // Gestão Acadêmica
        $_SESSION['criar_turma'] = true;                    // Criar turmas, séries e disciplinas
        $_SESSION['editar_turma'] = true;
        $_SESSION['excluir_turma'] = true;
        $_SESSION['criar_serie'] = true;
        $_SESSION['criar_disciplina'] = true;
        $_SESSION['matricular_alunos'] = true;              // Matricular alunos
        $_SESSION['transpor_alunos'] = true;               // Transpor alunos entre turmas
        
        // Gestão de Professores
        $_SESSION['gerenciar_professores'] = true;          // Controla a lotação de professores nas escolas
        $_SESSION['atribuir_professores'] = true;
        $_SESSION['alterar_professores'] = true;
        
        // Relatórios
        $_SESSION['relatorio_geral'] = true;                // Acesso total a todos os relatórios
        $_SESSION['relatorio_financeiro'] = true;
        $_SESSION['relatorio_pedagogico'] = true;
        $_SESSION['relatorio_merenda'] = true;
        
        // Módulo de Alimentação
        $_SESSION['gerenciar_estoque_produtos'] = true;     // Controle total do estoque
        $_SESSION['pedidos_nutricionista'] = true;          // Receber, aprovar e rejeitar pedidos dos nutricionistas
        
        // Validação e Configurações
        $_SESSION['validar_informacoes'] = true;            // Validar informações lançadas por outros usuários
        $_SESSION['gerenciar_configuracoes'] = true;        // Gerenciar configurações do sistema
        $_SESSION['gerenciar_seguranca'] = true;            // Gerenciar segurança e permissões
        
        // Acesso Total
        $_SESSION['acesso_total'] = true;                   // Flag para verificação rápida
    }
    
    /**
     * GESTÃO (Direção/Coordenação) - Gestão pedagógica e administrativa
     */
    private static function permissoesGestao() {
        // Gestão de Turmas
        $_SESSION['criar_turma'] = true;                    // Criar e organizar turmas
        $_SESSION['editar_turma'] = true;
        $_SESSION['organizar_turmas'] = true;
        
        // Gestão de Alunos
        $_SESSION['matricular_alunos'] = true;              // Realizar matrículas
        $_SESSION['alocar_alunos'] = true;                 // Alocar alunos em turmas
        $_SESSION['transpor_alunos'] = true;                // Transpor estudantes entre turmas
        
        // Gestão de Professores
        $_SESSION['atribuir_professores'] = true;           // Atribuir professores às turmas
        $_SESSION['alterar_professores'] = true;            // Alterar docentes quando necessário
        
        // Acompanhamento Acadêmico
        $_SESSION['acompanhar_frequencia'] = true;         // Acompanhar frequência
        $_SESSION['acompanhar_desempenho'] = true;         // Acompanhar desempenho
        $_SESSION['acompanhar_notas'] = true;               // Acompanhar notas
        $_SESSION['acessar_registros'] = true;              // Acessa todos os registros lançados pelos professores
        
        // Relatórios
        $_SESSION['gerar_relatorios_pedagogicos'] = true;   // Relatórios de desempenho e frequência
        $_SESSION['visualizar_relatorios_merenda'] = true; // Acesso aos relatórios do módulo de alimentação (somente visualização)
        
        // Validação
        $_SESSION['validar_lancamentos'] = true;            // Validar lançamentos feitos pelos professores
        
        // Comunicação
        $_SESSION['supervisionar_comunicacao'] = true;      // Supervisionar comunicação entre escola e responsáveis
        
        // NÃO PODE
        // - Alterar permissões gerais
        // - Configurações do sistema
        // - Dados acadêmicos fora de sua escola
    }
    
    /**
     * PROFESSOR - Acesso restrito às suas turmas e disciplinas
     */
    private static function permissoesProfessor() {
        // Planos de Aula
        $_SESSION['registrar_plano_aula'] = true;          // Criar e registrar planos de aula para as turmas
        $_SESSION['editar_plano_aula'] = true;
        
        // Avaliações
        $_SESSION['cadastrar_avaliacao'] = true;            // Criar provas e atividades avaliativas
        $_SESSION['editar_avaliacao'] = true;
        
        // Frequência
        $_SESSION['lancar_frequencia'] = true;              // Registrar presença/ausência dos alunos diariamente
        $_SESSION['justificar_faltas'] = true;             // Validar justificativas de ausências
        
        // Notas
        $_SESSION['lancar_nota'] = true;                    // Inserir notas e calcular médias
        $_SESSION['editar_nota'] = true;
        
        // Observações
        $_SESSION['adicionar_observacoes'] = true;          // Adicionar observações sobre desempenho dos alunos
        
        // Relatórios
        $_SESSION['gerar_relatorios_turmas'] = true;        // Gerar relatórios específicos das suas turmas
        
        // Comunicação
        $_SESSION['enviar_comunicados'] = true;              // Enviar comunicados à coordenação
        
        // Visualização
        $_SESSION['visualizar_cardapios'] = true;           // Visualizar cardápios
        $_SESSION['visualizar_avisos'] = true;              // Visualizar avisos gerais da escola
        
        // NÃO PODE
        // - Criar turmas
        // - Mover alunos
        // - Editar dados de outros docentes
        // - Acessar relatórios gerais da escola
    }
    
    /**
     * NUTRICIONISTA - Planejamento nutricional e cardápios
     */
    private static function permissoesNutricionista() {
        // Cardápios
        $_SESSION['adc_cardapio'] = true;                   // Criar e modificar cardápios de cada escola
        $_SESSION['editar_cardapio'] = true;
        $_SESSION['visualizar_cardapios'] = true;
        
        // Insumos
        $_SESSION['lista_insumos'] = true;                 // Gerar lista de insumos para suprir o mês
        $_SESSION['visualizar_insumos'] = true;
        
        // Pedidos
        $_SESSION['env_pedidos'] = true;                    // Solicitar produtos e ingredientes ao adm
        
        // NÃO PODE
        // - Alterar dados acadêmicos
        // - Dados administrativos fora de seu módulo
    }
    
    /**
     * ADMINISTRADOR DE MERENDA - Gestão operacional da merenda
     */
    private static function permissoesAdmMerenda() {
        // Cardápios
        $_SESSION['visualizar_cardapios'] = true;
        $_SESSION['revisar_cardapios'] = true;               // Revisar cardápios criados pelo nutricionista
        
        // Estoque
        $_SESSION['gerenciar_estoque_produtos'] = true;     // Controlar entrada/saída de produtos
        $_SESSION['cadastrar_produtos'] = true;
        $_SESSION['editar_produtos'] = true;
        $_SESSION['movimentacoes_estoque'] = true;          // Registrar movimentações de estoque
        
        // Consumo
        $_SESSION['registrar_consumo'] = true;              // Registrar consumo diário
        $_SESSION['monitorar_desperdicio'] = true;          // Monitorar desperdício
        
        // Custos
        $_SESSION['monitorar_custos'] = true;               // Monitorar custos
        
        // Fornecedores
        $_SESSION['gerenciar_fornecedores'] = true;         // Monitorar fornecedores
        
        // Pedidos
        $_SESSION['pedidos_nutricionista'] = true;          // Receber solicitações do nutricionista, aprovando ou recusando
        $_SESSION['aprovar_pedidos'] = true;
        $_SESSION['rejeitar_pedidos'] = true;
        
        // Distribuição
        $_SESSION['criar_pacotes_cestas'] = true;           // Montar kits de alimentação para as escolas
        $_SESSION['acompanhar_entregas'] = true;            // Acompanhar entregas
        
        // NÃO PODE
        // - Alterar dados acadêmicos
        // - Dados administrativos fora de seu módulo
    }
    
    /**
     * ALUNO - Acesso simplificado para consulta
     */
    private static function permissoesAluno() {
        // Visualização
        $_SESSION['notas'] = true;                          // Visualizar notas e boletins
        $_SESSION['frequencia'] = true;                     // Consultar frequência
        $_SESSION['historico_escolar'] = true;              // Visualizar histórico escolar
        $_SESSION['comunicados'] = true;                     // Receber avisos e comunicados
        $_SESSION['visualizar_cardapios'] = true;           // Visualizar cardápios da merenda
        
        // Atualização Pessoal
        $_SESSION['atualizar_dados_pessoais'] = true;        // Atualizar endereço ou telefone
        
        // NÃO PODE
        // - Editar notas
        // - Realizar cadastros
        // - Alterar turmas
        // - Modificar dados institucionais
    }
    
    /**
     * RESPONSÁVEL - Acesso às informações dos filhos
     */
    private static function permissoesResponsavel() {
        // Informações Acadêmicas dos Filhos
        $_SESSION['acompanhar_desempenho'] = true;           // Acompanhar desempenho dos filhos
        $_SESSION['acompanhar_frequencia'] = true;          // Acompanhar frequência
        $_SESSION['visualizar_comunicados'] = true;         // Acompanhar comunicados da escola
        
        // Informações de Alimentação
        $_SESSION['consultar_cardapios'] = true;             // Consultar cardápios
        
        // Comunicação
        $_SESSION['contatar_coordenacao'] = true;           // Manter contato com coordenação
        $_SESSION['contatar_professores'] = true;           // Manter contato com professores quando necessário
        
        // NÃO PODE
        // - Alterar dados
        // - Lançar informações
        // - Acessar áreas administrativas
    }
    
    /**
     * Limpa todas as permissões anteriores
     */
    private static function limparPermissoes() {
        // Lista de todas as permissões possíveis
        $permissoes = [
            // Gestão de Pessoas
            'cadastrar_pessoas', 'editar_pessoas', 'excluir_pessoas', 'visualizar_pessoas',
            // Gestão de Escolas
            'gerenciar_escolas', 'criar_escolas', 'editar_escolas', 'excluir_escolas',
            // Gestão Acadêmica
            'criar_turma', 'editar_turma', 'excluir_turma', 'criar_serie', 'criar_disciplina',
            'matricular_alunos', 'transpor_alunos', 'alocar_alunos', 'organizar_turmas',
            // Gestão de Professores
            'gerenciar_professores', 'atribuir_professores', 'alterar_professores',
            // Relatórios
            'relatorio_geral', 'relatorio_financeiro', 'relatorio_pedagogico', 'relatorio_merenda',
            'gerar_relatorios_pedagogicos', 'gerar_relatorios_turmas', 'visualizar_relatorios_merenda',
            // Módulo de Alimentação
            'gerenciar_estoque_produtos', 'cadastrar_produtos', 'editar_produtos', 'movimentacoes_estoque',
            'pedidos_nutricionista', 'aprovar_pedidos', 'rejeitar_pedidos', 'env_pedidos',
            'adc_cardapio', 'editar_cardapio', 'visualizar_cardapios', 'revisar_cardapios',
            'lista_insumos', 'visualizar_insumos', 'criar_pacotes_cestas', 'acompanhar_entregas',
            'registrar_consumo', 'monitorar_desperdicio', 'monitorar_custos', 'gerenciar_fornecedores',
            // Validação e Configurações
            'validar_informacoes', 'validar_lancamentos', 'gerenciar_configuracoes', 'gerenciar_seguranca',
            // Professor
            'registrar_plano_aula', 'editar_plano_aula', 'cadastrar_avaliacao', 'editar_avaliacao',
            'lancar_frequencia', 'justificar_faltas', 'lancar_nota', 'editar_nota',
            'adicionar_observacoes', 'enviar_comunicados',
            // Aluno
            'notas', 'frequencia', 'historico_escolar', 'comunicados', 'atualizar_dados_pessoais',
            // Responsável
            'acompanhar_desempenho', 'acompanhar_frequencia', 'visualizar_comunicados', 'consultar_cardapios',
            'contatar_coordenacao', 'contatar_professores',
            // Acompanhamento
            'acompanhar_frequencia', 'acompanhar_desempenho', 'acompanhar_notas', 'acessar_registros',
            // Comunicação
            'supervisionar_comunicacao', 'visualizar_avisos',
            // Permissões antigas (limpar)
            'Gerenciador de Usuarios', 'Estoque', 'Biblioteca', 'Entrada/saída',
            // Flag de acesso
            'acesso_total'
        ];
        
        foreach ($permissoes as $permissao) {
            unset($_SESSION[$permissao]);
        }
    }
    
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public static function temPermissao($permissao) {
        // Administrador geral tem acesso a tudo
        if (isset($_SESSION['acesso_total']) && $_SESSION['acesso_total'] === true) {
            return true;
        }
        
        // Verificar permissão específica
        return isset($_SESSION[$permissao]) && $_SESSION[$permissao] === true;
    }
    
    /**
     * Verifica se o usuário tem pelo menos uma das permissões
     */
    public static function temAlgumaPermissao($permissoes) {
        if (!is_array($permissoes)) {
            $permissoes = [$permissoes];
        }
        
        foreach ($permissoes as $permissao) {
            if (self::temPermissao($permissao)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário tem todas as permissões
     */
    public static function temTodasPermissoes($permissoes) {
        if (!is_array($permissoes)) {
            $permissoes = [$permissoes];
        }
        
        foreach ($permissoes as $permissao) {
            if (!self::temPermissao($permissao)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Retorna o tipo de usuário atual
     */
    public static function getTipoUsuario() {
        return isset($_SESSION['tipo']) ? strtolower(trim($_SESSION['tipo'])) : '';
    }
    
    /**
     * Verifica se o usuário é de um tipo específico
     */
    public static function eTipo($tipo) {
        return self::getTipoUsuario() === strtolower(trim($tipo));
    }
}

?>

