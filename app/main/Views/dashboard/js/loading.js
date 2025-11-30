/**
 * Loading States - JavaScript
 * 
 * Uso:
 * <script src="js/loading.js"></script>
 * 
 * setLoading(button, true);  // Ativar loading
 * setLoading(button, false); // Desativar loading
 */

(function() {
    'use strict';

    /**
     * Ativar/desativar loading em botão
     */
    function setLoading(button, loading = true) {
        if (!button) return;
        
        if (loading) {
            button.disabled = true;
            button.classList.add('btn-loading');
            
            // Salvar texto original
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }
            
            // Adicionar spinner
            button.innerHTML = '<span class="spinner"></span> ' + (button.dataset.loadingText || 'Processando...');
        } else {
            button.disabled = false;
            button.classList.remove('btn-loading');
            
            // Restaurar texto original
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
    }

    /**
     * Mostrar overlay de loading
     */
    function showLoadingOverlay(message = 'Carregando...') {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4">
                <div class="spinner" style="width: 40px; height: 40px; border-width: 4px; margin: 0;"></div>
                <p class="text-gray-700 font-medium">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    /**
     * Esconder overlay de loading
     */
    function hideLoadingOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Adicionar loading automático em formulários
     */
    function initFormLoading() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                
                if (submitBtn && !form.dataset.noLoading) {
                    setLoading(submitBtn, true);
                    
                    // Se for submit normal (não AJAX), remover loading após 5 segundos (fallback)
                    setTimeout(() => {
                        setLoading(submitBtn, false);
                    }, 5000);
                }
            });
        });
    }

    /**
     * Adicionar loading em links com data-loading
     */
    function initLinkLoading() {
        document.querySelectorAll('a[data-loading]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href && !this.href.startsWith('#')) {
                    setLoading(this, true);
                }
            });
        });
    }

    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initFormLoading();
            initLinkLoading();
        });
    } else {
        initFormLoading();
        initLinkLoading();
    }

    // Exportar funções globais
    window.setLoading = setLoading;
    window.showLoadingOverlay = showLoadingOverlay;
    window.hideLoadingOverlay = hideLoadingOverlay;
})();

