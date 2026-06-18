<?php
/**
 * Controlador del Chatbot
 * Maneja las interacciones con el sistema de ayuda
 */

require_once __DIR__ . '/../models/ChatbotConversacion.php';
require_once __DIR__ . '/../models/ChatbotConocimiento.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/AuthController.php';

class ChatbotController {
    private $conversacionModel;
    private $conocimientoModel;
    private $authController;
    
    public function __construct() {
        $this->conversacionModel = new ChatbotConversacion();
        $this->conocimientoModel = new ChatbotConocimiento();
        $this->authController = new AuthController();
    }
    
    /**
     * Procesar mensaje del usuario
     */
    public function processMessage() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['message'])) {
                Response::badRequest('Mensaje requerido');
                return;
            }
            
            $mensaje = trim($input['message']);
            $contexto = $input['context'] ?? 'general';
            $usuarioId = $input['user_id'] ?? null;
            $sesionId = $input['session_id'] ?? session_id();
            
            // Obtener usuario actual si no se proporciona
            if (!$usuarioId) {
                $usuario = $this->authController->getCurrentUser();
                $usuarioId = $usuario['id'];
            }
            
            $tiempoInicio = microtime(true);
            
            // Buscar respuesta en la base de conocimiento
            $respuestaData = $this->conocimientoModel->buscarRespuesta($mensaje, $contexto);
            
            $tiempoRespuesta = round((microtime(true) - $tiempoInicio) * 1000); // en milisegundos
            
            // Personalizar respuesta según el contexto
            $respuesta = $this->personalizarRespuesta($respuestaData['respuesta'], $contexto, $mensaje);
            
            // Guardar conversación
            $this->conversacionModel->crear([
                'usuario_id' => $usuarioId,
                'sesion_id' => $sesionId,
                'mensaje_usuario' => $mensaje,
                'respuesta_bot' => $respuesta,
                'contexto_modulo' => $contexto,
                'tiempo_respuesta' => $tiempoRespuesta
            ]);
            
            // Obtener sugerencias adicionales
            $sugerencias = $this->obtenerSugerenciasContextuales($contexto, $mensaje);
            
            Response::success([
                'response' => $respuesta,
                'context' => $contexto,
                'suggestions' => $sugerencias,
                'response_time' => $tiempoRespuesta
            ], 'Mensaje procesado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener sugerencias contextuales
     */
    public function getSuggestions() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $contexto = $_GET['context'] ?? 'general';
            $limite = $_GET['limit'] ?? 5;
            
            $sugerencias = $this->conocimientoModel->obtenerSugerencias($contexto, $limite);
            
            Response::success($sugerencias, 'Sugerencias obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener ayuda específica por módulo
     */
    public function getModuleHelp($modulo) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $ayuda = $this->obtenerAyudaModulo($modulo);
            
            Response::success($ayuda, 'Ayuda del módulo obtenida correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Enviar feedback sobre respuesta
     */
    public function submitFeedback() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['conversation_id']) || !isset($input['rating'])) {
                Response::badRequest('ID de conversación y calificación requeridos');
                return;
            }
            
            $conversacionId = $input['conversation_id'];
            $rating = (int)$input['rating'];
            
            if ($rating < 1 || $rating > 5) {
                Response::badRequest('La calificación debe estar entre 1 y 5');
                return;
            }
            
            $this->conversacionModel->registrarFeedback($conversacionId, $rating);
            
            Response::success(null, 'Feedback registrado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener historial de conversación
     */
    public function getHistory() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $usuario = $this->authController->getCurrentUser();
            $sesionId = $_GET['session_id'] ?? session_id();
            $limite = $_GET['limit'] ?? 20;
            
            $historial = $this->conversacionModel->obtenerPorSesion($sesionId, $limite);
            
            Response::success($historial, 'Historial obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas del chatbot (solo admin)
     */
    public function getStatistics() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos de administrador
            if (!$this->authController->hasPermission('configuracion', 'ver')) {
                Response::forbidden('No tienes permisos para ver estadísticas del chatbot');
                return;
            }
            
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            
            $estadisticas = $this->conversacionModel->obtenerEstadisticas($fechaInicio, $fechaFin);
            
            Response::success($estadisticas, 'Estadísticas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Personalizar respuesta según contexto
     */
    private function personalizarRespuesta($respuesta, $contexto, $mensaje) {
        // Agregar información contextual específica
        switch ($contexto) {
            case 'proyectos':
                if (strpos(strtolower($mensaje), 'crear') !== false) {
                    $respuesta .= "\n\n💡 Tip: Asegúrate de asignar un responsable y establecer fechas realistas para tu proyecto.";
                }
                break;
                
            case 'tareas':
                if (strpos(strtolower($mensaje), 'asignar') !== false) {
                    $respuesta .= "\n\n💡 Tip: Puedes cambiar la prioridad de las tareas para organizarlas mejor en el tablero Kanban.";
                }
                break;
                
            case 'agenda':
                if (strpos(strtolower($mensaje), 'calendario') !== false) {
                    $respuesta .= "\n\n💡 Tip: Puedes seleccionar múltiples segmentos para el mismo día según tus necesidades.";
                }
                break;
        }
        
        return $respuesta;
    }
    
    /**
     * Obtener sugerencias contextuales adicionales
     */
    private function obtenerSugerenciasContextuales($contexto, $mensaje) {
        $sugerencias = [];
        
        // Sugerencias basadas en palabras clave del mensaje
        if (strpos(strtolower($mensaje), 'error') !== false || strpos(strtolower($mensaje), 'problema') !== false) {
            $sugerencias[] = "¿Necesitas ayuda con algún error específico?";
            $sugerencias[] = "¿Qué mensaje de error estás viendo?";
        }
        
        if (strpos(strtolower($mensaje), 'cómo') !== false) {
            switch ($contexto) {
                case 'proyectos':
                    $sugerencias[] = "¿Cómo editar un proyecto?";
                    $sugerencias[] = "¿Cómo cambiar el estado de un proyecto?";
                    break;
                case 'tareas':
                    $sugerencias[] = "¿Cómo cambiar la prioridad de una tarea?";
                    $sugerencias[] = "¿Cómo filtrar tareas por estado?";
                    break;
                case 'agenda':
                    $sugerencias[] = "¿Cómo navegar entre meses?";
                    $sugerencias[] = "¿Cómo limpiar un día completo?";
                    break;
            }
        }
        
        return array_slice($sugerencias, 0, 3); // Máximo 3 sugerencias
    }
    
    /**
     * Obtener ayuda específica del módulo
     */
    private function obtenerAyudaModulo($modulo) {
        $ayudaModulos = [
            'dashboard' => [
                'titulo' => 'Dashboard - Panel Principal',
                'descripcion' => 'El dashboard te muestra un resumen de toda tu actividad.',
                'funciones' => [
                    'Ver estadísticas generales de proyectos y tareas',
                    'Acceder rápidamente a tareas pendientes',
                    'Revisar eventos próximos en la agenda',
                    'Navegar a otros módulos del sistema'
                ],
                'tips' => [
                    'El dashboard se actualiza automáticamente',
                    'Haz clic en las tarjetas para ir directamente a cada módulo',
                    'Las notificaciones aparecen en tiempo real'
                ]
            ],
            
            'proyectos' => [
                'titulo' => 'Gestión de Proyectos',
                'descripcion' => 'Administra todos los proyectos de producción audiovisual.',
                'funciones' => [
                    'Crear nuevos proyectos con información detallada',
                    'Asignar responsables y establecer fechas',
                    'Cambiar estados: Planificación, En Progreso, Completado, Cancelado',
                    'Ver tareas asociadas a cada proyecto',
                    'Filtrar y buscar proyectos'
                ],
                'tips' => [
                    'Usa descripciones claras para facilitar el seguimiento',
                    'Asigna fechas realistas para mejor planificación',
                    'Revisa regularmente el progreso de las tareas asociadas'
                ]
            ],
            
            'tareas' => [
                'titulo' => 'Gestión de Tareas',
                'descripcion' => 'Organiza y da seguimiento a las tareas del equipo.',
                'funciones' => [
                    'Crear tareas y asignarlas a miembros del equipo',
                    'Establecer prioridades: Alta, Media, Baja',
                    'Usar vista Kanban o vista de lista',
                    'Filtrar por estado, prioridad, usuario o proyecto',
                    'Establecer fechas de vencimiento'
                ],
                'tips' => [
                    'La vista Kanban es ideal para seguimiento visual',
                    'Usa filtros para enfocarte en tareas específicas',
                    'Las tareas vencidas se destacan automáticamente'
                ]
            ],
            
            'agenda' => [
                'titulo' => 'Agenda Mensual',
                'descripcion' => 'Programa la disponibilidad por segmentos de trabajo.',
                'funciones' => [
                    'Marcar días como disponibles para diferentes segmentos',
                    'Segmentos: Proyectos, Tareas, Agenda',
                    'Navegar entre meses con los botones de navegación',
                    'Agregar notas específicas para cada día',
                    'Ver disponibilidad de todo el equipo'
                ],
                'tips' => [
                    'Puedes seleccionar múltiples segmentos por día',
                    'Las notas ayudan a recordar detalles importantes',
                    'Los colores indican qué segmentos están disponibles'
                ]
            ]
        ];
        
        return $ayudaModulos[$modulo] ?? [
            'titulo' => 'Ayuda General',
            'descripcion' => 'Sistema de Gestión de Producción',
            'funciones' => [
                'Gestionar proyectos de producción audiovisual',
                'Asignar y seguir tareas del equipo',
                'Programar agenda mensual por segmentos'
            ],
            'tips' => [
                'Usa el menú superior para navegar entre módulos',
                'El chatbot está disponible en todo momento',
                'Puedes cerrar sesión de forma segura desde el menú'
            ]
        ];
    }
}
?>