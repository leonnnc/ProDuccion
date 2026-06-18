// Módulo de Gestión de Tareas

class TasksModule {
    constructor() {
        this.tasks = [];
        this.projects = [];
        this.users = [];
        this.currentTask = null;
        this.viewMode = 'board'; // 'board' o 'list'
        this.filters = {
            estado: '',
            prioridad: '',
            asignado: '',
            proyecto: '',
            search: ''
        };
    }

    render() {
        return `
            <div class="module tasks-module">
                <div class="tasks-header">
                    <h1 class="tasks-title">Gestión de Tareas</h1>
                    <button class="btn btn-success" onclick="tasksModule.showCreateModal()">
                        Nueva Tarea
                    </button>
                </div>

                <div class="task-filters">
                    <div class="filter-group">
                        <label class="form-label">Buscar:</label>
                        <input type="text" id="searchTasks" class="form-input" 
                               placeholder="Buscar por título..." 
                               onkeyup="tasksModule.applyFilters()">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Estado:</label>
                        <select id="filterEstado" class="form-select" onchange="tasksModule.applyFilters()">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_progreso">En Progreso</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Prioridad:</label>
                        <select id="filterPrioridad" class="form-select" onchange="tasksModule.applyFilters()">
                            <option value="">Todas las prioridades</option>
                            <option value="alta">Alta</option>
                            <option value="media">Media</option>
                            <option value="baja">Baja</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Asignado a:</label>
                        <select id="filterAsignado" class="form-select" onchange="tasksModule.applyFilters()">
                            <option value="">Todos los usuarios</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Proyecto:</label>
                        <select id="filterProyecto" class="form-select" onchange="tasksModule.applyFilters()">
                            <option value="">Todos los proyectos</option>
                        </select>
                    </div>
                </div>

                <div class="view-toggle">
                    <button class="toggle-btn active" onclick="tasksModule.setViewMode('board')">
                        Vista Tablero
                    </button>
                    <button class="toggle-btn" onclick="tasksModule.setViewMode('list')">
                        Vista Lista
                    </button>
                </div>

                <!-- Vista de tablero Kanban -->
                <div id="taskBoard" class="task-board">
                    <div class="task-column">
                        <div class="column-header column-pendiente">
                            Pendientes
                        </div>
                        <div id="pendienteTasks" class="task-column-content">
                            <div class="loading">Cargando tareas...</div>
                        </div>
                    </div>
                    <div class="task-column">
                        <div class="column-header column-en_progreso">
                            En Progreso
                        </div>
                        <div id="en_progresoTasks" class="task-column-content">
                            <div class="loading">Cargando tareas...</div>
                        </div>
                    </div>
                    <div class="task-column">
                        <div class="column-header column-completada">
                            Completadas
                        </div>
                        <div id="completadaTasks" class="task-column-content">
                            <div class="loading">Cargando tareas...</div>
                        </div>
                    </div>
                    <div class="task-column">
                        <div class="column-header column-cancelada">
                            Canceladas
                        </div>
                        <div id="canceladaTasks" class="task-column-content">
                            <div class="loading">Cargando tareas...</div>
                        </div>
                    </div>
                </div>

                <!-- Vista de lista -->
                <div id="taskList" class="task-list-view">
                    <div class="loading">Cargando tareas...</div>
                </div>

                <!-- Modal para crear/editar tarea -->
                <div id="taskModal" class="project-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="taskModalTitle">Nueva Tarea</h2>
                            <button class="close-modal" onclick="tasksModule.closeModal()">&times;</button>
                        </div>
                        <form id="taskForm">
                            <input type="hidden" id="taskId">
                            <div class="form-group">
                                <label class="form-label" for="taskTitle">Título de la Tarea:</label>
                                <input type="text" id="taskTitle" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="taskDescription">Descripción:</label>
                                <textarea id="taskDescription" class="form-textarea" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="taskProject">Proyecto:</label>
                                <select id="taskProject" class="form-select" required>
                                    <option value="">Seleccionar proyecto</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="taskAssignee">Asignado a:</label>
                                <select id="taskAssignee" class="form-select" required>
                                    <option value="">Seleccionar usuario</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="taskStatus">Estado:</label>
                                <select id="taskStatus" class="form-select" required>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_progreso">En Progreso</option>
                                    <option value="completada">Completada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="taskPriority">Prioridad:</label>
                                <select id="taskPriority" class="form-select" required>
                                    <option value="baja">Baja</option>
                                    <option value="media">Media</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="taskDueDate">Fecha de Vencimiento:</label>
                                <input type="date" id="taskDueDate" class="form-input">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Guardar Tarea</button>
                                <button type="button" class="btn" onclick="tasksModule.closeModal()">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    async init() {
        // Hacer disponible globalmente
        window.tasksModule = this;
        window.TasksModule = this;
        
        await this.loadTasks();
        await this.loadProjects();
        await this.loadUsers();
        this.populateSelects();
        this.renderTasks();
        this.setupEventListeners();
    }

    async loadTasks() {
        try {
            const response = await fetch('/api/tasks');
            if (response.ok) {
                this.tasks = await response.json();
            } else {
                console.error('Error cargando tareas');
                this.tasks = [];
            }
        } catch (error) {
            console.error('Error:', error);
            this.tasks = [];
        }
    }

    async loadProjects() {
        try {
            const response = await fetch('/api/projects');
            if (response.ok) {
                this.projects = await response.json();
            } else {
                this.projects = [];
            }
        } catch (error) {
            console.error('Error cargando proyectos:', error);
            this.projects = [];
        }
    }

    async loadUsers() {
        try {
            const response = await fetch('/api/users');
            if (response.ok) {
                this.users = await response.json();
            } else {
                this.users = [app.currentUser];
            }
        } catch (error) {
            console.error('Error cargando usuarios:', error);
            this.users = [app.currentUser];
        }
    }

    populateSelects() {
        // Poblar select de usuarios
        const userSelects = ['filterAsignado', 'taskAssignee'];
        userSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                // Limpiar opciones existentes (excepto la primera)
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }
                
                this.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.nombre;
                    select.appendChild(option);
                });
            }
        });

        // Poblar select de proyectos
        const projectSelects = ['filterProyecto', 'taskProject'];
        projectSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                // Limpiar opciones existentes (excepto la primera)
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }
                
                this.projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.nombre;
                    select.appendChild(option);
                });
            }
        });
    }

    renderTasks() {
        if (this.viewMode === 'board') {
            this.renderBoardView();
        } else {
            this.renderListView();
        }
    }

    renderBoardView() {
        const filteredTasks = this.getFilteredTasks();
        const tasksByStatus = {
            pendiente: [],
            en_progreso: [],
            completada: [],
            cancelada: []
        };

        // Agrupar tareas por estado
        filteredTasks.forEach(task => {
            if (tasksByStatus[task.estado]) {
                tasksByStatus[task.estado].push(task);
            }
        });

        // Renderizar cada columna
        Object.keys(tasksByStatus).forEach(estado => {
            const container = document.getElementById(`${estado}Tasks`);
            if (container) {
                if (tasksByStatus[estado].length === 0) {
                    container.innerHTML = '<div class="empty-column">No hay tareas</div>';
                } else {
                    const tasksHtml = tasksByStatus[estado].map(task => this.renderTaskCard(task)).join('');
                    container.innerHTML = tasksHtml;
                }
            }
        });
    }

    renderListView() {
        const container = document.getElementById('taskList');
        const filteredTasks = this.getFilteredTasks();
        
        if (filteredTasks.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <h3>No hay tareas</h3>
                    <p>Comienza creando tu primera tarea</p>
                    <button class="btn btn-success" onclick="tasksModule.showCreateModal()">
                        Crear Primera Tarea
                    </button>
                </div>
            `;
            return;
        }

        const tasksHtml = filteredTasks.map(task => this.renderTaskListItem(task)).join('');
        container.innerHTML = tasksHtml;
    }

    renderTaskCard(task) {
        const project = this.projects.find(p => p.id == task.proyecto_id);
        const assignee = this.users.find(u => u.id == task.asignado_a);
        
        return `
            <div class="task-card priority-${task.prioridad}" onclick="tasksModule.editTask(${task.id})">
                <div class="task-title">${task.titulo}</div>
                <div class="task-description">${task.descripcion || 'Sin descripción'}</div>
                <div class="task-meta">
                    <div>
                        <span class="task-priority priority-${task.prioridad}">${task.prioridad.toUpperCase()}</span>
                        ${task.fecha_vencimiento ? `<div class="task-due-date ${this.getDueDateClass(task.fecha_vencimiento)}">
                            ${utils.formatDate(task.fecha_vencimiento)}
                        </div>` : ''}
                    </div>
                    <div>
                        <div><strong>Proyecto:</strong> ${project ? project.nombre : 'N/A'}</div>
                        <div><strong>Asignado:</strong> ${assignee ? assignee.nombre : 'N/A'}</div>
                    </div>
                </div>
            </div>
        `;
    }

    renderTaskListItem(task) {
        const project = this.projects.find(p => p.id == task.proyecto_id);
        const assignee = this.users.find(u => u.id == task.asignado_a);
        
        return `
            <div class="task-list-item">
                <div class="task-info">
                    <h4>${task.titulo}</h4>
                    <p>${task.descripcion || 'Sin descripción'}</p>
                    <div class="task-meta">
                        <span class="task-priority priority-${task.prioridad}">${task.prioridad.toUpperCase()}</span>
                        <span class="status-badge status-${task.estado}">${this.getStatusText(task.estado)}</span>
                        <span><strong>Proyecto:</strong> ${project ? project.nombre : 'N/A'}</span>
                        <span><strong>Asignado:</strong> ${assignee ? assignee.nombre : 'N/A'}</span>
                        ${task.fecha_vencimiento ? `<span class="task-due-date ${this.getDueDateClass(task.fecha_vencimiento)}">
                            Vence: ${utils.formatDate(task.fecha_vencimiento)}
                        </span>` : ''}
                    </div>
                </div>
                <div class="task-actions">
                    <button class="btn" onclick="tasksModule.editTask(${task.id})">Editar</button>
                    <button class="btn btn-danger" onclick="tasksModule.deleteTask(${task.id})">Eliminar</button>
                </div>
            </div>
        `;
    }

    getFilteredTasks() {
        return this.tasks.filter(task => {
            const matchesSearch = !this.filters.search || 
                task.titulo.toLowerCase().includes(this.filters.search.toLowerCase());
            const matchesEstado = !this.filters.estado || task.estado === this.filters.estado;
            const matchesPrioridad = !this.filters.prioridad || task.prioridad === this.filters.prioridad;
            const matchesAsignado = !this.filters.asignado || task.asignado_a == this.filters.asignado;
            const matchesProyecto = !this.filters.proyecto || task.proyecto_id == this.filters.proyecto;
            
            return matchesSearch && matchesEstado && matchesPrioridad && matchesAsignado && matchesProyecto;
        });
    }

    applyFilters() {
        this.filters.search = document.getElementById('searchTasks').value;
        this.filters.estado = document.getElementById('filterEstado').value;
        this.filters.prioridad = document.getElementById('filterPrioridad').value;
        this.filters.asignado = document.getElementById('filterAsignado').value;
        this.filters.proyecto = document.getElementById('filterProyecto').value;
        this.renderTasks();
    }

    setViewMode(mode) {
        this.viewMode = mode;
        
        // Actualizar botones
        document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Mostrar/ocultar vistas
        const boardView = document.getElementById('taskBoard');
        const listView = document.getElementById('taskList');
        
        if (mode === 'board') {
            boardView.style.display = 'grid';
            listView.classList.remove('active');
        } else {
            boardView.style.display = 'none';
            listView.classList.add('active');
        }
        
        this.renderTasks();
    }

    showCreateModal() {
        this.currentTask = null;
        document.getElementById('taskModalTitle').textContent = 'Nueva Tarea';
        document.getElementById('taskForm').reset();
        document.getElementById('taskId').value = '';
        document.getElementById('taskModal').style.display = 'block';
    }

    async editTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) return;

        this.currentTask = task;
        document.getElementById('taskModalTitle').textContent = 'Editar Tarea';
        document.getElementById('taskId').value = task.id;
        document.getElementById('taskTitle').value = task.titulo;
        document.getElementById('taskDescription').value = task.descripcion || '';
        document.getElementById('taskProject').value = task.proyecto_id;
        document.getElementById('taskAssignee').value = task.asignado_a;
        document.getElementById('taskStatus').value = task.estado;
        document.getElementById('taskPriority').value = task.prioridad;
        document.getElementById('taskDueDate').value = task.fecha_vencimiento || '';
        document.getElementById('taskModal').style.display = 'block';
    }

    async deleteTask(taskId) {
        if (!utils.confirmAction('¿Estás seguro de que quieres eliminar esta tarea?')) {
            return;
        }

        try {
            const response = await fetch(`/api/tasks/${taskId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                utils.showNotification('Tarea eliminada correctamente', 'success');
                await this.loadTasks();
                this.renderTasks();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al eliminar tarea', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }

    closeModal() {
        document.getElementById('taskModal').style.display = 'none';
        this.currentTask = null;
    }

    setupEventListeners() {
        const form = document.getElementById('taskForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveTask();
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('taskModal').addEventListener('click', (e) => {
            if (e.target.id === 'taskModal') {
                this.closeModal();
            }
        });
    }

    async saveTask() {
        const formData = {
            titulo: document.getElementById('taskTitle').value,
            descripcion: document.getElementById('taskDescription').value,
            proyecto_id: document.getElementById('taskProject').value,
            asignado_a: document.getElementById('taskAssignee').value,
            estado: document.getElementById('taskStatus').value,
            prioridad: document.getElementById('taskPriority').value,
            fecha_vencimiento: document.getElementById('taskDueDate').value || null
        };

        const taskId = document.getElementById('taskId').value;
        const isEdit = taskId !== '';

        try {
            const url = isEdit ? `/api/tasks/${taskId}` : '/api/tasks';
            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                const message = isEdit ? 'Tarea actualizada correctamente' : 'Tarea creada correctamente';
                utils.showNotification(message, 'success');
                this.closeModal();
                await this.loadTasks();
                this.renderTasks();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al guardar tarea', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }

    filterByProject(projectId) {
        document.getElementById('filterProyecto').value = projectId;
        this.applyFilters();
    }

    getStatusText(status) {
        const statusMap = {
            'pendiente': 'Pendiente',
            'en_progreso': 'En Progreso',
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
        if (diffDays <= 3) return 'due-upcoming';
        return '';
    }
}

// Estilos adicionales específicos para tareas
const tasksAdditionalStyles = `
<style>
.empty-column {
    text-align: center;
    padding: 2rem;
    color: #666;
    font-style: italic;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-state h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #666;
    margin-bottom: 2rem;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', tasksAdditionalStyles);