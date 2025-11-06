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
        
        // Tentar buscar permissões do banco de dados
        try {
            require_once("../../config/Database.php");
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Criar tabela se não existir
            $sql = "CREATE TABLE IF NOT EXISTS `role_permissao` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA') NOT NULL,
                `permissao` varchar(100) NOT NULL,
                `ativo` tinyint(1) DEFAULT 1,
                `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
                `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `role_permissao_unique` (`role`, `permissao`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $conn->exec($sql);
            
            // Buscar permissões do banco
            $sql = "SELECT permissao FROM role_permissao WHERE role = :role AND ativo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':role', $tipo);
            $stmt->execute();
            $permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Definir permissões na sessão
            foreach ($permissoes as $permissao) {
                $_SESSION[$permissao] = true;
            }
            
            // Se não houver permissões no banco, usar permissões padrão
            if (empty($permissoes)) {
                $this->definirPermissoesPadrao($tipo);
            }
        } catch (PDOException $e) {
            // Se houver erro, usar permissões padrão
            error_log("Erro ao buscar permissões do banco: " . $e->getMessage());
            $this->definirPermissoesPadrao($tipo);
        }
    }
    
    private function definirPermissoesPadrao($tipo) {
        // Permissões padrão caso não existam no banco
        switch(strtolower($tipo)) {
            case 'adm':
                $_SESSION['cadastrar_pessoas'] = true;
                $_SESSION['gerenciar_escolas'] = true;
                $_SESSION['gerenciar_professores'] = true;
                $_SESSION['relatorio_geral'] = true;
                $_SESSION['gerenciar_estoque_produtos'] = true;
                $_SESSION['pedidos_nutricionista'] = true;
                $_SESSION['definir_permissoes'] = true;
                break;
                
            case 'gestao':
                $_SESSION['criar_turma'] = true;
                $_SESSION['matricular_alunos'] = true;
                $_SESSION['gerenciar_professores'] = true;
                $_SESSION['acessar_registros'] = true;
                $_SESSION['gerar_relatorios_pedagogicos'] = true;
                break;
                
            case 'professor':
                $_SESSION['resgistrar_plano_aula'] = true;
                $_SESSION['cadastrar_avaliacao'] = true;
                $_SESSION['lancar_frequencia'] = true;
                $_SESSION['lancar_nota'] = true;
                $_SESSION['justificar_faltas'] = true;
                break;
            
            case 'nutricionista':
                $_SESSION['adc_cardapio'] = true;
                $_SESSION['lista_insulmos'] = true;
                $_SESSION['env_pedidos'] = true;
                break;

            case 'adm_merenda':
                $_SESSION['gerenciar_estoque_produtos'] = true;
                $_SESSION['criar_pacotes/cestas'] = true;
                $_SESSION['pedidos_nutricionista'] = true;
                $_SESSION['movimentacoes_estoque'] = true;
                break;
                
            case 'aluno':
                $_SESSION['notas'] = true;
                $_SESSION['frequencia'] = true;
                $_SESSION['comunicados'] = true;
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
