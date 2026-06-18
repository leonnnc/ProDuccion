// Módulo de Gestión de Proyectos

class ProjectsModule {
    constructor() {
        this.projects = [];
        this.users = [];
        this.currentProject = null;
        this.filters = {
            estado: '',
            responsable: '',
            search: ''
        };
    }

    render() {
        return `
            <div class="module projects-module">
                <div class="projects-header">
                    <h1 class="projects-title">Gestión de Proyectos</h1>
                    <div class="header-actions">
                        <button class="btn" onclick="projectsModule.getProjectStatistics()">
                            📊 Estadísticas
                        </button>
                        <button class="btn" onclick="projectsModule.exportProjects()">
                            📥 Exportar
                        </button>
                        <button class="btn btn-success" onclick="projectsModule.showCreateModal()">
                            ➕ Nuevo Proyecto
                        </button>
                    </div>
                </div>

                <div class="project-filters">
                    <div class="filter-group">
                        <label class="form-label">Buscar:</label>
                        <input type="text" id="searchProjects" class="form-input" 
                               placeholder="Buscar por nombre..." 
                               onkeyup="projectsModule.applyFilters()">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Estado:</label>
                        <select id="filterEstado" class="form-select" onchange="projectsModule.applyFilters()">
                            <option value="">Todos los estados</option>
                            <option value="planificacion">Planificación</option>
                            <option value="en_progreso">En Progreso</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Responsable:</label>
                        <select id="filterResponsable" class="form-select" onchange="projectsModule.applyFilters()">
                            <option value="">Todos los responsables</option>
                        </select>
                    </div>
                </div>

                <div id="projectsList" class="projects-list">
                    <div class="loading">Cargando proyectos...</div>
                </div>

                <!-- Modal para crear/editar proyecto -->
                <div id="projectModal" class="project-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="modalTitle">Nuevo Proyecto</h2>
                            <button class="close-modal" onclick="projectsModule.closeModal()">&times;</button>
                        </div>
                        <form id="projectForm">
                            <input type="hidden" id="projectId">
                            <div class="form-group">
                                <label class="form-label" for="projectName">Nombre del Proyecto:</label>
                                <input type="text" id="projectName" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="projectDescription">Descripción:</label>
                                <textarea id="projectDescription" class="form-textarea" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="projectStatus">Estado:</label>
                                <select id="projectStatus" class="form-select" required>
                                    <option value="planificacion">Planificación</option>
                                    <option value="en_progreso">En Progreso</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="projectResponsible">Responsable:</label>
                                <select id="projectResponsible" class="form-select">
                                    <option value="">Seleccionar responsable</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="projectStartDate">Fecha de Inicio:</label>
                                <input type="date" id="projectStartDate" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="projectEndDate">Fecha de Fin:</label>
                                <input type="date" id="projectEndDate" class="form-input">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Guardar Proyecto</button>
                                <button type="button" class="btn" onclick="projectsModule.closeModal()">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    async init() {
        // Hacer disponible globalmente
        window.projectsModule = this;
        window.ProjectsModule = this;
        
        await this.loadProjects();
        await this.loadUsers();
        this.populateUserSelects();
        this.renderProjects();
        this.setupEventListeners();
    }

    async loadProjects() {
        try {
            const response = await fetch('/api/projects');
            if (response.ok) {
                this.projects = await response.json();
            } else {
                console.error('Error cargando proyectos');
                this.projects = [];
            }
        } catch (error) {
            console.error('Error:', error);
            this.projects = [];
        }
    }

    async loadUsers() {
        try {
            const response = await fetch('/api/users');
            if (response.ok) {
                this.users = await response.json();
            } else {
                this.users = [app.currentUser]; // Al menos el usuario actual
            }
        } catch (error) {
            console.error('Error cargando usuarios:', error);
            this.users = [app.currentUser];
        }
    }

    populateUserSelects() {
        const selects = ['filterResponsable', 'projectResponsible'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                // Limpiar opciones existentes (excepto la primera)
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }
                
                // Agregar usuarios
                this.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.nombre;
                    select.appendChild(option);
                });
            }
        });
    }

    renderProjects() {
        const container = document.getElementById('projectsList');
        
        if (this.projects.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <h3>No hay proyectos</h3>
                    <p>Comienza creando tu primer proyecto</p>
                    <button class="btn btn-success" onclick="projectsModule.showCreateModal()">
                        Crear Primer Proyecto
                    </button>
                </div>
            `;
            return;
        }

        const filteredProjects = this.getFilteredProjects();
        
        if (filteredProjects.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <h3>No se encontraron proyectos</h3>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                </div>
            `;
            return;
        }

        const projectsHtml = filteredProjects.map(project => this.renderProjectCard(project)).join('');
        container.innerHTML = projectsHtml;
    }

    renderProjectCard(project) {
        const responsable = this.users.find(u => u.id == project.responsable_id);
        const responsableName = responsable ? responsable.nombre : 'Sin asignar';
        
        return `
            <div class="project-card" data-project-id="${project.id}">
                <div class="project-header">
                    <div>
                        <h3 class="project-name">${project.nombre}</h3>
                        <span class="project-status status-${project.estado}">
                            ${this.getStatusText(project.estado)}
                        </span>
                    </div>
                    <div class="project-progress">
                        ${project.total_tareas ? `
                            <div class="progress-info">
                                <span>${project.tareas_completadas || 0}/${project.total_tareas} tareas</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${project.total_tareas > 0 ? (project.tareas_completadas / project.total_tareas * 100) : 0}%"></div>
                                </div>
                            </div>
                        ` : '<span class="no-tasks">Sin tareas</span>'}
                    </div>
                </div>
                <p class="project-description">${project.descripcion || 'Sin descripción'}</p>
                <div class="project-meta">
                    <span><strong>Responsable:</strong> ${responsableName}</span>
                    ${project.fecha_inicio ? `<span><strong>Inicio:</strong> ${utils.formatDate(project.fecha_inicio)}</span>` : ''}
                    ${project.fecha_fin ? `<span><strong>Fin:</strong> ${utils.formatDate(project.fecha_fin)}</span>` : ''}
                    <span><strong>Creado:</strong> ${utils.formatDate(project.fecha_creacion)}</span>
                </div>
                <div class="project-actions">
                    <button class="btn btn-sm" onclick="projectsModule.editProject(${project.id})">✏️ Editar</button>
                    <button class="btn btn-sm" onclick="projectsModule.viewProjectTasks(${project.id})">📋 Tareas</button>
                    <button class="btn btn-sm" onclick="projectsModule.duplicateProject(${project.id})">📄 Duplicar</button>
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle">Estado</button>
                        <div class="dropdown-menu">
                            <a href="#" onclick="projectsModule.changeProjectStatus(${project.id}, 'planificacion')">Planificación</a>
                            <a href="#" onclick="projectsModule.changeProjectStatus(${project.id}, 'en_progreso')">En Progreso</a>
                            <a href="#" onclick="projectsModule.changeProjectStatus(${project.id}, 'completado')">Completado</a>
                            <a href="#" onclick="projectsModule.changeProjectStatus(${project.id}, 'cancelado')">Cancelado</a>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="projectsModule.deleteProject(${project.id})">🗑️ Eliminar</button>
                </div>
            </div>
        `;
    }

    getFilteredProjects() {
        return this.projects.filter(project => {
            const matchesSearch = !this.filters.search || 
                project.nombre.toLowerCase().includes(this.filters.search.toLowerCase());
            const matchesEstado = !this.filters.estado || project.estado === this.filters.estado;
            const matchesResponsable = !this.filters.responsable || 
                project.responsable_id == this.filters.responsable;
            
            return matchesSearch && matchesEstado && matchesResponsable;
        });
    }

    applyFilters() {
        this.filters.search = document.getElementById('searchProjects').value;
        this.filters.estado = document.getElementById('filterEstado').value;
        this.filters.responsable = document.getElementById('filterResponsable').value;
        this.renderProjects();
    }

    showCreateModal() {
        this.currentProject = null;
        document.getElementById('modalTitle').textContent = 'Nuevo Proyecto';
        document.getElementById('projectForm').reset();
        document.getElementById('projectId').value = '';
        document.getElementById('projectModal').style.display = 'block';
    }

    async editProject(projectId) {
        const project = this.projects.find(p => p.id === projectId);
        if (!project) return;

        this.currentProject = project;
        document.getElementById('modalTitle').textContent = 'Editar Proyecto';
        document.getElementById('projectId').value = project.id;
        document.getElementById('projectName').value = project.nombre;
        document.getElementById('projectDescription').value = project.descripcion || '';
        document.getElementById('projectStatus').value = project.estado;
        document.getElementById('projectResponsible').value = project.responsable_id || '';
        document.getElementById('projectStartDate').value = project.fecha_inicio || '';
        document.getElementById('projectEndDate').value = project.fecha_fin || '';
        document.getElementById('projectModal').style.display = 'block';
    }

    async deleteProject(projectId) {
        if (!utils.confirmAction('¿Estás seguro de que quieres eliminar este proyecto?')) {
            return;
        }

        try {
            const response = await fetch(`/api/projects/${projectId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                utils.showNotification('Proyecto eliminado correctamente', 'success');
                await this.loadProjects();
                this.renderProjects();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al eliminar proyecto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }

    async viewProjectTasks(projectId) {
        // Cambiar al módulo de tareas con filtro por proyecto
        app.loadModule('tasks');
        // Aquí se podría implementar un filtro automático por proyecto
        setTimeout(() => {
            if (window.tasksModule) {
                window.tasksModule.filterByProject(projectId);
            }
        }, 100);
    }

    closeModal() {
        document.getElementById('projectModal').style.display = 'none';
        this.currentProject = null;
    }

    setupEventListeners() {
        const form = document.getElementById('projectForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveProject();
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('projectModal').addEventListener('click', (e) => {
            if (e.target.id === 'projectModal') {
                this.closeModal();
            }
        });
    }

    async saveProject() {
        const formData = {
            nombre: document.getElementById('projectName').value,
            descripcion: document.getElementById('projectDescription').value,
            estado: document.getElementById('projectStatus').value,
            responsable_id: document.getElementById('projectResponsible').value || null,
            fecha_inicio: document.getElementById('projectStartDate').value || null,
            fecha_fin: document.getElementById('projectEndDate').value || null
        };

        const projectId = document.getElementById('projectId').value;
        const isEdit = projectId !== '';

        try {
            const url = isEdit ? `/api/projects/${projectId}` : '/api/projects';
            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                const message = isEdit ? 'Proyecto actualizado correctamente' : 'Proyecto creado correctamente';
                utils.showNotification(message, 'success');
                this.closeModal();
                await this.loadProjects();
                this.renderProjects();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al guardar proyecto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }

    getStatusText(status) {
        const statusMap = {
            'planificacion': 'Planificación',
            'en_progreso': 'En Progreso',
            'completado': 'Completado',
            'cancelado': 'Cancelado'
        };
        return statusMap[status] || status;
    }
    
    async duplicateProject(projectId) {
        const project = this.projects.find(p => p.id === projectId);
        if (!project) return;
        
        const newName = prompt('Nombre para el proyecto duplicado:', `${project.nombre} (Copia)`);
        if (!newName) return;
        
        try {
            const response = await fetch(`/api/projects/${projectId}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ nombre: newName })
            });
            
            if (response.ok) {
                utils.showNotification('Proyecto duplicado correctamente', 'success');
                await this.loadProjects();
                this.renderProjects();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al duplicar proyecto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
    
    async changeProjectStatus(projectId, newStatus) {
        try {
            const response = await fetch(`/api/projects/${projectId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ estado: newStatus })
            });
            
            if (response.ok) {
                utils.showNotification('Estado actualizado correctamente', 'success');
                await this.loadProjects();
                this.renderProjects();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al cambiar estado', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
    
    async exportProjects() {
        try {
            const response = await fetch('/api/projects/export');
            if (response.ok) {
                const data = await response.json();
                utils.downloadJSON(data, 'proyectos_export.json');
                utils.showNotification('Proyectos exportados correctamente', 'success');
            } else {
                utils.showNotification('Error al exportar proyectos', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
    
    async getProjectStatistics() {
        try {
            const response = await fetch('/api/projects/summary');
            if (response.ok) {
                const stats = await response.json();
                this.showStatisticsModal(stats);
            } else {
                utils.showNotification('Error al cargar estadísticas', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
    
    showStatisticsModal(stats) {
        const modal = document.createElement('div');
        modal.className = 'project-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Estadísticas de Proyectos</h2>
                    <button class="close-modal" onclick="this.closest('.project-modal').remove()">&times;</button>
                </div>
                <div class="stats-content">
                    <div class="stat-card">
                        <h3>Total de Proyectos</h3>
                        <span class="stat-number">${stats.estadisticas_generales.total_proyectos}</span>
                    </div>
                    <div class="stat-card">
                        <h3>En Progreso</h3>
                        <span class="stat-number">${stats.estadisticas_generales.en_progreso}</span>
                    </div>
                    <div class="stat-card">
                        <h3>Completados</h3>
                        <span class="stat-number">${stats.estadisticas_generales.completados}</span>
                    </div>
                    <div class="stat-card">
                        <h3>Sin Responsable</h3>
                        <span class="stat-number">${stats.estadisticas_generales.sin_responsable}</span>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.style.display = 'block';
    }
    
    filterByProject(projectId) {
        // Función para ser llamada desde otros módulos
        this.filters.search = '';
        this.filters.estado = '';
        this.filters.responsable = '';
        
        // Actualizar campos de filtro
        document.getElementById('searchProjects').value = '';
        document.getElementById('filterEstado').value = '';
        document.getElementById('filterResponsable').value = '';
        
        // Resaltar proyecto específico si es necesario
        setTimeout(() => {
            const projectCard = document.querySelector(`[data-project-id="${projectId}"]`);
            if (projectCard) {
                projectCard.scrollIntoView({ behavior: 'smooth' });
                projectCard.classList.add('highlighted');
                setTimeout(() => projectCard.classList.remove('highlighted'), 3000);
            }
        }, 100);
    }
}

// Agregar estilos adicionales si es necesario
const projectsAdditionalStyles = `
<style>
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

.loading {
    text-align: center;
    padding: 3rem;
    color: #666;
    font-style: italic;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.projects-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.project-progress {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.progress-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.progress-bar {
    width: 100px;
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    transition: width 0.3s ease;
}

.no-tasks {
    font-size: 0.8rem;
    color: #999;
    font-style: italic;
}

.project-card.highlighted {
    border: 2px solid #3498db;
    box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
    transform: scale(1.02);
    transition: all 0.3s ease;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle::after {
    content: ' ▼';
    font-size: 0.8rem;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    min-width: 150px;
    z-index: 1000;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu a {
    display: block;
    padding: 0.5rem 1rem;
    color: #2c3e50;
    text-decoration: none;
    transition: background-color 0.3s;
}

.dropdown-menu a:hover {
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.stats-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #3498db;
}

.stat-card h3 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
    font-size: 0.9rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #3498db;
}

@media (max-width: 768px) {
    .projects-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .project-progress {
        align-items: flex-start;
        margin-top: 0.5rem;
    }
    
    .progress-info {
        align-items: flex-start;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', projectsAdditionalStyles);