// Módulo de Gestión de Calendario/Agenda

class CalendarModule {
    constructor() {
        this.currentDate = new Date();
        this.selectedDate = null;
        this.availability = [];
        this.currentMonth = this.currentDate.getMonth();
        this.currentYear = this.currentDate.getFullYear();
    }

    render() {
        return `
            <div class="module calendar-module">
                <div class="calendar-header">
                    <h1 class="calendar-title">Agenda Mensual</h1>
                    <div class="calendar-controls">
                        <div class="calendar-navigation">
                            <button class="nav-btn" onclick="calendarModule.previousMonth()">‹ Anterior</button>
                            <div class="current-month" id="currentMonth">
                                ${this.getMonthName(this.currentMonth)} ${this.currentYear}
                            </div>
                            <button class="nav-btn" onclick="calendarModule.nextMonth()">Siguiente ›</button>
                        </div>
                        <div class="calendar-actions">
                            <button class="btn btn-sm" onclick="calendarModule.goToToday()">📅 Hoy</button>
                            <button class="btn btn-sm" onclick="calendarModule.showStatistics()">📊 Stats</button>
                            <button class="btn btn-sm" onclick="calendarModule.generateTemplate()">📋 Plantilla</button>
                            <button class="btn btn-sm" onclick="calendarModule.cloneMonth()">📄 Clonar</button>
                            <button class="btn btn-sm" onclick="calendarModule.exportCalendar()">📥 Exportar</button>
                            <div class="dropdown">
                                <button class="btn btn-sm dropdown-toggle">⚡ Rápido</button>
                                <div class="dropdown-menu">
                                    <a href="#" onclick="calendarModule.quickSetWeek(true)">✅ Semana disponible</a>
                                    <a href="#" onclick="calendarModule.quickSetWeek(false)">❌ Semana no disponible</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="calendar-container">
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- El calendario se genera dinámicamente -->
                    </div>
                    
                    <div class="segment-legend">
                        <div class="legend-item">
                            <div class="legend-color segment-proyectos"></div>
                            <span>Proyectos</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color segment-tareas"></div>
                            <span>Tareas</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color segment-agenda"></div>
                            <span>Agenda</span>
                        </div>
                    </div>
                </div>

                <!-- Modal para configurar día -->
                <div id="dayModal" class="day-modal">
                    <div class="day-modal-content">
                        <div class="modal-header">
                            <h2 class="modal-day-title" id="modalDayTitle">Configurar Día</h2>
                            <button class="close-modal" onclick="calendarModule.closeDayModal()">&times;</button>
                        </div>
                        
                        <form id="dayForm">
                            <input type="hidden" id="selectedDateInput">
                            
                            <div class="segment-options">
                                <div class="segment-option" onclick="calendarModule.toggleSegment('proyectos')">
                                    <input type="checkbox" id="segmentProyectos" class="segment-checkbox">
                                    <div class="segment-info">
                                        <div class="segment-name">Proyectos</div>
                                        <div class="segment-description">Disponible para actividades de proyectos</div>
                                    </div>
                                    <div class="legend-color segment-proyectos"></div>
                                </div>
                                
                                <div class="segment-option" onclick="calendarModule.toggleSegment('tareas')">
                                    <input type="checkbox" id="segmentTareas" class="segment-checkbox">
                                    <div class="segment-info">
                                        <div class="segment-name">Tareas</div>
                                        <div class="segment-description">Disponible para gestión de tareas del staff</div>
                                    </div>
                                    <div class="legend-color segment-tareas"></div>
                                </div>
                                
                                <div class="segment-option" onclick="calendarModule.toggleSegment('agenda')">
                                    <input type="checkbox" id="segmentAgenda" class="segment-checkbox">
                                    <div class="segment-info">
                                        <div class="segment-name">Agenda</div>
                                        <div class="segment-description">Disponible para eventos de agenda</div>
                                    </div>
                                    <div class="legend-color segment-agenda"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="dayNotes">Notas del día:</label>
                                <textarea id="dayNotes" class="form-textarea" rows="3" 
                                         placeholder="Agregar notas o comentarios para este día..."></textarea>
                            </div>
                            
                            <div class="modal-actions">
                                <button type="submit" class="btn btn-success">Guardar</button>
                                <button type="button" class="btn" onclick="calendarModule.closeDayModal()">Cancelar</button>
                                <button type="button" class="btn btn-danger" onclick="calendarModule.clearDay()">Limpiar Día</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    async init() {
        // Hacer disponible globalmente
        window.calendarModule = this;
        window.CalendarModule = this;
        
        await this.loadAvailability();
        this.renderCalendar();
        this.setupEventListeners();
    }

    async loadAvailability() {
        try {
            const response = await fetch(`/api/calendar/${this.currentYear}/${this.currentMonth + 1}`);
            if (response.ok) {
                this.availability = await response.json();
            } else {
                console.error('Error cargando disponibilidad');
                this.availability = [];
            }
        } catch (error) {
            console.error('Error:', error);
            this.availability = [];
        }
    }

    renderCalendar() {
        const grid = document.getElementById('calendarGrid');
        const firstDay = new Date(this.currentYear, this.currentMonth, 1);
        const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
        const startDate = new Date(firstDay);
        
        // Ajustar al lunes de la semana
        startDate.setDate(startDate.getDate() - (startDate.getDay() === 0 ? 6 : startDate.getDay() - 1));
        
        let html = '';
        
        // Headers de días de la semana
        const dayHeaders = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        dayHeaders.forEach(day => {
            html += `<div class="calendar-header-cell">${day}</div>`;
        });
        
        // Generar días del calendario
        const currentDate = new Date(startDate);
        for (let week = 0; week < 6; week++) {
            for (let day = 0; day < 7; day++) {
                const dateStr = this.formatDate(currentDate);
                const isCurrentMonth = currentDate.getMonth() === this.currentMonth;
                const isToday = this.isToday(currentDate);
                const dayAvailability = this.getDayAvailability(dateStr);
                
                let classes = 'calendar-day';
                if (!isCurrentMonth) classes += ' other-month';
                if (isToday) classes += ' today';
                
                html += `
                    <div class="${classes}" onclick="calendarModule.selectDay('${dateStr}')">
                        <div class="day-number">${currentDate.getDate()}</div>
                        <div class="day-segments">
                            ${this.renderDaySegments(dayAvailability)}
                        </div>
                    </div>
                `;
                
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            // Si ya pasamos el último día del mes y estamos en otra semana completa, parar
            if (currentDate.getMonth() !== this.currentMonth && day === 6) {
                break;
            }
        }
        
        grid.innerHTML = html;
        
        // Actualizar título del mes
        document.getElementById('currentMonth').textContent = 
            `${this.getMonthName(this.currentMonth)} ${this.currentYear}`;
    }

    renderDaySegments(dayAvailability) {
        const segments = ['proyectos', 'tareas', 'agenda'];
        let html = '';
        
        segments.forEach(segment => {
            const availability = dayAvailability.find(a => a.segmento === segment);
            if (availability && availability.disponible) {
                html += `<div class="segment-indicator segment-${segment}"></div>`;
            }
        });
        
        return html;
    }

    getDayAvailability(dateStr) {
        return this.availability.filter(a => a.fecha === dateStr);
    }

    selectDay(dateStr) {
        this.selectedDate = dateStr;
        const dayAvailability = this.getDayAvailability(dateStr);
        
        // Actualizar título del modal
        const date = new Date(dateStr);
        const dayName = date.toLocaleDateString('es-ES', { weekday: 'long' });
        const formattedDate = date.toLocaleDateString('es-ES', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric' 
        });
        
        document.getElementById('modalDayTitle').textContent = 
            `${dayName.charAt(0).toUpperCase() + dayName.slice(1)}, ${formattedDate}`;
        
        // Establecer fecha seleccionada
        document.getElementById('selectedDateInput').value = dateStr;
        
        // Configurar checkboxes según disponibilidad actual
        const segments = ['proyectos', 'tareas', 'agenda'];
        segments.forEach(segment => {
            const checkbox = document.getElementById(`segment${segment.charAt(0).toUpperCase() + segment.slice(1)}`);
            const availability = dayAvailability.find(a => a.segmento === segment);
            checkbox.checked = availability && availability.disponible;
            
            // Actualizar clase visual del contenedor
            const option = checkbox.closest('.segment-option');
            if (checkbox.checked) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
        
        // Cargar notas si existen
        const notesAvailability = dayAvailability.find(a => a.notas);
        document.getElementById('dayNotes').value = notesAvailability ? notesAvailability.notas : '';
        
        // Mostrar modal
        document.getElementById('dayModal').style.display = 'block';
    }

    toggleSegment(segment) {
        const checkbox = document.getElementById(`segment${segment.charAt(0).toUpperCase() + segment.slice(1)}`);
        checkbox.checked = !checkbox.checked;
        
        // Actualizar clase visual
        const option = checkbox.closest('.segment-option');
        if (checkbox.checked) {
            option.classList.add('selected');
        } else {
            option.classList.remove('selected');
        }
    }

    async saveDayConfiguration() {
        const dateStr = document.getElementById('selectedDateInput').value;
        const notes = document.getElementById('dayNotes').value;
        
        const segments = ['proyectos', 'tareas', 'agenda'];
        const configurations = [];
        
        segments.forEach(segment => {
            const checkbox = document.getElementById(`segment${segment.charAt(0).toUpperCase() + segment.slice(1)}`);
            configurations.push({
                fecha: dateStr,
                segmento: segment,
                disponible: checkbox.checked,
                notas: notes
            });
        });
        
        try {
            const response = await fetch('/api/calendar/availability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ configurations })
            });
            
            if (response.ok) {
                utils.showNotification('Configuración guardada correctamente', 'success');
                this.closeDayModal();
                await this.loadAvailability();
                this.renderCalendar();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al guardar configuración', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }

    async clearDay() {
        if (!utils.confirmAction('¿Estás seguro de que quieres limpiar la configuración de este día?')) {
            return;
        }
        
        const dateStr = document.getElementById('selectedDateInput').value;
        
        try {
            const response = await fetch(`/api/calendar/availability/${dateStr}`, {
                method: 'DELETE'
            });
            
            if (response.ok) {
                utils.showNotification('Día limpiado correctamente', 'success');
                this.closeDayModal();
                await this.loadAvailability();
                this.renderCalendar();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al limpiar día', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }

    closeDayModal() {
        document.getElementById('dayModal').style.display = 'none';
        this.selectedDate = null;
    }

    previousMonth() {
        this.currentMonth--;
        if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear--;
        }
        this.loadAvailability().then(() => this.renderCalendar());
    }

    nextMonth() {
        this.currentMonth++;
        if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear++;
        }
        this.loadAvailability().then(() => this.renderCalendar());
    }

    setupEventListeners() {
        const form = document.getElementById('dayForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveDayConfiguration();
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('dayModal').addEventListener('click', (e) => {
            if (e.target.id === 'dayModal') {
                this.closeDayModal();
            }
        });
    }

    // Utilidades
    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    isToday(date) {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    }

    getMonthName(monthIndex) {
        const months = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return months[monthIndex];
    }

    // Método para obtener eventos de la semana (usado por dashboard)
    async getWeekEvents() {
        const today = new Date();
        const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
        const weekEnd = new Date(today.setDate(today.getDate() - today.getDay() + 6));
        
        try {
            const response = await fetch(`/api/calendar/week?start=${this.formatDate(weekStart)}&end=${this.formatDate(weekEnd)}`);
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Error cargando eventos de la semana:', error);
        }
        
        return [];
    }
    
    async exportCalendar() {
        try {
            const response = await fetch(`/api/calendar/export?year=${this.currentYear}&month=${this.currentMonth + 1}`);
            if (response.ok) {
                const data = await response.json();
                utils.downloadJSON(data, `calendario_${this.currentYear}_${this.currentMonth + 1}.json`);
                utils.showNotification('Calendario exportado correctamente', 'success');
            } else {
                utils.showNotification('Error al exportar calendario', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
    
    async cloneMonth() {
        const targetMonth = prompt('¿A qué mes quieres clonar? (formato: YYYY-MM)', 
                                 `${this.currentYear}-${String(this.currentMonth + 2).padStart(2, '0')}`);
        
        if (!targetMonth || !targetMonth.match(/^\d{4}-\d{2}$/)) {
            utils.showNotification('Formato de mes inválido', 'error');
            return;
        }
        
        const [targetYear, targetMonthNum] = targetMonth.split('-');
        
        try {
            const response = await fetch('/api/calendar/clone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    source_year: this.currentYear,
                    source_month: this.currentMonth + 1,
                    target_year: parseInt(targetYear),
                    target_month: parseInt(targetMonthNum)
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                utils.showNotification(`Mes clonado correctamente. ${result.cloned} días copiados.`, 'success');
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al clonar mes', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
    
    async generateTemplate() {
        const segments = ['proyectos', 'tareas', 'agenda'];
        const selectedSegments = [];
        
        // Crear modal para seleccionar segmentos
        const modal = document.createElement('div');
        modal.className = 'day-modal';
        modal.innerHTML = `
            <div class="day-modal-content">
                <div class="modal-header">
                    <h2>Generar Plantilla de Mes</h2>
                    <button class="close-modal" onclick="this.closest('.day-modal').remove()">&times;</button>
                </div>
                <form id="templateForm">
                    <p>Selecciona los segmentos que estarán disponibles por defecto:</p>
                    <div class="segment-options">
                        ${segments.map(segment => `
                            <div class="segment-option">
                                <input type="checkbox" id="template_${segment}" value="${segment}" checked>
                                <label for="template_${segment}">${segment.charAt(0).toUpperCase() + segment.slice(1)}</label>
                            </div>
                        `).join('')}
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="templateAvailable" checked>
                            Marcar como disponible por defecto
                        </label>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-success">Generar Plantilla</button>
                        <button type="button" class="btn" onclick="this.closest('.day-modal').remove()">Cancelar</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.style.display = 'block';
        
        modal.querySelector('#templateForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const selectedSegments = Array.from(modal.querySelectorAll('input[type="checkbox"]:checked'))
                .filter(cb => cb.id.startsWith('template_'))
                .map(cb => cb.value);
            
            const available = modal.querySelector('#templateAvailable').checked;
            
            try {
                const response = await fetch('/api/calendar/template', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        year: this.currentYear,
                        month: this.currentMonth + 1,
                        segments: selectedSegments,
                        available: available
                    })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    utils.showNotification(`Plantilla aplicada. ${result.applied} días configurados.`, 'success');
                    modal.remove();
                    await this.loadAvailability();
                    this.renderCalendar();
                } else {
                    const error = await response.json();
                    utils.showNotification(error.message || 'Error al generar plantilla', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                utils.showNotification('Error de conexión', 'error');
            }
        });
    }
    
    async showStatistics() {
        try {
            const response = await fetch(`/api/calendar/statistics?year=${this.currentYear}&month=${this.currentMonth + 1}`);
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
        modal.className = 'day-modal';
        modal.innerHTML = `
            <div class="day-modal-content">
                <div class="modal-header">
                    <h2>Estadísticas del Calendario</h2>
                    <button class="close-modal" onclick="this.closest('.day-modal').remove()">&times;</button>
                </div>
                <div class="stats-content">
                    ${stats.map(stat => `
                        <div class="stat-card">
                            <h3>${stat.segmento.charAt(0).toUpperCase() + stat.segmento.slice(1)}</h3>
                            <div class="stat-numbers">
                                <div class="stat-item">
                                    <span class="stat-label">Total días:</span>
                                    <span class="stat-value">${stat.total_dias}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Disponibles:</span>
                                    <span class="stat-value available">${stat.dias_disponibles}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">No disponibles:</span>
                                    <span class="stat-value unavailable">${stat.dias_no_disponibles}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">% Disponibilidad:</span>
                                    <span class="stat-value">${Math.round((stat.dias_disponibles / stat.total_dias) * 100)}%</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.style.display = 'block';
    }
    
    goToToday() {
        const today = new Date();
        this.currentMonth = today.getMonth();
        this.currentYear = today.getFullYear();
        this.loadAvailability().then(() => this.renderCalendar());
    }
    
    async quickSetWeek(available = true) {
        const startOfWeek = new Date();
        startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay() + 1); // Lunes
        
        const dates = [];
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(date.getDate() + i);
            dates.push(this.formatDate(date));
        }
        
        const segments = ['proyectos', 'tareas', 'agenda'];
        const configurations = [];
        
        dates.forEach(date => {
            segments.forEach(segment => {
                configurations.push({
                    fecha: date,
                    segmento: segment,
                    disponible: available,
                    notas: available ? 'Configuración rápida semanal' : 'Semana no disponible'
                });
            });
        });
        
        try {
            const response = await fetch('/api/calendar/availability/bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ configurations })
            });
            
            if (response.ok) {
                utils.showNotification(`Semana configurada como ${available ? 'disponible' : 'no disponible'}`, 'success');
                await this.loadAvailability();
                this.renderCalendar();
            } else {
                const error = await response.json();
                utils.showNotification(error.message || 'Error al configurar semana', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showNotification('Error de conexión', 'error');
        }
    }
}

// Estilos adicionales específicos para el calendario
const calendarAdditionalStyles = `
<style>
.calendar-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.calendar-navigation {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.calendar-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.current-month {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
    min-width: 200px;
    text-align: center;
}

.nav-btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s;
}

.nav-btn:hover {
    background: #2980b9;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background-color: #ddd;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.calendar-header-cell {
    background-color: #2c3e50;
    color: white;
    padding: 1rem;
    text-align: center;
    font-weight: 600;
}

.calendar-day {
    background-color: white;
    min-height: 100px;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
}

.calendar-day:hover {
    background-color: #f8f9fa;
    transform: scale(1.02);
}

.calendar-day.other-month {
    background-color: #f5f5f5;
    color: #999;
}

.calendar-day.today {
    background-color: #e3f2fd;
    border-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}

.calendar-day.selected {
    background-color: #bbdefb;
    border-color: #1976d2;
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.day-segments {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.segment-indicator {
    height: 6px;
    border-radius: 3px;
    margin-bottom: 1px;
    opacity: 0.8;
}

.segment-legend {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 20px;
    height: 6px;
    border-radius: 3px;
}

.segment-proyectos {
    background: linear-gradient(90deg, #3498db, #2980b9);
}

.segment-tareas {
    background: linear-gradient(90deg, #2ecc71, #27ae60);
}

.segment-agenda {
    background: linear-gradient(90deg, #e74c3c, #c0392b);
}

.day-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.day-modal-content {
    background-color: white;
    margin: 5% auto;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
}

.segment-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin: 1rem 0;
}

.segment-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.segment-option:hover {
    border-color: #3498db;
    background-color: #f8f9fa;
}

.segment-option.selected {
    border-color: #3498db;
    background-color: #e3f2fd;
}

.segment-checkbox {
    width: 20px;
    height: 20px;
}

.segment-info {
    flex: 1;
}

.segment-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.segment-description {
    font-size: 0.9rem;
    color: #666;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.stats-content {
    padding: 1rem;
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid #3498db;
}

.stat-card h3 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
}

.stat-numbers {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.stat-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-value.available {
    color: #27ae60;
}

.stat-value.unavailable {
    color: #e74c3c;
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
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    min-width: 180px;
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

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
    }
    
    .calendar-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .calendar-navigation {
        justify-content: center;
    }
    
    .calendar-actions {
        justify-content: center;
    }
    
    .segment-legend {
        flex-direction: column;
        gap: 1rem;
        align-items: center;
    }
    
    .day-modal-content {
        margin: 2% auto;
        width: 95%;
    }
    
    .modal-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .calendar-day {
        min-height: 60px;
        padding: 0.125rem;
    }
    
    .day-number {
        font-size: 0.9rem;
    }
    
    .segment-indicator {
        height: 4px;
    }
    
    .calendar-actions {
        flex-direction: column;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', calendarAdditionalStyles);