// Utilidades globales del sistema

class Utils {
    constructor() {
        this.dateFormats = {
            'es': {
                short: { day: 'numeric', month: 'short' },
                medium: { day: 'numeric', month: 'short', year: 'numeric' },
                long: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
                time: { hour: '2-digit', minute: '2-digit' },
                datetime: { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }
            }
        };
    }
    
    /**
     * Formatear fecha según el idioma y formato
     */
    formatDate(date, format = 'medium', locale = 'es-ES') {
        if (!date) return '';
        
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        if (isNaN(dateObj.getTime())) return '';
        
        const language = appState?.getLanguage() || 'es';
        const formatOptions = this.dateFormats[language]?.[format] || this.dateFormats.es[format];
        
        return dateObj.toLocaleDateString(locale, formatOptions);
    }
    
    /**
     * Formatear fecha y hora
     */
    formatDateTime(date, locale = 'es-ES') {
        return this.formatDate(date, 'datetime', locale);
    }
    
    /**
     * Obtener fecha relativa (hace X tiempo)
     */
    getRelativeTime(date, locale = 'es-ES') {
        if (!date) return '';
        
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        const now = new Date();
        const diffMs = now - dateObj;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        
        if (diffMinutes < 1) return 'Ahora mismo';
        if (diffMinutes < 60) return `Hace ${diffMinutes} minuto${diffMinutes > 1 ? 's' : ''}`;
        if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
        
        return this.formatDate(dateObj, 'medium', locale);
    }
    
    /**
     * Validar email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validar contraseña
     */
    validatePassword(password) {
        const result = {
            isValid: false,
            errors: [],
            strength: 0
        };
        
        if (!password) {
            result.errors.push('La contraseña es requerida');
            return result;
        }
        
        if (password.length < 6) {
            result.errors.push('Debe tener al menos 6 caracteres');
        } else {
            result.strength++;
        }
        
        if (!/[A-Z]/.test(password)) {
            result.errors.push('Debe contener al menos una mayúscula');
        } else {
            result.strength++;
        }
        
        if (!/[a-z]/.test(password)) {
            result.errors.push('Debe contener al menos una minúscula');
        } else {
            result.strength++;
        }
        
        if (!/[0-9]/.test(password)) {
            result.errors.push('Debe contener al menos un número');
        } else {
            result.strength++;
        }
        
        if (!/[^A-Za-z0-9]/.test(password)) {
            result.errors.push('Debe contener al menos un símbolo');
        } else {
            result.strength++;
        }
        
        result.isValid = result.errors.length === 0;
        return result;
    }
    
    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'info', duration = 5000) {
        if (window.app && window.app.showNotification) {
            window.app.showNotification(message, type);
        } else {
            // Fallback para cuando app no esté disponible
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        // Agregar al estado global
        if (window.appState) {
            window.appState.addNotification({
                message,
                type,
                duration
            });
        }
    }
    
    /**
     * Confirmar acción
     */
    confirmAction(message, title = 'Confirmar') {
        return confirm(`${title}\n\n${message}`);
    }
    
    /**
     * Descargar archivo JSON
     */
    downloadJSON(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    /**
     * Descargar archivo CSV
     */
    downloadCSV(data, filename, headers = null) {
        if (!Array.isArray(data) || data.length === 0) {
            this.showNotification('No hay datos para exportar', 'warning');
            return;
        }
        
        let csv = '';
        
        // Headers
        if (headers) {
            csv += headers.join(',') + '\n';
        } else {
            csv += Object.keys(data[0]).join(',') + '\n';
        }
        
        // Data
        data.forEach(row => {
            const values = Object.values(row).map(value => {
                // Escapar comillas y envolver en comillas si contiene comas
                const stringValue = String(value || '');
                if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
                    return '"' + stringValue.replace(/"/g, '""') + '"';
                }
                return stringValue;
            });
            csv += values.join(',') + '\n';
        });
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    /**
     * Debounce function
     */
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }
    
    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Generar ID único
     */
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    /**
     * Capitalizar primera letra
     */
    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    /**
     * Truncar texto
     */
    truncate(str, length = 100, suffix = '...') {
        if (!str || str.length <= length) return str;
        return str.substring(0, length) + suffix;
    }
    
    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Formatear número
     */
    formatNumber(num, decimals = 0, locale = 'es-ES') {
        if (typeof num !== 'number') return num;
        return num.toLocaleString(locale, { 
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals 
        });
    }
    
    /**
     * Formatear bytes
     */
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    /**
     * Copiar al portapapeles
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showNotification('Copiado al portapapeles', 'success');
            return true;
        } catch (error) {
            console.error('Error copiando al portapapeles:', error);
            this.showNotification('Error al copiar', 'error');
            return false;
        }
    }
    
    /**
     * Detectar dispositivo móvil
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    /**
     * Obtener parámetros de URL
     */
    getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    }
    
    /**
     * Actualizar URL sin recargar
     */
    updateUrl(params, replace = false) {
        const url = new URL(window.location);
        
        Object.keys(params).forEach(key => {
            if (params[key] === null || params[key] === undefined) {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, params[key]);
            }
        });
        
        if (replace) {
            window.history.replaceState({}, '', url);
        } else {
            window.history.pushState({}, '', url);
        }
    }
    
    /**
     * Hacer petición HTTP con manejo de errores
     */
    async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
            
        } catch (error) {
            console.error('Error en petición:', error);
            this.showNotification(`Error: ${error.message}`, 'error');
            throw error;
        }
    }
    
    /**
     * Validar formulario
     */
    validateForm(formData, rules) {
        const errors = {};
        
        Object.keys(rules).forEach(field => {
            const value = formData[field];
            const fieldRules = rules[field];
            
            // Required
            if (fieldRules.required && (!value || value.toString().trim() === '')) {
                errors[field] = errors[field] || [];
                errors[field].push('Este campo es requerido');
            }
            
            // Min length
            if (fieldRules.minLength && value && value.length < fieldRules.minLength) {
                errors[field] = errors[field] || [];
                errors[field].push(`Debe tener al menos ${fieldRules.minLength} caracteres`);
            }
            
            // Max length
            if (fieldRules.maxLength && value && value.length > fieldRules.maxLength) {
                errors[field] = errors[field] || [];
                errors[field].push(`No puede tener más de ${fieldRules.maxLength} caracteres`);
            }
            
            // Email
            if (fieldRules.email && value && !this.isValidEmail(value)) {
                errors[field] = errors[field] || [];
                errors[field].push('Formato de email inválido');
            }
            
            // Custom validator
            if (fieldRules.validator && value) {
                const customError = fieldRules.validator(value);
                if (customError) {
                    errors[field] = errors[field] || [];
                    errors[field].push(customError);
                }
            }
        });
        
        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }
    
    /**
     * Almacenamiento local con expiración
     */
    setLocalStorage(key, value, ttl = null) {
        const item = {
            value,
            timestamp: Date.now(),
            ttl
        };
        localStorage.setItem(key, JSON.stringify(item));
    }
    
    getLocalStorage(key) {
        try {
            const item = JSON.parse(localStorage.getItem(key));
            if (!item) return null;
            
            if (item.ttl && Date.now() - item.timestamp > item.ttl) {
                localStorage.removeItem(key);
                return null;
            }
            
            return item.value;
        } catch (error) {
            console.error('Error leyendo localStorage:', error);
            return null;
        }
    }
    
    removeLocalStorage(key) {
        localStorage.removeItem(key);
    }
}

// Crear instancia global
window.utils = new Utils();

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Utils;
}