/**
 * Arbitragem Cripto - Main JavaScript File
 * Handles form validation, interactive elements, and enhanced UX
 */

// Global configuration
const App = {
    initialized: false,
    
    // Initialize application
    init: function() {
        if (this.initialized) return;
        
        console.log('Initializing Arbitragem Cripto Application...');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeComponents());
        } else {
            this.initializeComponents();
        }
        
        this.initialized = true;
    },
    
    // Initialize all components
    initializeComponents: function() {
        this.initializeFormValidation();
        this.initializeTooltips();
        this.initializeCurrencyFormatting();
        this.initializeNavigation();
        this.initializeTableFeatures();
        this.initializeFormEnhancements();
        this.initializeLoadingStates();
        this.initializeAlerts();
    },
    
    // Form validation
    initializeFormValidation: function() {
        const forms = document.querySelectorAll('form[method="POST"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!App.validateForm(this)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            });
        });
    },
    
    // Validate individual form
    validateForm: function(form) {
        let isValid = true;
        const formData = new FormData(form);
        
        // Custom validation rules
        const emailInputs = form.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            if (input.value && !this.isValidEmail(input.value)) {
                this.showFieldError(input, 'Digite um email válido');
                isValid = false;
            }
        });
        
        const passwordInputs = form.querySelectorAll('input[type="password"]');
        const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');
        
        if (passwordInputs.length > 0 && confirmPasswordInput) {
            const password = form.querySelector('input[name="password"]').value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password !== confirmPassword) {
                this.showFieldError(confirmPasswordInput, 'As senhas não coincidem');
                isValid = false;
            }
        }
        
        // Validate monetary values
        const monetaryInputs = form.querySelectorAll('input[step], input[name*="valor"], input[name*="lucro"]');
        monetaryInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (input.hasAttribute('required') && (isNaN(value) || value <= 0)) {
                this.showFieldError(input, 'Digite um valor válido maior que zero');
                isValid = false;
            }
        });
        
        // Validate dates
        const dateInputs = form.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            if (input.value && input.hasAttribute('max')) {
                const inputDate = new Date(input.value);
                const maxDate = new Date(input.getAttribute('max'));
                
                if (inputDate > maxDate) {
                    this.showFieldError(input, 'A data não pode ser futura');
                    isValid = false;
                }
            }
        });
        
        return isValid;
    },
    
    // Show field-specific error
    showFieldError: function(field, message) {
        // Remove existing error messages
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error class
        field.classList.add('is-invalid');
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    },
    
    // Email validation
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    // Initialize tooltips
    initializeTooltips: function() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },
    
    // Currency formatting
    initializeCurrencyFormatting: function() {
        const currencyInputs = document.querySelectorAll('input[step], input[name*="valor"], input[name*="lucro"]');
        
        currencyInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value) {
                    const value = parseFloat(this.value);
                    if (!isNaN(value)) {
                        const decimals = this.step && this.step.includes('.') 
                            ? this.step.split('.')[1].length 
                            : 2;
                        this.value = value.toFixed(decimals);
                    }
                }
            });
        });
        
        // Format display values
        const currencyElements = document.querySelectorAll('.currency-value, [data-currency]');
        currencyElements.forEach(element => {
            this.formatCurrencyDisplay(element);
        });
    },
    
    // Format currency display
    formatCurrencyDisplay: function(element) {
        const value = element.textContent || element.innerText;
        const numericValue = parseFloat(value.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(numericValue)) {
            const currency = element.dataset.currency || 'USD';
            const formatted = this.formatCurrency(numericValue, currency);
            element.textContent = formatted;
            element.classList.add('text-currency');
        }
    },
    
    // Format currency value
    formatCurrency: function(amount, currency = 'USD') {
        switch(currency) {
            case 'BRL':
                return 'R$ ' + amount.toLocaleString('pt-BR', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                });
            case 'USD':
            case 'USDT':
                return '$' + amount.toLocaleString('en-US', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 8 
                }).replace(/\.?0+$/, '');
            default:
                return amount.toLocaleString('en-US', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 8 
                });
        }
    },
    
    // Navigation enhancements
    initializeNavigation: function() {
        // Highlight current page in navigation
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPath || 
                (currentPath.includes('operations/') && link.href.includes('operations/'))) {
                link.classList.add('active');
            }
        });
        
        // Auto-collapse navbar on mobile after click
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarNav = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarNav) {
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        const bsCollapse = new bootstrap.Collapse(navbarNav, {
                            toggle: false
                        });
                        bsCollapse.hide();
                    }
                });
            });
        }
    },
    
    // Table features
    initializeTableFeatures: function() {
        // Make tables more responsive
        const tables = document.querySelectorAll('table:not(.table-responsive table)');
        tables.forEach(table => {
            if (!table.parentNode.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
        
        // Add sorting to table headers (basic implementation)
        this.initializeTableSorting();
    },
    
    // Basic table sorting
    initializeTableSorting: function() {
        const tables = document.querySelectorAll('table');
        
        tables.forEach(table => {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                if (header.textContent && !header.querySelector('a')) {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        this.sortTable(table, index);
                    });
                }
            });
        });
    },
    
    // Sort table by column
    sortTable: function(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isNumeric = rows.length > 0 && 
            !isNaN(parseFloat(rows[0].cells[columnIndex].textContent.replace(/[^\d.-]/g, '')));
        
        rows.sort((a, b) => {
            const aVal = a.cells[columnIndex].textContent.trim();
            const bVal = b.cells[columnIndex].textContent.trim();
            
            if (isNumeric) {
                const aNum = parseFloat(aVal.replace(/[^\d.-]/g, '')) || 0;
                const bNum = parseFloat(bVal.replace(/[^\d.-]/g, '')) || 0;
                return aNum - bNum;
            }
            
            return aVal.localeCompare(bVal);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    },
    
    // Form enhancements
    initializeFormEnhancements: function() {
        // Auto-focus first input
        const firstInput = document.querySelector('form input[type="text"], form input[type="email"], form input[type="password"]');
        if (firstInput && !firstInput.value) {
            setTimeout(() => firstInput.focus(), 100);
        }
        
        // Format cryptocurrency pairs
        const cryptoInputs = document.querySelectorAll('input[name="moeda"]');
        cryptoInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
            });
            
            input.addEventListener('blur', function() {
                if (this.value && !this.value.includes('USDT') && this.value.length > 3) {
                    // Auto-append USDT if not present and looks like a crypto symbol
                    if (!/USDT$|USD$|BTC$|ETH$/i.test(this.value)) {
                        this.value += 'USDT';
                    }
                }
            });
        });
        
        // Real-time validation feedback
        const inputs = document.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const errorMsg = this.parentNode.querySelector('.invalid-feedback');
                if (errorMsg) {
                    errorMsg.remove();
                }
            });
        });
    },
    
    // Loading states
    initializeLoadingStates: function() {
        const forms = document.querySelectorAll('form[method="POST"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.classList.add('loading');
                    submitButton.disabled = true;
                    
                    // Re-enable after 10 seconds to prevent permanent disability
                    setTimeout(() => {
                        submitButton.classList.remove('loading');
                        submitButton.disabled = false;
                    }, 10000);
                }
            });
        });
    },
    
    // Initialize alerts
    initializeAlerts: function() {
        // Auto-dismiss success alerts after 5 seconds
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            if (!alert.querySelector('.btn-close')) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            }
        });
    },
    
    // Utility functions
    utils: {
        // Show loading overlay
        showLoading: function() {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.innerHTML = `
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.9);
                z-index: 9999;
                backdrop-filter: blur(2px);
            `;
            document.body.appendChild(overlay);
        },
        
        // Hide loading overlay
        hideLoading: function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        },
        
        // Show toast notification
        showToast: function(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            if (typeof bootstrap !== 'undefined') {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', () => {
                    toast.remove();
                });
            }
        },
        
        // Create toast container
        createToastContainer: function() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        },
        
        // Format date for display
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        },
        
        // Calculate days between dates
        daysBetween: function(date1, date2) {
            const d1 = new Date(date1);
            const d2 = new Date(date2);
            const timeDiff = Math.abs(d2.getTime() - d1.getTime());
            return Math.ceil(timeDiff / (1000 * 3600 * 24));
        },
        
        // Debounce function
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        // Copy to clipboard
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast('Copiado para área de transferência!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showToast('Copiado para área de transferência!', 'success');
            }
        }
    }
};

// Page-specific functions
const PageHandlers = {
    // Dashboard page
    dashboard: function() {
        // Auto-refresh summary every 5 minutes
        if (window.location.pathname.endsWith('index.php') || window.location.pathname === '/') {
            setInterval(() => {
                // Could implement AJAX refresh here
                console.log('Auto-refresh would happen here');
            }, 300000); // 5 minutes
        }
    },
    
    // Operations list page
    operationsList: function() {
        if (window.location.pathname.includes('list-operations.php')) {
            // Remember filter preferences
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('change', App.utils.debounce(() => {
                    // Could auto-submit form or save preferences
                }, 500));
            }
        }
    },
    
    // New operation page
    newOperation: function() {
        if (window.location.pathname.includes('new-operation.php')) {
            // Auto-calculate USD from BRL if exchange rate is available
            const brlInput = document.querySelector('input[name="inicial_brl"]');
            const usdtInput = document.querySelector('input[name="inicial_usdt"]');
            
            if (brlInput && usdtInput) {
                brlInput.addEventListener('blur', function() {
                    if (this.value && !usdtInput.value) {
                        // Example exchange rate - in production, fetch from API
                        const exchangeRate = 0.20; // 1 BRL = 0.20 USD (example)
                        const usdValue = parseFloat(this.value) * exchangeRate;
                        usdtInput.value = usdValue.toFixed(8);
                    }
                });
            }
        }
    }
};

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    
    // Don't show error to user in production, just log it
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        App.utils.showToast('Erro JavaScript: ' + e.message, 'danger');
    }
});

// Initialize app when DOM is ready
App.init();

// Run page-specific handlers
document.addEventListener('DOMContentLoaded', function() {
    PageHandlers.dashboard();
    PageHandlers.operationsList();
    PageHandlers.newOperation();
});

// Export for global use
window.App = App;
window.PageHandlers = PageHandlers;
