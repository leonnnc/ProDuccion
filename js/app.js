// Aplicación principal del Sistema de Gestión de Producción

class App {
    constructor() {
        this.currentUser = null;
        this.currentModule = 'dashboard';
        this.modules = {};
        this.apiBaseUrl = this.detectApiBaseUrl();
        this.init();
    }

    detectApiBaseUrl() {
        // Detectar automáticamente la URL base correcta para la API
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        
        // Si estamos en la raíz del dominio
        if (pathname === '/' || pathname === '/index.html') {
            return origin + '/api';
        }
        
        // Si estamos en un subdirectorio
        const basePath = pathname.replace(/\/[^\/]*$/, '');
        return origin + basePath + '/api';
    }

    getApiUrl(endpoint) {
        const url = this.apiBaseUrl + '/' + endpoint;
        console.log('API URL:', url);
        return url;
    }

    async init() {
        try {
            console.log('Inicializando aplicación...');

            // Verificar si el usuario está autenticado
            await this.checkAuth();

            // Inicializar módulos
            this.initModules();

            // Configurar navegación
            this.setupNavigation();

            // Marcar como cargado
            document.body.classList.add('app-loaded');

            console.log('Aplicación inicializada correctamente');

        } catch (error) {
            console.error('Error inicializando aplicación:', error);
            this.showError('Error al cargar la aplicación. Por favor, recarga la página.');
        }
    }

    async checkAuth() {
        try {
            const response = await this.fetchWithFallback('auth/user');
            if (response && response.ok) {
                const user = await response.json();
                this.currentUser = user;
                this.showMainApp();
            } else {
                this.currentUser = null;
                this.showLogin();
            }
        } catch (error) {
            console.error('Error verificando autenticación:', error);
            this.currentUser = null;
            this.showLogin();
        }
    }

    async fetchWithFallback(endpoint, options = {}) {
        // Lista de URLs posibles para probar
        const possibleUrls = [
            this.getApiUrl(endpoint),
            `./api/${endpoint}`,
            `/api/${endpoint}`,
            `api/${endpoint}`
        ];

        for (let url of possibleUrls) {
            try {
                console.log(`Probando URL: ${url}`);
                const response = await fetch(url, options);
                
                // Si la respuesta es exitosa o es un error de autenticación (401), la URL funciona
                if (response.ok || response.status === 401) {
                    console.log(`✅ URL funcionando: ${url}`);
                    // Actualizar la URL base para futuras peticiones
                    this.apiBaseUrl = url.replace(`/${endpoint}`, '');
                    return response;
                }
            } catch (error) {
                console.log(`❌ Error con URL ${url}:`, error.message);
                continue;
            }
        }
        
        throw new Error('No se pudo conectar con la API. Verifica que el servidor esté funcionando.');
    }

    showLogin() {
        document.body.classList.remove('app-loaded');
        document.body.innerHTML = `
            <div class="login-container">
                <div class="welcome-message">
                    <h1>Sistema de Gestión de Producción</h1>
                    <p>Gestiona proyectos, tareas y agenda de manera eficiente</p>
                </div>
                
                <div class="auth-section">
                    <form class="login-form" id="loginForm">
                        <h2 class="login-title">Iniciar Sesión</h2>
                        <div id="loginMessage" class="message-container"></div>
                        
                        <div class="form-group">
                            <label class="form-label" for="loginEmail">Email:</label>
                            <input type="email" id="loginEmail" class="form-input" required 
                                   placeholder="tu@email.com" autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="loginPassword">Contraseña:</label>
                            <input type="password" id="loginPassword" class="form-input" required 
                                   placeholder="Tu contraseña" autocomplete="current-password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Iniciar Sesión
                        </button>
                        
                        <div class="auth-options">
                            <p>¿No tienes cuenta? <a href="#" onclick="app.showRegisterModal()" class="auth-link">Regístrate aquí</a></p>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal de Registro Simple -->
            <div id="registerModal" class="register-modal">
                <div class="register-modal-content">
                    <div class="modal-header">
                        <h2>Crear Nueva Cuenta</h2>
                        <button class="close-modal" onclick="app.closeRegisterModal()">&times;</button>
                    </div>
                    
                    <form id="registerForm" class="register-form">
                        <div id="registerMessage"></div>
                        
                        <div class="form-group">
                            <label class="form-label" for="registerName">Nombre Completo:</label>
                            <input type="text" id="registerName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="registerEmail">Email:</label>
                            <input type="email" id="registerEmail" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="registerPassword">Contraseña:</label>
                            <input type="password" id="registerPassword" class="form-input" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="registerConfirmPassword">Confirmar Contraseña:</label>
                            <input type="password" id="registerConfirmPassword" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="registerGrupo">Grupo:</label>
                            <select id="registerGrupo" class="form-select" required>
                                <option value="">Seleccionar grupo...</option>
                                <option value="3">Users - Usuarios regulares</option>
                                <option value="2">Staff - Personal de gestión</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="registerArea">Área:</label>
                            <select id="registerArea" class="form-select" required>
                                <option value="">Seleccionar área...</option>
                                <option value="1">Visuales</option>
                                <option value="2">Filmmakers</option>
                                <option value="3">Fotografía</option>
                                <option value="4">Coordinación</option>
                                <option value="5">Switchers</option>
                                <option value="6">Streaming</option>
                                <option value="7">Luces</option>
                                <option value="8">Diseño</option>
                                <option value="9">Edición</option>
                                <option value="10">Protocolos</option>
                                <option value="11">Cámaras</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Registrarse</button>
                            <button type="button" class="btn" onclick="app.closeRegisterModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        // Configurar eventos
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });
    }

    async handleLogin() {
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        const messageDiv = document.getElementById('loginMessage');

        try {
            const response = await this.fetchWithFallback('auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const result = await response.json();

            if (response.ok) {
                this.currentUser = result.user;
                messageDiv.innerHTML = '<div class="login-success">Iniciando sesión...</div>';
                setTimeout(() => {
                    this.showMainApp();
                }, 1000);
            } else {
                messageDiv.innerHTML = `<div class="login-error">${result.message}</div>`;
            }
        } catch (error) {
            messageDiv.innerHTML = '<div class="login-error">Error de conexión. Intenta nuevamente.</div>';
        }
    }

    async handleRegister() {
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('registerConfirmPassword').value;
        const grupoId = document.getElementById('registerGrupo').value;
        const areaId = document.getElementById('registerArea').value;
        const messageDiv = document.getElementById('registerMessage');

        // Validaciones básicas
        if (!name || !email || !password || !confirmPassword || !grupoId || !areaId) {
            messageDiv.innerHTML = '<div class="login-error">Todos los campos son requeridos</div>';
            return;
        }

        if (password !== confirmPassword) {
            messageDiv.innerHTML = '<div class="login-error">Las contraseñas no coinciden</div>';
            return;
        }

        if (password.length < 6) {
            messageDiv.innerHTML = '<div class="login-error">La contraseña debe tener al menos 6 caracteres</div>';
            return;
        }

        try {
            console.log('Enviando datos de registro:', {
                nombre: name,
                email: email,
                grupo_id: grupoId,
                area_id: areaId
            });

            const response = await this.fetchWithFallback('auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    nombre: name,
                    email: email,
                    password: password,
                    grupo_id: parseInt(grupoId),
                    area_id: parseInt(areaId)
                })
            });

            console.log('Respuesta del servidor:', response.status, response.statusText);

            const result = await response.json();
            console.log('Datos de respuesta:', result);

            if (response.ok) {
                messageDiv.innerHTML = '<div class="login-success">Cuenta creada exitosamente. Iniciando sesión...</div>';
                setTimeout(() => {
                    this.currentUser = result.user;
                    this.closeRegisterModal();
                    this.showMainApp();
                }, 1500);
            } else {
                messageDiv.innerHTML = `<div class="login-error">${result.message}</div>`;
            }
        } catch (error) {
            console.error('Error en registro:', error);
            messageDiv.innerHTML = `<div class="login-error">Error de conexión: ${error.message}</div>`;
        }
    }

    showMainApp() {
        document.body.classList.add('app-loaded');
        document.body.innerHTML = `
            <div class="app-container">
                <!-- Barra de navegación superior -->
                <nav class="main-nav">
                    <div class="nav-brand">
                        <span class="nav-title">Gestión de Producción</span>
                    </div>
                    
                    <div class="nav-menu">
                        <button class="nav-item ${this.currentModule === 'dashboard' ? 'active' : ''}" 
                                onclick="app.loadModule('dashboard')">
                            <span class="nav-icon">🏠</span>
                            <span class="nav-text">Dashboard</span>
                        </button>
                        <button class="nav-item ${this.currentModule === 'projects' ? 'active' : ''}" 
                                onclick="app.loadModule('projects')">
                            <span class="nav-icon">📋</span>
                            <span class="nav-text">Proyectos</span>
                        </button>
                        <button class="nav-item ${this.currentModule === 'tasks' ? 'active' : ''}" 
                                onclick="app.loadModule('tasks')">
                            <span class="nav-icon">✅</span>
                            <span class="nav-text">Tareas</span>
                        </button>
                        <button class="nav-item ${this.currentModule === 'calendar' ? 'active' : ''}" 
                                onclick="app.loadModule('calendar')">
                            <span class="nav-icon">📅</span>
                            <span class="nav-text">Agenda</span>
                        </button>
                    </div>
                    
                    <div class="nav-user">
                        <div class="user-info">
                            <span class="user-name">${this.currentUser.nombre}</span>
                            <span class="user-area">${this.currentUser.area_nombre || 'Sin área'}</span>
                        </div>
                        <button class="btn btn-sm" onclick="app.logout()">Cerrar Sesión</button>
                    </div>
                </nav>
                
                <!-- Contenido principal -->
                <main class="main-content" id="mainContent">
                    <div class="loading-content">
                        <div class="loading-spinner"></div>
                        <p>Cargando ${this.getModuleName(this.currentModule)}...</p>
                    </div>
                </main>
            </div>
        `;

        // Cargar módulo inicial
        this.loadModule(this.currentModule);
    }

    initModules() {
        this.modules = {
            dashboard: { name: 'Dashboard', icon: '🏠' },
            projects: { name: 'Proyectos', icon: '📋' },
            tasks: { name: 'Tareas', icon: '✅' },
            calendar: { name: 'Agenda', icon: '📅' }
        };
    }

    setupNavigation() {
        // Navegación básica con teclado
        document.addEventListener('keydown', (e) => {
            if (e.altKey) {
                switch (e.key) {
                    case '1':
                        e.preventDefault();
                        this.loadModule('dashboard');
                        break;
                    case '2':
                        e.preventDefault();
                        this.loadModule('projects');
                        break;
                    case '3':
                        e.preventDefault();
                        this.loadModule('tasks');
                        break;
                    case '4':
                        e.preventDefault();
                        this.loadModule('calendar');
                        break;
                }
            }
        });
    }

    async loadModule(moduleName) {
        if (!this.modules[moduleName]) {
            console.error('Módulo no encontrado:', moduleName);
            return;
        }

        // Actualizar navegación activa
        this.updateActiveNavigation(moduleName);

        // Mostrar loading
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <p>Cargando ${this.getModuleName(moduleName)}...</p>
                </div>
            `;
        }

        try {
            let content = '';

            switch (moduleName) {
                case 'dashboard':
                    content = await this.loadDashboard();
                    break;
                case 'projects':
                    content = await this.loadProjects();
                    break;
                case 'tasks':
                    content = await this.loadTasks();
                    break;
                case 'calendar':
                    content = await this.loadCalendar();
                    break;
                default:
                    content = '<div class="error-content">Módulo no implementado</div>';
            }

            if (mainContent) {
                mainContent.innerHTML = content;
            }

            this.currentModule = moduleName;

        } catch (error) {
            console.error('Error cargando módulo:', error);
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="error-content">
                        <h3>Error al cargar el módulo</h3>
                        <p>Ha ocurrido un error al cargar ${this.getModuleName(moduleName)}.</p>
                        <button onclick="app.loadModule('${moduleName}')" class="btn">Reintentar</button>
                    </div>
                `;
            }
        }
    }

    updateActiveNavigation(moduleName) {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        const activeItem = document.querySelector(`[onclick="app.loadModule('${moduleName}')"]`);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }

    async loadDashboard() {
        return `
            <div class="dashboard-container">
                <h1>Dashboard</h1>
                <div class="dashboard-widgets">
                    <div class="widget">
                        <h3>Proyectos Activos</h3>
                        <div class="widget-content">
                            <span class="widget-number" id="projectsCount">-</span>
                            <span class="widget-label">En progreso</span>
                        </div>
                    </div>
                    <div class="widget">
                        <h3>Tareas Pendientes</h3>
                        <div class="widget-content">
                            <span class="widget-number" id="tasksCount">-</span>
                            <span class="widget-label">Por completar</span>
                        </div>
                    </div>
                    <div class="widget">
                        <h3>Eventos Hoy</h3>
                        <div class="widget-content">
                            <span class="widget-number" id="eventsCount">-</span>
                            <span class="widget-label">Programados</span>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-actions">
                    <button class="btn" onclick="app.loadModule('projects')">Ver Proyectos</button>
                    <button class="btn" onclick="app.loadModule('tasks')">Ver Tareas</button>
                    <button class="btn" onclick="app.loadModule('calendar')">Ver Agenda</button>
                </div>
            </div>
        `;
    }

    async loadProjects() {
        if (typeof ProjectsModule !== 'undefined' && ProjectsModule.render) {
            return ProjectsModule.render();
        }

        return `
            <div class="projects-container">
                <h1>Gestión de Proyectos</h1>
                <div class="module-placeholder">
                    <p>Módulo de proyectos cargando...</p>
                    <button class="btn" onclick="location.reload()">Recargar</button>
                </div>
            </div>
        `;
    }

    async loadTasks() {
        if (typeof TasksModule !== 'undefined' && TasksModule.render) {
            return TasksModule.render();
        }

        return `
            <div class="tasks-container">
                <h1>Gestión de Tareas</h1>
                <div class="module-placeholder">
                    <p>Módulo de tareas cargando...</p>
                    <button class="btn" onclick="location.reload()">Recargar</button>
                </div>
            </div>
        `;
    }

    async loadCalendar() {
        if (typeof CalendarModule !== 'undefined' && CalendarModule.render) {
            return CalendarModule.render();
        }

        return `
            <div class="calendar-container">
                <h1>Agenda Mensual</h1>
                <div class="module-placeholder">
                    <p>Módulo de calendario cargando...</p>
                    <button class="btn" onclick="location.reload()">Recargar</button>
                </div>
            </div>
        `;
    }

    getModuleName(moduleName) {
        const names = {
            dashboard: 'Dashboard',
            projects: 'Proyectos',
            tasks: 'Tareas',
            calendar: 'Agenda'
        };
        return names[moduleName] || moduleName;
    }

    showRegisterModal() {
        document.getElementById('registerModal').style.display = 'block';
    }

    closeRegisterModal() {
        document.getElementById('registerModal').style.display = 'none';
        document.getElementById('registerForm').reset();
    }

    async logout() {
        try {
            await this.fetchWithFallback('auth/logout', {
                method: 'POST'
            });

            this.currentUser = null;
            this.showLogin();
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
            this.currentUser = null;
            this.showLogin();
        }
    }

    showError(message) {
        document.body.innerHTML = `
            <div class="error-screen">
                <div class="error-content">
                    <h1>Error</h1>
                    <p>${message}</p>
                    <button onclick="location.reload()" class="btn btn-primary">Recargar Página</button>
                    <a href="diagnostico.php" class="btn">Ver Diagnóstico</a>
                </div>
            </div>
            <style>
                .error-screen {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: #2c3e50;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                .error-content {
                    text-align: center;
                    color: white;
                    max-width: 500px;
                    padding: 2rem;
                }
                .error-content h1 {
                    color: #e74c3c;
                    margin-bottom: 1rem;
                }
                .error-content p {
                    margin-bottom: 2rem;
                    font-size: 1.1rem;
                }
                .btn {
                    background: #3498db;
                    color: white;
                    border: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 1rem;
                    margin: 0.5rem;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn:hover {
                    background: #2980b9;
                }
            </style>
        `;
    }

    showNotification(message, type = 'info') {
        // Crear notificación simple
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            max-width: 300px;
            border-left: 4px solid ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : type === 'warning' ? '#f39c12' : '#3498db'};
        `;

        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; float: right; cursor: pointer; font-size: 18px; margin-left: 10px;">&times;</button>
        `;

        document.body.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Manejar errores globales
window.addEventListener('error', (e) => {
    console.error('Error global:', e.error);
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Promesa rechazada:', e.reason);
});