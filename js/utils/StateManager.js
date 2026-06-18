// Gestor de Estado Global de la Aplicación

class StateManager {
    constructor() {
        this.state = {
            user: null,
            currentModule: 'dashboard',
            preferences: {},
            cache: {},
            notifications: [],
            filters: {},
            ui: {
                sidebarCollapsed: false,
                theme: 'light',
                language: 'es'
            }
        };
        
        this.listeners = {};
        this.persistentKeys = ['preferences', 'ui', 'filters'];
        
        this.loadFromStorage();
        this.setupAutoSave();
    }
    
    /**
     * Obtener valor del estado
     */
    get(path) {
        return this.getNestedValue(this.state, path);
    }
    
    /**
     * Establecer valor en el estado
     */
    set(path, value) {
        this.setNestedValue(this.state, path, value);
        this.notifyListeners(path, value);
        this.saveToStorage();
    }
    
    /**
     * Actualizar múltiples valores
     */
    update(updates) {
        Object.keys(updates).forEach(path => {
            this.setNestedValue(this.state, path, updates[path]);
            this.notifyListeners(path, updates[path]);
        });
        this.saveToStorage();
    }
    
    /**
     * Suscribirse a cambios en el estado
     */
    subscribe(path, callback) {
        if (!this.listeners[path]) {
            this.listeners[path] = [];
        }
        this.listeners[path].push(callback);
        
        // Retornar función para desuscribirse
        return () => {
            this.listeners[path] = this.listeners[path].filter(cb => cb !== callback);
        };
    }
    
    /**
     * Notificar a los listeners
     */
    notifyListeners(path, value) {
        // Notificar listeners específicos del path
        if (this.listeners[path]) {
            this.listeners[path].forEach(callback => {
                try {
                    callback(value, path);
                } catch (error) {
                    console.error('Error en listener del estado:', error);
                }
            });
        }
        
        // Notificar listeners globales
        if (this.listeners['*']) {
            this.listeners['*'].forEach(callback => {
                try {
                    callback(value, path);
                } catch (error) {
                    console.error('Error en listener global del estado:', error);
                }
            });
        }
    }
    
    /**
     * Obtener valor anidado usando path con puntos
     */
    getNestedValue(obj, path) {
        if (!path) return obj;
        
        const keys = path.split('.');
        let current = obj;
        
        for (const key of keys) {
            if (current === null || current === undefined) {
                return undefined;
            }
            current = current[key];
        }
        
        return current;
    }
    
    /**
     * Establecer valor anidado usando path con puntos
     */
    setNestedValue(obj, path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        let current = obj;
        
        for (const key of keys) {
            if (!(key in current) || typeof current[key] !== 'object') {
                current[key] = {};
            }
            current = current[key];
        }
        
        current[lastKey] = value;
    }
    
    /**
     * Cargar estado desde localStorage
     */
    loadFromStorage() {
        try {
            const saved = localStorage.getItem('appState');
            if (saved) {
                const savedState = JSON.parse(saved);
                
                // Solo cargar claves persistentes
                this.persistentKeys.forEach(key => {
                    if (savedState[key]) {
                        this.state[key] = { ...this.state[key], ...savedState[key] };
                    }
                });
            }
        } catch (error) {
            console.error('Error cargando estado desde localStorage:', error);
        }
    }
    
    /**
     * Guardar estado en localStorage
     */
    saveToStorage() {
        try {
            const toSave = {};
            
            // Solo guardar claves persistentes
            this.persistentKeys.forEach(key => {
                if (this.state[key]) {
                    toSave[key] = this.state[key];
                }
            });
            
            localStorage.setItem('appState', JSON.stringify(toSave));
        } catch (error) {
            console.error('Error guardando estado en localStorage:', error);
        }
    }
    
    /**
     * Configurar auto-guardado
     */
    setupAutoSave() {
        // Guardar cada 30 segundos
        setInterval(() => {
            this.saveToStorage();
        }, 30000);
        
        // Guardar antes de cerrar la página
        window.addEventListener('beforeunload', () => {
            this.saveToStorage();
        });
    }
    
    /**
     * Limpiar estado
     */
    clear() {
        this.state = {
            user: null,
            currentModule: 'dashboard',
            preferences: {},
            cache: {},
            notifications: [],
            filters: {},
            ui: {
                sidebarCollapsed: false,
                theme: 'light',
                language: 'es'
            }
        };
        
        localStorage.removeItem('appState');
        this.notifyListeners('*', this.state);
    }
    
    /**
     * Obtener todo el estado
     */
    getState() {
        return { ...this.state };
    }
    
    // Métodos específicos para funcionalidades comunes
    
    /**
     * Gestión de usuario
     */
    setUser(user) {
        this.set('user', user);
    }
    
    getUser() {
        return this.get('user');
    }
    
    isAuthenticated() {
        return !!this.get('user');
    }
    
    /**
     * Gestión de módulo actual
     */
    setCurrentModule(module) {
        this.set('currentModule', module);
    }
    
    getCurrentModule() {
        return this.get('currentModule');
    }
    
    /**
     * Gestión de preferencias
     */
    setPreference(key, value) {
        this.set(`preferences.${key}`, value);
    }
    
    getPreference(key, defaultValue = null) {
        return this.get(`preferences.${key}`) ?? defaultValue;
    }
    
    /**
     * Gestión de caché
     */
    setCache(key, value, ttl = 300000) { // TTL por defecto: 5 minutos
        const cacheItem = {
            value,
            timestamp: Date.now(),
            ttl
        };
        this.set(`cache.${key}`, cacheItem);
    }
    
    getCache(key) {
        const cacheItem = this.get(`cache.${key}`);
        
        if (!cacheItem) return null;
        
        // Verificar si el caché ha expirado
        if (Date.now() - cacheItem.timestamp > cacheItem.ttl) {
            this.clearCache(key);
            return null;
        }
        
        return cacheItem.value;
    }
    
    clearCache(key = null) {
        if (key) {
            this.set(`cache.${key}`, undefined);
        } else {
            this.set('cache', {});
        }
    }
    
    /**
     * Gestión de notificaciones
     */
    addNotification(notification) {
        const notifications = this.get('notifications') || [];
        const newNotification = {
            id: Date.now(),
            timestamp: new Date(),
            ...notification
        };
        
        notifications.push(newNotification);
        this.set('notifications', notifications);
        
        return newNotification.id;
    }
    
    removeNotification(id) {
        const notifications = this.get('notifications') || [];
        const filtered = notifications.filter(n => n.id !== id);
        this.set('notifications', filtered);
    }
    
    getNotifications() {
        return this.get('notifications') || [];
    }
    
    clearNotifications() {
        this.set('notifications', []);
    }
    
    /**
     * Gestión de filtros
     */
    setFilter(module, filterName, value) {
        this.set(`filters.${module}.${filterName}`, value);
    }
    
    getFilter(module, filterName, defaultValue = null) {
        return this.get(`filters.${module}.${filterName}`) ?? defaultValue;
    }
    
    getModuleFilters(module) {
        return this.get(`filters.${module}`) || {};
    }
    
    clearModuleFilters(module) {
        this.set(`filters.${module}`, {});
    }
    
    /**
     * Gestión de UI
     */
    setTheme(theme) {
        this.set('ui.theme', theme);
        document.body.className = document.body.className.replace(/theme-\w+/, '') + ` theme-${theme}`;
    }
    
    getTheme() {
        return this.get('ui.theme');
    }
    
    toggleSidebar() {
        const collapsed = !this.get('ui.sidebarCollapsed');
        this.set('ui.sidebarCollapsed', collapsed);
        return collapsed;
    }
    
    isSidebarCollapsed() {
        return this.get('ui.sidebarCollapsed');
    }
    
    setLanguage(language) {
        this.set('ui.language', language);
    }
    
    getLanguage() {
        return this.get('ui.language');
    }
    
    /**
     * Utilidades de debugging
     */
    debug() {
        console.log('Estado actual:', this.state);
        console.log('Listeners:', Object.keys(this.listeners));
    }
    
    /**
     * Exportar estado para debugging
     */
    export() {
        return JSON.stringify(this.state, null, 2);
    }
    
    /**
     * Importar estado (para debugging)
     */
    import(stateJson) {
        try {
            const importedState = JSON.parse(stateJson);
            this.state = { ...this.state, ...importedState };
            this.saveToStorage();
            this.notifyListeners('*', this.state);
        } catch (error) {
            console.error('Error importando estado:', error);
        }
    }
}

// Crear instancia global
window.StateManager = StateManager;
window.appState = new StateManager();

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StateManager;
}