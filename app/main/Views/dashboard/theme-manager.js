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
        // Aplicar tema salvo ao carregar a página
        this.applyTheme(this.currentTheme);
        
        // Configurar botões de tema se existirem
        this.setupThemeButtons();
        
        // Aplicar tema ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            this.applyTheme(this.currentTheme);
        });
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
            document.body.classList.add('bg-gray-900', 'text-white');
        } else {
            document.documentElement.classList.remove('dark');
            document.body.classList.remove('bg-gray-900', 'text-white');
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
        // Configurar botões de tema se existirem
        const lightBtn = document.getElementById('theme-light');
        const darkBtn = document.getElementById('theme-dark');

        if (lightBtn) {
            lightBtn.addEventListener('click', () => this.setTheme('light'));
        }

        if (darkBtn) {
            darkBtn.addEventListener('click', () => this.setTheme('dark'));
        }
    }

    /**
     * Atualiza o estado visual dos botões de tema
     */
    updateThemeButtons(theme) {
        // Atualizar todos os botões de tema
        document.querySelectorAll('[id^="theme-"]').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
            btn.classList.add('border-gray-300', 'text-gray-700');
        });

        // Ativar botão do tema atual
        const activeBtn = document.getElementById(`theme-${theme}`);
        if (activeBtn) {
            activeBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            activeBtn.classList.remove('border-gray-300', 'text-gray-700');
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
    window.themeManager.setTheme(theme);
}

function toggleTheme() {
    window.themeManager.toggleTheme();
}

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
