# Sistema de Gerenciamento de Tema

Este documento explica como o sistema de tema funciona no dashboard do SIGAE.

## Arquivos Envolvidos

### 1. `theme-manager.js`
Arquivo JavaScript principal que gerencia o sistema de tema:
- Classe `ThemeManager` que controla a aplicação e persistência do tema
- Funções globais `setTheme()` e `toggleTheme()` para compatibilidade
- Atalho de teclado Alt + T para alternar tema
- Persistência no localStorage

### 2. Páginas Modificadas
- `dashboard.php` - Página principal do dashboard
- `gestao_escolas.php` - Página de gestão de escolas
- `gestao_usuarios.php` - Página de gestão de usuários

### 3. `test-theme.html`
Arquivo de teste para verificar o funcionamento do sistema de tema.

## Como Funciona

### Inicialização
1. O `theme-manager.js` é carregado em todas as páginas
2. Na inicialização, o tema salvo no localStorage é aplicado automaticamente
3. Os botões de tema são configurados automaticamente

### Persistência
- O tema é salvo no localStorage na chave `accessibilitySettings`
- O formato é: `{"theme": "light"}` ou `{"theme": "dark"}`
- O tema persiste entre sessões e páginas

### Aplicação do Tema
- O tema é aplicado através do atributo `data-theme` no elemento `<html>`
- Estilos CSS são aplicados usando seletores `[data-theme="dark"]`
- Classes do Tailwind são aplicadas dinamicamente

## Uso

### Botões de Tema
```html
<button onclick="setTheme('light')" id="theme-light">Claro</button>
<button onclick="setTheme('dark')" id="theme-dark">Escuro</button>
```

### JavaScript
```javascript
// Definir tema
setTheme('dark');

// Alternar tema
toggleTheme();

// Obter tema atual
const currentTheme = window.themeManager.getCurrentTheme();
```

### Atalho de Teclado
- **Alt + T**: Alterna entre tema claro e escuro

## Estilos CSS

### Variáveis CSS para Tema Escuro
```css
[data-theme="dark"] {
    --bg-primary: #0f0f0f;
    --bg-secondary: #1a1a1a;
    --bg-tertiary: #262626;
    --bg-quaternary: #333333;
    --text-primary: #ffffff;
    --text-secondary: #e5e5e5;
    --text-muted: #a3a3a3;
    --border-color: #404040;
    --border-light: #525252;
    --primary-green: #22c55e;
    --primary-green-hover: #16a34a;
}
```

### Aplicação de Estilos
```css
[data-theme="dark"] .bg-white {
    background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border-color) !important;
}
```

## Testando o Sistema

1. Acesse `test-theme.html` para testar o sistema
2. Teste a alternância entre temas
3. Teste a persistência recarregando a página
4. Teste a navegação entre páginas do dashboard
5. Teste o atalho Alt + T

## Solução de Problemas

### Tema não persiste
- Verifique se o localStorage está habilitado
- Verifique se o `theme-manager.js` está sendo carregado

### Estilos não aplicados
- Verifique se os estilos CSS estão incluídos na página
- Verifique se o atributo `data-theme` está sendo definido

### Botões não funcionam
- Verifique se os IDs dos botões estão corretos (`theme-light`, `theme-dark`)
- Verifique se o `theme-manager.js` está sendo carregado antes dos botões

## Estrutura de Arquivos

```
app/main/Views/dashboard/
├── theme-manager.js          # Sistema principal de tema
├── dashboard.php            # Dashboard principal
├── gestao_escolas.php       # Gestão de escolas
├── gestao_usuarios.php      # Gestão de usuários
├── test-theme.html          # Página de teste
└── THEME_SYSTEM.md          # Esta documentação
```
