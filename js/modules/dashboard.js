// Módulo Dashboard - Página principal con resumen

class DashboardModule {
    constructor() {
        this.data = {
            projects: [],
            tasks: [],
            calendar: []
        };
    }

    render() {
        return `
            <div class="module dashboard-module">
                <h1 class="dashboard-title">Dashboard - Sistema de Gestión de Producción</h1>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3 id="totalProjects">0</h3>
                            <p>Proyectos Activos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3 id="totalTasks">0</h3>
                            <p>Tareas Pendientes</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3 id="upcomingEvents">0</h3>
                            <p>Eventos Esta Semana</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3 id="activeUsers">1</h3>
                            <p>Usuarios Activos</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-content">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Proyectos Recientes</h2>
                            <button class="btn" onclick="app.loadModule('projects')">Ver Todos</button>
                        </div>
                        <div id="recentProjects" class="recent-items">
                            <div class="loading">Cargando proyectos...</div>
                        </div>
                    </div>

                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Mis Tareas</h2>
                            <button class="btn" onclick="app.loadModule('tasks')">Ver Todas</button>
                        </div>
                        <div id="myTasks" class="recent-items">
                            <div class="loading">Cargando tareas...</div>
                        </div>
                    </div>

                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Agenda de Esta Semana</h2>
                            <button class="btn" onclick="app.loadModule('calendar')">Ver Calendario</button>
                        </div>
                        <div id="weeklyCalendar" class="recent-items">
                            <div class="loading">Cargando agenda...</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async init() {
        await this.loadDashboardData();
        this.updateStats();
        this.renderRecentProjects();
        this.renderMyTasks();
        this.renderWeeklyCalendar();
    }

    async loadDashboardData() {
        try {
            // Cargar proyectos
            const projectsResponse = await fetch('/api/projects?limit=5');
            if (projectsResponse.ok) {
                this.data.projects = await projectsResponse.json();
            }

            // Cargar tareas del usuario actual
            const tasksResponse = await fetch(`/api/tasks/user/${app.currentUser.id}?limit=5`);
            if (tasksResponse.ok) {
                this.data.tasks = await tasksResponse.json();
            }

            // Cargar eventos de la semana
            const today = new Date();
            const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
            const weekEnd = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            
            const calendarResponse = await fetch(`/api/calendar/week?start=${weekStart.toISOString().split('T')[0]}&end=${weekEnd.toISOString().split('T')[0]}`);
            if (calendarResponse.ok) {
                this.data.calendar = await calendarResponse.json();
            }
        } catch (error) {
            console.error('Error cargando datos del dashboard:', error);
        }
    }

    updateStats() {
        // Actualizar estadísticas
        document.getElementById('totalProjects').textContent = this.data.projects.length;
        
        const pendingTasks = this.data.tasks.filter(task => task.estado === 'pendiente' || task.estado === 'en_progreso');
        document.getElementById('totalTasks').textContent = pendingTasks.length;
        
        document.getElementById('upcomingEvents').textContent = this.data.calendar.length;
        
        // El número de usuarios activos se mantiene como 1 por ahora
        document.getElementById('activeUsers').textContent = '1';
    }

    renderRecentProjects() {
        const container = document.getElementById('recentProjects');
        
        if (this.data.projects.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No hay proyectos recientes</p>
                    <button class="btn" onclick="app.loadModule('projects')">Crear Primer Proyecto</button>
                </div>
            `;
            return;
        }

        const projectsHtml = this.data.projects.map(project => `
            <div class="dashboard-item">
                <div class="item-header">
                    <h4>${project.nombre}</h4>
                    <span class="status-badge status-${project.estado}">${this.getStatusText(project.estado)}</span>
                </div>
                <p class="item-description">${project.descripcion || 'Sin descripción'}</p>
                <div class="item-meta">
                    <span>Creado: ${utils.formatDate(project.fecha_creacion)}</span>
                    ${project.fecha_inicio ? `<span>Inicio: ${utils.formatDate(project.fecha_inicio)}</span>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = projectsHtml;
    }

    renderMyTasks() {
        const container = document.getElementById('myTasks');
        
        if (this.data.tasks.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No tienes tareas asignadas</p>
                    <button class="btn" onclick="app.loadModule('tasks')">Ver Todas las Tareas</button>
                </div>
            `;
            return;
        }

        const tasksHtml = this.data.tasks.map(task => `
            <div class="dashboard-item">
                <div class="item-header">
                    <h4>${task.titulo}</h4>
                    <span class="priority-badge priority-${task.prioridad}">${task.prioridad.toUpperCase()}</span>
                </div>
                <p class="item-description">${task.descripcion || 'Sin descripción'}</p>
                <div class="item-meta">
                    <span class="status-badge status-${task.estado}">${this.getStatusText(task.estado)}</span>
                    ${task.fecha_vencimiento ? `<span class="due-date ${this.getDueDateClass(task.fecha_vencimiento)}">
                        Vence: ${utils.formatDate(task.fecha_vencimiento)}
                    </span>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = tasksHtml;
    }

    renderWeeklyCalendar() {
        const container = document.getElementById('weeklyCalendar');
        
        if (this.data.calendar.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No hay eventos programados esta semana</p>
                    <button class="btn" onclick="app.loadModule('calendar')">Programar Eventos</button>
                </div>
            `;
            return;
        }

        const calendarHtml = this.data.calendar.map(event => `
            <div class="dashboard-item">
                <div class="item-header">
                    <h4>${utils.formatDate(event.fecha)}</h4>
                    <span class="segment-badge segment-${event.segmento}">${event.segmento.toUpperCase()}</span>
                </div>
                <p class="item-description">${event.notas || 'Día disponible para servicios'}</p>
                <div class="item-meta">
                    <span>Segmento: ${event.segmento}</span>
                </div>
            </div>
        `).join('');

        container.innerHTML = calendarHtml;
    }

    getStatusText(status) {
        const statusMap = {
            'planificacion': 'Planificación',
            'en_progreso': 'En Progreso',
            'completado': 'Completado',
            'cancelado': 'Cancelado',
            'pendiente': 'Pendiente',
            'completada': 'Completada',
            'cancelada': 'Cancelada'
        };
        return statusMap[status] || status;
    }

    getDueDateClass(dueDate) {
        const today = new Date();
        const due = new Date(dueDate);
        const diffTime = due - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) return 'due-overdue';
        if (diffDays === 0) return 'due-today';
        if (diffDays <= 3) return 'due-soon';
        return 'due-upcoming';
    }
}

// Agregar estilos específicos del dashboard
const dashboardStyles = `
<style>
.dashboard-title {
    color: #2c3e50;
    margin-bottom: 2rem;
    text-align: center;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-content h3 {
    font-size: 2rem;
    margin: 0;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.dashboard-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.dashboard-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}

.section-header h2 {
    margin: 0;
    color: #2c3e50;
}

.dashboard-item {
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.dashboard-item:last-child {
    margin-bottom: 0;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.item-header h4 {
    margin: 0;
    color: #2c3e50;
}

.item-description {
    color: #666;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.item-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #777;
    flex-wrap: wrap;
}

.status-badge, .priority-badge, .segment-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-planificacion { background: #fff3cd; color: #856404; }
.status-en_progreso { background: #cce5ff; color: #004085; }
.status-completado, .status-completada { background: #d4edda; color: #155724; }
.status-cancelado, .status-cancelada { background: #f8d7da; color: #721c24; }
.status-pendiente { background: #f8f9fa; color: #495057; }

.priority-alta { background: #f8d7da; color: #721c24; }
.priority-media { background: #fff3cd; color: #856404; }
.priority-baja { background: #d4edda; color: #155724; }

.segment-proyectos { background: #e8f5e8; color: #2e7d32; }
.segment-tareas { background: #fff3e0; color: #f57c00; }
.segment-agenda { background: #f3e5f5; color: #7b1fa2; }

.due-overdue { color: #dc3545; font-weight: 600; }
.due-today { color: #ffc107; font-weight: 600; }
.due-soon { color: #fd7e14; font-weight: 600; }
.due-upcoming { color: #28a745; }

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.empty-state p {
    margin-bottom: 1rem;
}

.loading {
    text-align: center;
    padding: 2rem;
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .item-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .item-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>
`;

// Inyectar estilos
document.head.insertAdjacentHTML('beforeend', dashboardStyles);

// Exportar módulo globalmente
const dashboardInstance = new DashboardModule();
window.DashboardModule = dashboardInstance;
window.dashboardModule = dashboardInstance;