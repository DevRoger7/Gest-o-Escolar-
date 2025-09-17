<?php

require_once __DIR__ . '/../Models/UserModel.php';

class AuthController
{
    public function login()
    {
        // Verificar se já está logado
        if (isset($_SESSION['user_id'])) {
            header('Location: /hub');
            exit;
        }
        
        // Renderizar a view de login
        $title = 'Login - Sistema de Gestão Escolar Maranguape';
        
        // Incluir a view
        include_once __DIR__ . '/../Views/auth/login.php';
    }
    
    public function authenticate()
    {
        // Debug: verificar se está recebendo POST
        error_log('Método: ' . $_SERVER['REQUEST_METHOD']);
        error_log('POST data: ' . print_r($_POST, true));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cpf = $_POST['cpf'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            error_log('CPF recebido: ' . $cpf);
            error_log('Senha recebida: ' . $senha);
            
            // Autenticar usuário
            $user = UserModel::authenticate($cpf, $senha);
            
            error_log('Resultado autenticação: ' . ($user ? 'SUCESSO' : 'FALHA'));
            
            if ($user) {
                // Salvar dados na sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo'];
                $_SESSION['user_schools'] = $user['escolas'];
                
                error_log('Dados salvos na sessão, redirecionando para /hub');
                
                // Redirecionar para o hub de escolas
                header('Location: /hub');
                exit;
            } else {
                // Credenciais inválidas
                $_SESSION['login_error'] = 'CPF ou senha incorretos';
                error_log('Credenciais inválidas, redirecionando para /login');
                header('Location: /login');
                exit;
            }
        }
        
        error_log('Não é POST, redirecionando para /login');
        header('Location: /login');
        exit;
    }
    
    public function logout()
    {
        // Limpar sessão
        session_destroy();
        header('Location: /login');
        exit;
    }
}
