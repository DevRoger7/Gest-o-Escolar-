/**
 * Validação de Formulários - Frontend
 * 
 * Uso:
 * <script src="js/form-validation.js"></script>
 * 
 * O formulário será validado automaticamente ao submeter
 */

(function() {
    'use strict';

    // Validadores
    const validators = {
        cpf: function(cpf) {
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11) return false;
            
            // Validação básica de CPF
            if (/^(\d)\1{10}$/.test(cpf)) return false;
            
            let sum = 0;
            for (let i = 0; i < 9; i++) {
                sum += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let digit = 11 - (sum % 11);
            if (digit >= 10) digit = 0;
            if (digit !== parseInt(cpf.charAt(9))) return false;
            
            sum = 0;
            for (let i = 0; i < 10; i++) {
                sum += parseInt(cpf.charAt(i)) * (11 - i);
            }
            digit = 11 - (sum % 11);
            if (digit >= 10) digit = 0;
            if (digit !== parseInt(cpf.charAt(10))) return false;
            
            return true;
        },
        
        email: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        telefone: function(tel) {
            tel = tel.replace(/\D/g, '');
            return tel.length >= 10 && tel.length <= 11;
        },
        
        required: function(value) {
            return value.trim().length > 0;
        },
        
        minLength: function(value, min) {
            return value.trim().length >= min;
        }
    };

    // Função principal de validação
    function validateForm(form) {
        const errors = [];
        const inputs = form.querySelectorAll('[required], [data-validate]');
        
        inputs.forEach(input => {
            const value = input.value.trim();
            const type = input.type || input.dataset.validate;
            const name = input.name || input.id || 'Campo';
            
            // Remover classes de erro anteriores
            input.classList.remove('border-red-500', 'ring-red-500');
            
            // Validação de campo obrigatório
            if (input.hasAttribute('required') && !validators.required(value)) {
                errors.push(`${name} é obrigatório`);
                input.classList.add('border-red-500', 'ring-red-500');
                return;
            }
            
            // Validações específicas
            if (value) {
                if (type === 'email' && !validators.email(value)) {
                    errors.push('Email inválido');
                    input.classList.add('border-red-500', 'ring-red-500');
                } else if (input.dataset.validate === 'cpf' && !validators.cpf(value)) {
                    errors.push('CPF inválido');
                    input.classList.add('border-red-500', 'ring-red-500');
                } else if (input.dataset.validate === 'telefone' && !validators.telefone(value)) {
                    errors.push('Telefone inválido');
                    input.classList.add('border-red-500', 'ring-red-500');
                } else if (input.hasAttribute('minlength')) {
                    const min = parseInt(input.getAttribute('minlength'));
                    if (!validators.minLength(value, min)) {
                        errors.push(`${name} deve ter no mínimo ${min} caracteres`);
                        input.classList.add('border-red-500', 'ring-red-500');
                    }
                }
            }
        });
        
        return errors;
    }

    // Adicionar validação em tempo real
    function addRealTimeValidation() {
        document.querySelectorAll('input[data-validate], input[type="email"]').forEach(input => {
            input.addEventListener('blur', function() {
                const value = this.value.trim();
                const type = this.type || this.dataset.validate;
                
                this.classList.remove('border-red-500', 'ring-red-500', 'border-green-500');
                
                if (value) {
                    let isValid = true;
                    
                    if (type === 'email' && !validators.email(value)) {
                        isValid = false;
                    } else if (this.dataset.validate === 'cpf' && !validators.cpf(value)) {
                        isValid = false;
                    } else if (this.dataset.validate === 'telefone' && !validators.telefone(value)) {
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        this.classList.add('border-red-500', 'ring-red-500');
                    } else {
                        this.classList.add('border-green-500');
                    }
                }
            });
        });
    }

    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            addRealTimeValidation();
            
            // Adicionar validação em todos os formulários
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const errors = validateForm(this);
                    
                    if (errors.length > 0) {
                        e.preventDefault();
                        
                        // Mostrar primeiro erro
                        alert(errors[0]);
                        
                        // Ou mostrar todos os erros
                        // alert(errors.join('\n'));
                        
                        return false;
                    }
                });
            });
        });
    } else {
        addRealTimeValidation();
        
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const errors = validateForm(this);
                
                if (errors.length > 0) {
                    e.preventDefault();
                    alert(errors[0]);
                    return false;
                }
            });
        });
    }

    // Exportar para uso global
    window.validateForm = validateForm;
    window.validators = validators;
})();

