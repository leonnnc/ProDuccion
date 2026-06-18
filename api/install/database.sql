-- Script de Instalación de Base de Datos
-- Sistema de Gestión de Producción
-- Versión: 1.0.0

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS gestion_produccion 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE gestion_produccion;

-- Tabla de grupos de usuarios
CREATE TABLE IF NOT EXISTS grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    permisos JSON,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de áreas de trabajo
CREATE TABLE IF NOT EXISTS areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    grupo_id INT NOT NULL,
    area_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    intentos_login INT DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_email (email),
    INDEX idx_grupo (grupo_id),
    INDEX idx_area (area_id),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de proyectos
CREATE TABLE IF NOT EXISTS proyectos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('planificacion', 'en_progreso', 'completado', 'cancelado') DEFAULT 'planificacion',
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    responsable_id INT NULL,
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_responsable (responsable_id),
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    INDEX idx_creado_por (creado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tareas
CREATE TABLE IF NOT EXISTS tareas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    proyecto_id INT NOT NULL,
    asignado_a INT NOT NULL,
    estado ENUM('pendiente', 'en_progreso', 'completada', 'cancelada') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
    fecha_vencimiento DATE NULL,
    fecha_completada TIMESTAMP NULL,
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_proyecto (proyecto_id),
    INDEX idx_asignado (asignado_a),
    INDEX idx_estado (estado),
    INDEX idx_prioridad (prioridad),
    INDEX idx_vencimiento (fecha_vencimiento),
    INDEX idx_creado_por (creado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de disponibilidad de agenda
CREATE TABLE IF NOT EXISTS agenda_disponibilidad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    segmento ENUM('proyectos', 'tareas', 'agenda') NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    notas TEXT,
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unique_fecha_segmento (fecha, segmento),
    INDEX idx_fecha (fecha),
    INDEX idx_segmento (segmento),
    INDEX idx_disponible (disponible),
    INDEX idx_creado_por (creado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de conversaciones del chatbot
CREATE TABLE IF NOT EXISTS chatbot_conversaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    sesion_id VARCHAR(100) NOT NULL,
    mensaje_usuario TEXT NOT NULL,
    respuesta_bot TEXT NOT NULL,
    contexto_modulo ENUM('proyectos', 'tareas', 'agenda', 'general') DEFAULT 'general',
    utilidad_respuesta INT DEFAULT NULL CHECK (utilidad_respuesta >= 1 AND utilidad_respuesta <= 5),
    tiempo_respuesta INT DEFAULT NULL COMMENT 'Tiempo de respuesta en milisegundos',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_sesion (sesion_id),
    INDEX idx_contexto (contexto_modulo),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de base de conocimiento del chatbot
CREATE TABLE IF NOT EXISTS chatbot_conocimiento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(50) NOT NULL,
    pregunta_clave TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    palabras_clave JSON,
    modulo_relacionado ENUM('proyectos', 'tareas', 'agenda', 'general') DEFAULT 'general',
    activo BOOLEAN DEFAULT TRUE,
    prioridad INT DEFAULT 1 COMMENT 'Prioridad de la respuesta (1=alta, 5=baja)',
    veces_utilizada INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_modulo (modulo_relacionado),
    INDEX idx_activo (activo),
    INDEX idx_prioridad (prioridad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones (para manejo de sesiones personalizado)
CREATE TABLE IF NOT EXISTS sesiones (
    id VARCHAR(128) PRIMARY KEY,
    usuario_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos TEXT,
    ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_actividad (ultima_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs del sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nivel ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
    mensaje TEXT NOT NULL,
    contexto JSON,
    usuario_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    archivo VARCHAR(255),
    linea INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_nivel (nivel),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descripcion TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos iniciales

-- Insertar grupos de usuarios
INSERT INTO grupos (nombre, descripcion, permisos) VALUES
('Admin', 'Administradores del sistema con acceso completo', JSON_OBJECT(
    'proyectos', JSON_ARRAY('crear', 'editar', 'eliminar', 'ver'),
    'tareas', JSON_ARRAY('crear', 'editar', 'eliminar', 'ver', 'asignar'),
    'agenda', JSON_ARRAY('crear', 'editar', 'eliminar', 'ver'),
    'usuarios', JSON_ARRAY('crear', 'editar', 'eliminar', 'ver'),
    'reportes', JSON_ARRAY('ver', 'exportar'),
    'configuracion', JSON_ARRAY('ver', 'editar')
)),
('Staff', 'Personal de staff con permisos de gestión', JSON_OBJECT(
    'proyectos', JSON_ARRAY('crear', 'editar', 'ver'),
    'tareas', JSON_ARRAY('crear', 'editar', 'ver', 'asignar'),
    'agenda', JSON_ARRAY('crear', 'editar', 'ver'),
    'usuarios', JSON_ARRAY('ver'),
    'reportes', JSON_ARRAY('ver')
)),
('Users', 'Usuarios regulares con permisos básicos', JSON_OBJECT(
    'proyectos', JSON_ARRAY('ver'),
    'tareas', JSON_ARRAY('editar', 'ver'),
    'agenda', JSON_ARRAY('ver'),
    'reportes', JSON_ARRAY('ver')
));

-- Insertar áreas de trabajo específicas
INSERT INTO areas (nombre, descripcion) VALUES
('Visuales', 'Equipo de efectos visuales y gráficos'),
('Filmmakers', 'Directores y productores de contenido'),
('Fotografía', 'Equipo de fotografía y captura de imagen'),
('Coordinación', 'Coordinadores de producción y logística'),
('Switchers', 'Operadores de switcher y mezcla de video'),
('Streaming', 'Equipo de transmisión en vivo y streaming'),
('Luces', 'Técnicos de iluminación y ambientación'),
('Diseño', 'Diseñadores gráficos y creativos'),
('Edición', 'Editores de video y postproducción'),
('Protocolos', 'Encargados de protocolos y ceremonial'),
('Cámaras', 'Camarógrafos y operadores de cámara');

-- Usuario administrador por defecto
INSERT INTO usuarios (nombre, email, password_hash, grupo_id, area_id) VALUES
('Administrador del Sistema', 'admin@gestionproduccion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 4);
-- Contraseña por defecto: "password" (cambiar en producción)
-- grupo_id: 1 (Admin), area_id: 4 (Coordinación)

-- Código de invitación para administradores (cambiar en producción)
INSERT INTO configuracion_sistema (clave, valor, descripcion) VALUES
('codigo_invitacion_admin', 'ADMIN2024', 'Código requerido para registrarse como administrador'),
('emails_autorizados_admin', '["admin@gestionproduccion.com","coordinador@gestionproduccion.com"]', 'Lista de emails autorizados para registro de administradores');

-- Usuarios de ejemplo
INSERT INTO usuarios (nombre, email, password_hash, grupo_id, area_id) VALUES
('Coordinador General', 'coordinador@gestionproduccion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 4),
-- grupo_id: 2 (Staff), area_id: 4 (Coordinación)

('Director de Filmmakers', 'filmmaker@gestionproduccion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 2),
-- grupo_id: 2 (Staff), area_id: 2 (Filmmakers)

('Operador de Streaming', 'streaming@gestionproduccion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 6),
-- grupo_id: 3 (Users), area_id: 6 (Streaming)

('Camarógrafo Principal', 'camaras@gestionproduccion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 11);
-- grupo_id: 3 (Users), area_id: 11 (Cámaras)

-- Base de conocimiento inicial del chatbot
INSERT INTO chatbot_conocimiento (categoria, pregunta_clave, respuesta, palabras_clave, modulo_relacionado, prioridad) VALUES
('proyectos', '¿Cómo crear un proyecto?', 'Para crear un nuevo proyecto, ve al módulo de Proyectos y haz clic en "Nuevo Proyecto". Completa el formulario con el nombre, descripción, responsable y fechas del proyecto.', JSON_ARRAY('crear', 'proyecto', 'nuevo'), 'proyectos', 1),
('proyectos', '¿Cómo editar un proyecto?', 'Para editar un proyecto existente, ve al módulo de Proyectos, busca el proyecto que deseas modificar y haz clic en "Editar". Actualiza la información necesaria y guarda los cambios.', JSON_ARRAY('editar', 'modificar', 'proyecto'), 'proyectos', 1),
('tareas', '¿Cómo asignar tareas?', 'Para asignar una tarea, ve al módulo de Tareas, crea una nueva tarea o edita una existente, y selecciona el usuario al que deseas asignarla en el campo "Asignado a".', JSON_ARRAY('asignar', 'tarea', 'usuario'), 'tareas', 1),
('tareas', '¿Cómo cambiar el estado de una tarea?', 'Puedes cambiar el estado de una tarea editándola y seleccionando el nuevo estado: Pendiente, En Progreso, Completada o Cancelada.', JSON_ARRAY('estado', 'tarea', 'cambiar'), 'tareas', 1),
('agenda', '¿Cómo usar el calendario?', 'El calendario te permite marcar días como disponibles para diferentes segmentos (proyectos, tareas, agenda). Haz clic en un día para seleccionar los segmentos disponibles.', JSON_ARRAY('calendario', 'agenda', 'días'), 'agenda', 1),
('general', '¿Cómo navegar entre módulos?', 'Usa el menú de navegación en la parte superior para cambiar entre Dashboard, Proyectos, Tareas y Agenda. Tu sesión se mantiene activa al cambiar de módulo.', JSON_ARRAY('navegar', 'módulos', 'menú'), 'general', 1),
('general', '¿Cómo cerrar sesión?', 'Para cerrar sesión de forma segura, haz clic en "Cerrar Sesión" en el menú de navegación superior.', JSON_ARRAY('cerrar', 'sesión', 'logout'), 'general', 1),
('general', 'Ayuda general', 'Este sistema te permite gestionar proyectos de producción, asignar tareas al staff y programar la agenda mensual. Usa los diferentes módulos para acceder a cada funcionalidad.', JSON_ARRAY('ayuda', 'sistema', 'funciones'), 'general', 2),

-- Información específica sobre áreas de producción audiovisual
('areas', '¿Qué áreas hay disponibles?', 'Las áreas están organizadas por departamentos: Dirección y Producción (Filmmakers, Coordinación, Protocolos), Técnico Visual (Cámaras, Switchers, Luces), Creativo y Diseño (Visuales, Diseño, Fotografía), Postproducción (Edición) y Transmisión (Streaming).', JSON_ARRAY('áreas', 'departamentos', 'equipos'), 'general', 1),
('areas', '¿Qué hace el área de Filmmakers?', 'El área de Filmmakers incluye directores y productores de contenido. Tienen permisos para crear y gestionar proyectos, asignar tareas y generar reportes.', JSON_ARRAY('filmmakers', 'directores', 'productores'), 'general', 1),
('areas', '¿Qué hace el área de Streaming?', 'El área de Streaming se encarga de las transmisiones en vivo. Pueden gestionar proyectos relacionados con streaming, crear tareas específicas y coordinar la agenda de transmisiones.', JSON_ARRAY('streaming', 'transmisión', 'vivo'), 'general', 1),
('areas', '¿Qué hace el área de Coordinación?', 'Coordinación maneja la logística y organización general. Tienen amplios permisos para crear proyectos, asignar tareas, gestionar la agenda y ver usuarios del sistema.', JSON_ARRAY('coordinación', 'logística', 'organización'), 'general', 1),
('areas', '¿Cuáles son los grupos de usuarios?', 'Existen 3 grupos principales: Admin (acceso completo), Staff (permisos de gestión) y Users (permisos básicos). Además hay áreas especializadas para cada departamento de producción.', JSON_ARRAY('grupos', 'admin', 'staff', 'users'), 'general', 1),
('areas', '¿Qué permisos tiene cada grupo?', 'Admin: acceso total. Staff: puede crear/editar proyectos y tareas, gestionar agenda. Users: solo puede ver proyectos, editar sus tareas asignadas y consultar agenda.', JSON_ARRAY('permisos', 'acceso', 'roles'), 'general', 1),
('areas', '¿Qué hace Dirección y Producción?', 'Incluye Filmmakers (directores y productores), Coordinación (logística) y Protocolos (ceremonial). Se encargan de la planificación y dirección general de proyectos.', JSON_ARRAY('dirección', 'producción', 'filmmakers', 'coordinación'), 'general', 1),
('areas', '¿Qué hace el área Técnico Visual?', 'Incluye Cámaras (camarógrafos), Switchers (operadores de video) y Luces (iluminación). Manejan todos los aspectos técnicos de captura y producción visual.', JSON_ARRAY('técnico', 'visual', 'cámaras', 'switchers', 'luces'), 'general', 1),
('areas', '¿Qué hace Creativo y Diseño?', 'Incluye Visuales (efectos), Diseño (gráfico) y Fotografía (captura de imagen). Se encargan de todos los aspectos creativos y de diseño visual.', JSON_ARRAY('creativo', 'diseño', 'visuales', 'fotografía'), 'general', 1),
('areas', '¿Qué hace Postproducción?', 'El área de Edición se encarga de toda la postproducción, edición de video y finalización de contenido audiovisual.', JSON_ARRAY('postproducción', 'edición', 'video'), 'general', 1),
('areas', '¿Qué hace Transmisión?', 'El área de Streaming maneja todas las transmisiones en vivo, configuración de streaming y distribución de contenido en tiempo real.', JSON_ARRAY('transmisión', 'streaming', 'vivo'), 'general', 1);

-- Crear índices adicionales para optimización
CREATE INDEX idx_usuarios_email_activo ON usuarios(email, activo);
CREATE INDEX idx_proyectos_estado_fecha ON proyectos(estado, fecha_creacion);
CREATE INDEX idx_tareas_asignado_estado ON tareas(asignado_a, estado);
CREATE INDEX idx_chatbot_palabras ON chatbot_conocimiento((CAST(palabras_clave AS CHAR(255))));

-- Crear vistas útiles para reportes
CREATE VIEW vista_proyectos_activos AS
SELECT 
    p.id,
    p.nombre,
    p.descripcion,
    p.estado,
    p.fecha_inicio,
    p.fecha_fin,
    u.nombre as responsable_nombre,
    COUNT(t.id) as total_tareas,
    COUNT(CASE WHEN t.estado = 'completada' THEN 1 END) as tareas_completadas,
    p.fecha_creacion
FROM proyectos p
LEFT JOIN usuarios u ON p.responsable_id = u.id
LEFT JOIN tareas t ON p.id = t.proyecto_id
WHERE p.estado IN ('planificacion', 'en_progreso')
GROUP BY p.id;

CREATE VIEW vista_tareas_pendientes AS
SELECT 
    t.id,
    t.titulo,
    t.descripcion,
    t.estado,
    t.prioridad,
    t.fecha_vencimiento,
    p.nombre as proyecto_nombre,
    u.nombre as asignado_nombre,
    t.fecha_creacion
FROM tareas t
JOIN proyectos p ON t.proyecto_id = p.id
JOIN usuarios u ON t.asignado_a = u.id
WHERE t.estado IN ('pendiente', 'en_progreso')
ORDER BY t.prioridad DESC, t.fecha_vencimiento ASC;

-- Procedimientos almacenados útiles
DELIMITER //

CREATE PROCEDURE ObtenerEstadisticasDashboard(IN usuario_id INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM proyectos WHERE estado IN ('planificacion', 'en_progreso')) as proyectos_activos,
        (SELECT COUNT(*) FROM tareas WHERE asignado_a = usuario_id AND estado IN ('pendiente', 'en_progreso')) as tareas_pendientes,
        (SELECT COUNT(*) FROM agenda_disponibilidad WHERE fecha >= CURDATE() AND fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as eventos_semana,
        (SELECT COUNT(*) FROM usuarios WHERE activo = 1) as usuarios_activos;
END //

CREATE PROCEDURE LimpiarSesionesExpiradas()
BEGIN
    DELETE FROM sesiones 
    WHERE ultima_actividad < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END //

CREATE PROCEDURE LimpiarLogsAntiguos()
BEGIN
    DELETE FROM logs_sistema 
    WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //

DELIMITER ;

-- Triggers para auditoría
DELIMITER //

CREATE TRIGGER tr_proyectos_audit AFTER UPDATE ON proyectos
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO logs_sistema (nivel, mensaje, contexto, usuario_id) 
        VALUES ('INFO', 'Estado de proyecto cambiado', 
                JSON_OBJECT('proyecto_id', NEW.id, 'estado_anterior', OLD.estado, 'estado_nuevo', NEW.estado),
                NEW.responsable_id);
    END IF;
END //

CREATE TRIGGER tr_tareas_completada AFTER UPDATE ON tareas
FOR EACH ROW
BEGIN
    IF OLD.estado != 'completada' AND NEW.estado = 'completada' THEN
        UPDATE tareas SET fecha_completada = NOW() WHERE id = NEW.id;
    END IF;
END //

DELIMITER ;

-- Configuración final
SET GLOBAL event_scheduler = ON;

-- Evento para limpiar sesiones expiradas automáticamente
CREATE EVENT IF NOT EXISTS ev_limpiar_sesiones
ON SCHEDULE EVERY 1 HOUR
DO
  CALL LimpiarSesionesExpiradas();

-- Evento para limpiar logs antiguos
CREATE EVENT IF NOT EXISTS ev_limpiar_logs
ON SCHEDULE EVERY 1 DAY
STARTS '2024-01-01 02:00:00'
DO
  CALL LimpiarLogsAntiguos();

-- Mensaje de finalización
SELECT 'Base de datos instalada correctamente' as mensaje;