<?php
/**
 * Controlador de Tareas
 * Maneja las operaciones CRUD de tareas del staff
 */

require_once __DIR__ . '/../models/Tarea.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/AuthController.php';

class TaskController {
    private $tareaModel;
    private $authController;
    
    public function __construct() {
        $this->tareaModel = new Tarea();
        $this->authController = new AuthController();
    }
    
    /**
     * Obtener todas las tareas
     */
    public function index() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            // Obtener parámetros de filtro
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'asignado_a' => $_GET['asignado_a'] ?? '',
                'proyecto_id' => $_GET['proyecto_id'] ?? '',
                'buscar' => $_GET['search'] ?? '',
                'fecha_vencimiento_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_vencimiento_hasta' => $_GET['fecha_hasta'] ?? '',
                'vencidas' => isset($_GET['vencidas']) ? (bool)$_GET['vencidas'] : false,
                'proximas_vencer' => isset($_GET['proximas_vencer']) ? (bool)$_GET['proximas_vencer'] : false,
                'dias_vencimiento' => $_GET['dias_vencimiento'] ?? 7,
                'limite' => $_GET['limit'] ?? null,
                'orden' => $_GET['sort'] ?? 'fecha_creacion',
                'direccion' => $_GET['direction'] ?? 'DESC'
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            // Obtener tareas
            $tareas = $this->tareaModel->obtenerTodas($filtros);
            
            Response::success($tareas, 'Tareas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tarea por ID
     */
    public function show($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            if (!$tarea) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            Response::success($tarea, 'Tarea obtenida correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Crear nueva tarea
     */
    public function store() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'crear')) {
                Response::forbidden('No tienes permisos para crear tareas');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            $errors = Response::validateInput($input, [
                'titulo' => ['required' => true, 'min_length' => 3, 'max_length' => 200],
                'proyecto_id' => ['required' => true, 'type' => 'integer'],
                'asignado_a' => ['required' => true, 'type' => 'integer']
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Crear tarea
            $tareaId = $this->tareaModel->crear($input, $usuarioActual['id']);
            
            // Obtener tarea creada
            $tarea = $this->tareaModel->obtenerPorId($tareaId);
            
            Response::success($tarea, 'Tarea creada correctamente', 201);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Actualizar tarea
     */
    public function update($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'editar')) {
                Response::forbidden('No tienes permisos para editar tareas');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Datos inválidos');
                return;
            }
            
            // Validar campos si están presentes
            $validationRules = [];
            if (isset($input['titulo'])) {
                $validationRules['titulo'] = ['min_length' => 3, 'max_length' => 200];
            }
            if (isset($input['proyecto_id'])) {
                $validationRules['proyecto_id'] = ['type' => 'integer'];
            }
            if (isset($input['asignado_a'])) {
                $validationRules['asignado_a'] = ['type' => 'integer'];
            }
            
            if (!empty($validationRules)) {
                $errors = Response::validateInput($input, $validationRules);
                if (!empty($errors)) {
                    Response::validation($errors);
                    return;
                }
            }
            
            // Actualizar tarea
            $filasAfectadas = $this->tareaModel->actualizar($id, $input);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            // Obtener tarea actualizada
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            Response::success($tarea, 'Tarea actualizada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Eliminar tarea
     */
    public function delete($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'eliminar')) {
                Response::forbidden('No tienes permisos para eliminar tareas');
                return;
            }
            
            // Eliminar tarea
            $filasAfectadas = $this->tareaModel->eliminar($id);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            Response::success(null, 'Tarea eliminada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Cambiar estado de tarea
     */
    public function changeStatus($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'editar')) {
                Response::forbidden('No tienes permisos para cambiar el estado de tareas');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['estado'])) {
                Response::badRequest('Estado requerido');
                return;
            }
            
            // Cambiar estado
            $filasAfectadas = $this->tareaModel->cambiarEstado($id, $input['estado']);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            // Obtener tarea actualizada
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            Response::success($tarea, 'Estado de tarea actualizado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas por usuario
     */
    public function byUser($userId) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            // Obtener filtros adicionales
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'proyecto_id' => $_GET['proyecto_id'] ?? '',
                'limite' => $_GET['limit'] ?? null
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            $tareas = $this->tareaModel->obtenerPorUsuario($userId, $filtros);
            
            Response::success($tareas, 'Tareas del usuario obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas por proyecto
     */
    public function byProject($projectId) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            // Obtener filtros adicionales
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'asignado_a' => $_GET['asignado_a'] ?? '',
                'limite' => $_GET['limit'] ?? null
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            $tareas = $this->tareaModel->obtenerPorProyecto($projectId, $filtros);
            
            Response::success($tareas, 'Tareas del proyecto obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas vencidas
     */
    public function overdue() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            $usuarioId = $_GET['user_id'] ?? null;
            $tareas = $this->tareaModel->obtenerVencidas($usuarioId);
            
            Response::success($tareas, 'Tareas vencidas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas próximas a vencer
     */
    public function upcoming() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            $dias = $_GET['days'] ?? 7;
            $usuarioId = $_GET['user_id'] ?? null;
            
            $tareas = $this->tareaModel->obtenerProximasVencer($dias, $usuarioId);
            
            Response::success($tareas, 'Tareas próximas a vencer obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de tareas
     */
    public function statistics() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver estadísticas de tareas');
                return;
            }
            
            // Obtener filtros
            $filtros = [
                'asignado_a' => $_GET['user_id'] ?? '',
                'proyecto_id' => $_GET['project_id'] ?? ''
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            $estadisticas = $this->tareaModel->obtenerEstadisticas($filtros);
            
            Response::success($estadisticas, 'Estadísticas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas por usuario
     */
    public function userStatistics() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver estadísticas de tareas');
                return;
            }
            
            $estadisticas = $this->tareaModel->obtenerEstadisticasPorUsuario();
            
            Response::success($estadisticas, 'Estadísticas por usuario obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Buscar tareas
     */
    public function search() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para buscar tareas');
                return;
            }
            
            $query = $_GET['q'] ?? '';
            $limite = $_GET['limit'] ?? 10;
            
            if (empty($query)) {
                Response::badRequest('Parámetro de búsqueda requerido');
                return;
            }
            
            $tareas = $this->tareaModel->buscar($query, $limite);
            
            Response::success($tareas, 'Búsqueda completada');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Duplicar tarea
     */
    public function duplicate($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'crear')) {
                Response::forbidden('No tienes permisos para duplicar tareas');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            $nuevoTitulo = $input['titulo'] ?? '';
            
            if (empty($nuevoTitulo)) {
                Response::badRequest('Título requerido para la tarea duplicada');
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Duplicar tarea
            $nuevaTareaId = $this->tareaModel->duplicar($id, $nuevoTitulo, $usuarioActual['id']);
            
            // Obtener tarea duplicada
            $nuevaTarea = $this->tareaModel->obtenerPorId($nuevaTareaId);
            
            Response::success($nuevaTarea, 'Tarea duplicada correctamente', 201);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas del usuario actual
     */
    public function myTasks() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Obtener filtros
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'proyecto_id' => $_GET['proyecto_id'] ?? '',
                'limite' => $_GET['limit'] ?? null
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            $tareas = $this->tareaModel->obtenerPorUsuario($usuarioActual['id'], $filtros);
            
            Response::success($tareas, 'Mis tareas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener dashboard de tareas para el usuario actual
     */
    public function dashboard() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Obtener estadísticas del usuario
            $estadisticas = $this->tareaModel->obtenerEstadisticas(['asignado_a' => $usuarioActual['id']]);
            
            // Obtener tareas vencidas
            $tareasVencidas = $this->tareaModel->obtenerVencidas($usuarioActual['id']);
            
            // Obtener tareas próximas a vencer
            $tareasProximas = $this->tareaModel->obtenerProximasVencer(7, $usuarioActual['id']);
            
            // Obtener tareas recientes
            $tareasRecientes = $this->tareaModel->obtenerPorUsuario($usuarioActual['id'], [
                'limite' => 5,
                'orden' => 'fecha_creacion',
                'direccion' => 'DESC'
            ]);
            
            $dashboard = [
                'estadisticas' => $estadisticas,
                'tareas_vencidas' => $tareasVencidas,
                'tareas_proximas' => $tareasProximas,
                'tareas_recientes' => $tareasRecientes
            ];
            
            Response::success($dashboard, 'Dashboard de tareas obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Asignar tarea a usuario
     */
    public function assign($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'asignar')) {
                Response::forbidden('No tienes permisos para asignar tareas');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['asignado_a'])) {
                Response::badRequest('Usuario asignado requerido');
                return;
            }
            
            // Asignar tarea
            $filasAfectadas = $this->tareaModel->asignar($id, $input['asignado_a']);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            // Obtener tarea actualizada
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            Response::success($tarea, 'Tarea asignada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Cambiar prioridad de tarea
     */
    public function changePriority($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'editar')) {
                Response::forbidden('No tienes permisos para cambiar la prioridad de tareas');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['prioridad'])) {
                Response::badRequest('Prioridad requerida');
                return;
            }
            
            // Validar prioridad
            $prioridadesValidas = ['baja', 'media', 'alta'];
            if (!in_array($input['prioridad'], $prioridadesValidas)) {
                Response::badRequest('Prioridad inválida');
                return;
            }
            
            // Cambiar prioridad
            $filasAfectadas = $this->tareaModel->cambiarPrioridad($id, $input['prioridad']);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            // Obtener tarea actualizada
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            Response::success($tarea, 'Prioridad de tarea actualizada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Marcar tarea como completada
     */
    public function complete($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'editar')) {
                Response::forbidden('No tienes permisos para completar tareas');
                return;
            }
            
            // Marcar como completada
            $filasAfectadas = $this->tareaModel->marcarCompletada($id);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            // Obtener tarea actualizada
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            Response::success($tarea, 'Tarea marcada como completada');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Reabrir tarea completada
     */
    public function reopen($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'editar')) {
                Response::forbidden('No tienes permisos para reabrir tareas');
                return;
            }
            
            // Reabrir tarea
            $filasAfectadas = $this->tareaModel->cambiarEstado($id, 'pendiente');
            
            if ($filasAfectadas === 0) {
                Response::notFound('Tarea no encontrada');
                return;
            }
            
            // Obtener tarea actualizada
            $tarea = $this->tareaModel->obtenerPorId($id);
            
            Response::success($tarea, 'Tarea reabierta correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas por fecha de vencimiento
     */
    public function byDueDate() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            $fecha = $_GET['date'] ?? date('Y-m-d');
            $usuarioId = $_GET['user_id'] ?? null;
            
            $tareas = $this->tareaModel->obtenerPorFechaVencimiento($fecha, $usuarioId);
            
            Response::success($tareas, 'Tareas por fecha de vencimiento obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener calendario de tareas
     */
    public function calendar() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver tareas');
                return;
            }
            
            $mes = $_GET['month'] ?? date('Y-m');
            $usuarioId = $_GET['user_id'] ?? null;
            
            $calendario = $this->tareaModel->obtenerCalendario($mes, $usuarioId);
            
            Response::success($calendario, 'Calendario de tareas obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Exportar tareas
     */
    public function export() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para exportar tareas');
                return;
            }
            
            // Obtener filtros
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'asignado_a' => $_GET['asignado_a'] ?? '',
                'proyecto_id' => $_GET['proyecto_id'] ?? '',
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            $datos = $this->tareaModel->exportarDatos($filtros);
            
            Response::success($datos, 'Datos de tareas exportados correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener notificaciones de tareas
     */
    public function notifications() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            $notificaciones = $this->tareaModel->obtenerNotificaciones($usuarioActual['id']);
            
            Response::success($notificaciones, 'Notificaciones obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener resumen de productividad
     */
    public function productivity() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('tareas', 'ver')) {
                Response::forbidden('No tienes permisos para ver estadísticas de productividad');
                return;
            }
            
            $usuarioId = $_GET['user_id'] ?? null;
            $periodo = $_GET['period'] ?? 'month'; // week, month, quarter, year
            
            $productividad = $this->tareaModel->obtenerProductividad($usuarioId, $periodo);
            
            Response::success($productividad, 'Resumen de productividad obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
?>