// Utilidades para DOM
function $(id) { return document.getElementById(id); }

// Navegación entre páginas
const introPage = $('introPage');
const dashboardPage = $('dashboardPage');
const agendaPage = $('agendaPage');
const agendaButton = $('agendaButton');
const registerButton = $('registerButton');
const backButton = $('backButton');
const backToDashboardButton = $('backToDashboardButton');

// Mostrar página de inicio
function showIntro() {
    introPage.classList.remove('hidden');
    dashboardPage.classList.add('hidden');
    agendaPage.classList.add('hidden');
}
function showDashboard() {
    introPage.classList.add('hidden');
    dashboardPage.classList.remove('hidden');
    agendaPage.classList.add('hidden');
    renderAgendaDashboard();
}
function showAgenda() {
    introPage.classList.add('hidden');
    dashboardPage.classList.add('hidden');
    agendaPage.classList.remove('hidden');
    initAgendaPage();
}

// Eventos de navegación
if (backButton) backButton.onclick = showIntro;
if (backToDashboardButton) backToDashboardButton.onclick = showDashboard;
if (agendaButton) agendaButton.onclick = () => { $('agendaModal').classList.add('active'); };
if (registerButton) registerButton.onclick = () => { $('registerModal').classList.add('active'); };
$('startButton').onclick = showDashboard;

// Cerrar modales
document.querySelectorAll('.close-btn').forEach(btn => {
    btn.onclick = function() {
        this.closest('.modal').classList.remove('active');
    };
});

// Registro de usuarios
const registerForm = $('registerForm');
registerForm.onsubmit = function(e) {
    e.preventDefault();
    const nombre = $('nombre').value.trim();
    const apellido = $('apellido').value.trim();
    const correo = $('correo').value.trim().toLowerCase();
    const telefono = $('telefono').value.trim();
    const distrito = $('distrito').value.trim();
    const area = $('area').value;
    const grupo = $('grupo').value;
    if (!nombre || !apellido || !correo || !telefono || !distrito || !area || !grupo) {
        alert('Completa todos los campos.');
        return;
    }
    let users = JSON.parse(localStorage.getItem('registeredUsers')) || [];
    if (users.some(u => u.correo === correo)) {
        alert('Este correo ya está registrado.');
        return;
    }
    users.push({ nombre, apellido, correo, telefono, distrito, area, grupo });
    localStorage.setItem('registeredUsers', JSON.stringify(users));
    $('registerModal').classList.remove('active');
    $('confirmationModal').classList.add('active');
    registerForm.reset();
    updateRegisteredList();
};
$('confirmationOkButton').onclick = () => $('confirmationModal').classList.remove('active');

// Actualizar lista de inscritos
function updateRegisteredList() {
    const users = JSON.parse(localStorage.getItem('registeredUsers')) || [];
    const tbody = $('registeredList');
    tbody.innerHTML = '';
    users.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${u.nombre} ${u.apellido}</td><td>${u.correo}</td><td>${u.area}</td><td>${u.grupo}</td>`;
        tbody.appendChild(tr);
    });
    $('memberCount').textContent = `${users.length} miembros registrados`;
}
updateRegisteredList();

// Agenda de cultos: acceso
const agendaForm = $('agendaForm');
agendaForm.onsubmit = function(e) {
    e.preventDefault();
    const email = $('agendaCorreo').value.trim().toLowerCase();
    let users = JSON.parse(localStorage.getItem('registeredUsers')) || [];
    if (!users.some(u => u.correo === email)) {
        $('agendaModal').classList.remove('active');
        $('redirectMessage').classList.remove('hidden');
        $('registerModal').classList.add('active');
        $('correo').value = email;
        return;
    }
    sessionStorage.setItem('currentUserEmail', email);
    $('agendaModal').classList.remove('active');
    showAgenda();
    agendaForm.reset();
};

// Inicializar agenda solo cuando se entra
function initAgendaPage() {
    // --- CALENDARIO DE DOMINGOS Y MIÉRCOLES SELECCIONABLES ---
    function getDatesOfWeekdayInMonth(year, month, weekday) {
        const dates = [];
        const date = new Date(year, month, 1);
        while (date.getMonth() === month) {
            if (date.getDay() === weekday) {
                dates.push(new Date(date));
            }
            date.setDate(date.getDate() + 1);
        }
        return dates;
    }
    function formatDate(date) {
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    }
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();

    // Domingos seleccionables
    const calendarDomingos = $('calendarDomingos');
    const domingos = getDatesOfWeekdayInMonth(year, month, 0);
    let selectedDomingos = [];
    calendarDomingos.innerHTML = '';
    domingos.forEach(d => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'px-4 py-3 rounded-lg bg-blue-900/60 text-blue-100 font-semibold shadow hover:bg-blue-700 transition w-full';
        btn.textContent = formatDate(d);
        btn.dataset.date = d.toISOString();
        btn.addEventListener('click', function() {
            const dateStr = this.dataset.date;
            if (selectedDomingos.includes(dateStr)) {
                selectedDomingos = selectedDomingos.filter(f => f !== dateStr);
                this.classList.remove('ring', 'ring-blue-400', 'bg-blue-700');
            } else {
                selectedDomingos.push(dateStr);
                this.classList.add('ring', 'ring-blue-400', 'bg-blue-700');
            }
            showAgendarBtn();
        });
        calendarDomingos.appendChild(btn);
    });

    // Miércoles seleccionables
    const miercolesList = $('miercolesList');
    const miercoles = getDatesOfWeekdayInMonth(year, month, 3);
    let selectedMiercoles = [];
    miercolesList.innerHTML = '';
    miercoles.forEach(d => {
        const span = document.createElement('span');
        span.className = 'px-4 py-2 rounded bg-indigo-800/60 text-indigo-100 font-semibold shadow cursor-pointer hover:bg-indigo-700 transition';
        span.textContent = formatDate(d);
        span.dataset.date = d.toISOString();
        span.addEventListener('click', function() {
            const dateStr = this.dataset.date;
            if (selectedMiercoles.includes(dateStr)) {
                selectedMiercoles = selectedMiercoles.filter(f => f !== dateStr);
                this.classList.remove('ring', 'ring-indigo-400', 'bg-indigo-700');
            } else {
                selectedMiercoles.push(dateStr);
                this.classList.add('ring', 'ring-indigo-400', 'bg-indigo-700');
            }
            showAgendarBtn();
        });
        miercolesList.appendChild(span);
    });

    // --- HORAS DE CULTO Y ÁREAS ---
    const horasCulto = [
        "1er culto 8:00am",
        "2do culto 11:00am",
        "3er culto 1:00pm",
        "4to culto 7:00pm"
    ];
    const horasList = $('horasList');
    let selectedHora = null;
    horasList.innerHTML = '';
    horasCulto.forEach(hora => {
        const span = document.createElement('span');
        span.className = 'px-4 py-2 rounded bg-blue-900/60 text-blue-200 font-semibold shadow cursor-pointer hover:bg-blue-700 transition';
        span.textContent = hora;
        span.dataset.hora = hora;
        span.addEventListener('click', function() {
            selectedHora = this.dataset.hora;
            Array.from(horasList.children).forEach(s => s.classList.remove('ring', 'ring-blue-400', 'bg-blue-700'));
            this.classList.add('ring', 'ring-blue-400', 'bg-blue-700');
            showAgendarBtn();
        });
        horasList.appendChild(span);
    });

    const areasServicio = [
        "Visuales", "Filmakers", "Fotografía", "Coordinación", "Switchers",
        "Cámaras", "Streaming", "Luces", "Diseño", "Edición", "Protocolo"
    ];
    const areasList = $('areasList');
    let selectedArea = null;
    areasList.innerHTML = '';
    areasServicio.forEach(area => {
        const span = document.createElement('span');
        span.className = 'px-4 py-2 rounded bg-indigo-900/60 text-indigo-200 font-semibold shadow cursor-pointer hover:bg-indigo-700 transition';
        span.textContent = area;
        span.dataset.area = area;
        span.addEventListener('click', function() {
            selectedArea = this.dataset.area;
            Array.from(areasList.children).forEach(s => s.classList.remove('ring', 'ring-indigo-400', 'bg-indigo-700'));
            this.classList.add('ring', 'ring-indigo-400', 'bg-indigo-700');
            showAgendarBtn();
        });
        areasList.appendChild(span);
    });

    // --- BOTÓN AGENDAR Y GUARDADO ---
    const agendarBtn = $('agendarBtn');
    const agendaMsg = $('agendaMsg');
    function showAgendarBtn() {
        const domingosReady = selectedDomingos.length > 0 && selectedHora && selectedArea;
        const miercolesReady = selectedMiercoles.length > 0;
        if (domingosReady || miercolesReady) {
            agendarBtn.classList.remove('hidden');
        } else {
            agendarBtn.classList.add('hidden');
        }
        agendaMsg.classList.add('hidden');
    }

    agendarBtn.onclick = function() {
        const email = sessionStorage.getItem('currentUserEmail');
        if (!email) {
            alert('No se ha identificado el usuario.');
            return;
        }
        let agenda = JSON.parse(localStorage.getItem('agendaServicios')) || [];
        // Domingos
        if (selectedDomingos.length > 0 && selectedHora && selectedArea) {
            selectedDomingos.forEach(dateStr => {
                const existe = agenda.find(s =>
                    s.correo === email &&
                    s.fecha === dateStr &&
                    s.hora === selectedHora &&
                    s.area === selectedArea
                );
                if (!existe) {
                    agenda.push({
                        correo: email,
                        fecha: dateStr,
                        hora: selectedHora,
                        area: selectedArea,
                        tipo: 'Domingo'
                    });
                }
            });
        }
        // Miércoles
        if (selectedMiercoles.length > 0) {
            selectedMiercoles.forEach(dateStr => {
                const existe = agenda.find(s =>
                    s.correo === email &&
                    s.fecha === dateStr &&
                    s.hora === 'Culto de Oración 8:00pm'
                );
                if (!existe) {
                    agenda.push({
                        correo: email,
                        fecha: dateStr,
                        hora: 'Culto de Oración 8:00pm',
                        area: 'Oración',
                        tipo: 'Miércoles'
                    });
                }
            });
        }
        localStorage.setItem('agendaServicios', JSON.stringify(agenda));
        agendaMsg.textContent = '¡Servicio(s) agendado(s)!';
        agendaMsg.classList.remove('hidden');
        agendarBtn.classList.add('hidden');
        // Limpiar selección visual
        Array.from(calendarDomingos.children).forEach(btn => btn.classList.remove('ring', 'ring-blue-400', 'bg-blue-700'));
        Array.from(horasList.children).forEach(s => s.classList.remove('ring', 'ring-blue-400', 'bg-blue-700'));
        Array.from(areasList.children).forEach(s => s.classList.remove('ring', 'ring-indigo-400', 'bg-indigo-700'));
        Array.from(miercolesList.children).forEach(s => s.classList.remove('ring', 'ring-indigo-400', 'bg-indigo-700'));
        selectedDomingos = [];
        selectedHora = null;
        selectedArea = null;
        selectedMiercoles = [];
        // Actualizar agenda en dashboard si está visible
        renderAgendaDashboard();
    };
}

// Mostrar agenda en dashboard
function renderAgendaDashboard() {
    const agenda = JSON.parse(localStorage.getItem('agendaServicios')) || [];
    const tbody = document.querySelector('#agendaDashboard tbody');
    if (!tbody) return;
    tbody.innerHTML = agenda.map((s, idx) => `
        <tr>
            <td>${s.correo}</td>
            <td>${formatDate(new Date(s.fecha))}</td>
            <td>${s.hora}</td>
            <td>${s.area}</td>
            <td>${s.tipo || ''}</td>
            <td>
                <button class="btn-secondary text-red-400" onclick="eliminarAgenda(${idx})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}
window.eliminarAgenda = function(idx) {
    let agenda = JSON.parse(localStorage.getItem('agendaServicios')) || [];
    if (confirm('¿Estás seguro de eliminar este servicio agendado?')) {
        agenda.splice(idx, 1);
        localStorage.setItem('agendaServicios', JSON.stringify(agenda));
        renderAgendaDashboard();
    }
};
renderAgendaDashboard();

// Utilidad para formatear fechas
function formatDate(date) {
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
}