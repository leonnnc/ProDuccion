<?php
/**
 * Controlador de Proyectos
 * Maneja las operaciones CRUD de proyectos
 */

require_once __DIR__ . '/../models/Proyecto.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/AuthController.php';

class ProjectController {
    private $proyectoModel;
    private $authController;
    
    public function __construct() {
        $this->proyectoModel = new Proyecto();
        $this->authController = new AuthController();
    }
    
    /**
     * Obtener todos los proyectos
     */
    public function index() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            // Obtener parámetros de filtro
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'responsable_id' => $_GET['responsable_id'] ?? '',
                'buscar' => $_GET['search'] ?? '',
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
                'limite' => $_GET['limit'] ?? null,
                'orden' => $_GET['sort'] ?? 'fecha_creacion',
                'direccion' => $_GET['direction'] ?? 'DESC'
            ];
            
            $proyectos = $this->proyectoModel->obtenerTodos($filtros);
            
            Response::success($proyectos, 'Proyectos obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener proyecto por ID
     */
    public function show($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            
            if (!$proyecto) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            // Obtener tareas del proyecto
            $tareas = $this->proyectoModel->obtenerTareas($id);
            $proyecto['tareas'] = $tareas;
            
            // Obtener estadísticas
            $estadisticas = $this->proyectoModel->obtenerEstadisticas($id);
            $proyecto['estadisticas'] = $estadisticas;
            
            Response::success($proyecto, 'Proyecto obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }    
 
   /**
     * Crear nuevo proyecto
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
            if (!$this->authController->hasPermission('proyectos', 'crear')) {
                Response::forbidden('No tienes permisos para crear proyectos');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos inválidos');
                return;
            }
            
            // Validar datos
            $errors = Response::validateInput($input, [
                'nombre' => ['required' => true, 'min_length' => 3, 'max_length' => 200],
                'descripcion' => ['max_length' => 1000],
                'estado' => ['in' => ['planificacion', 'en_progreso', 'completado', 'cancelado']],
                'fecha_inicio' => ['type' => 'date'],
                'fecha_fin' => ['type' => 'date'],
                'responsable_id' => ['type' => 'integer']
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Crear proyecto
            $proyectoId = $this->proyectoModel->crear($input, $usuarioActual['id']);
            
            // Obtener proyecto creado
            $proyecto = $this->proyectoModel->obtenerPorId($proyectoId);
            
            Response::success($proyecto, 'Proyecto creado correctamente', 201);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Actualizar proyecto
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
            if (!$this->authController->hasPermission('proyectos', 'editar')) {
                Response::forbidden('No tienes permisos para editar proyectos');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos inválidos');
                return;
            }
            
            // Validar datos
            $errors = Response::validateInput($input, [
                'nombre' => ['min_length' => 3, 'max_length' => 200],
                'descripcion' => ['max_length' => 1000],
                'estado' => ['in' => ['planificacion', 'en_progreso', 'completado', 'cancelado']],
                'fecha_inicio' => ['type' => 'date'],
                'fecha_fin' => ['type' => 'date'],
                'responsable_id' => ['type' => 'integer']
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Actualizar proyecto
            $filasAfectadas = $this->proyectoModel->actualizar($id, $input);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            // Obtener proyecto actualizado
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            
            Response::success($proyecto, 'Proyecto actualizado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Eliminar proyecto
     */
    public function destroy($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'eliminar')) {
                Response::forbidden('No tienes permisos para eliminar proyectos');
                return;
            }
            
            // Eliminar proyecto
            $filasAfectadas = $this->proyectoModel->eliminar($id);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            Response::success(null, 'Proyecto eliminado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Cambiar estado del proyecto
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
            if (!$this->authController->hasPermission('proyectos', 'editar')) {
                Response::forbidden('No tienes permisos para editar proyectos');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['estado'])) {
                Response::error('Estado requerido');
                return;
            }
            
            // Validar estado
            $estadosValidos = ['planificacion', 'en_progreso', 'completado', 'cancelado'];
            if (!in_array($input['estado'], $estadosValidos)) {
                Response::error('Estado inválido');
                return;
            }
            
            // Cambiar estado
            $filasAfectadas = $this->proyectoModel->cambiarEstado($id, $input['estado']);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            // Obtener proyecto actualizado
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            
            Response::success($proyecto, 'Estado del proyecto actualizado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener tareas del proyecto
     */
    public function getTasks($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            // Verificar que el proyecto existe
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            if (!$proyecto) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            // Obtener filtros
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'asignado_a' => $_GET['asignado_a'] ?? ''
            ];
            
            $tareas = $this->proyectoModel->obtenerTareas($id, $filtros);
            
            Response::success($tareas, 'Tareas del proyecto obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas del proyecto
     */
    public function getStatistics($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            // Verificar que el proyecto existe
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            if (!$proyecto) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            $estadisticas = $this->proyectoModel->obtenerEstadisticas($id);
            
            Response::success($estadisticas, 'Estadísticas del proyecto obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Duplicar proyecto
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
            if (!$this->authController->hasPermission('proyectos', 'crear')) {
                Response::forbidden('No tienes permisos para crear proyectos');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['nombre'])) {
                Response::error('Nombre del nuevo proyecto requerido');
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Duplicar proyecto
            $nuevoProyectoId = $this->proyectoModel->duplicar($id, $input['nombre'], $usuarioActual['id']);
            
            // Obtener proyecto duplicado
            $nuevoProyecto = $this->proyectoModel->obtenerPorId($nuevoProyectoId);
            
            Response::success($nuevoProyecto, 'Proyecto duplicado correctamente', 201);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Buscar proyectos
     */
    public function search() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            $query = $_GET['q'] ?? '';
            $limite = $_GET['limit'] ?? 10;
            
            if (empty($query)) {
                Response::error('Parámetro de búsqueda requerido');
                return;
            }
            
            $proyectos = $this->proyectoModel->buscar($query, $limite);
            
            Response::success($proyectos, 'Búsqueda completada');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener resumen de proyectos por estado
     */
    public function getSummary() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            $resumen = $this->proyectoModel->obtenerResumenPorEstado();
            $estadisticas = $this->proyectoModel->obtenerEstadisticasGenerales();
            
            Response::success([
                'resumen_por_estado' => $resumen,
                'estadisticas_generales' => $estadisticas
            ], 'Resumen de proyectos obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener proyectos del usuario actual
     */
    public function getMyProjects() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Obtener proyectos donde el usuario es responsable
            $proyectosResponsable = $this->proyectoModel->obtenerPorResponsable($usuarioActual['id']);
            
            // Obtener proyectos creados por el usuario
            $proyectosCreados = $this->proyectoModel->obtenerPorCreador($usuarioActual['id']);
            
            Response::success([
                'como_responsable' => $proyectosResponsable,
                'creados_por_mi' => $proyectosCreados
            ], 'Mis proyectos obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Archivar proyecto
     */
    public function archive($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'editar')) {
                Response::forbidden('No tienes permisos para archivar proyectos');
                return;
            }
            
            // Archivar proyecto
            $this->proyectoModel->archivar($id);
            
            // Obtener proyecto actualizado
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            
            Response::success($proyecto, 'Proyecto archivado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Reactivar proyecto archivado
     */
    public function reactivate($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'editar')) {
                Response::forbidden('No tienes permisos para reactivar proyectos');
                return;
            }
            
            // Reactivar proyecto
            $this->proyectoModel->reactivar($id);
            
            // Obtener proyecto actualizado
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            
            Response::success($proyecto, 'Proyecto reactivado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener proyectos próximos a vencer
     */
    public function getUpcoming() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            $dias = $_GET['days'] ?? 7;
            $proyectos = $this->proyectoModel->obtenerProximosAVencer($dias);
            
            Response::success($proyectos, 'Proyectos próximos a vencer obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener proyectos vencidos
     */
    public function getOverdue() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            $proyectos = $this->proyectoModel->obtenerVencidos();
            
            Response::success($proyectos, 'Proyectos vencidos obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Calcular progreso del proyecto
     */
    public function getProgress($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            // Verificar que el proyecto existe
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            if (!$proyecto) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            $progreso = $this->proyectoModel->calcularProgreso($id);
            
            Response::success([
                'proyecto_id' => $id,
                'progreso_porcentaje' => $progreso
            ], 'Progreso del proyecto calculado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Exportar datos del proyecto
     */
    public function export($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            $datos = $this->proyectoModel->exportarDatos($id);
            
            Response::success($datos, 'Datos del proyecto exportados correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener historial del proyecto
     */
    public function getHistory($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('proyectos', 'ver')) {
                Response::forbidden('No tienes permisos para ver proyectos');
                return;
            }
            
            // Verificar que el proyecto existe
            $proyecto = $this->proyectoModel->obtenerPorId($id);
            if (!$proyecto) {
                Response::notFound('Proyecto no encontrado');
                return;
            }
            
            $historial = $this->proyectoModel->obtenerHistorial($id);
            
            Response::success($historial, 'Historial del proyecto obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener proyectos activos del usuario
     */
    public function getActiveProjects() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $usuarioActual = $this->authController->getCurrentUser();
            $proyectos = $this->proyectoModel->obtenerActivosDelUsuario($usuarioActual['id']);
            
            Response::success($proyectos, 'Proyectos activos obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Validar permisos específicos del proyecto
     */
    public function checkPermissions($id) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $usuarioActual = $this->authController->getCurrentUser();
            $accion = $_GET['action'] ?? 'ver';
            
            $tienePermiso = $this->proyectoModel->validarPermisos($id, $usuarioActual['id'], $accion);
            
            Response::success([
                'proyecto_id' => $id,
                'usuario_id' => $usuarioActual['id'],
                'accion' => $accion,
                'tiene_permiso' => $tienePermiso
            ], 'Permisos verificados correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
?>