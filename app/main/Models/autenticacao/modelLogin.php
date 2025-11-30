<?php



Class ModelLogin {
    public function login($cpf, $senha) {
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() == PHP_SESSION_NONE) {
            // Configura o tempo de vida da sessão para 24 horas
            $lifetime = 24 * 60 * 60; // 24 horas em segundos
            session_set_cookie_params($lifetime);
            ini_set('session.gc_maxlifetime', $lifetime);
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
            
            // Definir permissões baseadas no tipo de usuário usando PermissionManager
            require_once("../../Models/permissions/PermissionManager.php");
            PermissionManager::definirPermissoes($resultado['role'] ?? '');
            
            return $resultado;
        } else {
            // Usuário não encontrado ou senha incorreta
            return false;
        }
    }
    
    // Método removido - agora usa PermissionManager
    // As permissões são definidas em Models/permissions/PermissionManager.php
}

?>
