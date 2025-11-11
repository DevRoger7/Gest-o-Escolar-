// User Profile Modal JavaScript Functions
// Este arquivo contém todas as funções necessárias para o funcionamento do modal de perfil

// Profile Modal Functions
function openUserProfile() {
    const modal = document.getElementById('userProfileModal');
    const modalContent = document.getElementById('modalContent');
    
    if (!modal || !modalContent) {
        console.error('Modal elements not found');
        return;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Trigger animation after a small delay
    setTimeout(() => {
        modalContent.style.transform = 'scale(1) translateY(0)';
        modalContent.style.opacity = '1';
    }, 10);
    
    // Initialize theme buttons and accessibility
    setTimeout(() => {
        if (typeof initializeThemeButtons === 'function') {
            initializeThemeButtons();
        }
        initializeScrollIndicator();
        initializeAccessibilityControls();
    }, 100);
}

function closeUserProfile() {
    const modal = document.getElementById('userProfileModal');
    const modalContent = document.getElementById('modalContent');
    
    if (!modal || !modalContent) {
        return;
    }
    
    // Animate out
    modalContent.style.transform = 'scale(0.95) translateY(20px)';
    modalContent.style.opacity = '0';
    
    // Hide modal after animation
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }, 300);
}

function switchProfileTab(tabName) {
    // Remove active class from all tabs and reset styles
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.classList.remove('active');
        
        // Reset tab styles - use correct class selectors
        const icon = tab.querySelector('.w-6.h-6, .w-7.h-7, .w-8.h-8');
        const iconSvg = tab.querySelector('svg');
        const text = tab.querySelector('span');
        
        if (icon) {
            icon.classList.remove('bg-white');
            icon.classList.add('bg-gray-200');
        }
        
        if (iconSvg) {
            iconSvg.classList.remove('text-primary-green', 'text-green-600');
            iconSvg.classList.add('text-gray-600');
        }
        
        if (text) {
            text.classList.remove('text-gray-900', 'font-semibold');
            text.classList.add('text-gray-700');
        }
    });

    // Hide all tab contents
    document.querySelectorAll('.profile-tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Add active class to clicked tab and apply styles
    const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('active');
        
        // Apply active styles - use correct class selectors
        const icon = activeTab.querySelector('.w-6.h-6, .w-7.h-7, .w-8.h-8');
        const iconSvg = activeTab.querySelector('svg');
        const text = activeTab.querySelector('span');
        
        if (icon) {
            icon.classList.remove('bg-gray-200');
            icon.classList.add('bg-white');
        }
        
        if (iconSvg) {
            iconSvg.classList.remove('text-gray-600');
            iconSvg.classList.add('text-primary-green');
        }
        
        if (text) {
            text.classList.remove('text-gray-700');
            text.classList.add('text-gray-900', 'font-semibold');
        }
    }

    // Show corresponding content
    const selectedContent = document.getElementById(`profile-${tabName}`);
    if (selectedContent) {
        selectedContent.classList.remove('hidden');
        
        // Initialize theme buttons if settings tab is selected
        if (tabName === 'settings') {
            setTimeout(() => {
                if (typeof initializeThemeButtons === 'function') {
                    initializeThemeButtons();
                }
                initializeAccessibilityControls();
            }, 100);
        }
    }
}

// Initialize accessibility controls
function initializeAccessibilityControls() {
    // VLibras Toggle
    const vlibrasToggle = document.getElementById('vlibras-toggle');
    if (vlibrasToggle) {
        vlibrasToggle.addEventListener('change', function() {
            const vlibrasWidget = document.getElementById('vlibras-widget');
            if (vlibrasWidget) {
                if (this.checked) {
                    vlibrasWidget.style.display = 'block';
                    vlibrasWidget.classList.remove('disabled');
                    vlibrasWidget.classList.add('enabled');
                    localStorage.setItem('vlibras-enabled', 'true');
                    // Reinicializar VLibras se necessário
                    if (window.VLibras) {
                        new window.VLibras.Widget('https://vlibras.gov.br/app');
                    }
                } else {
                    vlibrasWidget.style.display = 'none';
                    vlibrasWidget.classList.remove('enabled');
                    vlibrasWidget.classList.add('disabled');
                    localStorage.setItem('vlibras-enabled', 'false');
                }
            }
        });
        
        // Load saved state
        const vlibrasEnabled = localStorage.getItem('vlibras-enabled');
        if (vlibrasEnabled === 'false') {
            vlibrasToggle.checked = false;
            const vlibrasWidget = document.getElementById('vlibras-widget');
            if (vlibrasWidget) {
                vlibrasWidget.style.display = 'none';
                vlibrasWidget.classList.remove('enabled');
                vlibrasWidget.classList.add('disabled');
            }
        } else {
            // Garantir que está visível se habilitado
            vlibrasToggle.checked = true;
            const vlibrasWidget = document.getElementById('vlibras-widget');
            if (vlibrasWidget) {
                vlibrasWidget.style.display = 'block';
                vlibrasWidget.classList.remove('disabled');
                vlibrasWidget.classList.add('enabled');
            }
        }
    }
    
    // High Contrast Toggle
    const contrastToggle = document.getElementById('contrast-toggle');
    if (contrastToggle) {
        contrastToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('high-contrast');
                localStorage.setItem('high-contrast', 'true');
            } else {
                document.body.classList.remove('high-contrast');
                localStorage.setItem('high-contrast', 'false');
            }
        });
        
        // Load saved state
        const highContrast = localStorage.getItem('high-contrast');
        if (highContrast === 'true') {
            contrastToggle.checked = true;
            document.body.classList.add('high-contrast');
        }
    }
    
    // Font Size Controls
    const fontDecrease = document.getElementById('font-decrease');
    const fontIncrease = document.getElementById('font-increase');
    const fontReset = document.getElementById('font-reset');
    const fontSizeDisplay = document.getElementById('font-size-display');
    
    if (fontDecrease && fontIncrease && fontReset && fontSizeDisplay) {
        let currentFontSize = parseInt(localStorage.getItem('font-size') || '100');
        updateFontSize(currentFontSize);
        
        fontDecrease.addEventListener('click', function() {
            if (currentFontSize > 80) {
                currentFontSize -= 10;
                updateFontSize(currentFontSize);
                localStorage.setItem('font-size', currentFontSize.toString());
            }
        });
        
        fontIncrease.addEventListener('click', function() {
            if (currentFontSize < 150) {
                currentFontSize += 10;
                updateFontSize(currentFontSize);
                localStorage.setItem('font-size', currentFontSize.toString());
            }
        });
        
        fontReset.addEventListener('click', function() {
            currentFontSize = 100;
            updateFontSize(currentFontSize);
            localStorage.setItem('font-size', '100');
        });
        
        function updateFontSize(size) {
            document.documentElement.style.fontSize = size + '%';
            fontSizeDisplay.textContent = size + '%';
        }
    }
}

// Initialize scroll indicator
function initializeScrollIndicator() {
    const scrollContainer = document.querySelector('#userProfileModal .overflow-y-auto');
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    if (scrollContainer && scrollToTopBtn) {
        scrollContainer.addEventListener('scroll', function() {
            if (scrollContainer.scrollTop > 300) {
                scrollToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
                scrollToTopBtn.classList.add('opacity-100');
            } else {
                scrollToTopBtn.classList.add('opacity-0', 'pointer-events-none');
                scrollToTopBtn.classList.remove('opacity-100');
            }
        });
    }
}

// Scroll to top function
function scrollToTop() {
    const scrollContainer = document.querySelector('#userProfileModal .overflow-y-auto');
    if (scrollContainer) {
        scrollContainer.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

// Initialize theme buttons (if theme manager exists)
function initializeThemeButtons() {
    const themeLight = document.getElementById('theme-light');
    const themeDark = document.getElementById('theme-dark');
    
    if (themeLight && themeDark) {
        // Remove existing event listeners by cloning
        const newThemeLight = themeLight.cloneNode(true);
        const newThemeDark = themeDark.cloneNode(true);
        themeLight.parentNode.replaceChild(newThemeLight, themeLight);
        themeDark.parentNode.replaceChild(newThemeDark, themeDark);
        
        newThemeLight.addEventListener('click', function() {
            if (typeof setTheme === 'function') {
                setTheme('light');
            } else if (typeof window.setTheme === 'function') {
                window.setTheme('light');
            }
            updateThemeButtons('light');
        });
        
        newThemeDark.addEventListener('click', function() {
            if (typeof setTheme === 'function') {
                setTheme('dark');
            } else if (typeof window.setTheme === 'function') {
                window.setTheme('dark');
            }
            updateThemeButtons('dark');
        });
        
        // Update button states based on current theme
        const currentTheme = localStorage.getItem('theme') || 'light';
        updateThemeButtons(currentTheme);
    }
}

function updateThemeButtons(theme) {
    const themeLight = document.getElementById('theme-light');
    const themeDark = document.getElementById('theme-dark');
    
    if (theme === 'dark') {
        if (themeDark) {
            themeDark.classList.add('border-gray-900', 'bg-gray-50');
            themeDark.classList.remove('border-gray-200');
        }
        if (themeLight) {
            themeLight.classList.remove('border-gray-900', 'bg-gray-50');
            themeLight.classList.add('border-gray-200');
        }
    } else {
        if (themeLight) {
            themeLight.classList.add('border-gray-900', 'bg-gray-50');
            themeLight.classList.remove('border-gray-200');
        }
        if (themeDark) {
            themeDark.classList.remove('border-gray-900', 'bg-gray-50');
            themeDark.classList.add('border-gray-200');
        }
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    if (event.altKey && event.key === 'a') {
        event.preventDefault();
        openUserProfile();
    }
    if (event.key === 'Escape') {
        closeUserProfile();
    }
});

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside (on backdrop)
    const modal = document.getElementById('userProfileModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeUserProfile();
            }
        });
    }
    
    // Initialize scroll indicator
    initializeScrollIndicator();
});

