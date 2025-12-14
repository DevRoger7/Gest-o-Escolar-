/**
 * Função para verificar se a escola está ativa e redirecionar se necessário
 * Deve ser chamada quando detectar "escola não encontrada" ou erros relacionados
 */
function verificarESeRedirecionarEscolaInativa() {
    // Verificar se há erro de escola não encontrada
    fetch('../../Controllers/gestao/EscolaController.php?acao=verificar_escola_usuario')
        .then(response => response.json())
        .then(data => {
            if (data && data.escolaInativa === true) {
                // Escola está inativa, destruir sessão e redirecionar
                window.location.href = '../../Views/auth/sem_acesso.php';
            }
        })
        .catch(error => {
            console.error('Erro ao verificar escola:', error);
        });
}

/**
 * Função para ser chamada quando detectar "Escola não encontrada"
 * Destrói a sessão e redireciona para sem_acesso.php
 */
function handleEscolaNaoEncontrada() {
    // Primeiro, tentar fazer logout via AJAX para limpar sessão no servidor
    fetch('../../Models/sessao/sessions.php?acao=logout', {
        method: 'POST'
    })
    .then(() => {
        // Depois redirecionar
        window.location.href = '../../Views/auth/sem_acesso.php';
    })
    .catch(() => {
        // Mesmo se o logout falhar, redirecionar
        window.location.href = '../../Views/auth/sem_acesso.php';
    });
}

// Adicionar listener global para detectar mensagens de erro
document.addEventListener('DOMContentLoaded', function() {
    // Interceptar alerts relacionados a escola não encontrada
    const originalAlert = window.alert;
    window.alert = function(message) {
        if (message && (message.includes('Escola não encontrada') || 
                       message.includes('escola não encontrada') ||
                       message.includes('Escola nao encontrada') ||
                       message.includes('escola nao encontrada'))) {
            // Se for mensagem de escola não encontrada, redirecionar
            handleEscolaNaoEncontrada();
            return;
        }
        originalAlert.call(window, message);
    };
});





