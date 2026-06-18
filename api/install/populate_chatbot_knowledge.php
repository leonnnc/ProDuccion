<?php
/**
 * Script para poblar la base de conocimiento inicial del chatbot
 * Sistema de Gestión de Producción
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ChatbotConocimiento.php';

class ChatbotKnowledgePopulator {
    private $chatbotModel;
    
    public function __construct() {
        $this->chatbotModel = new ChatbotConocimiento();
    }
    
    public function populate() {
        echo "Iniciando población de base de conocimiento del chatbot...\n";
        
        $conocimientos = $this->getKnowledgeBase();
        $resultado = $this->chatbotModel->importarConocimientos($conocimientos, true);
        
        echo "Resultado de la importación:\n";
        echo "- Importados: {$resultado['importados']}\n";
        echo "- Actualizados: {$resultado['actualizados']}\n";
        echo "- Errores: " . count($resultado['errores']) . "\n";
        
        if (!empty($resultado['errores'])) {
            echo "\nErrores encontrados:\n";
            foreach ($resultado['errores'] as $error) {
                echo "- {$error}\n";
            }
        }
        
        echo "\n¡Base de conocimiento poblada exitosamente!\n";
    }
    
    private function getKnowledgeBase() {
        return [
            // CONOCIMIENTO GENERAL DEL SISTEMA
            [
                'categoria' => 'sistema',
                'pregunta_clave' => '¿Qué es este sistema?',
                'respuesta' => 'Este es un Sistema de Gestión de Producción diseñado para coordinar proyectos, tareas y agenda en equipos de producción audiovisual. Permite gestionar proyectos, asignar tareas al staff y programar la disponibilidad mensual.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'sistema',
                'pregunta_clave' => '¿Cómo funciona el sistema?',
                'respuesta' => 'El sistema está dividido en módulos: Dashboard (resumen general), Proyectos (gestión de proyectos), Tareas (asignación y seguimiento), y Agenda (disponibilidad mensual). Cada módulo tiene funciones específicas para optimizar el flujo de trabajo.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'navegacion',
                'pregunta_clave' => '¿Cómo navego entre módulos?',
                'respuesta' => 'Usa el menú de navegación superior para cambiar entre Dashboard, Proyectos, Tareas y Agenda. También puedes usar atajos de teclado: Alt+1 (Dashboard), Alt+2 (Proyectos), Alt+3 (Tareas), Alt+4 (Agenda).',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'ayuda',
                'pregunta_clave' => 'Necesito ayuda',
                'respuesta' => '¡Estoy aquí para ayudarte! Puedes preguntarme sobre cómo usar cualquier función del sistema. También puedes usar las opciones de ayuda rápida o consultar la documentación integrada.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            
            // MÓDULO DE PROYECTOS
            [
                'categoria' => 'proyectos',
                'pregunta_clave' => '¿Cómo crear un proyecto?',
                'respuesta' => 'Para crear un proyecto: 1) Ve al módulo Proyectos, 2) Haz clic en "Nuevo Proyecto", 3) Completa el formulario con nombre, descripción, responsable y fechas, 4) Guarda el proyecto. El proyecto aparecerá en la lista principal.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 1
            ],
            [
                'categoria' => 'proyectos',
                'pregunta_clave' => '¿Cómo editar un proyecto?',
                'respuesta' => 'Para editar un proyecto: 1) Busca el proyecto en la lista, 2) Haz clic en "Editar", 3) Modifica los campos necesarios, 4) Guarda los cambios. También puedes cambiar el estado directamente desde el menú desplegable.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 1
            ],
            [
                'categoria' => 'proyectos',
                'pregunta_clave' => '¿Cuáles son los estados de proyecto?',
                'respuesta' => 'Los proyectos tienen 4 estados: Planificación (proyecto en diseño), En Progreso (proyecto activo), Completado (proyecto terminado), y Cancelado (proyecto suspendido). Puedes cambiar el estado desde la lista de proyectos.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 1
            ],
            [
                'categoria' => 'proyectos',
                'pregunta_clave' => '¿Cómo asignar responsable a proyecto?',
                'respuesta' => 'Al crear o editar un proyecto, selecciona un responsable del menú desplegable "Responsable". Solo usuarios activos del sistema aparecerán en la lista. El responsable recibirá notificaciones sobre el proyecto.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 1
            ],
            [
                'categoria' => 'proyectos',
                'pregunta_clave' => '¿Cómo duplicar un proyecto?',
                'respuesta' => 'Para duplicar un proyecto: 1) Encuentra el proyecto en la lista, 2) Haz clic en "Duplicar", 3) Ingresa el nombre para la copia, 4) Confirma. El nuevo proyecto se creará con la misma información pero en estado "Planificación".',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 1
            ],
            [
                'categoria' => 'proyectos',
                'pregunta_clave' => '¿Cómo ver las tareas de un proyecto?',
                'respuesta' => 'Haz clic en "Ver Tareas" en cualquier proyecto para ir al módulo de Tareas con un filtro automático por ese proyecto. También puedes ver un resumen de tareas en la tarjeta del proyecto.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 1
            ],
            
            // MÓDULO DE TAREAS
            [
                'categoria' => 'tareas',
                'pregunta_clave' => '¿Cómo crear una tarea?',
                'respuesta' => 'Para crear una tarea: 1) Ve al módulo Tareas, 2) Haz clic en "Nueva Tarea", 3) Completa título, descripción, proyecto, usuario asignado y fecha de vencimiento, 4) Selecciona la prioridad, 5) Guarda la tarea.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 1
            ],
            [
                'categoria' => 'tareas',
                'pregunta_clave' => '¿Cómo asignar una tarea?',
                'respuesta' => 'Al crear o editar una tarea, selecciona el usuario en el campo "Asignado a". Solo puedes asignar tareas a usuarios activos del sistema. El usuario asignado recibirá una notificación.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 1
            ],
            [
                'categoria' => 'tareas',
                'pregunta_clave' => '¿Cuáles son los estados de tarea?',
                'respuesta' => 'Las tareas tienen 4 estados: Pendiente (nueva tarea), En Progreso (tarea iniciada), Completada (tarea terminada), y Cancelada (tarea suspendida). Puedes cambiar el estado desde la lista de tareas.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 1
            ],
            [
                'categoria' => 'tareas',
                'pregunta_clave' => '¿Cómo cambiar la prioridad de una tarea?',
                'respuesta' => 'Las tareas tienen 3 niveles de prioridad: Baja, Media y Alta. Puedes cambiar la prioridad editando la tarea o usando el botón de cambio rápido en la lista. Las tareas de alta prioridad aparecen primero.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 1
            ],
            [
                'categoria' => 'tareas',
                'pregunta_clave' => '¿Cómo ver mis tareas?',
                'respuesta' => 'En el módulo Tareas, usa el filtro "Asignado a" y selecciona tu nombre, o ve al Dashboard donde aparece un resumen de tus tareas pendientes y próximas a vencer.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 1
            ],
            [
                'categoria' => 'tareas',
                'pregunta_clave' => '¿Qué son las tareas vencidas?',
                'respuesta' => 'Las tareas vencidas son aquellas cuya fecha de vencimiento ya pasó y aún no están completadas. Aparecen marcadas en rojo en el sistema y en el Dashboard para atención prioritaria.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 1
            ],
            
            // MÓDULO DE AGENDA/CALENDARIO
            [
                'categoria' => 'agenda',
                'pregunta_clave' => '¿Cómo usar el calendario?',
                'respuesta' => 'El calendario muestra la disponibilidad mensual por segmentos. Haz clic en cualquier día para configurar la disponibilidad de los segmentos: Proyectos, Tareas y Agenda. Los días configurados muestran indicadores de color.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 1
            ],
            [
                'categoria' => 'agenda',
                'pregunta_clave' => '¿Qué son los segmentos de agenda?',
                'respuesta' => 'Los segmentos dividen el día en tres áreas: Proyectos (tiempo para trabajo en proyectos), Tareas (tiempo para tareas del staff), y Agenda (tiempo para eventos y reuniones). Puedes marcar cada segmento como disponible o no disponible.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 1
            ],
            [
                'categoria' => 'agenda',
                'pregunta_clave' => '¿Cómo configurar un día?',
                'respuesta' => 'Haz clic en el día que quieres configurar, selecciona los segmentos disponibles (Proyectos, Tareas, Agenda), agrega notas si es necesario, y guarda. Los segmentos seleccionados aparecerán como indicadores de color en el calendario.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 1
            ],
            [
                'categoria' => 'agenda',
                'pregunta_clave' => '¿Cómo navegar entre meses?',
                'respuesta' => 'Usa los botones "Anterior" y "Siguiente" en la parte superior del calendario, o haz clic en "Hoy" para ir al mes actual. También puedes usar las funciones rápidas para configurar semanas completas.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 1
            ],
            [
                'categoria' => 'agenda',
                'pregunta_clave' => '¿Cómo generar una plantilla de mes?',
                'respuesta' => 'Haz clic en "Plantilla" en el calendario, selecciona los segmentos que quieres configurar por defecto, marca si estarán disponibles o no, y aplica. Esto configurará automáticamente todos los días del mes.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 1
            ],
            [
                'categoria' => 'agenda',
                'pregunta_clave' => '¿Cómo clonar un mes?',
                'respuesta' => 'Usa la función "Clonar" en el calendario, especifica el mes destino en formato YYYY-MM, y confirma. Toda la configuración del mes actual se copiará al mes seleccionado.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 1
            ],
            
            // DASHBOARD
            [
                'categoria' => 'dashboard',
                'pregunta_clave' => '¿Qué muestra el Dashboard?',
                'respuesta' => 'El Dashboard es tu página de inicio que muestra un resumen de: proyectos activos, tareas pendientes, eventos de la semana, y estadísticas generales. Es tu centro de control para ver el estado general del sistema.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'dashboard',
                'pregunta_clave' => '¿Cómo interpretar los widgets del Dashboard?',
                'respuesta' => 'Cada widget muestra información clave: el número grande es la métrica principal, y debajo hay detalles adicionales. Los colores indican el estado: verde (bueno), amarillo (atención), rojo (urgente).',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            
            // ÁREAS Y PERMISOS
            [
                'categoria' => 'areas',
                'pregunta_clave' => '¿Qué áreas hay en el sistema?',
                'respuesta' => 'El sistema tiene áreas especializadas de producción audiovisual: Visuales, Filmmakers, Fotografía, Coordinación, Switchers, Streaming, Luces, Diseño, Edición, Protocolos y Cámaras. Cada área tiene permisos específicos.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'areas',
                'pregunta_clave' => '¿Qué hace el área de Filmmakers?',
                'respuesta' => 'Filmmakers incluye directores y productores de contenido. Tienen permisos para crear y gestionar proyectos, asignar tareas, gestionar la agenda y generar reportes. Es un área con amplios permisos de gestión.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'areas',
                'pregunta_clave' => '¿Qué hace Coordinación?',
                'respuesta' => 'Coordinación maneja la logística y organización general. Tienen permisos para crear proyectos, asignar tareas, gestionar la agenda completa y ver usuarios del sistema. Es el área de coordinación principal.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'areas',
                'pregunta_clave' => '¿Qué permisos tiene cada grupo?',
                'respuesta' => 'Admin: acceso total al sistema. Staff: puede crear/editar proyectos y tareas, gestionar agenda. Users: solo puede ver proyectos, editar sus tareas asignadas y consultar la agenda.',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            
            // FUNCIONES AVANZADAS
            [
                'categoria' => 'avanzado',
                'pregunta_clave' => '¿Cómo exportar datos?',
                'respuesta' => 'Cada módulo tiene opciones de exportación. Busca el botón "Exportar" en Proyectos, Tareas o Calendario. Los datos se descargan en formato JSON que puedes abrir con Excel o importar en otros sistemas.',
                'modulo_relacionado' => 'general',
                'prioridad' => 2
            ],
            [
                'categoria' => 'avanzado',
                'pregunta_clave' => '¿Cómo ver estadísticas?',
                'respuesta' => 'Cada módulo tiene un botón "Estadísticas" que muestra métricas detalladas: totales, promedios, distribuciones por estado, usuario, etc. Útil para análisis de productividad y planificación.',
                'modulo_relacionado' => 'general',
                'prioridad' => 2
            ],
            [
                'categoria' => 'avanzado',
                'pregunta_clave' => '¿Cómo buscar información?',
                'respuesta' => 'Usa las cajas de búsqueda en cada módulo para encontrar proyectos, tareas o información específica. También puedes usar los filtros avanzados para refinar los resultados por estado, responsable, fecha, etc.',
                'modulo_relacionado' => 'general',
                'prioridad' => 2
            ],
            
            // SOLUCIÓN DE PROBLEMAS
            [
                'categoria' => 'problemas',
                'pregunta_clave' => 'No puedo crear un proyecto',
                'respuesta' => 'Verifica que: 1) Tienes permisos para crear proyectos (área Staff o Admin), 2) Has completado todos los campos requeridos (nombre es obligatorio), 3) Tu sesión no ha expirado. Si persiste el problema, contacta al administrador.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 2
            ],
            [
                'categoria' => 'problemas',
                'pregunta_clave' => 'No veo mis tareas',
                'respuesta' => 'Verifica que: 1) Estás en el módulo Tareas, 2) Los filtros no están limitando la vista, 3) Las tareas están asignadas a tu usuario. Usa "Limpiar filtros" si no ves las tareas esperadas.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 2
            ],
            [
                'categoria' => 'problemas',
                'pregunta_clave' => 'El calendario no guarda cambios',
                'respuesta' => 'Asegúrate de: 1) Hacer clic en "Guardar" después de configurar el día, 2) Tener permisos para modificar la agenda, 3) Que tu conexión a internet esté estable. Si el problema persiste, recarga la página.',
                'modulo_relacionado' => 'agenda',
                'prioridad' => 2
            ],
            [
                'categoria' => 'problemas',
                'pregunta_clave' => 'Error de conexión',
                'respuesta' => 'Si ves errores de conexión: 1) Verifica tu conexión a internet, 2) Recarga la página, 3) Cierra sesión y vuelve a entrar, 4) Si persiste, contacta al administrador del sistema.',
                'modulo_relacionado' => 'general',
                'prioridad' => 2
            ],
            
            // CONSEJOS Y MEJORES PRÁCTICAS
            [
                'categoria' => 'consejos',
                'pregunta_clave' => 'Consejos para usar el sistema',
                'respuesta' => 'Consejos útiles: 1) Revisa el Dashboard diariamente, 2) Mantén actualizadas las fechas de vencimiento, 3) Usa las notas para detalles importantes, 4) Configura la agenda semanalmente, 5) Exporta datos regularmente como respaldo.',
                'modulo_relacionado' => 'general',
                'prioridad' => 2
            ],
            [
                'categoria' => 'consejos',
                'pregunta_clave' => 'Mejores prácticas para proyectos',
                'respuesta' => 'Para proyectos efectivos: 1) Define fechas realistas, 2) Asigna responsables claros, 3) Divide en tareas pequeñas, 4) Actualiza el estado regularmente, 5) Usa descripciones detalladas para contexto.',
                'modulo_relacionado' => 'proyectos',
                'prioridad' => 2
            ],
            [
                'categoria' => 'consejos',
                'pregunta_clave' => 'Cómo organizar tareas eficientemente',
                'respuesta' => 'Para tareas organizadas: 1) Usa prioridades correctamente, 2) Establece fechas de vencimiento realistas, 3) Agrupa tareas por proyecto, 4) Revisa tareas vencidas diariamente, 5) Comunica cambios de estado.',
                'modulo_relacionado' => 'tareas',
                'prioridad' => 2
            ],
            
            // RESPUESTAS DE CORTESÍA
            [
                'categoria' => 'cortesia',
                'pregunta_clave' => 'Gracias',
                'respuesta' => '¡De nada! Me alegra poder ayudarte. Si tienes más preguntas sobre el sistema, no dudes en preguntarme. Estoy aquí para hacer tu trabajo más eficiente.',
                'modulo_relacionado' => 'general',
                'prioridad' => 3
            ],
            [
                'categoria' => 'cortesia',
                'pregunta_clave' => 'Hola',
                'respuesta' => '¡Hola! Soy tu asistente virtual del Sistema de Gestión de Producción. Puedo ayudarte con proyectos, tareas, agenda y cualquier función del sistema. ¿En qué puedo ayudarte hoy?',
                'modulo_relacionado' => 'general',
                'prioridad' => 1
            ],
            [
                'categoria' => 'cortesia',
                'pregunta_clave' => 'Adiós',
                'respuesta' => '¡Hasta luego! Que tengas un excelente día de trabajo. Recuerda que siempre puedes consultarme si necesitas ayuda con el sistema.',
                'modulo_relacionado' => 'general',
                'prioridad' => 3
            ]
        ];
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $populator = new ChatbotKnowledgePopulator();
        $populator->populate();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>