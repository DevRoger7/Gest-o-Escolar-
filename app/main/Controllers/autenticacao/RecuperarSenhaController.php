<?php
require_once('../../Models/autenticacao/RecuperarSenhaModel.php');

class RecuperarSenhaController {
    private $model;
    
    public function __construct() {
        $this->model = new RecuperarSenhaModel();
    }
    
    /**
     * Verifica CPF e email para permitir mudança direta de senha
     */
    public function verificarCredenciais($cpf, $email) {
        // Limpar CPF (remover pontos e traços)
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Validar CPF
        if (strlen($cpf) !== 11) {
            return ['status' => false, 'mensagem' => 'CPF inválido.'];
        }
        
        // Validar email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'mensagem' => 'E-mail inválido.'];
        }
        
        // Verificar se o usuário existe e se o email corresponde
        $usuario = $this->model->buscarUsuarioPorCpfEmail($cpf, $email);
        
        if (!$usuario) {
            return ['status' => false, 'mensagem' => 'CPF e/ou e-mail não encontrados no sistema.'];
        }
        
        // Retornar sucesso com dados do usuário (sem revelar informações sensíveis)
        return [
            'status' => true, 
            'mensagem' => 'Credenciais verificadas. Defina sua nova senha abaixo.',
            'usuario_id' => $usuario['usuario_id']
        ];
    }
    
    /**
     * Redefine senha diretamente (sem token)
     */
    public function redefinirSenhaDireta($usuarioId, $novaSenha, $confirmarSenha) {
        // Validar senhas
        if (empty($novaSenha) || strlen($novaSenha) < 6) {
            return ['status' => false, 'mensagem' => 'A senha deve ter no mínimo 6 caracteres.'];
        }
        
        if ($novaSenha !== $confirmarSenha) {
            return ['status' => false, 'mensagem' => 'As senhas não coincidem.'];
        }
        
        // Redefinir senha diretamente
        $resultado = $this->model->redefinirSenhaDireta($usuarioId, $novaSenha);
        
        if ($resultado) {
            return ['status' => true, 'mensagem' => 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.'];
        }
        
        return ['status' => false, 'mensagem' => 'Erro ao redefinir senha. Tente novamente.'];
    }
    
    /**
     * Verifica se o token é válido
     */
    public function verificarToken($token) {
        return $this->model->verificarToken($token);
    }
    
    /**
     * Redefine a senha usando o token
     */
    public function redefinirSenha($token, $novaSenha, $confirmarSenha) {
        // Validar senhas
        if (empty($novaSenha) || strlen($novaSenha) < 6) {
            return ['status' => false, 'mensagem' => 'A senha deve ter no mínimo 6 caracteres.'];
        }
        
        if ($novaSenha !== $confirmarSenha) {
            return ['status' => false, 'mensagem' => 'As senhas não coincidem.'];
        }
        
        // Verificar token
        $tokenValido = $this->model->verificarToken($token);
        if (!$tokenValido) {
            return ['status' => false, 'mensagem' => 'Token inválido ou expirado.'];
        }
        
        // Redefinir senha
        $resultado = $this->model->redefinirSenha($token, $novaSenha);
        
        if ($resultado) {
            return ['status' => true, 'mensagem' => 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.'];
        }
        
        return ['status' => false, 'mensagem' => 'Erro ao redefinir senha. Tente novamente.'];
    }
}

?>

