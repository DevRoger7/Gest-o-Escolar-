<?php



Class ModelLogin {
    public function login($cpf, $senha) {
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Remove pontos e hífens do CPF, mantendo apenas números
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        require_once("../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Primeiro, buscar o usuário apenas pelo CPF para obter o hash da senha
        $sql = "SELECT u.*, p.* FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id 
                WHERE p.cpf = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cpf]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar se o usuário existe e se a senha está correta usando password_verify
        if ($resultado && password_verify($senha, $resultado['senha_hash'])) {
            // Senha correta, continuar com o login
            
            // Definir o fuso horário para América/Sao_Paulo (GMT-3)
            date_default_timezone_set('America/Sao_Paulo');
            
            // Atualizar o campo ultimo_login com a data e hora atual no fuso horário correto
            $dataHoraAtual = date('Y-m-d H:i:s');
            $sqlAtualizarLogin = "UPDATE usuario SET ultimo_login = :ultimo_login WHERE id = :id";
            $stmtAtualizarLogin = $conn->prepare($sqlAtualizarLogin);
            $stmtAtualizarLogin->bindParam(':ultimo_login', $dataHoraAtual);
            $stmtAtualizarLogin->bindParam(':id', $resultado['id']);
            $stmtAtualizarLogin->execute();
            
            // Criar as sessões com os dados do usuário
            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $resultado['id'];
            $_SESSION['pessoa_id'] = $resultado['pessoa_id'];
            $_SESSION['nome'] = $resultado['nome'];
            $_SESSION['email'] = $resultado['email'];
            $_SESSION['cpf'] = $resultado['cpf'];
            $_SESSION['telefone'] = $resultado['telefone'] ?? '';
            $_SESSION['tipo'] = $resultado['role'] ?? 'Professor';
            $_SESSION['escola_atual'] = 'Escola Municipal';
            
            // Definir permissões baseadas no tipo de usuário
            $this->definirPermissoes($resultado);
            
            return $resultado;
        } else {
            // Usuário não encontrado ou senha incorreta
            return false;
        }
    }
    
    private function definirPermissoes($resultado) {
        // Limpar permissões anteriores
        $this->limparPermissoes();
        
        // Definir permissões baseadas no setor/tipo do usuário
        $tipo = $resultado['role'] ?? '';
        
        switch(strtolower($tipo)) {
            case 'adm':
                // === ADMINISTRADOR GERAL ===
                // Usuário com máximo nível de acesso - controla todo o sistema
                $_SESSION['cadastrar_pessoas'] = true;              // Criar/editar usuários, alunos, professores
                $_SESSION['gerenciar_escolas'] = true;              // Administrar dados das escolas
                $_SESSION['gerenciar_professores'] = true;          // Controla a lotação de professores nas escolas
                $_SESSION['relatorio_geral'] = true;                // Acesso total a todos os relatórios do sistema
                $_SESSION['gerenciar_estoque_produtos'] = true;     // Controle total do estoque de produtos
                $_SESSION['pedidos_nutricionista'] = true;          // Receber, aprovar e rejeitar o pedido dos nutricionistas
                break;
                
            case 'gestao':
                // === DIRETOR/COORDENADOR ===
                // Gestão pedagógica e administrativa da escola
                $_SESSION['criar_turma'] = true;                    // Criar as turmas de acordo com o ano letivo
                $_SESSION['matricular_alunos'] = true;              // Realizar matrículas de estudantes com possibilidade de transição
                $_SESSION['gerenciar_professores'] = true;          // Controla a lotação de professores nas escolas
                $_SESSION['acessar_registros'] = true;              // Acessa todos os registros lançados pelos professores
                $_SESSION['gerar_relatorios_pedagogicos'] = true;   // Relatórios de desempenho e frequência dos alunos
                break;
                
            case 'professor':
                // === PROFESSOR ===
                // Atividades pedagógicas e acompanhamento de alunos
                $_SESSION['resgistrar_plano_aula'] = true;          // Criar e registrar planos de aula para as turmas
                $_SESSION['cadastrar_avaliacao'] = true;            // Criar provas e atividades avaliativas
                $_SESSION['lancar_frequencia'] = true;              // Registrar presença/ausência dos alunos diariamente
                $_SESSION['lancar_nota'] = true;                    // Inserir notas e calcular médias
                $_SESSION['justificar_faltas'] = true;              // Validar justificativas de ausências
                break;
            
            
            case 'nutricionista':
                // === NUTRICIONISTA ===
                // Planejamento nutricional e cardápios escolares
                $_SESSION['adc_cardapio'] = true;                   // Criar e modificar cardápios de cada escola
                $_SESSION['lista_insulmos'] = true;                 // Gerar lista de insulmos para suprir o mês
                $_SESSION['env_pedidos'] = true;                    // Solicitar produtos e ingredientes ao adm
                break;

            case 'adm_merenda':
                // === ADMINISTRADOR DE MERENDA ===
                // Gestão do estoque e distribuição da alimentação escolar
                $_SESSION['gerenciar_estoque_produtos'] = true;     // Controlar entrada/saída de produtos
                $_SESSION['criar_pacotes/cestas'] = true;           // Montar kits de alimentação para as escolas
                $_SESSION['pedidos_nutricionista'] = true;          // Receber solicitações do nutricionista, aprovando ou recusando com obsevação
                $_SESSION['movimentacoes_estoque'] = true;          // Registrar movimentações de estoque
                break;
                
            case 'aluno':
                // === ALUNO ===
                // Acesso limitado apenas para consulta de informações pessoais
                $_SESSION['notas'] = true;                          // Visualizar próprias notas e conceitos
                $_SESSION['frequencia'] = true;                     // Consultar própria frequência
                $_SESSION['comunicados'] = true;                    // Receber avisos e comunicados da escola
                break;
        }
    }
    
    private function limparPermissoes() {
        // === LIMPEZA DE PERMISSÕES ===
        // Remove permissões antigas antes de definir as novas
        // Evita conflitos entre diferentes tipos de usuário
        unset($_SESSION['Gerenciador de Usuarios']);    // Remove acesso ao gerenciamento de usuários
        unset($_SESSION['Estoque']);                     // Remove acesso ao controle de estoque
        unset($_SESSION['Biblioteca']);                  // Remove acesso à biblioteca
        unset($_SESSION['Entrada/saída']);               // Remove acesso ao controle de entrada/saída
    }
}

?>
