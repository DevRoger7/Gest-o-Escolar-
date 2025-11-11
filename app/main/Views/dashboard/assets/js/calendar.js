// JavaScript do calendário - FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        // Configuração igual à imagem
        initialView: 'dayGridMonth',
        locale: 'pt-br', // Português brasileiro
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'monthViewButton addEventButton'
        },
        
        // Botões personalizados
        customButtons: {
            monthViewButton: {
                text: 'Visualização mensal',
                click: function() {
                    calendar.changeView('dayGridMonth');
                }
            },
            addEventButton: {
                text: 'Adicionar evento',
                click: function() {
                    openAddEventModal();
                }
            }
        },
        
        // Eventos
        events: {
            url: 'api/events.php',
            method: 'GET',
            failure: function() {
                console.error('Erro ao carregar eventos');
                showNotification('Erro ao carregar eventos', 'error');
            }
        },
        
        // Interações
        eventClick: function(info) {
            openEditEventModal(info.event);
        },
        
        dateClick: function(info) {
            createEventOnDate(info.dateStr);
        },
        
        // Configurações visuais
        dayMaxEvents: 3,
        moreLinkClick: 'popover',
        
        // Cores e estilos
        eventDisplay: 'block',
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        },
        
        // Localização
        locale: 'pt-br',
        firstDay: 1, // Segunda-feira
        
        // Recursos adicionais
        selectable: true,
        selectMirror: true,
        unselectAuto: false,
        
        // Callbacks
        eventDidMount: function(info) {
            // Adicionar classes CSS baseadas no tipo
            info.el.classList.add('fc-event-' + info.event.extendedProps.event_type);
            
            // Adicionar bordas arredondadas e estilos
            info.el.classList.add('rounded-lg', 'shadow-sm', 'px-2', 'py-1', 'text-xs', 'font-medium', 'text-white');
            
            // Aplicar cor baseada no tipo de evento
            const eventColor = info.event.extendedProps.color || '#3B82F6';
            info.el.style.backgroundColor = eventColor;
            info.el.style.borderColor = eventColor;
        },
        
        // Estilização dos eventos
        eventClassNames: function(arg) {
            return ['fc-event-' + arg.event.extendedProps.event_type];
        },
        
        // Configuração dos dias da semana
        dayHeaderContent: function(arg) {
            return arg.text.substring(0, 3); // Ex: Seg, Ter, Qua
        }
    });
    
    calendar.render();
    
    // Variáveis globais
    let currentEvent = null;
    
    // Função para abrir modal de adicionar evento
    function openAddEventModal() {
        currentEvent = null;
        document.getElementById('event-modal').classList.remove('hidden');
        document.querySelector('#event-modal h3').textContent = 'Adicionar Evento';
        document.getElementById('event-form').reset();
        
        // Definir data atual
        const now = new Date();
        const startDate = now.toISOString().slice(0, 16);
        const endDate = new Date(now.getTime() + 60 * 60 * 1000).toISOString().slice(0, 16);
        
        document.getElementById('event-start').value = startDate;
        document.getElementById('event-end').value = endDate;
    }
    
    // Função para abrir modal de editar evento
    function openEditEventModal(event) {
        currentEvent = event;
        document.getElementById('event-modal').classList.remove('hidden');
        document.querySelector('#event-modal h3').textContent = 'Editar Evento';
        
        // Preencher formulário com dados do evento
        document.getElementById('event-title').value = event.title;
        document.getElementById('event-description').value = event.extendedProps.description || '';
        document.getElementById('event-start').value = event.start.toISOString().slice(0, 16);
        document.getElementById('event-end').value = event.end ? event.end.toISOString().slice(0, 16) : '';
        document.getElementById('event-type').value = event.extendedProps.event_type || 'event';
        document.getElementById('event-color').value = event.color || '#3B82F6';
    }
    
    // Função para criar evento na data clicada
    function createEventOnDate(dateStr) {
        openAddEventModal();
        
        // Definir data clicada
        const clickedDate = new Date(dateStr);
        const startDate = clickedDate.toISOString().slice(0, 16);
        const endDate = new Date(clickedDate.getTime() + 60 * 60 * 1000).toISOString().slice(0, 16);
        
        document.getElementById('event-start').value = startDate;
        document.getElementById('event-end').value = endDate;
    }
    
    // Função para salvar evento
    function saveEvent(eventData) {
        const url = currentEvent ? 'api/events.php?id=' + currentEvent.id : 'api/events.php';
        const method = currentEvent ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(eventData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                calendar.refetchEvents();
                closeEventModal();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao salvar evento', 'error');
        });
    }
    
    // Função para fechar modal
    function closeEventModal() {
        document.getElementById('event-modal').classList.add('hidden');
        currentEvent = null;
    }
    
    // Função para mostrar notificação
    function showNotification(message, type) {
        // Criar elemento de notificação
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Event listeners
    document.getElementById('add-event-btn').addEventListener('click', openAddEventModal);
    document.getElementById('cancel-event').addEventListener('click', closeEventModal);
    
    // Fechar modal ao clicar fora
    document.getElementById('event-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEventModal();
        }
    });
    
    // Submissão do formulário
    document.getElementById('event-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            title: document.getElementById('event-title').value,
            description: document.getElementById('event-description').value,
            start_date: document.getElementById('event-start').value,
            end_date: document.getElementById('event-end').value,
            event_type: document.getElementById('event-type').value,
            color: document.getElementById('event-color').value,
            all_day: false
        };
        
        // Validação básica
        if (!formData.title.trim()) {
            showNotification('Título é obrigatório', 'error');
            return;
        }
        
        if (!formData.start_date) {
            showNotification('Data de início é obrigatória', 'error');
            return;
        }
        
        saveEvent(formData);
    });
    
    // Atualizar data de fim quando data de início mudar
    document.getElementById('event-start').addEventListener('change', function() {
        const startDate = new Date(this.value);
        const endDate = new Date(startDate.getTime() + 60 * 60 * 1000);
        document.getElementById('event-end').value = endDate.toISOString().slice(0, 16);
    });
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEventModal();
        }
    });
    
    // Função para deletar evento
    function deleteEvent(eventId) {
        if (confirm('Tem certeza que deseja excluir este evento?')) {
            fetch('api/events.php?id=' + eventId, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Evento excluído com sucesso', 'success');
                    calendar.refetchEvents();
                    closeEventModal();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao excluir evento', 'error');
            });
        }
    }
    
    // Adicionar botão de deletar no modal de edição
    function addDeleteButton() {
        if (currentEvent) {
            const form = document.getElementById('event-form');
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm';
            deleteBtn.textContent = 'Excluir';
            deleteBtn.onclick = () => deleteEvent(currentEvent.id);
            
            const buttonContainer = form.querySelector('.bg-gray-50');
            buttonContainer.appendChild(deleteBtn);
        }
    }
    
    // Chamar addDeleteButton quando abrir modal de edição
    const originalOpenEditEventModal = openEditEventModal;
    openEditEventModal = function(event) {
        originalOpenEditEventModal(event);
        addDeleteButton();
    };
});
