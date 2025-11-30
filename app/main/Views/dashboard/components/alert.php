<?php
/**
 * Componente de Alertas Reutilizável
 * 
 * Uso:
 * require_once 'components/alert.php';
 * echo showAlert('Mensagem de sucesso!', 'success');
 * echo showAlert('Erro ao salvar!', 'error');
 */

function showAlert($message, $type = 'success', $dismissible = true) {
    $colors = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];
    
    $icons = [
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'error' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    $icon = $icons[$type] ?? $icons['info'];
    
    $dismiss = $dismissible ? '<button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600 transition-colors" aria-label="Fechar">✕</button>' : '';
    
    $html = "
    <div class='{$color} border rounded-lg p-4 mb-4 flex items-start gap-3 animate-fade-in' role='alert' id='alert-" . uniqid() . "'>
        <svg class='w-5 h-5 flex-shrink-0 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='{$icon}'></path>
        </svg>
        <div class='flex-1'>{$message}</div>
        {$dismiss}
    </div>
    ";
    
    // Adicionar animação CSS se não existir
    if (!defined('ALERT_CSS_ADDED')) {
        echo "<style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
        </style>";
        define('ALERT_CSS_ADDED', true);
    }
    
    return $html;
}

/**
 * Função para mostrar alerta via JavaScript (para uso após AJAX)
 */
function showAlertJS($message, $type = 'success') {
    $colors = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    
    return "
    <script>
    (function() {
        const alert = document.createElement('div');
        alert.className = '{$color} border rounded-lg p-4 mb-4 flex items-start gap-3';
        alert.innerHTML = `
            <div class='flex-1'>{$message}</div>
            <button onclick='this.parentElement.remove()' class='text-gray-400 hover:text-gray-600'>✕</button>
        `;
        document.body.insertBefore(alert, document.body.firstChild);
        setTimeout(() => alert.remove(), 5000);
    })();
    </script>
    ";
}
?>

