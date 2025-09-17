<?php

Class ModelLogin {
    public function login($cpf, $senha) {
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        require_once("../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT u.*, p.* FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id 
                WHERE p.cpf = ? AND u.senha_hash = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cpf, $senha]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($resultado) {
            // Criar as sessões com os dados do usuário
            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $resultado['id'];
            $_SESSION['pessoa_id'] = $resultado['pessoa_id'];
            $_SESSION['nome'] = $resultado['nome'];
            $_SESSION['email'] = $resultado['email'];
            $_SESSION['cpf'] = $resultado['cpf'];
            $_SESSION['telefone'] = $resultado['telefone'] ?? '';
            $_SESSION['setor'] = $resultado['setor'] ?? 'Professor';
            $_SESSION['escola_atual'] = 'Escola Municipal';
            
            // Definir permissões baseadas no tipo de usuário
            $this->definirPermissoes($resultado);
            
            return $resultado;
        } else {
            return false;
        }
    }
    
    private function definirPermissoes($usuario) {
        // Limpar permissões anteriores
        $this->limparPermissoes();
        
        // Definir permissões baseadas no setor/tipo do usuário
        $setor = $usuario['setor'] ?? '';
        
        switch(strtolower($setor)) {
            case 'administrador':
            case 'admin':
                // Administrador tem acesso a tudo
                $_SESSION['Gerenciador de Usuarios'] = true;
                $_SESSION['Estoque'] = true;
                $_SESSION['Biblioteca'] = true;
                $_SESSION['Entrada/saída'] = true;
                break;
                
            case 'diretor':
            case 'diretora':
                // Diretor tem acesso a quase tudo, exceto gerenciador de usuários
                $_SESSION['Estoque'] = true;
                $_SESSION['Biblioteca'] = true;
                $_SESSION['Entrada/saída'] = true;
                break;
                
            case 'secretario':
            case 'secretaria':
                // Secretário tem acesso a biblioteca e entrada/saída
                $_SESSION['Biblioteca'] = true;
                $_SESSION['Entrada/saída'] = true;
                break;
                
            case 'professor':
            case 'professora':
            default:
                // Professor tem acesso básico apenas à biblioteca
                $_SESSION['Biblioteca'] = true;
                break;
        }
    }
    
    private function limparPermissoes() {
        // Remove todas as permissões específicas
        unset($_SESSION['Gerenciador de Usuarios']);
        unset($_SESSION['Estoque']);
        unset($_SESSION['Biblioteca']);
        unset($_SESSION['Entrada/saída']);
    }
}

?>
