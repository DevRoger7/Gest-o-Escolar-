/**
 * Sistema de Gerenciamento de Tema
 * Gerencia a persistência e aplicação de temas entre todas as páginas do dashboard
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme();
        this.init();
    }

    /**
     * Inicializa o sistema de tema
     */
    init() {
        // Aguardar DOM estar pronto antes de aplicar tema
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.applyTheme(this.currentTheme);
                this.setupThemeButtons();
            });
        } else {
            // DOM já está pronto
            this.applyTheme(this.currentTheme);
            this.setupThemeButtons();
        }

        // Configurar botões também após um pequeno delay para garantir que estejam no DOM
        setTimeout(() => {
            this.setupThemeButtons();
        }, 100);
    }

    /**
     * Obtém o tema salvo no localStorage
     */
    getStoredTheme() {
        const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
        return settings.theme || 'light';
    }

    /**
     * Define o tema atual
     */
    setTheme(theme) {
        this.currentTheme = theme;
        this.applyTheme(theme);
        this.saveTheme(theme);
    }

    /**
     * Aplica o tema especificado
     */
    applyTheme(theme) {
        // Definir atributo data-theme no documento
        document.documentElement.setAttribute('data-theme', theme);
        
        // Aplicar classes do Tailwind para tema escuro
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            if (document.body) {
                document.body.classList.add('bg-gray-900', 'text-white');
            }
        } else {
            document.documentElement.classList.remove('dark');
            if (document.body) {
                document.body.classList.remove('bg-gray-900', 'text-white');
            }
        }

        // Atualizar estado dos botões de tema
        this.updateThemeButtons(theme);
    }

    /**
     * Salva o tema no localStorage
     */
    saveTheme(theme) {
        const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
        settings.theme = theme;
        localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
    }

    /**
     * Configura os botões de tema
     */
    setupThemeButtons() {
        // Aguardar o DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupThemeButtons());
            return;
        }

        // Configurar botões de tema se existirem
        const lightBtn = document.getElementById('theme-light');
        const darkBtn = document.getElementById('theme-dark');

        console.log('Configurando botões de tema:', { lightBtn, darkBtn });

        if (lightBtn) {
            // Remover event listeners existentes
            lightBtn.replaceWith(lightBtn.cloneNode(true));
            const newLightBtn = document.getElementById('theme-light');
            newLightBtn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Botão claro clicado');
                this.setTheme('light');
            });
        }

        if (darkBtn) {
            // Remover event listeners existentes
            darkBtn.replaceWith(darkBtn.cloneNode(true));
            const newDarkBtn = document.getElementById('theme-dark');
            newDarkBtn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Botão escuro clicado');
                this.setTheme('dark');
            });
        }
    }

    /**
     * Atualiza o estado visual dos botões de tema
     */
    updateThemeButtons(theme) {
        try {
            // Atualizar todos os botões de tema
            const themeButtons = document.querySelectorAll('[id^="theme-"]');
            themeButtons.forEach(btn => {
                if (btn && btn.classList) {
                    btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                    btn.classList.add('border-gray-300', 'text-gray-700');
                }
            });

            // Ativar botão do tema atual
            const activeBtn = document.getElementById(`theme-${theme}`);
            if (activeBtn && activeBtn.classList) {
                activeBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
                activeBtn.classList.remove('border-gray-300', 'text-gray-700');
            }
        } catch (error) {
            console.warn('Erro ao atualizar botões de tema:', error);
        }
    }

    /**
     * Alterna entre tema claro e escuro
     */
    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    /**
     * Obtém o tema atual
     */
    getCurrentTheme() {
        return this.currentTheme;
    }
}

// Instanciar o gerenciador de tema globalmente
window.themeManager = new ThemeManager();

// Funções globais para compatibilidade com código existente
function setTheme(theme) {
    if (window.themeManager) {
        window.themeManager.setTheme(theme);
    } else {
        console.error('ThemeManager não está disponível');
    }
}

function toggleTheme() {
    if (window.themeManager) {
        window.themeManager.toggleTheme();
    } else {
        console.error('ThemeManager não está disponível');
    }
}

// Fallback: Configurar botões diretamente se o ThemeManager falhar
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar um pouco para o ThemeManager se configurar
    setTimeout(() => {
        const lightBtn = document.getElementById('theme-light');
        const darkBtn = document.getElementById('theme-dark');
        
        if (lightBtn && !lightBtn.onclick) {
            lightBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Fallback: Botão claro clicado');
                setTheme('light');
            });
        }
        
        if (darkBtn && !darkBtn.onclick) {
            darkBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Fallback: Botão escuro clicado');
                setTheme('dark');
            });
        }
    }, 500);
});

// Atalho de teclado para alternar tema (Alt + T)
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key.toLowerCase() === 't') {
        e.preventDefault();
        window.themeManager.toggleTheme();
    }
});

// Exportar para uso em outros scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
