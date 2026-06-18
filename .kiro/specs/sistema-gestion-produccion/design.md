# Documento de Diseño - Sistema de Gestión de Producción

## Resumen

El Sistema de Gestión de Producción será una aplicación web de página única (SPA) desarrollada con tecnologías compatibles con hosting compartido de GoDaddy. La aplicación integrará tres módulos principales: gestión de proyectos, gestión de tareas del staff, y agenda mensual, con un sistema de autenticación basado en áreas de usuario.

## Arquitectura

### Arquitectura General
```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (SPA)                           │
│  ┌─────────────────┬─────────────────┬─────────────────┐   │
│  │   Proyectos     │     Tareas      │     Agenda      │   │
│  │    Module       │     Module      │     Module      │   │
│  └─────────────────┴─────────────────┴─────────────────┘   │
│                         │                                   │
│                    Auth Module                              │
└─────────────────────────┼─────────────────────────────────┘
                          │
┌─────────────────────────┼─────────────────────────────────┐
│                    Backend API                              │
│  ┌─────────────────┬─────────────────┬─────────────────┐   │
│  │   Controllers   │    Services     │   Repositories  │   │
│  └─────────────────┴─────────────────┴─────────────────┘   │
└─────────────────────────┼─────────────────────────────────┘
                          │
┌─────────────────────────┼─────────────────────────────────┐
│                   Database Layer                            │
│                  (MySQL/MariaDB)                           │
└─────────────────────────────────────────────────────────────┘
```

### Tecnologías Recomendadas para GoDaddy

**Opción 1: PHP + MySQL + Vanilla JavaScript (Recomendada)**
- **Backend**: PHP 8.x (nativo en GoDaddy)
- **Base de datos**: MySQL (incluido en planes de GoDaddy)
- **Frontend**: HTML5, CSS3, JavaScript vanilla o jQuery
- **Ventajas**: Máxima compatibilidad, sin dependencias externas, fácil despliegue

**Opción 2: PHP + MySQL + Framework Ligero**
- **Backend**: PHP con Slim Framework o similar
- **Base de datos**: MySQL
- **Frontend**: Vue.js CDN o React CDN
- **Ventajas**: Mejor organización del código, componentes reutilizables

**Opción 3: Node.js (Si está disponible)**
- **Backend**: Node.js + Express
- **Base de datos**: MySQL
- **Frontend**: React o Vue.js
- **Limitación**: Requiere verificar soporte de Node.js en GoDaddy

## Componentes e Interfaces

### Estructura de Módulos Frontend

#### 1. Módulo de Autenticación
```javascript
AuthModule {
  - LoginForm
  - SessionManager
  - UserPermissions
  - AreaValidator
}
```

#### 2. Módulo de Proyectos
```javascript
ProjectsModule {
  - ProjectList
  - ProjectForm
  - ProjectDetails
  - ProjectStatus
}
```

#### 3. Módulo de Tareas
```javascript
TasksModule {
  - TaskList
  - TaskForm
  - TaskAssignment
  - TaskFilters
}
```

#### 4. Módulo de Agenda
```javascript
CalendarModule {
  - MonthlyCalendar
  - DaySelector
  - SegmentAssigner
  - AvailabilityView
}
```

#### 5. Módulo de Bot de Ayuda
```javascript
ChatBotModule {
  - ChatInterface
  - MessageHandler
  - KnowledgeBase
  - UserGuidance
  - ContextAwareHelp
}
```

### API Endpoints

#### Autenticación
- `POST /api/auth/login` - Iniciar sesión
- `POST /api/auth/logout` - Cerrar sesión
- `GET /api/auth/user` - Obtener usuario actual

#### Proyectos
- `GET /api/projects` - Listar proyectos
- `POST /api/projects` - Crear proyecto
- `PUT /api/projects/{id}` - Actualizar proyecto
- `DELETE /api/projects/{id}` - Eliminar proyecto

#### Tareas
- `GET /api/tasks` - Listar tareas
- `POST /api/tasks` - Crear tarea
- `PUT /api/tasks/{id}` - Actualizar tarea
- `GET /api/tasks/user/{userId}` - Tareas por usuario

#### Agenda
- `GET /api/calendar/{year}/{month}` - Obtener calendario mensual
- `POST /api/calendar/availability` - Establecer disponibilidad
- `PUT /api/calendar/availability/{id}` - Actualizar disponibilidad

#### Bot de Ayuda
- `POST /api/chatbot/message` - Enviar mensaje al bot
- `GET /api/chatbot/suggestions` - Obtener sugerencias contextuales
- `GET /api/chatbot/help/{module}` - Ayuda específica por módulo
- `POST /api/chatbot/feedback` - Enviar feedback sobre respuestas del bot

## Modelos de Datos

### Base de Datos MySQL

#### Tabla: usuarios
```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    area_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id)
);
```

#### Tabla: areas
```sql
CREATE TABLE areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    permisos JSON
);
```

#### Tabla: proyectos
```sql
CREATE TABLE proyectos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('planificacion', 'en_progreso', 'completado', 'cancelado') DEFAULT 'planificacion',
    fecha_inicio DATE,
    fecha_fin DATE,
    responsable_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id)
);
```

#### Tabla: tareas
```sql
CREATE TABLE tareas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    proyecto_id INT NOT NULL,
    asignado_a INT NOT NULL,
    estado ENUM('pendiente', 'en_progreso', 'completada', 'cancelada') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
    fecha_vencimiento DATE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id)
);
```

#### Tabla: agenda_disponibilidad
```sql
CREATE TABLE agenda_disponibilidad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    segmento ENUM('proyectos', 'tareas', 'agenda') NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    notas TEXT,
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id),
    UNIQUE KEY unique_fecha_segmento (fecha, segmento)
);
```

#### Tabla: chatbot_conversaciones
```sql
CREATE TABLE chatbot_conversaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    sesion_id VARCHAR(100) NOT NULL,
    mensaje_usuario TEXT NOT NULL,
    respuesta_bot TEXT NOT NULL,
    contexto_modulo ENUM('proyectos', 'tareas', 'agenda', 'general') DEFAULT 'general',
    utilidad_respuesta INT DEFAULT NULL, -- 1-5 rating
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

#### Tabla: chatbot_conocimiento
```sql
CREATE TABLE chatbot_conocimiento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(50) NOT NULL,
    pregunta_clave TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    palabras_clave JSON,
    modulo_relacionado ENUM('proyectos', 'tareas', 'agenda', 'general') DEFAULT 'general',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Manejo de Errores

### Estrategia de Manejo de Errores

#### Frontend
- Validación de formularios en tiempo real
- Mensajes de error contextuales
- Manejo de errores de conectividad
- Fallbacks para funcionalidades offline

#### Backend
- Códigos de estado HTTP apropiados
- Mensajes de error estructurados en JSON
- Logging de errores para debugging
- Validación de datos de entrada

#### Códigos de Error Estándar
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los datos proporcionados no son válidos",
    "details": {
      "field": "email",
      "issue": "Formato de email inválido"
    }
  }
}
```

## Estrategia de Pruebas

### Pruebas Frontend
- Pruebas unitarias para funciones de utilidad
- Pruebas de integración para módulos
- Pruebas de interfaz de usuario para flujos críticos

### Pruebas Backend
- Pruebas unitarias para lógica de negocio
- Pruebas de API para endpoints
- Pruebas de base de datos para operaciones CRUD

### Pruebas de Sistema
- Pruebas end-to-end para flujos completos de usuario
- Pruebas de compatibilidad con navegadores
- Pruebas de rendimiento básicas

## Integración del Bot de Ayuda

### Características del Bot
- **Bot contextual**: Proporciona ayuda específica según el módulo activo
- **Base de conocimiento**: Respuestas predefinidas para preguntas frecuentes
- **Aprendizaje básico**: Almacena conversaciones para mejorar respuestas
- **Interfaz flotante**: Chat widget accesible desde cualquier pantalla

### Opciones de Implementación del Bot

#### Opción 1: Bot Básico con PHP (Recomendada para GoDaddy)
- **Tecnología**: PHP + MySQL + JavaScript
- **Funcionalidad**: Respuestas basadas en palabras clave y patrones
- **Ventajas**: Sin dependencias externas, funciona en cualquier hosting
- **Limitaciones**: Respuestas más básicas, requiere configuración manual

#### Opción 2: Integración con API Externa
- **Servicios**: OpenAI API, Dialogflow, o similar
- **Ventajas**: Respuestas más inteligentes y naturales
- **Limitaciones**: Requiere conexión a internet, costos adicionales

#### Opción 3: Bot Híbrido
- **Combinación**: Base de conocimiento local + API externa para casos complejos
- **Ventajas**: Balance entre funcionalidad y costo
- **Implementación**: Fallback a respuestas locales si la API falla

### Funcionalidades del Bot
- **Ayuda contextual**: Guías paso a paso para cada módulo
- **Búsqueda rápida**: Encontrar proyectos, tareas o fechas específicas
- **Recordatorios**: Notificar sobre tareas pendientes o fechas importantes
- **Tutoriales**: Explicar cómo usar cada funcionalidad del sistema
- **Soporte técnico**: Resolver problemas comunes de uso

## Consideraciones de Despliegue

### Estructura de Archivos para GoDaddy
```
public_html/
├── index.html
├── css/
│   ├── main.css
│   └── modules/
├── js/
│   ├── app.js
│   ├── modules/
│   └── utils/
├── api/
│   ├── index.php
│   ├── config/
│   ├── controllers/
│   ├── models/
│   ├── chatbot/
│   └── utils/
└── assets/
    └── images/
```

### Configuración de Base de Datos
- Utilizar variables de entorno para credenciales
- Configurar conexiones persistentes para mejor rendimiento
- Implementar pool de conexiones si es posible

### Optimizaciones para Hosting Compartido
- Minimizar consultas a base de datos
- Implementar caché básico con archivos
- Optimizar imágenes y recursos estáticos
- Usar compresión GZIP cuando esté disponible