<?php
/**
 * Componente reutilizável de modal de logout
 * Este componente deve ser incluído em todas as páginas do dashboard
 */
?>
<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Confirmar Saída</h3>
                <p class="text-sm text-gray-600">Tem certeza que deseja sair do sistema?</p>
            </div>
        </div>
        <div class="flex space-x-3">
            <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                Cancelar
            </button>
            <button onclick="window.logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                Sim, Sair
            </button>
        </div>
    </div>
</div>

<script>
    // Funções do modal de logout - Garantir que estejam sempre disponíveis
    if (typeof window.confirmLogout === 'undefined') {
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } else {
                console.error('Modal de logout não encontrado');
            }
        };
    }
    
    if (typeof window.closeLogoutModal === 'undefined') {
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };
    }
    
    if (typeof window.logout === 'undefined') {
        window.logout = function() {
            try {
                // Tentar diferentes caminhos para logout.php
                const paths = [
                    '../auth/logout.php',
                    '../../auth/logout.php',
                    '../../../auth/logout.php',
                    '/app/main/Views/auth/logout.php'
                ];
                
                // Determinar o caminho correto baseado na URL atual
                const currentPath = window.location.pathname;
                let logoutPath = '../auth/logout.php';
                
                if (currentPath.includes('/dashboard/')) {
                    logoutPath = '../auth/logout.php';
                } else if (currentPath.includes('/Views/dashboard/')) {
                    logoutPath = '../auth/logout.php';
                }
                
                console.log('Redirecionando para logout:', logoutPath);
                window.location.href = logoutPath;
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
                alert('Erro ao fazer logout. Por favor, tente novamente.');
            }
        };
    }
    
    // Fechar modal ao clicar fora dele
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    window.closeLogoutModal();
                }
            });
        }
    });
</script>

