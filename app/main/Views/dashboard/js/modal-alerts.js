/**
 * Sistema de Modais Estilizados
 * Substitui os alertas padrão do navegador por modais bonitos e responsivos
 */

// Criar estrutura HTML do modal se não existir
function initModalSystem() {
    if (document.getElementById('modalAlertContainer')) {
        return; // Já existe
    }

    const modalHTML = `
        <div id="modalAlertContainer" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300" style="display: none;">
            <div id="modalAlertBox" class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0">
                <div id="modalAlertContent" class="p-6">
                    <div class="flex items-start">
                        <div id="modalAlertIcon" class="flex-shrink-0 mr-4"></div>
                        <div class="flex-1">
                            <h3 id="modalAlertTitle" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                            <p id="modalAlertMessage" class="text-gray-600 text-sm"></p>
                        </div>
                        <button id="modalAlertClose" onclick="closeModalAlert()" class="ml-4 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button id="modalAlertButton" onclick="closeModalAlert()" class="px-6 py-2 rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"></button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Configurações de tipos de modal
const modalTypes = {
    success: {
        icon: `<div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>`,
        title: 'Sucesso!',
        buttonClass: 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
        buttonText: 'OK'
    },
    error: {
        icon: `<div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>`,
        title: 'Erro!',
        buttonClass: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        buttonText: 'Entendi'
    },
    warning: {
        icon: `<div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>`,
        title: 'Atenção!',
        buttonClass: 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500',
        buttonText: 'OK'
    },
    info: {
        icon: `<div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>`,
        title: 'Informação',
        buttonClass: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        buttonText: 'OK'
    }
};

/**
 * Mostra um modal estilizado
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo do modal: 'success', 'error', 'warning', 'info'
 * @param {string} customTitle - Título customizado (opcional)
 * @param {function} onClose - Callback executado ao fechar o modal (opcional)
 */
function showModalAlert(message, type = 'info', customTitle = null, onClose = null) {
    initModalSystem();

    const config = modalTypes[type] || modalTypes.info;
    const container = document.getElementById('modalAlertContainer');
    const box = document.getElementById('modalAlertBox');
    const icon = document.getElementById('modalAlertIcon');
    const title = document.getElementById('modalAlertTitle');
    const messageEl = document.getElementById('modalAlertMessage');
    const button = document.getElementById('modalAlertButton');

    // Configurar conteúdo
    icon.innerHTML = config.icon;
    title.textContent = customTitle || config.title;
    messageEl.textContent = message;
    button.className = config.buttonClass;
    button.textContent = config.buttonText;

    // Armazenar callback
    if (onClose) {
        button.onclick = () => {
            closeModalAlert();
            onClose();
        };
    } else {
        button.onclick = closeModalAlert;
    }

    // Mostrar modal com animação
    container.style.display = 'flex';
    setTimeout(() => {
        container.classList.remove('hidden');
        box.classList.remove('scale-95', 'opacity-0');
        box.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Fechar ao clicar no overlay
    container.onclick = (e) => {
        if (e.target === container) {
            closeModalAlert();
        }
    };

    // Fechar com ESC
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            closeModalAlert();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

/**
 * Fecha o modal
 */
function closeModalAlert() {
    const container = document.getElementById('modalAlertContainer');
    const box = document.getElementById('modalAlertBox');

    if (!container) return;

    box.classList.remove('scale-100', 'opacity-100');
    box.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        container.classList.add('hidden');
        container.style.display = 'none';
    }, 300);
}

/**
 * Substitui window.alert por modal estilizado
 */
function replaceAlerts() {
    // Substituir alert padrão
    window.originalAlert = window.alert;
    window.alert = function(message) {
        showModalAlert(message, 'info', 'Atenção');
    };
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', replaceAlerts);
} else {
    replaceAlerts();
}

// Exportar funções globalmente
window.showModalAlert = showModalAlert;
window.closeModalAlert = closeModalAlert;
window.showSuccessAlert = (message, title, onClose) => showModalAlert(message, 'success', title, onClose);
window.showErrorAlert = (message, title, onClose) => showModalAlert(message, 'error', title, onClose);
window.showWarningAlert = (message, title, onClose) => showModalAlert(message, 'warning', title, onClose);
window.showInfoAlert = (message, title, onClose) => showModalAlert(message, 'info', title, onClose);

